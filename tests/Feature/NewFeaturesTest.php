<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the "do all except auto-debit" follow-up batch: super-admin Keens
 * top-up, owner declining a pending pocket payment, the placeholder
 * account-claim register flow, and the public legal pages.
 */
class NewFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        $u = User::create([
            'name' => 'U'.uniqid(), 'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => 'u'.uniqid(), 'password' => bcrypt('secret123'),
        ]);
        foreach ($attrs as $k => $v) {
            $u->$k = $v;
        }
        $u->save();

        return $u;
    }

    public function test_super_admin_can_top_up_a_user_by_username()
    {
        $admin = $this->makeUser(['is_super_admin' => true]);
        $target = $this->makeUser(['keens' => 10]);

        $this->actingAs($admin)->post('/super-admin/keens', [
            'contact' => $target->username,
            'amount' => 40,
        ])->assertRedirect();

        $this->assertSame(50, (int) $target->fresh()->keens);
    }

    public function test_super_admin_user_list_shows_balance_and_grants_by_email()
    {
        $admin = $this->makeUser(['is_super_admin' => true]);
        $target = $this->makeUser(['email' => 'topup.target@example.com', 'keens' => 20]);

        // The user list shows the Keens balance.
        $this->actingAs($admin)->get('/super-admin')->assertOk()->assertSee('🪙');

        // The inline form grants by email (its hidden contact field).
        $this->actingAs($admin)->post('/super-admin/keens', [
            'contact' => 'topup.target@example.com',
            'amount' => 30,
        ])->assertRedirect();

        $this->assertSame(50, (int) $target->fresh()->keens);
    }

    public function test_non_super_admin_cannot_top_up()
    {
        $user = $this->makeUser();
        $target = $this->makeUser(['keens' => 5]);

        $this->actingAs($user)->post('/super-admin/keens', [
            'contact' => $target->username, 'amount' => 100,
        ])->assertForbidden();

        $this->assertSame(5, (int) $target->fresh()->keens);
    }

    public function test_pocket_owner_can_decline_a_pending_payment()
    {
        $owner = $this->makeUser();
        $pocket = new Pocket();
        $pocket->user_id = $owner->id;
        $pocket->title = 'P'.uniqid();
        $pocket->amount_per_hand = 10000;
        $pocket->month_count = 12;
        $pocket->start_month = 6;
        $pocket->year = 2026;
        $pocket->max_keens = 0;
        $pocket->status = 1;
        $pocket->save();

        $member = $this->makeUser();
        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $member->id;
        $slot->slot_number = 1;
        $slot->hand_count = 1;
        $slot->amount_paying = 10000;
        $slot->status = 1;
        $slot->comment = '';
        $slot->save();

        $inv = new Invoice();
        $inv->pocket_slot_id = $slot->id;
        $inv->invoice_no = 'KP/PEND/'.$slot->id;
        $inv->amount = 10000;
        $inv->reference_no = $inv->invoice_no;
        $inv->payment_status = 'Not Paid';
        $inv->paid_through = 'Manual';
        $inv->save();
        InvoiceItem::create(['invoice_id' => $inv->id, 'item_id' => 1, 'amount' => 10000, 'type' => 'Submitted', 'month' => 6]);

        $this->actingAs($owner)->post("/invoices/{$inv->id}/decline")->assertRedirect();

        $this->assertDatabaseMissing('invoices', ['id' => $inv->id]);
        $this->assertDatabaseMissing('invoice_item', ['invoice_id' => $inv->id]);
    }

    public function test_non_owner_cannot_decline()
    {
        $owner = $this->makeUser();
        $pocket = new Pocket();
        $pocket->user_id = $owner->id;
        $pocket->title = 'P'.uniqid();
        $pocket->amount_per_hand = 10000;
        $pocket->month_count = 12;
        $pocket->start_month = 6;
        $pocket->year = 2026;
        $pocket->max_keens = 0;
        $pocket->status = 1;
        $pocket->save();

        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $owner->id;
        $slot->slot_number = 1;
        $slot->hand_count = 1;
        $slot->amount_paying = 10000;
        $slot->status = 1;
        $slot->comment = '';
        $slot->save();

        $inv = new Invoice();
        $inv->pocket_slot_id = $slot->id;
        $inv->invoice_no = 'KP/PEND/'.$slot->id;
        $inv->amount = 10000;
        $inv->reference_no = $inv->invoice_no;
        $inv->payment_status = 'Not Paid';
        $inv->paid_through = 'Manual';
        $inv->save();

        $stranger = $this->makeUser();
        $this->actingAs($stranger)->post("/invoices/{$inv->id}/decline")->assertForbidden();
        $this->assertDatabaseHas('invoices', ['id' => $inv->id]);
    }

    public function test_placeholder_account_can_be_claimed_on_register()
    {
        // Admin-added member: email == phone, no real password set yet.
        $phone = '08055555555';
        $placeholder = $this->makeUser(['phone_number' => $phone, 'email' => $phone]);

        $this->post('/register', [
            'name' => 'Claimed Person',
            'email' => 'claimed@example.com',
            'phone_number' => $phone,
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
            'accept_terms' => 1,
        ])->assertRedirect(route('dashboard'));

        $fresh = $placeholder->fresh();
        $this->assertSame($placeholder->id, $fresh->id);
        $this->assertSame('claimed@example.com', $fresh->email);
        $this->assertSame('Claimed Person', $fresh->name);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpass123', $fresh->password));
    }

    public function test_register_rejects_already_registered_phone()
    {
        $existing = $this->makeUser(['phone_number' => '08066666666']);

        $this->post('/register', [
            'name' => 'Someone', 'email' => 'someone@example.com',
            'phone_number' => '08066666666', 'password' => 'newpass123',
            'password_confirmation' => 'newpass123', 'accept_terms' => 1,
        ])->assertSessionHasErrors('phone_number');
    }

    public function test_legal_pages_are_public()
    {
        $this->get('/terms')->assertOk()->assertSee('record-keeping tool', false);
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy', false);
    }

    public function test_forgot_password_page_loads_and_sends_link()
    {
        \Illuminate\Support\Facades\Notification::fake();
        $user = $this->makeUser(['email' => 'reset.me@example.com']);

        $this->get('/forgot-password')->assertOk()->assertSee('Forgot your password', false);

        $this->post('/forgot-password', ['email' => 'reset.me@example.com'])
            ->assertRedirect()->assertSessionHas('status');

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $user, \Illuminate\Auth\Notifications\ResetPassword::class
        );
    }

    public function test_password_can_be_reset_with_a_valid_token()
    {
        $user = $this->makeUser(['email' => 'changer@example.com']);
        $token = \Illuminate\Support\Facades\Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'changer@example.com',
            'password' => 'brandnew123',
            'password_confirmation' => 'brandnew123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('brandnew123', $user->fresh()->password));
    }
}
