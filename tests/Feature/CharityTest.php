<?php

namespace Tests\Feature;

use App\Models\CharityProject;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use App\Services\Charity\CharityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharityTest extends TestCase
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

    private function pocketWithAdmin(User $admin): Pocket
    {
        $pocket = new Pocket();
        $pocket->user_id = $admin->id;
        $pocket->title = 'Ramadan '.uniqid();
        $pocket->amount_per_hand = 5000;
        $pocket->month_count = 12;
        $pocket->start_month = 1;
        $pocket->year = 2026;
        $pocket->max_keens = 0;
        $pocket->status = 1;
        $pocket->save();
        $this->addMember($pocket, $admin); // the admin is also a member/slot

        return $pocket;
    }

    private function addMember(Pocket $pocket, User $user): void
    {
        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $user->id;
        $slot->slot_number = PocketSlot::where('pocket_id', $pocket->id)->count() + 1;
        $slot->hand_count = 1;
        $slot->amount_paying = 5000;
        $slot->status = 1;
        $slot->comment = '';
        $slot->save();
    }

    public function test_admin_sets_up_amount_drive_and_member_donates()
    {
        $admin = $this->makeUser();
        $pocket = $this->pocketWithAdmin($admin);
        $member = $this->makeUser();
        $this->addMember($pocket, $member);

        $this->actingAs($admin)->post("/pockets/{$pocket->id}/charity", [
            'enabled' => 1, 'title' => 'Food for orphans', 'goal_type' => 'amount', 'target_amount' => 100000,
        ])->assertRedirect();

        $this->assertTrue((bool) $pocket->fresh()->charity_enabled);
        $this->assertDatabaseHas('charity_projects', ['pocket_id' => $pocket->id, 'goal_type' => 'amount', 'status' => 'ACTIVE']);

        $this->actingAs($member)->post("/pockets/{$pocket->id}/charity/donate", ['amount' => 5000])->assertRedirect();

        $project = CharityProject::where('pocket_id', $pocket->id)->first();
        $svc = app(CharityService::class);
        $this->assertSame(5000, $svc->raised($project));
        $this->assertSame(5000, $svc->myTotal($project, $member->id));
        $this->assertSame(0, $svc->myTotal($project, $admin->id));
        $this->assertDatabaseHas('invoice_item', ['type' => 'Donation', 'charity_project_id' => $project->id, 'amount' => 5000]);
    }

    public function test_item_goal_progress()
    {
        $admin = $this->makeUser();
        $pocket = $this->pocketWithAdmin($admin);

        $this->actingAs($admin)->post("/pockets/{$pocket->id}/charity", [
            'enabled' => 1, 'title' => 'Ramadan staples', 'goal_type' => 'items',
            'items' => [['name' => 'Bag of rice', 'unit' => 'bag', 'target_quantity' => 50, 'unit_price' => 30000]],
        ])->assertRedirect();

        $project = CharityProject::where('pocket_id', $pocket->id)->first();
        $goal = $project->goalItems()->first();
        $this->assertNotNull($goal);

        $this->actingAs($admin)->post("/pockets/{$pocket->id}/charity/donate", [
            'items' => [['goal_item_id' => $goal->id, 'quantity' => 2]],
        ])->assertRedirect();

        $svc = app(CharityService::class);
        $progress = $svc->itemProgress($project);
        $this->assertSame(2, $progress[0]['collected_quantity']);
        $this->assertSame(60000, $progress[0]['raised_amount']); // 2 * 30000
    }

    public function test_donations_are_private_to_members_visible_to_admin()
    {
        $admin = $this->makeUser();
        $pocket = $this->pocketWithAdmin($admin);
        $a = $this->makeUser();
        $b = $this->makeUser();
        $this->addMember($pocket, $a);
        $this->addMember($pocket, $b);

        $this->actingAs($admin)->post("/pockets/{$pocket->id}/charity", [
            'enabled' => 1, 'title' => 'Drive', 'goal_type' => 'amount', 'target_amount' => 0,
        ]);
        $this->actingAs($a)->post("/pockets/{$pocket->id}/charity/donate", ['amount' => 3000]);
        $this->actingAs($b)->post("/pockets/{$pocket->id}/charity/donate", ['amount' => 7000]);

        $project = CharityProject::where('pocket_id', $pocket->id)->first();
        $pocket->refresh();
        $svc = app(CharityService::class);

        // Member view: own + group total only, NO per-member breakdown.
        $memberView = $svc->summary($pocket, $project, $a, false);
        $this->assertSame(3000, $memberView['my_total']);
        $this->assertSame(10000, $memberView['group_total']);
        $this->assertArrayNotHasKey('breakdown', $memberView);

        // Admin view: breakdown present.
        $adminView = $svc->summary($pocket, $project, $admin, true);
        $this->assertArrayHasKey('breakdown', $adminView);
        $this->assertCount(2, $adminView['breakdown']);
    }

    public function test_member_can_rate_the_adashi_admin_on_web()
    {
        $admin = $this->makeUser();
        $adashi = \App\Models\Adashi::create([
            'name' => 'A'.uniqid(), 'amount_per_cycle' => 50000, 'total_members' => 2,
            'start_date' => now()->toDateString(), 'cycle_duration_days' => 30,
            'current_cycle_number' => 1, 'admin_id' => $admin->id, 'rotation_mode' => 'AUTO', 'status' => 'ACTIVE',
        ]);
        $member = $this->makeUser();
        \App\Models\AdashiMember::create([
            'adashi_id' => $adashi->id, 'user_id' => $member->id, 'position' => 2,
            'has_received' => false, 'joined_at' => now(), 'is_active' => true,
        ]);

        $this->actingAs($member)->post("/adashi/{$adashi->id}/rate-admin", ['stars' => 4])->assertRedirect();

        $this->assertDatabaseHas('ratings', [
            'rater_id' => $member->id, 'ratee_id' => $admin->id,
            'context_type' => 'adashi', 'context_id' => $adashi->id, 'stars' => 4,
        ]);
    }

    public function test_member_can_rate_the_admin_on_web()
    {
        $admin = $this->makeUser();
        $pocket = $this->pocketWithAdmin($admin);
        $member = $this->makeUser();
        $this->addMember($pocket, $member);

        $this->actingAs($member)->post("/pockets/{$pocket->id}/rate-admin", ['stars' => 5, 'comment' => 'Great admin'])
            ->assertRedirect();

        $this->assertDatabaseHas('ratings', [
            'rater_id' => $member->id, 'ratee_id' => $admin->id,
            'context_type' => 'pocket', 'context_id' => $pocket->id, 'stars' => 5,
        ]);
    }
}
