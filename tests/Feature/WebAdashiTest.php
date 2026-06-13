<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAdashiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Web User', 'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(), 'password' => bcrypt('secret123'),
        ]);
    }

    public function test_user_can_create_adashi_via_web()
    {
        $user = $this->makeUser();
        $this->actingAs($user); // web (session) guard

        $this->post('/adashi', [
            'name' => 'Family Web Adashi',
            'amount_per_cycle' => 50000,
            'cycle_duration_days' => 30,
            'start_date' => '2026-01-01',
            'rotation_mode' => 'manual',
            'is_public' => '1',
            'accept_terms' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('adashis', [
            'name' => 'Family Web Adashi',
            'admin_id' => $user->id,
            'rotation_mode' => 'MANUAL',
            'is_public' => 1,
        ]);
        // Admin auto-enrolled as member + first cycle created.
        $this->assertDatabaseHas('adashi_members', ['user_id' => $user->id, 'position' => 1]);
        $this->assertDatabaseHas('adashi_records', ['cycle_number' => 1, 'status' => 'PENDING']);
    }

    public function test_user_can_create_pocket_via_web()
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $this->post('/pockets', [
            'title' => 'Web Pocket', 'pocket_type' => 'Monthly', 'description' => 'x',
            'year' => 2026, 'start_month' => 1, 'month_count' => 12, 'max_keens' => 0,
            'amount_per_hand' => 5000, 'hand_count' => 1, 'accept_terms' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('pockets', ['title' => 'Web Pocket', 'user_id' => $user->id]);
        $this->assertDatabaseHas('pocket_slots', ['user_id' => $user->id, 'status' => 1]);
    }
}
