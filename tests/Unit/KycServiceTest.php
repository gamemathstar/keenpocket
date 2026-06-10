<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Kyc\KycService;
use ReflectionMethod;
use Tests\TestCase;

class KycServiceTest extends TestCase
{
    public function test_disabled_submit_is_a_noop()
    {
        config(['kyc.enabled' => false]);

        $result = (new KycService())->submit(new User(['kyc_status' => 'none']), 'BVN', '12345678901');

        $this->assertFalse($result['enabled']);
        $this->assertSame('none', $result['status']);
    }

    public function test_is_verified_predicate()
    {
        $svc = new KycService();
        $this->assertTrue($svc->isVerified(new User(['kyc_status' => 'verified'])));
        $this->assertFalse($svc->isVerified(new User(['kyc_status' => 'none'])));
        $this->assertFalse($svc->isVerified(new User(['kyc_status' => 'failed'])));
    }

    public function test_log_provider_verifies_well_formed_id_only()
    {
        config(['kyc.provider' => 'log']);
        $m = new ReflectionMethod(KycService::class, 'verifyWithProvider');
        $m->setAccessible(true);
        $svc = new KycService();

        $this->assertTrue($m->invoke($svc, 'BVN', '12345678901', new User())['verified']); // 11 digits
        $this->assertFalse($m->invoke($svc, 'BVN', '12345', new User())['verified']);       // too short
    }
}
