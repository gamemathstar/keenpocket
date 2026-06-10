<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use App\Services\Gamification\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GamificationTest extends TestCase
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

    private function memberWithInvoices(): User
    {
        $user = $this->makeUser();
        $pocket = new Pocket();
        $pocket->user_id = $this->makeUser()->id;
        $pocket->title = 'P'.uniqid();
        $pocket->pocket_type = 'Ramadan';
        $pocket->year = 2026;
        $pocket->start_month = 1;
        $pocket->month_count = 6;
        $pocket->max_keens = 5;
        $pocket->amount_per_hand = 1000;
        $pocket->status = 1;
        $pocket->save();

        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $user->id;
        $slot->hand_count = 1;
        $slot->status = 1;
        $slot->amount_paying = 1000;
        $slot->save();

        // Two paid invoices (streak = 2), then nothing unpaid after.
        foreach (['Paid', 'Paid'] as $i => $status) {
            $inv = new Invoice();
            $inv->pocket_slot_id = $slot->id;
            $inv->invoice_no = 'KP/G/'.uniqid();
            $inv->amount = 2500;
            $inv->payment_status = $status;
            $inv->paid_through = 'Manual';
            $inv->save();
        }

        return $user;
    }

    public function test_profile_reports_streak_total_and_first_pocket_badge()
    {
        $user = $this->memberWithInvoices();
        Sanctum::actingAs($user);

        $res = $this->getJson('/api/gamification/me')->assertStatus(200);

        $this->assertSame(2, $res->json('streak'));
        $this->assertSame(5000, $res->json('total_contributed'));

        $earned = collect($res->json('badges'))->filter(fn ($b) => $b['earned'])->pluck('slug');
        $this->assertTrue($earned->contains('first_pocket'));
    }

    public function test_badges_endpoint_returns_only_earned()
    {
        $user = $this->memberWithInvoices();

        $badges = app(GamificationService::class)->badgesFor($user->id);
        $slugs = collect($badges)->pluck('slug');

        $this->assertTrue($slugs->contains('first_pocket'));
        $this->assertFalse($slugs->contains('big_saver')); // 5000 < 100000 threshold
    }
}
