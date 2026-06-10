<?php

namespace App\Services\Otp;

use App\Models\OtpCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Phone OTP verification.
 *
 * Entirely driven by config/otp.php. While OTP_ENABLED is false the service is
 * inert: callers can still invoke it, but nothing is sent and verification is
 * not required anywhere, so the app behaves exactly as before.
 */
class OtpService
{
    public function enabled(): bool
    {
        return (bool) config('otp.enabled', false);
    }

    /**
     * Generate a code for a phone number and dispatch it via the configured
     * provider. Returns a small status array (never the raw code in prod).
     */
    public function send(string $phone, string $purpose = 'verify'): array
    {
        $phone = trim($phone);
        $cooldown = (int) config('otp.resend_cooldown_seconds', 60);

        $recent = OtpCode::where('phone_number', $phone)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        if ($recent && $recent->created_at && $recent->created_at->diffInSeconds(now()) < $cooldown) {
            $wait = $cooldown - $recent->created_at->diffInSeconds(now());
            return ['sent' => false, 'reason' => 'cooldown', 'retry_after' => max(1, $wait)];
        }

        $code = $this->generateCode();

        OtpCode::create([
            'phone_number' => $phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => Carbon::now()->addMinutes((int) config('otp.expiry_minutes', 10)),
        ]);

        $delivered = $this->deliver($phone, $this->messageFor($code));

        $result = ['sent' => $delivered, 'expires_in' => (int) config('otp.expiry_minutes', 10) * 60];

        // In local/testing the log driver also surfaces the code to ease dev.
        if (config('otp.provider') === 'log' && app()->environment(['local', 'testing'])) {
            $result['debug_code'] = $code;
        }

        return $result;
    }

    /**
     * Verify a submitted code. Marks the record verified on success.
     */
    public function verify(string $phone, string $code, string $purpose = 'verify'): bool
    {
        $phone = trim($phone);

        $record = OtpCode::where('phone_number', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (!$record || $record->isExpired()) {
            return false;
        }

        if ($record->attempts >= (int) config('otp.max_attempts', 5)) {
            return false;
        }

        $record->increment('attempts');

        if (!Hash::check($code, $record->code_hash)) {
            return false;
        }

        $record->forceFill(['verified_at' => now()])->save();

        return true;
    }

    /**
     * Whether a phone number has a recently verified, unexpired OTP — used to
     * gate registration when OTP is enabled.
     */
    public function isVerified(string $phone, string $purpose = 'verify'): bool
    {
        return OtpCode::where('phone_number', trim($phone))
            ->where('purpose', $purpose)
            ->whereNotNull('verified_at')
            ->where('expires_at', '>=', now())
            ->exists();
    }

    private function generateCode(): string
    {
        $length = max(4, (int) config('otp.code_length', 6));
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function messageFor(string $code): string
    {
        $app = config('app.name', 'KeenPocket');
        $mins = (int) config('otp.expiry_minutes', 10);

        return "Your {$app} verification code is {$code}. It expires in {$mins} minutes.";
    }

    /**
     * Dispatch the SMS through the configured provider. Best-effort: failures
     * are logged and reported, never thrown.
     */
    private function deliver(string $phone, string $message): bool
    {
        $provider = config('otp.provider', 'log');

        try {
            switch ($provider) {
                case 'log':
                    Log::info("[OTP][log] to {$phone}: {$message}");
                    return true;

                case 'termii':
                    $cfg = config('otp.providers.termii');
                    $resp = Http::asJson()->post(rtrim($cfg['base_url'], '/').'/api/sms/send', [
                        'api_key' => $cfg['api_key'],
                        'to' => $phone,
                        'from' => $cfg['sender_id'],
                        'sms' => $message,
                        'type' => 'plain',
                        'channel' => 'generic',
                    ]);
                    return $resp->successful();

                case 'africastalking':
                    $cfg = config('otp.providers.africastalking');
                    $resp = Http::asForm()
                        ->withHeaders(['apiKey' => $cfg['api_key'], 'Accept' => 'application/json'])
                        ->post('https://api.africastalking.com/version1/messaging', [
                            'username' => $cfg['username'],
                            'to' => $phone,
                            'message' => $message,
                        ]);
                    return $resp->successful();

                case 'twilio':
                    $cfg = config('otp.providers.twilio');
                    $resp = Http::asForm()
                        ->withBasicAuth($cfg['sid'], $cfg['token'])
                        ->post("https://api.twilio.com/2010-04-01/Accounts/{$cfg['sid']}/Messages.json", [
                            'From' => $cfg['from'],
                            'To' => $phone,
                            'Body' => $message,
                        ]);
                    return $resp->successful();

                default:
                    Log::warning("[OTP] unknown provider '{$provider}'");
                    return false;
            }
        } catch (\Throwable $e) {
            Log::warning("[OTP] delivery failed via {$provider}: ".$e->getMessage());
            return false;
        }
    }
}
