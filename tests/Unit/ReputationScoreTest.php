<?php

namespace Tests\Unit;

use App\Services\Reputation\ReputationService;
use PHPUnit\Framework\TestCase;

class ReputationScoreTest extends TestCase
{
    public function test_score_weights_reliability_and_activity()
    {
        $this->assertSame(0, ReputationService::score(0, 0));
        $this->assertSame(70, ReputationService::score(100, 0)); // reliability maxes at 70
        $this->assertSame(30, ReputationService::score(0, 10));  // activity maxes at 30
        $this->assertSame(100, ReputationService::score(100, 10));
        $this->assertSame(38, ReputationService::score(50, 1));  // 35 + 3
    }

    public function test_score_clamps_out_of_range_reliability()
    {
        $this->assertSame(70, ReputationService::score(150, 0));
        $this->assertSame(0, ReputationService::score(-20, 0));
    }

    public function test_band_reflects_history_and_thresholds()
    {
        $this->assertSame('New', ReputationService::band(0, false));
        $this->assertSame('New', ReputationService::band(90, false)); // no history overrides score
        $this->assertSame('Gold', ReputationService::band(80, true));
        $this->assertSame('Silver', ReputationService::band(60, true));
        $this->assertSame('Bronze', ReputationService::band(40, true));
        $this->assertSame('Building', ReputationService::band(39, true));
    }
}
