<?php

namespace App\Services\Payments;

use App\Actions\MarkInvoicePaid;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Online payment collection.
 *
 * Config-driven (config/payments.php) and inert while PAYMENTS_ENABLED is false.
 * Re-verifies every settlement against the gateway before flipping an invoice —
 * webhook payloads are treated as untrusted notifications, never as proof.
 */
class PaymentService
{
    public function __construct(private MarkInvoicePaid $markPaid)
    {
    }

    public function enabled(): bool
    {
        return (bool) config('payments.enabled', false);
    }

    public function provider(): string
    {
        return (string) config('payments.provider', 'log');
    }

    /**
     * Start a payment for an invoice. Returns a checkout URL + our reference.
     */
    public function initialize(Invoice $invoice, User $user): array
    {
        $reference = 'KP_'.$invoice->id.'_'.bin2hex(random_bytes(6));
        $amount = (int) round((float) $invoice->amount); // major unit (e.g. naira)

        $txn = PaymentTransaction::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'provider' => $this->provider(),
            'reference' => $reference,
            'amount' => $amount,
            'currency' => config('payments.currency', 'NGN'),
            'status' => 'pending',
        ]);

        $authUrl = $this->driverInitialize($reference, $amount, $user);
        $txn->forceFill(['authorization_url' => $authUrl])->save();

        return ['reference' => $reference, 'authorization_url' => $authUrl];
    }

    /**
     * Verify a payment by our reference and settle the invoice on success.
     */
    public function verify(string $reference): bool
    {
        $txn = PaymentTransaction::where('reference', $reference)->first();
        if (!$txn) {
            return false;
        }
        if ($txn->status === 'success') {
            return true; // idempotent
        }

        $ok = $this->driverVerify($reference);

        $txn->forceFill([
            'status' => $ok ? 'success' : 'failed',
            'paid_at' => $ok ? now() : null,
        ])->save();

        if ($ok) {
            $invoice = Invoice::find($txn->invoice_id);
            if ($invoice) {
                $this->markPaid->execute($invoice, 'Online');
            }
        }

        return $ok;
    }

    /**
     * Handle a gateway webhook: validate the signature, then re-verify the
     * referenced transaction via the API before settling.
     */
    public function handleWebhook(string $provider, string $rawBody, array $headers): bool
    {
        if (!$this->verifyWebhookSignature($provider, $rawBody, $headers)) {
            Log::warning("[payments] webhook signature rejected for {$provider}");
            return false;
        }

        $payload = json_decode($rawBody, true) ?: [];
        $reference = $this->extractReference($provider, $payload);
        if (!$reference) {
            return false;
        }

        return $this->verify($reference);
    }

    // ── Driver: initialize ─────────────────────────────────────────────

    private function driverInitialize(string $reference, int $amount, User $user): ?string
    {
        switch ($this->provider()) {
            case 'log':
                Log::info("[payments][log] init {$reference} amount={$amount}");
                return rtrim((string) config('payments.callback_url'), '/')."?reference={$reference}&simulate=success";

            case 'paystack':
                $cfg = config('payments.providers.paystack');
                $resp = Http::withToken($cfg['secret_key'])->post(rtrim($cfg['base_url'], '/').'/transaction/initialize', [
                    'email' => $user->email,
                    'amount' => $amount * 100, // kobo
                    'reference' => $reference,
                    'currency' => config('payments.currency', 'NGN'),
                    'callback_url' => config('payments.callback_url'),
                ]);
                return $resp->json('data.authorization_url');

            case 'flutterwave':
                $cfg = config('payments.providers.flutterwave');
                $resp = Http::withToken($cfg['secret_key'])->post(rtrim($cfg['base_url'], '/').'/v3/payments', [
                    'tx_ref' => $reference,
                    'amount' => $amount,
                    'currency' => config('payments.currency', 'NGN'),
                    'redirect_url' => config('payments.callback_url'),
                    'customer' => ['email' => $user->email],
                ]);
                return $resp->json('data.link');

            default:
                Log::warning('[payments] unknown provider '.$this->provider());
                return null;
        }
    }

    // ── Driver: verify ─────────────────────────────────────────────────

    private function driverVerify(string $reference): bool
    {
        try {
            switch ($this->provider()) {
                case 'log':
                    return true; // simulate success for local/dev

                case 'paystack':
                    $cfg = config('payments.providers.paystack');
                    $resp = Http::withToken($cfg['secret_key'])
                        ->get(rtrim($cfg['base_url'], '/')."/transaction/verify/{$reference}");
                    return $resp->successful() && $resp->json('data.status') === 'success';

                case 'flutterwave':
                    $cfg = config('payments.providers.flutterwave');
                    $resp = Http::withToken($cfg['secret_key'])
                        ->get(rtrim($cfg['base_url'], '/').'/v3/transactions/verify_by_reference', ['tx_ref' => $reference]);
                    return $resp->successful() && $resp->json('data.status') === 'successful';

                default:
                    return false;
            }
        } catch (\Throwable $e) {
            Log::warning('[payments] verify failed: '.$e->getMessage());
            return false;
        }
    }

    // ── Webhook signature ──────────────────────────────────────────────

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
                $secret = config('payments.providers.paystack.secret_key');
                $signature = $header('x-paystack-signature');
                if (!$secret || !$signature) {
                    return false;
                }
                return hash_equals(hash_hmac('sha512', $rawBody, $secret), $signature);

            case 'flutterwave':
                $expected = config('payments.providers.flutterwave.secret_hash');
                $given = $header('verif-hash');
                return $expected && $given && hash_equals($expected, $given);

            default:
                return false;
        }
    }

    private function extractReference(string $provider, array $payload): ?string
    {
        switch ($provider) {
            case 'log':
                return $payload['reference'] ?? null;
            case 'paystack':
                return $payload['data']['reference'] ?? null;
            case 'flutterwave':
                return $payload['data']['tx_ref'] ?? $payload['data']['txRef'] ?? null;
            default:
                return null;
        }
    }
}
