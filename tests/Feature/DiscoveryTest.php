<?php

namespace Tests\Feature;

use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use App\Services\Reputation\ReputationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiscoveryTest extends TestCase
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

    private function makePocket(int $ownerId, array $attrs = []): Pocket
    {
        $p = new Pocket();
        $p->user_id = $ownerId;
        $p->title = $attrs['title'] ?? 'Pocket '.uniqid();
        $p->pocket_type = 'Ramadan';
        $p->year = 2026;
        $p->start_month = 1;
        $p->month_count = 6;
        $p->max_keens = $attrs['max_keens'] ?? 5;
        $p->amount_per_hand = 1000;
        $p->status = $attrs['status'] ?? 1;
        $p->save();

        return $p;
    }

    private function fillSlots(Pocket $pocket, int $hands): void
    {
        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $this->makeUser()->id;
        $slot->hand_count = $hands;
        $slot->status = 1;
        $slot->amount_paying = $hands * $pocket->amount_per_hand;
        $slot->save();
    }

    public function test_directory_lists_open_pockets_with_availability()
    {
        $owner = $this->makeUser();
        $open = $this->makePocket($owner->id, ['max_keens' => 5, 'title' => 'Open Pocket']);
        $this->fillSlots($open, 2);

        Sanctum::actingAs($this->makeUser());
        $res = $this->getJson('/api/directory/pockets')->assertStatus(200);

        $row = collect($res->json('data'))->firstWhere('id', $open->id);
        $this->assertNotNull($row);
        $this->assertSame(2, $row['slots_used']);
        $this->assertSame(3, $row['slots_available']);
        $this->assertArrayNotHasKey('phone_number', $row); // organizer phone is masked, not raw
    }

    public function test_directory_excludes_invitation_only_and_full_pockets()
    {
        $owner = $this->makeUser();
        $invite = $this->makePocket($owner->id, ['status' => 0]);        // invitation-only
        $full = $this->makePocket($owner->id, ['max_keens' => 2]);
        $this->fillSlots($full, 2);                                       // exactly full

        Sanctum::actingAs($this->makeUser());
        $ids = collect($this->getJson('/api/directory/pockets')->json('data'))->pluck('id');

        $this->assertFalse($ids->contains($invite->id));
        $this->assertFalse($ids->contains($full->id));
    }

    public function test_reputation_me_is_new_for_a_fresh_user()
    {
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/reputation/me')
            ->assertStatus(200)
            ->assertJson(['reputation' => ['band' => 'New']]);
    }

    public function test_reputation_reflects_activity()
    {
        $user = $this->makeUser();
        $pocket = $this->makePocket($user->id);

        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $user->id;
        $slot->hand_count = 1;
        $slot->status = 1;
        $slot->amount_paying = 1000;
        $slot->save();

        $rep = app(ReputationService::class)->forUser($user->id);
        $this->assertSame(1, $rep['pockets_joined']);
        $this->assertNotSame('New', $rep['band']); // has activity now
    }
}
