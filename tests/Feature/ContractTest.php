<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Guards the mobile-client contract (docs API_REFERENCE.md) for the endpoints
 * and shapes that were missing or mismatched.
 */
class ContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private array $reg = [
        'name' => 'Contract User',
        'email' => 'contract@example.com',
        'phone_number' => '08010000123',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ];

    public function test_login_returns_status_token_and_refresh_token()
    {
        $this->postJson('/api/register', $this->reg);

        $res = $this->postJson('/api/login', ['phone_number' => '08010000123', 'password' => 'secret123'])
            ->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertNotEmpty($res->json('token'));
        $this->assertNotEmpty($res->json('refresh_token'));
    }

    public function test_refresh_token_rotates_into_a_new_pair()
    {
        $this->postJson('/api/register', $this->reg);
        $refresh = $this->postJson('/api/login', ['phone_number' => '08010000123', 'password' => 'secret123'])->json('refresh_token');

        $this->postJson('/api/refresh-token', ['refresh_token' => $refresh])
            ->assertStatus(200)
            ->assertJson(['status' => 1]);

        // An access token must not be accepted as a refresh token.
        $access = $this->postJson('/api/login', ['phone_number' => '08010000123', 'password' => 'secret123'])->json('token');
        $this->postJson('/api/refresh-token', ['refresh_token' => $access])->assertJson(['status' => 0]);
    }

    public function test_change_password()
    {
        $this->postJson('/api/register', $this->reg);
        $user = User::where('phone_number', '08010000123')->first();
        Sanctum::actingAs($user);

        $this->postJson('/api/change-password', [
            'old_password' => 'wrong', 'new_password' => 'newpass1', 'password_confirmation' => 'newpass1',
        ])->assertStatus(200)->assertSee('false', false);

        $this->postJson('/api/change-password', [
            'old_password' => 'secret123', 'new_password' => 'newpass1', 'password_confirmation' => 'newpass1',
        ])->assertStatus(200)->assertSee('true', false);
    }

    public function test_request_and_verify_token()
    {
        $this->postJson('/api/register', $this->reg);

        $this->getJson('/api/request-token?email=contract@example.com')->assertStatus(200)->assertSee('true', false);

        $token = DB::table('password_resets')->where('email', 'contract@example.com')->value('token');
        $this->assertNotNull($token);

        $this->postJson('/api/verify-token', ['token' => $token])->assertStatus(200)->assertSee('true', false);
        $this->postJson('/api/verify-token', ['token' => '000000'])->assertStatus(200)->assertSee('false', false);
    }

    public function test_adashi_accepts_lowercase_rotation_mode_and_returns_integer_flags()
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'a@x.com', 'phone_number' => '08020000001',
            'username' => 'a', 'password' => bcrypt('secret123'),
        ]);
        Sanctum::actingAs($admin);

        $res = $this->postJson('/api/adashi', [
            'name' => 'Family Adashi',
            'amount_per_cycle' => '50000',
            'cycle_duration_days' => 30,
            'start_date' => '2026-01-01',
            'rotation_mode' => 'manual', // lowercase — must be accepted
            'members' => [],
        ])->assertStatus(200)->assertJson(['success' => true]);

        // Member flags are integers 0/1, not booleans.
        $member = $res->json('adashi.members.0');
        $this->assertSame(0, $member['has_received']);
        $this->assertSame(1, $member['is_active']);
    }
}
