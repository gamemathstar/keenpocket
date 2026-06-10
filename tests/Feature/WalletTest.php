<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['wallet.enabled' => true, 'payments.enabled' => false, 'payments.provider' => 'log']);
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'U'.uniqid(),
            'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(),
            'password' => bcrypt('secret123'),
        ]);
    }

    private function invoiceFor(User $user, int $amount): Invoice
    {
        $pocket = new Pocket();
        $pocket->user_id = $this->makeUser()->id;
        $pocket->title = 'P'.uniqid();
        $pocket->pocket_type = 'Ramadan';
        $pocket->year = 2026;
        $pocket->start_month = 1;
        $pocket->month_count = 6;
        $pocket->max_keens = 5;
        $pocket->amount_per_hand = 1000;
        $pocket->status = 1;
        $pocket->save();

        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $user->id;
        $slot->hand_count = 1;
        $slot->status = 1;
        $slot->amount_paying = 1000;
        $slot->save();

        $inv = new Invoice();
        $inv->pocket_slot_id = $slot->id;
        $inv->invoice_no = 'KP/W/'.uniqid();
        $inv->amount = $amount;
        $inv->payment_status = 'Not Paid';
        $inv->paid_through = 'Pending';
        $inv->save();

        return $inv;
    }

    public function test_topup_then_pay_invoice_from_balance()
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/wallet/topup', ['amount' => 10000])->assertStatus(200)->assertJson(['balance' => 10000]);

        $invoice = $this->invoiceFor($user, 2500);
        $this->postJson('/api/wallet/pay-invoice', ['invoice_id' => $invoice->id])
            ->assertStatus(200)
            ->assertJson(['balance' => 7500]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id, 'payment_status' => 'Paid', 'paid_through' => 'Wallet',
        ]);
    }

    public function test_paying_more_than_balance_is_rejected_and_leaves_invoice_unpaid()
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);
        $this->postJson('/api/wallet/topup', ['amount' => 1000]);

        $invoice = $this->invoiceFor($user, 5000);
        $this->postJson('/api/wallet/pay-invoice', ['invoice_id' => $invoice->id])->assertStatus(422);

        // Wallet untouched, invoice still unpaid (atomic rollback).
        $this->getJson('/api/wallet')->assertJson(['balance' => 1000]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'payment_status' => 'Not Paid']);
    }

    public function test_cannot_pay_someone_elses_invoice()
    {
        $owner = $this->makeUser();
        $invoice = $this->invoiceFor($owner, 1000);

        $stranger = $this->makeUser();
        Sanctum::actingAs($stranger);
        $this->postJson('/api/wallet/topup', ['amount' => 5000]);
        $this->postJson('/api/wallet/pay-invoice', ['invoice_id' => $invoice->id])->assertStatus(403);
    }

    public function test_disabled_wallet_is_inert()
    {
        config(['wallet.enabled' => false]);
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/wallet')->assertStatus(200)->assertJson(['enabled' => false]);
    }
}
