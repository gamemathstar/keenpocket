<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        $u = User::create([
            'name' => 'U'.uniqid(), 'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => 'u'.uniqid(), 'password' => bcrypt('secret123'),
        ]);
        foreach ($attrs as $k => $v) {
            $u->$k = $v;
        }
        $u->save();

        return $u;
    }

    public function test_request_then_accept_creates_a_mutual_friendship()
    {
        $a = $this->makeUser();
        $b = $this->makeUser(['email' => 'friend@example.com']);

        // A sends a request to B by email.
        $this->actingAs($a)->post('/friends', ['contact' => 'friend@example.com'])->assertRedirect();
        $this->assertDatabaseHas('friendships', ['user_id' => $a->id, 'friend_id' => $b->id, 'status' => 'pending']);
        // B is notified.
        $this->assertDatabaseHas('notifications', ['user_id' => $b->id, 'type' => 'Friend Request', 'status' => 'Not Read']);

        // B sees it as incoming and accepts.
        $req = Friendship::where('user_id', $a->id)->where('friend_id', $b->id)->first();
        $this->actingAs($b)->post("/friends/{$req->id}/accept")->assertRedirect();
        $this->assertDatabaseHas('friendships', ['id' => $req->id, 'status' => 'accepted']);
        // A is notified that B accepted.
        $this->assertDatabaseHas('notifications', ['user_id' => $a->id, 'type' => 'Friend Accepted']);

        // Both now see each other on their Friends page.
        $this->actingAs($a)->get('/friends')->assertOk()->assertSee($b->name);
        $this->actingAs($b)->get('/friends')->assertOk()->assertSee($a->name);
    }

    public function test_accepting_a_reverse_request_does_not_duplicate()
    {
        $a = $this->makeUser();
        $b = $this->makeUser();

        // A requests B.
        Friendship::create(['user_id' => $a->id, 'friend_id' => $b->id, 'status' => 'pending']);

        // B "adds" A back → should accept the existing request, not create a second row.
        $this->actingAs($b)->post('/friends', ['contact' => $a->phone_number])->assertRedirect();

        $this->assertSame(1, Friendship::where(function ($q) use ($a, $b) {
            $q->where(['user_id' => $a->id, 'friend_id' => $b->id])->orWhere(['user_id' => $b->id, 'friend_id' => $a->id]);
        })->count());
        $this->assertDatabaseHas('friendships', ['user_id' => $a->id, 'friend_id' => $b->id, 'status' => 'accepted']);
    }

    public function test_opening_friend_notification_marks_read_and_routes_to_friends()
    {
        $a = $this->makeUser();
        $b = $this->makeUser(['email' => 'recip@example.com']);

        $this->actingAs($a)->post('/friends', ['contact' => 'recip@example.com'])->assertRedirect();
        $note = \App\Models\Notification::where('user_id', $b->id)->where('type', 'Friend Request')->firstOrFail();

        // Opening it (an update of `status`) must not throw mass-assignment, and routes to Friends.
        $this->actingAs($b)->get("/notifications/{$note->id}/open")->assertRedirect(route('friends.index'));
        $this->assertDatabaseHas('notifications', ['id' => $note->id, 'status' => 'Read']);
    }

    public function test_cannot_friend_yourself()
    {
        $a = $this->makeUser();
        $this->actingAs($a)->post('/friends', ['contact' => $a->username])->assertSessionHasErrors('contact');
    }

    public function test_decline_and_remove()
    {
        $a = $this->makeUser();
        $b = $this->makeUser();

        $req = Friendship::create(['user_id' => $a->id, 'friend_id' => $b->id, 'status' => 'pending']);
        $this->actingAs($b)->post("/friends/{$req->id}/decline")->assertRedirect();
        $this->assertDatabaseMissing('friendships', ['id' => $req->id]);

        $f = Friendship::create(['user_id' => $a->id, 'friend_id' => $b->id, 'status' => 'accepted']);
        $this->actingAs($a)->post("/friends/{$b->id}/remove")->assertRedirect();
        $this->assertDatabaseMissing('friendships', ['id' => $f->id]);
    }
}
