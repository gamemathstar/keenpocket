<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanItem;
use App\Models\User;
use App\Services\Plan\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(?string $email = null): User
    {
        return User::create([
            'name' => 'U'.uniqid(), 'email' => $email ?? uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(), 'password' => bcrypt('secret123'),
        ]);
    }

    public function test_create_plan_add_item_and_summary()
    {
        $user = $this->makeUser();
        $this->actingAs($user)->post('/plans', ['title' => 'June groceries', 'budget' => 100000])->assertRedirect();

        $plan = Plan::first();
        $this->assertSame($user->id, $plan->owner_id);

        $this->actingAs($user)->post("/plans/{$plan->id}/items", [
            'name' => 'Rice', 'quantity' => 2, 'unit' => 'bag', 'unit_price' => 30000,
        ])->assertRedirect();

        $summary = app(PlanService::class)->summary($plan);
        $this->assertSame(1, $summary['total']);
        $this->assertSame(60000, $summary['estimated']); // 2 * 30000
        $this->assertSame(100000, $summary['budget']);
    }

    public function test_purchase_and_defer_update_summary()
    {
        $user = $this->makeUser();
        $plan = Plan::create(['owner_id' => $user->id, 'title' => 'P', 'budget' => 0, 'status' => 'ACTIVE']);
        $bought = PlanItem::create(['plan_id' => $plan->id, 'name' => 'Oil', 'quantity' => 1, 'unit_price' => 5000, 'status' => 'pending']);
        $defer = PlanItem::create(['plan_id' => $plan->id, 'name' => 'Meat', 'quantity' => 1, 'unit_price' => 8000, 'status' => 'pending']);

        $this->actingAs($user)->post("/plan-items/{$bought->id}/update", ['action' => 'purchased'])->assertRedirect();
        $this->actingAs($user)->post("/plan-items/{$defer->id}/update", ['action' => 'deferred'])->assertRedirect();

        $summary = app(PlanService::class)->summary($plan);
        $this->assertSame(1, $summary['purchased']);
        $this->assertSame(1, $summary['deferred']);
        $this->assertSame(5000, $summary['spent']);
        $this->assertSame(5000, $summary['estimated']); // deferred excluded
    }

    public function test_carry_over_deferred_items_into_new_plan()
    {
        $user = $this->makeUser();
        $old = Plan::create(['owner_id' => $user->id, 'title' => 'May', 'status' => 'ACTIVE']);
        PlanItem::create(['plan_id' => $old->id, 'name' => 'Beans', 'quantity' => 3, 'status' => 'deferred']);

        $this->actingAs($user)->post('/plans', ['title' => 'June', 'carry_from' => $old->id])->assertRedirect();

        $new = Plan::where('title', 'June')->first();
        $carried = $new->items()->first();
        $this->assertNotNull($carried);
        $this->assertSame('Beans', $carried->name);
        $this->assertSame('pending', $carried->status);
        $this->assertTrue((bool) $carried->priority);
    }

    public function test_sharing_grants_access_and_others_are_blocked()
    {
        $owner = $this->makeUser();
        $spouse = $this->makeUser('spouse@example.com');
        $stranger = $this->makeUser();

        $plan = Plan::create(['owner_id' => $owner->id, 'title' => 'Shared', 'status' => 'ACTIVE']);

        $this->actingAs($owner)->post("/plans/{$plan->id}/share", ['contact' => 'spouse@example.com'])->assertRedirect();
        $this->assertDatabaseHas('plan_collaborators', ['plan_id' => $plan->id, 'user_id' => $spouse->id]);

        // Collaborator can view and add items.
        $this->actingAs($spouse)->get("/plans/{$plan->id}")->assertStatus(200);
        $this->actingAs($spouse)->post("/plans/{$plan->id}/items", ['name' => 'Salt'])->assertRedirect();
        $this->assertDatabaseHas('plan_items', ['plan_id' => $plan->id, 'name' => 'Salt']);

        // A stranger cannot.
        $this->actingAs($stranger)->get("/plans/{$plan->id}")->assertStatus(403);
    }
}
