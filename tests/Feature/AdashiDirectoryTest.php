<?php

namespace Tests\Feature;

use App\Models\Adashi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdashiDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'U'.uniqid(),
            'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(),
            'password' => bcrypt('secret123'),
        ]);
    }

    private function makeAdashi(int $adminId, bool $public, string $status = 'ACTIVE'): Adashi
    {
        return Adashi::create([
            'name' => 'A'.uniqid(),
            'amount_per_cycle' => 5000,
            'total_members' => 1,
            'start_date' => now()->toDateString(),
            'cycle_duration_days' => 30,
            'current_cycle_number' => 1,
            'admin_id' => $adminId,
            'rotation_mode' => 'MANUAL',
            'status' => $status,
            'is_public' => $public,
        ]);
    }

    public function test_directory_lists_only_public_active_adashis()
    {
        $admin = $this->makeUser();
        $public = $this->makeAdashi($admin->id, true);
        $private = $this->makeAdashi($admin->id, false);
        $closedPublic = $this->makeAdashi($admin->id, true, 'COMPLETED');

        Sanctum::actingAs($this->makeUser());
        $ids = collect($this->getJson('/api/directory/adashi')->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($public->id));
        $this->assertFalse($ids->contains($private->id));
        $this->assertFalse($ids->contains($closedPublic->id));
    }

    public function test_private_adashi_join_is_admin_only()
    {
        $admin = $this->makeUser();
        $outsider = $this->makeUser();
        $private = $this->makeAdashi($admin->id, false);

        Sanctum::actingAs($outsider);
        $this->postJson("/api/adashi/{$private->id}/join", ['user_id' => $outsider->id])
            ->assertStatus(403);
    }

    public function test_public_adashi_allows_self_join_but_not_adding_others()
    {
        $admin = $this->makeUser();
        $joiner = $this->makeUser();
        $other = $this->makeUser();
        $public = $this->makeAdashi($admin->id, true);

        Sanctum::actingAs($joiner);
        $this->postJson("/api/adashi/{$public->id}/join", ['user_id' => $joiner->id])
            ->assertStatus(200);

        // Cannot add a third party to a public adashi.
        $this->postJson("/api/adashi/{$public->id}/join", ['user_id' => $other->id])
            ->assertStatus(403);
    }

    public function test_only_admin_can_change_visibility()
    {
        $admin = $this->makeUser();
        $adashi = $this->makeAdashi($admin->id, false);

        Sanctum::actingAs($this->makeUser());
        $this->postJson("/api/adashi/{$adashi->id}/visibility", ['is_public' => true])->assertStatus(403);

        Sanctum::actingAs($admin);
        $this->postJson("/api/adashi/{$adashi->id}/visibility", ['is_public' => true])
            ->assertStatus(200)->assertJson(['is_public' => true]);
    }
}
