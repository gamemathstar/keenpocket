<?php

namespace Tests\Feature;

use App\Models\Pocket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KycTest extends TestCase
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

    private function makePocket(int $ownerId): Pocket
    {
        $p = new Pocket();
        $p->user_id = $ownerId;
        $p->title = 'P'.uniqid();
        $p->pocket_type = 'Ramadan';
        $p->year = 2026;
        $p->start_month = 1;
        $p->month_count = 6;
        $p->max_keens = 5;
        $p->amount_per_hand = 1000;
        $p->status = 1;
        $p->save();

        return $p;
    }

    public function test_status_is_none_by_default()
    {
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/kyc/status')->assertStatus(200)->assertJson(['status' => 'none']);
    }

    public function test_submit_verifies_with_log_provider_and_stores_only_last4()
    {
        config(['kyc.enabled' => true, 'kyc.provider' => 'log']);
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/kyc/submit', ['type' => 'BVN', 'id_number' => '22212345678'])
            ->assertStatus(200)
            ->assertJson(['verified' => true]);

        $fresh = $user->fresh();
        $this->assertSame('verified', $fresh->kyc_status);
        $this->assertSame('5678', $fresh->kyc_id_last4);   // only last 4 retained
        $this->assertNull($fresh->getAttribute('bvn'));     // raw id never persisted
    }

    public function test_directory_hides_unverified_organizers_when_kyc_enabled()
    {
        config(['kyc.enabled' => true, 'kyc.provider' => 'log', 'kyc.gate_directory' => true]);

        $verified = $this->makeUser(['kyc_status' => 'verified']);
        $unverified = $this->makeUser(['kyc_status' => 'none']);
        $shown = $this->makePocket($verified->id);
        $hidden = $this->makePocket($unverified->id);

        Sanctum::actingAs($this->makeUser());
        $ids = collect($this->getJson('/api/directory/pockets')->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($shown->id));
        $this->assertFalse($ids->contains($hidden->id));
    }
}
