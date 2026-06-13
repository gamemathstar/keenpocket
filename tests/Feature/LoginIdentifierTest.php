<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginIdentifierTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'Login User', 'email' => 'login.test@example.com',
            'phone_number' => '08099887766', 'username' => uniqid(),
            'password' => bcrypt('secret123'),
        ]);
    }

    public function test_web_login_with_phone()
    {
        $this->user();
        $this->post('/login', ['login' => '08099887766', 'password' => 'secret123'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_web_login_with_email()
    {
        $this->user();
        $this->post('/login', ['login' => 'login.test@example.com', 'password' => 'secret123'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_web_login_rejects_bad_credentials()
    {
        $this->user();
        $this->post('/login', ['login' => 'login.test@example.com', 'password' => 'wrong'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_api_login_with_email()
    {
        $this->user();
        $res = $this->postJson('/api/login', ['phone_number' => 'login.test@example.com', 'password' => 'secret123']);
        $res->assertStatus(200)->assertJson(['status' => 1]);
        $this->assertNotEmpty($res->json('token'));
    }
}
