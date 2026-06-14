<?php

namespace Tests\Feature;

use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicViewTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        $u = User::create([
            'name' => 'U'.uniqid(), 'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => 'user'.uniqid(), 'password' => bcrypt('secret123'),
        ]);
        foreach ($attrs as $k => $v) {
            $u->$k = $v;
        }
        $u->save();

        return $u;
    }

    public function test_login_with_username()
    {
        $this->makeUser(['username' => 'kenny99']);
        $this->post('/login', ['login' => 'kenny99', 'password' => 'secret123'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_non_member_sees_limited_pocket_view_without_member_names()
    {
        $owner = $this->makeUser();
        $pocket = new Pocket();
        $pocket->user_id = $owner->id;
        $pocket->title = 'Open Pocket';
        $pocket->amount_per_hand = 5000;
        $pocket->month_count = 12;
        $pocket->start_month = 1;
        $pocket->year = 2026;
        $pocket->max_keens = 0;
        $pocket->status = 1; // open
        $pocket->save();

        $member = $this->makeUser(['name' => 'SecretMember']);
        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $member->id;
        $slot->slot_number = 1;
        $slot->hand_count = 1;
        $slot->amount_paying = 5000;
        $slot->status = 1;
        $slot->comment = '';
        $slot->save();

        $stranger = $this->makeUser();
        $res = $this->actingAs($stranger)->get("/pockets/{$pocket->id}");
        $res->assertStatus(200)->assertSee('Request to join')->assertDontSee('SecretMember');
    }

    public function test_public_adashi_self_join_and_private_blocked()
    {
        $admin = $this->makeUser();
        $public = Adashi::create([
            'name' => 'Public A', 'amount_per_cycle' => 50000, 'total_members' => 1,
            'start_date' => now()->toDateString(), 'cycle_duration_days' => 30, 'current_cycle_number' => 1,
            'admin_id' => $admin->id, 'rotation_mode' => 'AUTO', 'status' => 'ACTIVE', 'is_public' => true,
        ]);
        AdashiMember::create(['adashi_id' => $public->id, 'user_id' => $admin->id, 'position' => 1, 'has_received' => false, 'joined_at' => now(), 'is_active' => true]);

        $joiner = $this->makeUser();
        $this->actingAs($joiner)->post("/adashi/{$public->id}/join", ['accept_terms' => 1])->assertRedirect();
        $this->assertDatabaseHas('adashi_members', ['adashi_id' => $public->id, 'user_id' => $joiner->id]);
        $this->assertSame(2, (int) $public->fresh()->total_members);

        // Private adashi can't be self-joined.
        $private = Adashi::create([
            'name' => 'Private A', 'amount_per_cycle' => 50000, 'total_members' => 1,
            'start_date' => now()->toDateString(), 'cycle_duration_days' => 30, 'current_cycle_number' => 1,
            'admin_id' => $admin->id, 'rotation_mode' => 'AUTO', 'status' => 'ACTIVE', 'is_public' => false,
        ]);
        $this->actingAs($this->makeUser())->post("/adashi/{$private->id}/join", ['accept_terms' => 1])->assertStatus(403);
    }
}
