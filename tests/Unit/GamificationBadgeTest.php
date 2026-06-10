<?php

namespace Tests\Unit;

use App\Services\Gamification\GamificationService;
use Tests\TestCase;

class GamificationBadgeTest extends TestCase
{
    private function earned(array $metrics): array
    {
        $badges = GamificationService::evaluateBadges($metrics);

        return collect($badges)->filter(fn ($b) => $b['earned'])->pluck('slug')->all();
    }

    public function test_no_activity_earns_nothing()
    {
        $this->assertSame([], $this->earned([]));
    }

    public function test_activity_badges()
    {
        $this->assertContains('first_pocket', $this->earned(['pockets_joined' => 1]));
        $this->assertContains('adashi_member', $this->earned(['adashis_joined' => 2]));
        $this->assertContains('cycle_champion', $this->earned(['cycles_completed' => 1]));
        $this->assertContains('verified', $this->earned(['kyc_verified' => true]));
    }

    public function test_reliable_payer_needs_enough_invoices_and_high_reliability()
    {
        $this->assertContains('reliable_payer', $this->earned(['invoices_total' => 5, 'payment_reliability' => 95]));
        // High reliability but too few invoices → not earned.
        $this->assertNotContains('reliable_payer', $this->earned(['invoices_total' => 2, 'payment_reliability' => 100]));
    }

    public function test_top_organizer_and_recruiter_and_big_saver_thresholds()
    {
        $this->assertContains('top_organizer', $this->earned(['rating_count' => 3, 'rating_average' => 4.6]));
        $this->assertNotContains('top_organizer', $this->earned(['rating_count' => 2, 'rating_average' => 5.0]));

        $this->assertContains('recruiter', $this->earned(['referrals_qualified' => 3]));
        $this->assertNotContains('recruiter', $this->earned(['referrals_qualified' => 2]));

        $this->assertContains('big_saver', $this->earned(['total_contributed' => 100000]));
        $this->assertNotContains('big_saver', $this->earned(['total_contributed' => 99999]));
    }
}
