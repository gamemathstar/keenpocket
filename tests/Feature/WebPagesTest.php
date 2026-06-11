<?php

namespace Tests\Feature;

use App\Models\Adashi;
use App\Models\Pocket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders the component-heavy authenticated pages to catch Blade/component
 * runtime errors (mascot, stat-tile, progress-ring, empty-state).
 */
class WebPagesTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'Render User', 'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(), 'password' => bcrypt('secret123'),
        ]);
    }

    public function test_key_pages_render_for_authenticated_user()
    {
        $this->actingAs($this->user());

        foreach (['/dashboard', '/pockets', '/adashi', '/discover', '/leaderboard', '/profile', '/notifications', '/wallet', '/payouts', '/referrals', '/settings'] as $path) {
            $this->get($path)->assertStatus(200);
        }
    }

    public function test_detail_pages_render_with_progress_and_controls()
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->post('/pockets', [
            'title' => 'Render Pocket', 'pocket_type' => 'Monthly', 'description' => '',
            'year' => 2026, 'start_month' => 1, 'month_count' => 12, 'max_keens' => 0,
            'amount_per_hand' => 5000, 'hand_count' => 1,
        ]);
        $this->post('/adashi', [
            'name' => 'Render Adashi', 'amount_per_cycle' => 50000, 'cycle_duration_days' => 30,
            'start_date' => '2026-01-01', 'rotation_mode' => 'manual',
        ]);

        $pocket = Pocket::first();
        $adashi = Adashi::first();

        $this->get("/pockets/{$pocket->id}")->assertStatus(200);          // progress bar + shopping list
        $this->get("/pockets/{$pocket->id}/manage")->assertStatus(200);
        $this->get("/adashi/{$adashi->id}")->assertStatus(200);           // cycle progress bar
        $this->get("/adashi/{$adashi->id}/members")->assertStatus(200);   // admin controls
    }
}
