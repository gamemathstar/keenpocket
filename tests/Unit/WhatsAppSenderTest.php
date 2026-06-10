<?php

namespace Tests\Unit;

use App\Services\WhatsApp\WhatsAppSender;
use Tests\TestCase;

class WhatsAppSenderTest extends TestCase
{
    public function test_disabled_by_default_is_a_noop()
    {
        config(['whatsapp.enabled' => false]);

        $this->assertFalse((new WhatsAppSender())->enabled());
        $this->assertFalse((new WhatsAppSender())->send('08012345678', 'payment_reminder', ['hi']));
    }

    public function test_log_provider_succeeds_when_enabled()
    {
        config(['whatsapp.enabled' => true, 'whatsapp.provider' => 'log']);

        $this->assertTrue((new WhatsAppSender())->send('08012345678', 'payment_reminder', ['Your payment is due.']));
    }

    public function test_empty_phone_is_rejected()
    {
        config(['whatsapp.enabled' => true, 'whatsapp.provider' => 'log']);

        $this->assertFalse((new WhatsAppSender())->send('', 'payment_reminder', ['hi']));
    }
}
