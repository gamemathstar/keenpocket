<?php

namespace Tests\Unit;

use App\Models\Payout;
use App\Services\Payouts\PayoutService;
use ReflectionMethod;
use Tests\TestCase;

class PayoutSignatureTest extends TestCase
{
    private function verify(string $provider, string $body, array $headers): bool
    {
        $m = new ReflectionMethod(PayoutService::class, 'verifyWebhookSignature');
        $m->setAccessible(true);

        return $m->invoke(new PayoutService(), $provider, $body, $headers);
    }

    public function test_paystack_transfer_webhook_signature()
    {
        config(['payouts.providers.paystack.secret_key' => 'sk_payout']);
        $body = '{"event":"transfer.success","data":{"reference":"KP_PO_1_x"}}';
        $sig = hash_hmac('sha512', $body, 'sk_payout');

        $this->assertTrue($this->verify('paystack', $body, ['x-paystack-signature' => $sig]));
        $this->assertFalse($this->verify('paystack', $body, ['x-paystack-signature' => 'forged']));
    }

    public function test_flutterwave_transfer_webhook_signature()
    {
        config(['payouts.providers.flutterwave.secret_hash' => 'po-hash']);

        $this->assertTrue($this->verify('flutterwave', '{}', ['verif-hash' => 'po-hash']));
        $this->assertFalse($this->verify('flutterwave', '{}', ['verif-hash' => 'nope']));
    }

    public function test_settled_predicate_blocks_double_disbursement()
    {
        // The guard that prevents re-paying a pot: pending/success are "settled".
        $this->assertTrue((new Payout(['status' => 'pending']))->isSettled());
        $this->assertTrue((new Payout(['status' => 'success']))->isSettled());
        $this->assertFalse((new Payout(['status' => 'failed']))->isSettled());
    }
}
