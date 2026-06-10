<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reusable SMS channel (config/sms.php). Best-effort and config-gated: when
 * disabled, send() is a no-op returning false, so callers (e.g. reminders) can
 * always invoke it and simply fall back to push. Mirrors the OTP provider set.
 */
class SmsSender
{
    public function enabled(): bool
    {
        return (bool) config('sms.enabled', false);
    }

    /**
     * Send one SMS. Returns true on (best-effort) success, false if disabled or
     * the provider call fails. Never throws.
     */
    public function send(string $phone, string $message): bool
    {
        if (!$this->enabled() || trim($phone) === '') {
            return false;
        }

        $provider = config('sms.provider', 'log');

        try {
            switch ($provider) {
                case 'log':
                    Log::info("[SMS][log] to {$phone}: {$message}");
                    return true;

                case 'termii':
                    $cfg = config('sms.providers.termii');
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
                    $cfg = config('sms.providers.africastalking');
                    $resp = Http::asForm()
                        ->withHeaders(['apiKey' => $cfg['api_key'], 'Accept' => 'application/json'])
                        ->post('https://api.africastalking.com/version1/messaging', [
                            'username' => $cfg['username'],
                            'to' => $phone,
                            'message' => $message,
                        ]);
                    return $resp->successful();

                case 'twilio':
                    $cfg = config('sms.providers.twilio');
                    $resp = Http::asForm()
                        ->withBasicAuth($cfg['sid'], $cfg['token'])
                        ->post("https://api.twilio.com/2010-04-01/Accounts/{$cfg['sid']}/Messages.json", [
                            'From' => $cfg['from'],
                            'To' => $phone,
                            'Body' => $message,
                        ]);
                    return $resp->successful();

                default:
                    Log::warning("[SMS] unknown provider '{$provider}'");
                    return false;
            }
        } catch (\Throwable $e) {
            Log::warning("[SMS] delivery failed via {$provider}: ".$e->getMessage());
            return false;
        }
    }
}
