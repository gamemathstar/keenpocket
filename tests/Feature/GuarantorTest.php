<?php

namespace Tests\Feature;

use App\Models\Pocket;
use App\Models\PocketGuarantor;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuarantorTest extends TestCase
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

    private function pocket(User $admin, array $attrs = []): Pocket
    {
        $p = new Pocket();
        $p->user_id = $admin->id;
        $p->title = 'P'.uniqid();
        $p->amount_per_hand = 5000;
        $p->month_count = 12;
        $p->start_month = 1;
        $p->year = 2026;
        $p->max_keens = 0;
        $p->status = 1;
        foreach ($attrs as $k => $v) {
            $p->$k = $v;
        }
        $p->save();

        return $p;
    }

    public function test_open_pocket_join_is_a_pending_request_until_admin_accepts()
    {
        $admin = $this->makeUser();
        $pocket = $this->pocket($admin);
        $requester = $this->makeUser();

        $this->actingAs($requester)->post("/pockets/{$pocket->id}/join", ['hand_count' => 1, 'accept_terms' => 1])->assertRedirect();

        $slot = PocketSlot::where(['pocket_id' => $pocket->id, 'user_id' => $requester->id])->first();
        $this->assertNotNull($slot);
        $this->assertSame(0, (int) $slot->status); // pending, not active yet

        $this->actingAs($admin)->post("/pockets/{$pocket->id}/members/accept", ['slot_id' => $slot->id])->assertRedirect();
        $this->assertSame(1, (int) $slot->fresh()->status);
    }

    public function test_closed_pocket_blocks_join_requests()
    {
        $admin = $this->makeUser();
        $pocket = $this->pocket($admin, ['status' => 0]);
        $requester = $this->makeUser();

        $this->actingAs($requester)->post("/pockets/{$pocket->id}/join", ['hand_count' => 1])
            ->assertSessionHasErrors('hand_count');
        $this->assertDatabaseMissing('pocket_slots', ['pocket_id' => $pocket->id, 'user_id' => $requester->id]);
    }

    public function test_guarantor_required_flow()
    {
        $admin = $this->makeUser();
        $pocket = $this->pocket($admin, ['guarantor_required' => true]);
        $requester = $this->makeUser();
        $guarantor = $this->makeUser('guar@example.com');

        // Request names a guarantor.
        $this->actingAs($requester)->post("/pockets/{$pocket->id}/join", [
            'hand_count' => 1, 'guarantor_contact' => 'guar@example.com', 'accept_terms' => 1,
        ])->assertRedirect();

        $slot = PocketSlot::where(['pocket_id' => $pocket->id, 'user_id' => $requester->id])->first();
        $g = PocketGuarantor::where(['pocket_id' => $pocket->id, 'slot_id' => $slot->id])->first();
        $this->assertNotNull($g);
        $this->assertSame('PENDING', $g->status);

        // Admin cannot accept before the guarantor recommends.
        $this->actingAs($admin)->post("/pockets/{$pocket->id}/members/accept", ['slot_id' => $slot->id])
            ->assertSessionHasErrors('accept');
        $this->assertSame(0, (int) $slot->fresh()->status);

        // Guarantor recommends.
        $this->actingAs($guarantor)->post("/vouches/{$g->id}/recommend")->assertRedirect();
        $this->assertSame('RECOMMENDED', $g->fresh()->status);

        // Now the admin can accept.
        $this->actingAs($admin)->post("/pockets/{$pocket->id}/members/accept", ['slot_id' => $slot->id])->assertRedirect();
        $this->assertSame(1, (int) $slot->fresh()->status);
    }

    public function test_guarantor_decline_withdraws_the_request()
    {
        $admin = $this->makeUser();
        $pocket = $this->pocket($admin, ['guarantor_required' => true]);
        $requester = $this->makeUser();
        $guarantor = $this->makeUser('guar2@example.com');

        $this->actingAs($requester)->post("/pockets/{$pocket->id}/join", [
            'hand_count' => 1, 'guarantor_contact' => 'guar2@example.com', 'accept_terms' => 1,
        ]);
        $g = PocketGuarantor::where('pocket_id', $pocket->id)->first();

        $this->actingAs($guarantor)->post("/vouches/{$g->id}/decline")->assertRedirect();

        $this->assertSame('DECLINED', $g->fresh()->status);
        $this->assertDatabaseMissing('pocket_slots', ['pocket_id' => $pocket->id, 'user_id' => $requester->id]);
    }
}
