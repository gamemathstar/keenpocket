<?php

namespace Tests\Feature;

use App\Services\Otp\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_status_reports_disabled_by_default()
    {
        $this->getJson('/api/otp/status')->assertStatus(200)->assertJson(['enabled' => false]);
    }

    public function test_request_is_a_noop_while_disabled()
    {
        $response = $this->postJson('/api/otp/request', ['phone_number' => '08012345678']);

        $response->assertStatus(200)->assertJson(['enabled' => false]);
    }

    public function test_register_is_not_gated_while_disabled()
    {
        // With OTP off, registration must succeed without any verification.
        $this->postJson('/api/register', [
            'name' => 'No OTP',
            'email' => 'nootp@example.com',
            'phone_number' => '08055555555',
            'password' => 'secret123',
        ])->assertStatus(200)->assertJsonStructure(['user', 'token']);
    }

    public function test_send_and_verify_round_trip_when_enabled()
    {
        config(['otp.enabled' => true, 'otp.provider' => 'log']);

        /** @var OtpService $otp */
        $otp = app(OtpService::class);

        $result = $otp->send('08012345678');
        $this->assertTrue($result['sent']);
        $this->assertArrayHasKey('debug_code', $result); // surfaced only for log driver in testing

        $code = $result['debug_code'];

        $this->assertFalse($otp->verify('08012345678', '000000-wrong'));
        $this->assertTrue($otp->verify('08012345678', $code));
        $this->assertTrue($otp->isVerified('08012345678'));
    }

    public function test_register_is_gated_when_enabled_and_unverified()
    {
        config(['otp.enabled' => true, 'otp.provider' => 'log']);

        $this->postJson('/api/register', [
            'name' => 'Needs OTP',
            'email' => 'needsotp@example.com',
            'phone_number' => '08066666666',
            'password' => 'secret123',
        ])->assertStatus(422)->assertJson(['message' => 'Please verify your phone number first.']);
    }
}
