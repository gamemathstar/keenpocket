<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeensTest extends TestCase
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

    public function test_new_user_starts_with_50_keens()
    {
        $this->assertSame(50, (int) $this->makeUser()->fresh()->keens);
    }

    public function test_creating_pocket_charges_keens_when_enabled()
    {
        Setting::set('coins_enabled', '1');
        Setting::set('cost_pocket', 50);

        $user = $this->makeUser(['keens' => 50]);
        $this->actingAs($user)->post('/pockets', [
            'title' => 'P', 'year' => 2026, 'start_month' => 1, 'month_count' => 12,
            'max_keens' => 0, 'amount_per_hand' => 5000, 'hand_count' => 1, 'accept_terms' => 1,
        ])->assertRedirect();

        $this->assertSame(0, (int) $user->fresh()->keens);            // 50 - 50
        $this->assertDatabaseHas('keen_transactions', ['user_id' => $user->id, 'amount' => -50]);
        $this->assertDatabaseHas('pockets', ['title' => 'P', 'user_id' => $user->id]);
    }

    public function test_insufficient_keens_blocks_creation()
    {
        Setting::set('coins_enabled', '1');
        Setting::set('cost_pocket', 50);

        $user = $this->makeUser(['keens' => 10]);
        $this->actingAs($user)->post('/pockets', [
            'title' => 'TooPoor', 'year' => 2026, 'start_month' => 1, 'month_count' => 12,
            'max_keens' => 0, 'amount_per_hand' => 5000, 'hand_count' => 1, 'accept_terms' => 1,
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('pockets', ['title' => 'TooPoor']);
        $this->assertSame(10, (int) $user->fresh()->keens);
    }

    public function test_super_admin_creates_free()
    {
        Setting::set('coins_enabled', '1');
        $admin = $this->makeUser(['is_super_admin' => true, 'keens' => 0]);

        $this->actingAs($admin)->post('/pockets', [
            'title' => 'FreeP', 'year' => 2026, 'start_month' => 1, 'month_count' => 12,
            'max_keens' => 0, 'amount_per_hand' => 5000, 'hand_count' => 1, 'accept_terms' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('pockets', ['title' => 'FreeP']);
        $this->assertSame(0, (int) $admin->fresh()->keens); // not charged
    }

    public function test_adashi_cost_is_tiered_by_members()
    {
        Setting::set('coins_enabled', '1');
        Setting::set('cost_adashi', 50);
        Setting::set('adashi_tier', 12);
        Setting::set('adashi_step', 40);

        $svc = app(\App\Services\Coins\CoinService::class);
        $this->assertSame(50, $svc->cost('adashi', 12));   // ≤12
        $this->assertSame(90, $svc->cost('adashi', 13));   // 13 → 2nd tier
        $this->assertSame(90, $svc->cost('adashi', 24));   // ≤24
        $this->assertSame(130, $svc->cost('adashi', 25));  // 25 → 3rd tier
        $this->assertSame(130, $svc->cost('adashi', 36));  // ≤36
    }

    public function test_creating_adashi_charges_the_tier_for_expected_members()
    {
        Setting::set('coins_enabled', '1');
        Setting::set('cost_adashi', 50);
        Setting::set('adashi_tier', 12);
        Setting::set('adashi_step', 40);

        $user = $this->makeUser(['keens' => 200]);
        $this->actingAs($user)->post('/adashi', [
            'name' => 'Big circle', 'amount_per_cycle' => 50000, 'cycle_duration_days' => 30,
            'start_date' => '2026-07-01', 'rotation_mode' => 'MANUAL',
            'member_capacity' => 24, 'accept_terms' => 1,
        ])->assertRedirect();

        $this->assertSame(110, (int) $user->fresh()->keens);  // 200 - 90 (tier for 24)
        $this->assertDatabaseHas('keen_transactions', ['user_id' => $user->id, 'amount' => -90]);
    }

    public function test_super_admin_sets_costs()
    {
        $admin = $this->makeUser(['is_super_admin' => true]);
        $this->actingAs($admin)->post('/super-admin/coins', [
            'coins_enabled' => 1, 'cost_pocket' => 75, 'cost_adashi' => 60, 'cost_school' => 120,
        ])->assertRedirect();

        $this->assertSame('1', Setting::get('coins_enabled'));
        $this->assertSame('75', Setting::get('cost_pocket'));
    }
}
