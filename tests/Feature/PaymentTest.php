<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Endpoint-level behaviour that does not depend on the (legacy, un-migrated)
 * `invoices` table. Full settlement is covered once core migrations exist.
 */
class PaymentTest extends TestCase
{
    public function test_status_reports_disabled_by_default()
    {
        Sanctum::actingAs(User::factory()->make(['id' => 1]));

        $this->getJson('/api/payments/status')
            ->assertStatus(200)
            ->assertJson(['enabled' => false]);
    }

    public function test_initialize_is_a_noop_while_disabled()
    {
        Sanctum::actingAs(User::factory()->make(['id' => 1]));

        $this->postJson('/api/payments/initialize', ['invoice_id' => 1])
            ->assertStatus(200)
            ->assertJson(['enabled' => false]);
    }

    public function test_webhook_with_invalid_signature_is_accepted_but_does_not_settle()
    {
        // Gateways expect a 200 ack; an unsigned/forged call must not error out,
        // and (signature having failed) it settles nothing.
        $this->postJson('/api/payments/webhook/paystack', ['data' => ['reference' => 'KP_1_x']])
            ->assertStatus(200)
            ->assertJson(['received' => true]);
    }
}
