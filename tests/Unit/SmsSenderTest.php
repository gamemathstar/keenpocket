<?php

namespace Tests\Unit;

use App\Services\Sms\SmsSender;
use Tests\TestCase;

class SmsSenderTest extends TestCase
{
    public function test_disabled_by_default_is_a_noop()
    {
        config(['sms.enabled' => false]);

        $this->assertFalse((new SmsSender())->enabled());
        $this->assertFalse((new SmsSender())->send('08012345678', 'hi'));
    }

    public function test_log_provider_succeeds_when_enabled()
    {
        config(['sms.enabled' => true, 'sms.provider' => 'log']);

        $this->assertTrue((new SmsSender())->send('08012345678', 'Your payment is due.'));
    }

    public function test_empty_phone_is_rejected()
    {
        config(['sms.enabled' => true, 'sms.provider' => 'log']);

        $this->assertFalse((new SmsSender())->send('   ', 'hi'));
    }
}
