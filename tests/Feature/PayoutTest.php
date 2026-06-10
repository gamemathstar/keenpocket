<?php

namespace Tests\Feature;

use App\Models\Adashi;
use App\Models\AdashiRecord;
use App\Models\Payout;
use App\Models\User;
use App\Services\Payouts\PayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'U'.uniqid(),
            'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(),
            'password' => bcrypt('secret123'),
        ], $attrs));
    }

    private function makeRecord(int $receiverId, int $collected = 5000): AdashiRecord
    {
        $adashi = Adashi::create([
            'name' => 'A'.uniqid(),
            'amount_per_cycle' => 5000,
            'total_members' => 1,
            'start_date' => now()->toDateString(),
            'cycle_duration_days' => 30,
            'current_cycle_number' => 1,
            'admin_id' => $receiverId,
            'rotation_mode' => 'MANUAL',
            'status' => 'ACTIVE',
        ]);

        return AdashiRecord::create([
            'adashi_id' => $adashi->id,
            'cycle_number' => 1,
            'due_at' => now(),
            'total_collected' => $collected,
            'receiver_user_id' => $receiverId,
            'receiver_member_id' => null, // nullable FK — not needed for payout
            'paid_members_count' => 1,
            'status' => 'PAID_OUT',
        ]);
    }

    public function test_status_disabled_by_default()
    {
        Sanctum::actingAs($this->makeUser());
        $this->getJson('/api/payouts/status')->assertStatus(200)->assertJson(['enabled' => false]);
    }

    public function test_disabled_service_does_not_disburse()
    {
        config(['payouts.enabled' => false]);
        $record = $this->makeRecord($this->makeUser()->id);

        $this->assertNull(app(PayoutService::class)->attemptForRecord($record));
        $this->assertSame(0, Payout::count());
    }

    public function test_log_provider_disburses_to_member_with_bank_details()
    {
        config(['payouts.enabled' => true, 'payouts.provider' => 'log']);
        $receiver = $this->makeUser([
            'payout_bank_name' => 'GTBank',
            'payout_bank_code' => '058',
            'payout_account_number' => '0123456789',
        ]);
        $record = $this->makeRecord($receiver->id, 5000);

        $payout = app(PayoutService::class)->attemptForRecord($record);

        $this->assertSame('success', $payout->status);
        $this->assertSame(5000, (int) $payout->amount);
        $this->assertNotNull($payout->disbursed_at);
    }

    public function test_payout_is_idempotent_per_cycle()
    {
        config(['payouts.enabled' => true, 'payouts.provider' => 'log']);
        $receiver = $this->makeUser([
            'payout_bank_name' => 'GTBank', 'payout_bank_code' => '058', 'payout_account_number' => '0123456789',
        ]);
        $record = $this->makeRecord($receiver->id, 5000);

        $first = app(PayoutService::class)->attemptForRecord($record);
        $second = app(PayoutService::class)->attemptForRecord($record); // must NOT re-pay

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Payout::where('adashi_record_id', $record->id)->count());
    }

    public function test_missing_bank_details_records_a_failed_payout_without_paying()
    {
        config(['payouts.enabled' => true, 'payouts.provider' => 'log']);
        $record = $this->makeRecord($this->makeUser()->id); // no bank details

        $payout = app(PayoutService::class)->attemptForRecord($record);

        $this->assertSame('failed', $payout->status);
        $this->assertSame('no_bank_details', $payout->failure_reason);
        $this->assertNull($payout->disbursed_at);
    }
}
