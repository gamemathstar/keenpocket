<?php

namespace Tests\Feature;

use App\Models\Adashi;
use App\Models\Pocket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyAndTermsTest extends TestCase
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

    public function test_creating_a_pocket_requires_accepting_terms()
    {
        $user = $this->makeUser();
        $this->actingAs($user)->post('/pockets', [
            'title' => 'No Terms', 'year' => 2026, 'start_month' => 1, 'month_count' => 12,
            'max_keens' => 0, 'amount_per_hand' => 5000, 'hand_count' => 1,
            // accept_terms intentionally omitted
        ])->assertSessionHasErrors('accept_terms');

        $this->assertDatabaseMissing('pockets', ['title' => 'No Terms']);
    }

    public function test_settings_cannot_change_email_or_phone()
    {
        $user = $this->makeUser();
        $origEmail = $user->email;
        $origPhone = $user->phone_number;

        $this->actingAs($user)->post('/settings/profile', [
            'name' => 'New Name',
            'email' => 'hacker@example.com',
            'phone_number' => '08000000000',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('New Name', $user->name);     // name changes
        $this->assertSame($origEmail, $user->email);     // email locked
        $this->assertSame($origPhone, $user->phone_number); // phone locked
    }

    public function test_pocket_members_visibility_toggle()
    {
        $admin = $this->makeUser();
        $pocket = new Pocket();
        $pocket->user_id = $admin->id;
        $pocket->title = 'P';
        $pocket->amount_per_hand = 5000;
        $pocket->month_count = 12;
        $pocket->start_month = 1;
        $pocket->year = 2026;
        $pocket->status = 1;
        $pocket->save();

        $this->assertFalse((bool) $pocket->members_visible); // private by default
        $this->actingAs($admin)->post("/pockets/{$pocket->id}/members-visibility")->assertRedirect();
        $this->assertTrue((bool) $pocket->fresh()->members_visible);
    }

    public function test_adashi_payout_visibility_toggle()
    {
        $admin = $this->makeUser();
        $adashi = Adashi::create([
            'name' => 'A', 'amount_per_cycle' => 50000, 'total_members' => 1,
            'start_date' => now()->toDateString(), 'cycle_duration_days' => 30,
            'current_cycle_number' => 1, 'admin_id' => $admin->id, 'rotation_mode' => 'AUTO', 'status' => 'ACTIVE',
        ]);

        $this->assertFalse((bool) $adashi->payout_visible); // private by default
        $this->actingAs($admin)->post("/adashi/{$adashi->id}/payout-visibility")->assertRedirect();
        $this->assertTrue((bool) $adashi->fresh()->payout_visible);
    }
}
