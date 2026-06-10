<?php

namespace Tests\Feature;

use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RatingTest extends TestCase
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

    /** @return array{0: Pocket, 1: User} [pocket, member] */
    private function pocketWithMember(User $owner): array
    {
        $pocket = new Pocket();
        $pocket->user_id = $owner->id;
        $pocket->title = 'P'.uniqid();
        $pocket->pocket_type = 'Ramadan';
        $pocket->year = 2026;
        $pocket->start_month = 1;
        $pocket->month_count = 6;
        $pocket->max_keens = 5;
        $pocket->amount_per_hand = 1000;
        $pocket->status = 1;
        $pocket->save();

        $member = $this->makeUser();
        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $member->id;
        $slot->hand_count = 1;
        $slot->status = 1;
        $slot->amount_paying = 1000;
        $slot->save();

        return [$pocket, $member];
    }

    public function test_member_can_rate_the_organizer()
    {
        $owner = $this->makeUser();
        [$pocket, $member] = $this->pocketWithMember($owner);

        Sanctum::actingAs($member);
        $this->postJson('/api/ratings', [
            'context_type' => 'pocket', 'context_id' => $pocket->id, 'stars' => 5, 'comment' => 'Great organizer',
        ])->assertStatus(200);

        $this->assertDatabaseHas('ratings', [
            'rater_id' => $member->id, 'ratee_id' => $owner->id, 'context_type' => 'pocket', 'stars' => 5,
        ]);

        // Surfaced in the organizer's reputation profile.
        $this->getJson("/api/users/{$owner->id}/reputation")
            ->assertStatus(200)
            ->assertJson(['reputation' => ['rating_average' => 5, 'rating_count' => 1]]);
    }

    public function test_non_member_cannot_rate()
    {
        $owner = $this->makeUser();
        [$pocket] = $this->pocketWithMember($owner);

        Sanctum::actingAs($this->makeUser()); // not a member
        $this->postJson('/api/ratings', [
            'context_type' => 'pocket', 'context_id' => $pocket->id, 'stars' => 4,
        ])->assertStatus(403);
    }

    public function test_organizer_cannot_rate_themselves()
    {
        $owner = $this->makeUser();
        [$pocket] = $this->pocketWithMember($owner);

        Sanctum::actingAs($owner);
        $this->postJson('/api/ratings', [
            'context_type' => 'pocket', 'context_id' => $pocket->id, 'stars' => 5,
        ])->assertStatus(422);
    }

    public function test_rating_is_one_per_member_and_updatable()
    {
        $owner = $this->makeUser();
        [$pocket, $member] = $this->pocketWithMember($owner);

        Sanctum::actingAs($member);
        $this->postJson('/api/ratings', ['context_type' => 'pocket', 'context_id' => $pocket->id, 'stars' => 5]);
        $this->postJson('/api/ratings', ['context_type' => 'pocket', 'context_id' => $pocket->id, 'stars' => 2]);

        $this->assertSame(1, \App\Models\Rating::where('rater_id', $member->id)->count());
        $this->assertSame(2, (int) \App\Models\Rating::where('rater_id', $member->id)->first()->stars);
    }

    public function test_invalid_star_values_are_rejected()
    {
        $owner = $this->makeUser();
        [$pocket, $member] = $this->pocketWithMember($owner);

        Sanctum::actingAs($member);
        $this->postJson('/api/ratings', ['context_type' => 'pocket', 'context_id' => $pocket->id, 'stars' => 6])
            ->assertStatus(422);
    }
}
