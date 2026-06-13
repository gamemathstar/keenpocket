<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisputeTest extends TestCase
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

    private function pocketWithMember(User $owner): array
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

        $member = $this->makeUser();
        $slot = new PocketSlot();
        $slot->pocket_id = $p->id;
        $slot->user_id = $member->id;
        $slot->slot_number = 1;
        $slot->hand_count = 1;
        $slot->amount_paying = 5000;
        $slot->status = 1;
        $slot->comment = '';
        $slot->save();

        return [$p, $member];
    }

    public function test_member_raises_and_admin_resolves()
    {
        $owner = $this->makeUser();
        [$pocket, $member] = $this->pocketWithMember($owner);

        $this->actingAs($member)->post("/disputes/pocket/{$pocket->id}", [
            'subject' => 'Missed payout', 'body' => 'I did not receive my turn.',
        ])->assertRedirect();

        $dispute = Dispute::where(['context_type' => 'pocket', 'context_id' => $pocket->id])->first();
        $this->assertNotNull($dispute);
        $this->assertSame('OPEN', $dispute->status);

        $this->actingAs($owner)->post("/disputes/{$dispute->id}/resolve", ['resolution' => 'Sorted offline.'])->assertRedirect();
        $this->assertSame('RESOLVED', $dispute->fresh()->status);
        $this->assertSame($owner->id, (int) $dispute->fresh()->resolved_by);
    }

    public function test_non_member_cannot_raise()
    {
        $owner = $this->makeUser();
        [$pocket] = $this->pocketWithMember($owner);
        $stranger = $this->makeUser();

        $this->actingAs($stranger)->post("/disputes/pocket/{$pocket->id}", ['subject' => 'x', 'body' => 'y'])
            ->assertStatus(403);
    }

    public function test_non_admin_cannot_resolve()
    {
        $owner = $this->makeUser();
        [$pocket, $member] = $this->pocketWithMember($owner);
        $dispute = Dispute::create([
            'context_type' => 'pocket', 'context_id' => $pocket->id, 'raised_by' => $member->id,
            'subject' => 's', 'body' => 'b', 'status' => 'OPEN',
        ]);

        $this->actingAs($member)->post("/disputes/{$dispute->id}/resolve", [])->assertStatus(403);
        $this->assertSame('OPEN', $dispute->fresh()->status);
    }
}
