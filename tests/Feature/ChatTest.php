<?php

namespace Tests\Feature;

use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
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

    private function pocketWith(User $owner): Pocket
    {
        $p = new Pocket();
        $p->user_id = $owner->id;
        $p->title = 'P'.uniqid();
        $p->amount_per_hand = 5000;
        $p->month_count = 12;
        $p->start_month = 1;
        $p->year = 2026;
        $p->status = 1;
        $p->save();

        return $p;
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

    public function test_member_can_post_and_others_see_it()
    {
        $owner = $this->makeUser();
        $pocket = $this->pocketWith($owner);
        $member = $this->makeUser();
        $this->addMember($pocket, $member);

        $this->actingAs($member)->post("/chat/pocket/{$pocket->id}", ['body' => 'Salam everyone'])->assertRedirect();
        $this->assertDatabaseHas('messages', [
            'context_type' => 'pocket', 'context_id' => $pocket->id, 'user_id' => $member->id, 'body' => 'Salam everyone',
        ]);

        // The owner sees it on the pocket page.
        $this->actingAs($owner)->get("/pockets/{$pocket->id}")->assertStatus(200)->assertSee('Salam everyone');
    }

    public function test_feed_returns_new_messages_after_id()
    {
        $owner = $this->makeUser();
        $pocket = $this->pocketWith($owner);
        $member = $this->makeUser();
        $this->addMember($pocket, $member);

        $this->actingAs($member)->postJson("/chat/pocket/{$pocket->id}", ['body' => 'first'])
            ->assertStatus(200)->assertJson(['mine' => true]);

        $feed = $this->actingAs($owner)->getJson("/chat/pocket/{$pocket->id}/messages");
        $feed->assertStatus(200)->assertJsonFragment(['body' => 'first']);
        $lastId = $feed->json('0.id');

        // Nothing new after the last id.
        $this->actingAs($owner)->getJson("/chat/pocket/{$pocket->id}/messages?after={$lastId}")
            ->assertStatus(200)->assertExactJson([]);
    }

    public function test_non_member_cannot_post()
    {
        $owner = $this->makeUser();
        $pocket = $this->pocketWith($owner);
        $stranger = $this->makeUser();

        $this->actingAs($stranger)->post("/chat/pocket/{$pocket->id}", ['body' => 'let me in'])->assertStatus(403);
        $this->assertDatabaseMissing('messages', ['context_id' => $pocket->id, 'user_id' => $stranger->id]);
    }
}
