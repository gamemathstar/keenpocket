<?php

namespace Tests\Feature;

use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupToolsTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'U'.uniqid(), 'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(), 'password' => bcrypt('secret123'),
        ]);
    }

    private function pocket(User $owner): Pocket
    {
        $p = new Pocket();
        $p->user_id = $owner->id;
        $p->title = 'Ramadan';
        $p->amount_per_hand = 10000;
        $p->month_count = 12;
        $p->start_month = 1;
        $p->year = 2025;
        $p->status = 1;
        $p->save();

        return $p;
    }

    public function test_owner_sets_rules()
    {
        $owner = $this->makeUser();
        $pocket = $this->pocket($owner);

        $this->actingAs($owner)->post("/pockets/{$pocket->id}/rules", ['rules' => 'Pay by the 5th.'])->assertRedirect();
        $this->assertSame('Pay by the 5th.', $pocket->fresh()->rules);
    }

    public function test_clone_pocket_copies_settings_and_members()
    {
        $owner = $this->makeUser();
        $pocket = $this->pocket($owner);
        $member = $this->makeUser();
        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $member->id;
        $slot->slot_number = 1;
        $slot->hand_count = 2;
        $slot->amount_paying = 20000;
        $slot->status = 1;
        $slot->comment = '';
        $slot->save();

        $this->actingAs($owner)->post("/pockets/{$pocket->id}/clone", [
            'title' => 'Ramadan (copy)', 'year' => (int) date('Y'), 'amount_per_hand' => 10000,
            'month_count' => 12, 'max_keens' => 0, 'members' => [$member->id],
        ])->assertRedirect();

        $copy = Pocket::where('title', 'Ramadan (copy)')->first();
        $this->assertNotNull($copy);
        $this->assertSame((int) date('Y'), (int) $copy->year);          // reset to current year
        $this->assertSame(10000, (int) $copy->amount_per_hand);
        $this->assertDatabaseHas('pocket_slots', ['pocket_id' => $copy->id, 'user_id' => $member->id, 'hand_count' => 2]);
    }

    public function test_clone_adashi_copies_members_and_makes_cycle()
    {
        $admin = $this->makeUser();
        $adashi = Adashi::create([
            'name' => 'Family', 'amount_per_cycle' => 50000, 'total_members' => 2,
            'start_date' => '2025-01-01', 'cycle_duration_days' => 30, 'current_cycle_number' => 3,
            'admin_id' => $admin->id, 'rotation_mode' => 'AUTO', 'status' => 'ACTIVE',
        ]);
        AdashiMember::create(['adashi_id' => $adashi->id, 'user_id' => $admin->id, 'position' => 1, 'has_received' => true, 'joined_at' => now(), 'is_active' => true]);
        $m2 = $this->makeUser();
        AdashiMember::create(['adashi_id' => $adashi->id, 'user_id' => $m2->id, 'position' => 2, 'has_received' => false, 'joined_at' => now(), 'is_active' => true]);

        $this->actingAs($admin)->post("/adashi/{$adashi->id}/clone", [
            'name' => 'Family (copy)', 'amount_per_cycle' => 50000, 'cycle_duration_days' => 30,
            'start_date' => now()->toDateString(), 'members' => [$admin->id, $m2->id],
        ])->assertRedirect();

        $copy = Adashi::where('name', 'Family (copy)')->first();
        $this->assertNotNull($copy);
        $this->assertSame(1, (int) $copy->current_cycle_number);             // fresh cycle
        $this->assertSame(2, (int) $copy->total_members);
        $this->assertDatabaseHas('adashi_members', ['adashi_id' => $copy->id, 'user_id' => $m2->id]);
        $this->assertDatabaseHas('adashi_records', ['adashi_id' => $copy->id, 'cycle_number' => 1]);
    }

    public function test_insights_and_admin_pages_render()
    {
        $user = $this->makeUser();
        $this->pocket($user);

        $this->actingAs($user)->get('/insights')->assertStatus(200)->assertSee('Total saved');
        $this->actingAs($user)->get('/admin/health')->assertStatus(200)->assertSee('Ramadan');
    }
}
