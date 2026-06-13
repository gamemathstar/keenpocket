<?php

namespace Tests\Feature;

use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountTest extends TestCase
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

    public function test_user_can_save_accounts_first_is_default()
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/settings/accounts', [
            'account_name' => 'Abubakar A', 'bank' => 'GTBank', 'nuban' => '0123456789',
        ])->assertRedirect();

        $acc = BankAccount::where('user_id', $user->id)->first();
        $this->assertNotNull($acc);
        $this->assertTrue($acc->is_default); // first account becomes default

        // Add a second, mark default — the first loses default.
        $this->actingAs($user)->post('/settings/accounts', [
            'account_name' => 'Abubakar B', 'bank' => 'Access', 'nuban' => '0998877665', 'is_default' => 1,
        ])->assertRedirect();

        $this->assertFalse((bool) $acc->fresh()->is_default);
        $this->assertSame(1, BankAccount::where(['user_id' => $user->id, 'is_default' => true])->count());
    }

    public function test_member_sets_adashi_payout_account()
    {
        $admin = $this->makeUser();
        $member = $this->makeUser();
        $acc = $member->bankAccounts()->create(['account_name' => 'M', 'bank' => 'GTBank', 'nuban' => '0123456789', 'is_default' => true]);

        $adashi = Adashi::create([
            'name' => 'A', 'amount_per_cycle' => 50000, 'total_members' => 2,
            'start_date' => now()->toDateString(), 'cycle_duration_days' => 30,
            'current_cycle_number' => 1, 'admin_id' => $admin->id, 'rotation_mode' => 'AUTO', 'status' => 'ACTIVE',
        ]);
        $m = AdashiMember::create(['adashi_id' => $adashi->id, 'user_id' => $member->id, 'position' => 2, 'has_received' => false, 'joined_at' => now(), 'is_active' => true]);

        $this->actingAs($member)->post("/adashi/{$adashi->id}/payout-account", ['bank_account_id' => $acc->id])->assertRedirect();
        $this->assertSame($acc->id, (int) $m->fresh()->bank_account_id);
    }

    public function test_cannot_use_someone_elses_account()
    {
        $admin = $this->makeUser();
        $member = $this->makeUser();
        $stranger = $this->makeUser();
        $strangerAcc = $stranger->bankAccounts()->create(['account_name' => 'X', 'bank' => 'Zenith', 'nuban' => '0000000000']);

        $adashi = Adashi::create([
            'name' => 'A', 'amount_per_cycle' => 50000, 'total_members' => 2,
            'start_date' => now()->toDateString(), 'cycle_duration_days' => 30,
            'current_cycle_number' => 1, 'admin_id' => $admin->id, 'rotation_mode' => 'AUTO', 'status' => 'ACTIVE',
        ]);
        AdashiMember::create(['adashi_id' => $adashi->id, 'user_id' => $member->id, 'position' => 2, 'has_received' => false, 'joined_at' => now(), 'is_active' => true]);

        $this->actingAs($member)->post("/adashi/{$adashi->id}/payout-account", ['bank_account_id' => $strangerAcc->id])
            ->assertStatus(422);
    }
}
