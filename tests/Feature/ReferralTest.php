<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\User;
use App\Services\Referral\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'U'.uniqid(),
            'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(),
            'password' => bcrypt('secret123'),
        ], $attrs));
    }

    public function test_me_endpoint_returns_code_and_links()
    {
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/referrals/me')
            ->assertStatus(200)
            ->assertJson(['enabled' => true])
            ->assertJsonStructure(['code', 'invite_link', 'whatsapp_url', 'stats' => ['invited', 'qualified']]);
    }

    public function test_registering_with_a_code_attributes_the_referral()
    {
        $referrer = $this->makeUser();
        $code = app(ReferralService::class)->codeFor($referrer);

        $this->postJson('/api/register', [
            'name' => 'Invitee',
            'email' => 'invitee@example.com',
            'phone_number' => '08011112222',
            'password' => 'secret123',
            'referral_code' => $code,
        ])->assertStatus(200);

        $invitee = User::where('phone_number', '08011112222')->first();
        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
            'referred_id' => $invitee->id,
            'status' => 'pending',
        ]);
    }

    public function test_qualify_marks_pending_referral_and_is_idempotent()
    {
        $referrer = $this->makeUser();
        $invitee = $this->makeUser();
        $service = app(ReferralService::class);

        $service->attribute($invitee, $service->codeFor($referrer));
        $service->qualify($invitee);
        $service->qualify($invitee); // second call must not create/alter further

        $this->assertSame(1, Referral::where('referred_id', $invitee->id)->count());
        $this->assertSame('qualified', Referral::where('referred_id', $invitee->id)->first()->status);
    }

    public function test_self_referral_is_ignored()
    {
        $user = $this->makeUser();
        $service = app(ReferralService::class);

        $this->assertNull($service->attribute($user, $service->codeFor($user)));
        $this->assertSame(0, Referral::count());
    }
}
