<?php

namespace App\Services\Payouts;

use App\Models\AdashiRecord;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Automated disbursement of a collected Adashi pot to the cycle receiver.
 *
 * Config-gated (config/payouts.php) and OFF by default — this moves real money
 * OUT. Safety invariants:
 *  - One payout per Adashi cycle (unique `adashi_record_id`); a pending/success
 *    payout is never re-attempted, so a pot cannot be disbursed twice.
 *  - Webhook payloads are signature-verified before they change a payout state.
 */
class PayoutService
{
    public function enabled(): bool
    {
        return (bool) config('payouts.enabled', false);
    }

    public function provider(): string
    {
        return (string) config('payouts.provider', 'log');
    }

    /**
     * Idempotently disburse the pot for a closed cycle. Returns the Payout
     * (which may be a recorded failure, e.g. the receiver has no bank details),
     * or null when payouts are disabled / there is nothing to pay.
     */
    public function attemptForRecord(AdashiRecord $record): ?Payout
    {
        if (!$this->enabled()) {
            return null;
        }

        $amount = (int) round((float) $record->total_collected);
        if ($amount <= 0) {
            return null;
        }

        $payout = Payout::firstOrNew(['adashi_record_id' => $record->id]);

        // Never re-disburse a pot that is already pending or paid.
        if ($payout->exists && $payout->isSettled()) {
            return $payout;
        }

        $receiver = User::find($record->receiver_user_id);

        $payout->recipient_user_id = $record->receiver_user_id;
        $payout->amount = $amount;
        $payout->currency = config('payouts.currency', 'NGN');
        $payout->provider = $this->provider();
        $payout->reference = 'KP_PO_'.$record->id.'_'.bin2hex(random_bytes(5));
        $payout->transfer_code = null;
        $payout->failure_reason = null;

        if (!$receiver || !$receiver->payout_account_number || !$receiver->payout_bank_code) {
            $payout->status = 'failed';
            $payout->failure_reason = 'no_bank_details';
            $payout->save();
            return $payout;
        }

        $payout->status = 'pending';
        $payout->save();

        $result = $this->driverDisburse($receiver, $amount, $payout->reference);

        $payout->transfer_code = $result['transfer_code'] ?? null;
        $payout->status = $result['status'];
        $payout->gateway_response = isset($result['raw']) ? json_encode($result['raw']) : null;
        if ($result['status'] === 'success') {
            $payout->disbursed_at = now();
        }
        if ($result['status'] === 'failed') {
            $payout->failure_reason = $result['reason'] ?? 'gateway_error';
        }
        $payout->save();

        return $payout;
    }

    /**
     * Gateway webhook for transfer status. Verifies signature, then updates the
     * referenced payout (success/failed). Never trusts the payload blindly.
     */
    public function handleWebhook(string $provider, string $rawBody, array $headers): bool
    {
        if (!$this->verifyWebhookSignature($provider, $rawBody, $headers)) {
            Log::warning("[payouts] webhook signature rejected for {$provider}");
            return false;
        }

        $payload = json_decode($rawBody, true) ?: [];
        [$reference, $status] = $this->extractWebhookResult($provider, $payload);
        if (!$reference) {
            return false;
        }

        $payout = Payout::where('reference', $reference)->first();
        if (!$payout || $payout->status === 'success') {
            return false; // unknown, or already settled (idempotent)
        }

        $payout->status = $status;
        if ($status === 'success') {
            $payout->disbursed_at = now();
        }
        $payout->save();

        return true;
    }

    // ── Driver: disburse ───────────────────────────────────────────────

    private function driverDisburse(User $receiver, int $amount, string $reference): array
    {
        try {
            switch ($this->provider()) {
                case 'log':
                    Log::info("[payouts][log] disburse {$amount} to {$receiver->payout_account_number} ref={$reference}");
                    return ['status' => 'success', 'transfer_code' => 'LOG_'.$reference];

                case 'paystack':
                    return $this->paystackTransfer($receiver, $amount, $reference);

                case 'flutterwave':
                    return $this->flutterwaveTransfer($receiver, $amount, $reference);

                default:
                    return ['status' => 'failed', 'reason' => 'unknown_provider'];
            }
        } catch (\Throwable $e) {
            Log::warning('[payouts] disburse failed: '.$e->getMessage());
            return ['status' => 'failed', 'reason' => 'exception'];
        }
    }

    private function paystackTransfer(User $receiver, int $amount, string $reference): array
    {
        $cfg = config('payouts.providers.paystack');
        $base = rtrim($cfg['base_url'], '/');

        // 1) Resolve/create a transfer recipient.
        $recipient = Http::withToken($cfg['secret_key'])->post($base.'/transferrecipient', [
            'type' => 'nuban',
            'name' => $receiver->name,
            'account_number' => $receiver->payout_account_number,
            'bank_code' => $receiver->payout_bank_code,
            'currency' => config('payouts.currency', 'NGN'),
        ]);
        $recipientCode = $recipient->json('data.recipient_code');
        if (!$recipientCode) {
            return ['status' => 'failed', 'reason' => 'recipient_failed', 'raw' => $recipient->json()];
        }

        // 2) Initiate the transfer (kobo). Final state arrives via webhook.
        $transfer = Http::withToken($cfg['secret_key'])->post($base.'/transfer', [
            'source' => 'balance',
            'amount' => $amount * 100,
            'recipient' => $recipientCode,
            'reason' => 'KeenPocket Adashi payout',
            'reference' => $reference,
        ]);
        $status = $transfer->json('data.status');

        return [
            'status' => $status === 'success' ? 'success' : ($transfer->successful() ? 'pending' : 'failed'),
            'transfer_code' => $transfer->json('data.transfer_code'),
            'raw' => $transfer->json(),
        ];
    }

    private function flutterwaveTransfer(User $receiver, int $amount, string $reference): array
    {
        $cfg = config('payouts.providers.flutterwave');
        $resp = Http::withToken($cfg['secret_key'])->post(rtrim($cfg['base_url'], '/').'/v3/transfers', [
            'account_bank' => $receiver->payout_bank_code,
            'account_number' => $receiver->payout_account_number,
            'amount' => $amount,
            'currency' => config('payouts.currency', 'NGN'),
            'narration' => 'KeenPocket Adashi payout',
            'reference' => $reference,
        ]);
        $status = strtoupper((string) $resp->json('data.status'));

        return [
            'status' => $status === 'SUCCESSFUL' ? 'success' : ($resp->successful() ? 'pending' : 'failed'),
            'transfer_code' => (string) $resp->json('data.id'),
            'raw' => $resp->json(),
        ];
    }

    // ── Webhook signature + parsing ────────────────────────────────────

    private function verifyWebhookSignature(string $provider, string $rawBody, array $headers): bool
    {
        $header = function (string $name) use ($headers): ?string {
            $name = strtolower($name);
            foreach ($headers as $k => $v) {
                if (strtolower($k) === $name) {
                    return is_array($v) ? ($v[0] ?? null) : $v;
                }
            }
            return null;
        };

        switch ($provider) {
            case 'log':
                return app()->environment(['local', 'testing']);
            case 'paystack':
                $secret = config('payouts.providers.paystack.secret_key');
                $sig = $header('x-paystack-signature');
                return $secret && $sig && hash_equals(hash_hmac('sha512', $rawBody, $secret), $sig);
            case 'flutterwave':
                $expected = config('payouts.providers.flutterwave.secret_hash');
                $given = $header('verif-hash');
                return $expected && $given && hash_equals($expected, $given);
            default:
                return false;
        }
    }

    /**
     * @return array{0: ?string, 1: string} [reference, mapped status]
     */
    private function extractWebhookResult(string $provider, array $payload): array
    {
        switch ($provider) {
            case 'log':
                return [$payload['reference'] ?? null, $payload['status'] ?? 'success'];
            case 'paystack':
                $event = $payload['event'] ?? '';
                $ref = $payload['data']['reference'] ?? null;
                return [$ref, $event === 'transfer.success' ? 'success' : 'failed'];
            case 'flutterwave':
                $ref = $payload['data']['reference'] ?? null;
                $status = strtoupper((string) ($payload['data']['status'] ?? ''));
                return [$ref, $status === 'SUCCESSFUL' ? 'success' : 'failed'];
            default:
                return [null, 'failed'];
        }
    }
}
