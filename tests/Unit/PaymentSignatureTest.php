<?php

namespace Tests\Unit;

use App\Actions\MarkInvoicePaid;
use App\Services\Payments\PaymentService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Webhook signature verification is the gate that stops a forged HTTP call from
 * marking an invoice paid, so it is covered directly.
 */
class PaymentSignatureTest extends TestCase
{
    private function verifySignature(string $provider, string $body, array $headers): bool
    {
        $service = new PaymentService(new MarkInvoicePaid());
        $m = new ReflectionMethod($service, 'verifyWebhookSignature');
        $m->setAccessible(true);

        return $m->invoke($service, $provider, $body, $headers);
    }

    public function test_paystack_accepts_valid_hmac_signature()
    {
        config(['payments.providers.paystack.secret_key' => 'sk_test_secret']);
        $body = '{"event":"charge.success","data":{"reference":"KP_1_abc"}}';
        $signature = hash_hmac('sha512', $body, 'sk_test_secret');

        $this->assertTrue($this->verifySignature('paystack', $body, ['x-paystack-signature' => $signature]));
    }

    public function test_paystack_rejects_forged_signature()
    {
        config(['payments.providers.paystack.secret_key' => 'sk_test_secret']);
        $body = '{"event":"charge.success","data":{"reference":"KP_1_abc"}}';

        $this->assertFalse($this->verifySignature('paystack', $body, ['x-paystack-signature' => 'deadbeef']));
        $this->assertFalse($this->verifySignature('paystack', $body, [])); // missing header
    }

    public function test_flutterwave_matches_secret_hash()
    {
        config(['payments.providers.flutterwave.secret_hash' => 'my-hash']);

        $this->assertTrue($this->verifySignature('flutterwave', '{}', ['verif-hash' => 'my-hash']));
        $this->assertFalse($this->verifySignature('flutterwave', '{}', ['verif-hash' => 'wrong']));
        $this->assertFalse($this->verifySignature('flutterwave', '{}', []));
    }

    public function test_unknown_provider_is_rejected()
    {
        $this->assertFalse($this->verifySignature('fakepay', '{}', []));
    }
}
