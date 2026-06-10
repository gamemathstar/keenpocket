<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

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

    private function makeSlotWithInvoice(string $paymentStatus): User
    {
        $owner = $this->makeUser();
        $member = $this->makeUser();

        $pocket = new Pocket();
        $pocket->user_id = $owner->id;
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
        $slot->user_id = $member->id;
        $slot->hand_count = 1;
        $slot->status = 1;
        $slot->amount_paying = 1000;
        $slot->save();

        $inv = new Invoice();
        $inv->pocket_slot_id = $slot->id;
        $inv->invoice_no = 'KP/T/'.uniqid();
        $inv->amount = 1000;
        $inv->payment_status = $paymentStatus;
        $inv->paid_through = $paymentStatus === 'Paid' ? 'Manual' : 'Pending';
        $inv->save();

        return $member;
    }

    public function test_members_with_unpaid_invoices_are_reminded()
    {
        $member = $this->makeSlotWithInvoice('Not Paid');

        $this->artisan('pockets:remind')->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->id,
            'type' => 'Payment Reminder',
        ]);
    }

    public function test_members_who_are_fully_paid_are_not_reminded()
    {
        $member = $this->makeSlotWithInvoice('Paid');

        $this->artisan('pockets:remind')->assertExitCode(0);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $member->id,
            'type' => 'Payment Reminder',
        ]);
    }
}
