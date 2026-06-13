<?php

namespace Tests\Feature;

use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\AdashiRecord;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdashiContributionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'U'.uniqid(), 'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(), 'password' => bcrypt('secret123'),
        ]);
    }

    /** Adashi (₦50k/cycle) with admin + one member + an open cycle 1. */
    private function scenario(): array
    {
        $admin = $this->makeUser();
        $member = $this->makeUser();
        $adashi = Adashi::create([
            'name' => 'A'.uniqid(), 'amount_per_cycle' => 50000, 'total_members' => 2,
            'start_date' => now()->toDateString(), 'cycle_duration_days' => 30,
            'current_cycle_number' => 1, 'admin_id' => $admin->id, 'rotation_mode' => 'MANUAL', 'status' => 'ACTIVE',
        ]);
        $adminMember = AdashiMember::create(['adashi_id' => $adashi->id, 'user_id' => $admin->id, 'position' => 1, 'has_received' => false, 'joined_at' => now(), 'is_active' => true]);
        $m = AdashiMember::create(['adashi_id' => $adashi->id, 'user_id' => $member->id, 'position' => 2, 'has_received' => false, 'joined_at' => now(), 'is_active' => true]);
        $record = AdashiRecord::create([
            'adashi_id' => $adashi->id, 'cycle_number' => 1, 'due_at' => now()->addDays(30),
            'total_collected' => 0, 'receiver_user_id' => $admin->id, 'receiver_member_id' => $adminMember->id,
            'paid_members_count' => 0, 'status' => 'PENDING',
        ]);

        return [$admin, $member, $adashi, $m, $record];
    }

    public function test_member_contribution_is_pending_until_admin_verifies()
    {
        [$admin, $member, $adashi, $m, $record] = $this->scenario();

        $this->actingAs($member)->post("/adashi/{$adashi->id}/contribute", ['amount' => 50000])->assertRedirect();

        $invoice = Invoice::where(['adashi_record_id' => $record->id, 'adashi_member_id' => $m->id])->first();
        $this->assertNotNull($invoice);
        $this->assertSame('Not Paid', $invoice->payment_status);   // pending, not counted
        $this->assertSame(0, (int) $record->fresh()->total_collected);

        // Admin verifies → counts.
        $this->actingAs($admin)->post("/adashi/contributions/{$invoice->id}/verify")->assertRedirect();
        $this->assertSame('Paid', $invoice->fresh()->payment_status);
        $this->assertSame(50000, (int) $record->fresh()->total_collected);
    }

    public function test_member_cannot_contribute_more_than_cycle_max()
    {
        [$admin, $member, $adashi, $m, $record] = $this->scenario();

        $this->actingAs($member)->post("/adashi/{$adashi->id}/contribute", ['amount' => 60000])
            ->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('invoices', ['adashi_record_id' => $record->id, 'adashi_member_id' => $m->id]);
    }

    public function test_admin_can_add_contribution_for_member_then_verify()
    {
        [$admin, $member, $adashi, $m, $record] = $this->scenario();

        $this->actingAs($admin)->post("/adashi/{$adashi->id}/contributions/add", [
            'member_user_id' => $member->id, 'amount' => 50000,
        ])->assertRedirect();

        $invoice = Invoice::where(['adashi_record_id' => $record->id, 'adashi_member_id' => $m->id])->first();
        $this->assertNotNull($invoice);
        $this->assertSame('Not Paid', $invoice->payment_status);

        $this->actingAs($admin)->post("/adashi/contributions/{$invoice->id}/verify")->assertRedirect();
        $this->assertSame('Paid', $invoice->fresh()->payment_status);
    }
}
