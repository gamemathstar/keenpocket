<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Streak\StreakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StreakTest extends TestCase
{
    use RefreshDatabase;

    private function user(int $freezes): User
    {
        return User::create([
            'name' => 'Streak User', 'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(), 'password' => bcrypt('secret123'),
            'streak_freezes' => $freezes,
        ]);
    }

    public function test_freeze_bridges_a_missed_week()
    {
        $user = $this->user(1);
        $now = Carbon::now();
        // Contributed this week and two weeks ago; last week missed.
        $weeks = [$now->copy()->format('oW'), $now->copy()->subWeeks(2)->format('oW')];

        $result = app(StreakService::class)->evaluate($user, $weeks);

        $this->assertSame(3, $result['streak']);   // this + (frozen) last + 2-weeks-ago
        $this->assertSame(0, $result['freezes']);   // freeze consumed
        $user->refresh();
        $this->assertContains($now->copy()->subWeek()->format('oW'), $user->streak_frozen_weeks ?? []);
    }

    public function test_no_freeze_breaks_at_the_gap()
    {
        $user = $this->user(0);
        $now = Carbon::now();
        $weeks = [$now->copy()->format('oW'), $now->copy()->subWeeks(2)->format('oW')];

        $result = app(StreakService::class)->evaluate($user, $weeks);

        $this->assertSame(1, $result['streak']);    // breaks at the missed week
    }
}
