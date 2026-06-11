<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_root_redirects_to_login_when_guest()
    {
        // Root redirects guests to the login page (see routes/web.php).
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertStatus(200);
    }
}
