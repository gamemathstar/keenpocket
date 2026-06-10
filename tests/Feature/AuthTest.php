<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Rate limiting is exercised separately; disable it here so repeated
        // auth calls across assertions don't trip the throttle.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private array $payload = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone_number' => '08012345678',
        'password' => 'secret123',
    ];

    public function test_register_creates_user_with_hashed_password_and_token()
    {
        $response = $this->postJson('/api/register', $this->payload);

        $response->assertStatus(200);
        $response->assertJsonStructure(['user', 'token']);

        $user = User::where('phone_number', '08012345678')->first();
        $this->assertNotNull($user);
        // Password must be hashed, never stored in plaintext.
        $this->assertNotSame('secret123', $user->password);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_login_with_valid_credentials_returns_token()
    {
        $this->postJson('/api/register', $this->payload);

        $response = $this->postJson('/api/login', [
            'phone_number' => '08012345678',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_with_wrong_password_is_rejected()
    {
        $this->postJson('/api/register', $this->payload);

        $response = $this->postJson('/api/login', [
            'phone_number' => '08012345678',
            'password' => 'wrong-password',
        ]);

        $response->assertJson(['message' => 'Invalid Credentials']);
        $this->assertNull($response->json('token'));
    }

    public function test_login_with_unknown_phone_does_not_leak_existence()
    {
        // Unknown phone returns the SAME generic response as a wrong password —
        // no validation error revealing whether the number is registered.
        $response = $this->postJson('/api/login', [
            'phone_number' => '08099999999',
            'password' => 'whatever',
        ]);

        $response->assertJson(['message' => 'Invalid Credentials']);
        $response->assertJsonMissingValidationErrors(['phone_number']);
    }

    public function test_short_password_is_rejected_on_register()
    {
        $response = $this->postJson('/api/register', array_merge($this->payload, [
            'password' => '123',
        ]));

        $response->assertStatus(422);
    }
}
