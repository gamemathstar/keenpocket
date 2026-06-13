<?php

namespace Tests\Feature;

use App\Models\CharityProject;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use App\Services\Contribution\ContributionPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributionTest extends TestCase
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

    /** Pocket starting in June (start_month 6), ₦10k/month, with a member slot. */
    private function scenario(array $pocketAttrs = []): array
    {
        $admin = $this->makeUser();
        $pocket = new Pocket();
        $pocket->user_id = $admin->id;
        $pocket->title = 'Ramadan '.uniqid();
        $pocket->amount_per_hand = 10000;
        $pocket->month_count = 12;
        $pocket->start_month = 6; // June
        $pocket->year = 2026;
        $pocket->max_keens = 0;
        $pocket->status = 1;
        foreach ($pocketAttrs as $k => $v) {
            $pocket->$k = $v;
        }
        $pocket->save();

        $member = $this->makeUser();
        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $member->id;
        $slot->slot_number = 1;
        $slot->hand_count = 1;
        $slot->amount_paying = 10000;
        $slot->status = 1;
        $slot->comment = '';
        $slot->save();

        return [$admin, $pocket, $member, $slot];
    }

    private function payMonth(PocketSlot $slot, int $month, int $amount): void
    {
        $inv = new Invoice();
        $inv->pocket_slot_id = $slot->id;
        $inv->invoice_no = 'KP/PAID/'.$slot->id.'/'.$month;
        $inv->amount = $amount;
        $inv->reference_no = $inv->invoice_no;
        $inv->payment_status = 'Paid';
        $inv->paid_through = 'Manual';
        $inv->payment_date = now();
        $inv->save();
        InvoiceItem::create(['invoice_id' => $inv->id, 'item_id' => 1, 'amount' => $amount, 'type' => 'Paid', 'month' => $month]);
    }

    public function test_allocation_spreads_across_next_unpaid_months()
    {
        [$admin, $pocket, $member, $slot] = $this->scenario();
        $this->payMonth($slot, 1, 10000); // June fully paid

        $plan = app(ContributionPlanner::class)->plan($pocket, $slot, 28000);

        $this->assertCount(3, $plan);
        $this->assertSame([2, 3, 4], array_column($plan, 'month'));
        $this->assertSame([10000, 10000, 8000], array_column($plan, 'amount'));
        $this->assertSame(['July', 'August', 'September'], array_column($plan, 'label'));
    }

    public function test_already_paid_month_is_skipped()
    {
        [$admin, $pocket, $member, $slot] = $this->scenario();
        $this->payMonth($slot, 1, 10000);

        // First unpaid month is July (2), not June again.
        $plan = app(ContributionPlanner::class)->plan($pocket, $slot, 5000);
        $this->assertSame(2, $plan[0]['month']);
        $this->assertSame('July', $plan[0]['label']);
        $this->assertSame(5000, $plan[0]['amount']); // partial July
    }

    public function test_web_flow_creates_one_invoice_with_donation_and_month_items()
    {
        [$admin, $pocket, $member, $slot] = $this->scenario(['charity_enabled' => true]);
        CharityProject::create(['pocket_id' => $pocket->id, 'title' => 'Orphans', 'goal_type' => 'amount', 'status' => 'ACTIVE']);
        $this->payMonth($slot, 1, 10000); // June paid

        // Preview: ₦33k with ₦5k donation.
        $this->actingAs($member)->post("/pockets/{$pocket->id}/invoices/preview", ['amount' => 33000, 'donation' => 5000])
            ->assertStatus(200)->assertSee('July')->assertSee('September');

        // Confirm the allocation.
        $this->actingAs($member)->post("/pockets/{$pocket->id}/invoices", [
            'balance' => 33000,
            'donation' => 5000,
            'months' => [2, 3, 4],
            'amounts' => [10000, 10000, 8000],
        ])->assertRedirect();

        // One new invoice (besides the seeded June one), with 4 items.
        $invoice = Invoice::where('pocket_slot_id', $slot->id)->where('payment_status', 'Not Paid')->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertSame(33000, (int) $invoice->amount);
        $this->assertSame(3, InvoiceItem::where(['invoice_id' => $invoice->id, 'type' => 'Paid'])->count());
        $this->assertDatabaseHas('invoice_item', ['invoice_id' => $invoice->id, 'type' => 'Donation', 'amount' => 5000]);
        $this->assertDatabaseHas('invoice_item', ['invoice_id' => $invoice->id, 'type' => 'Paid', 'month' => 4, 'amount' => 8000]);
    }

    public function test_allocation_exceeding_balance_is_rejected()
    {
        [$admin, $pocket, $member, $slot] = $this->scenario();

        // Balance 10k but allocation sums to 25k → rejected, no invoice.
        $this->actingAs($member)->post("/pockets/{$pocket->id}/invoices", [
            'balance' => 10000,
            'months' => [1, 2],
            'amounts' => [15000, 10000],
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('invoices', ['pocket_slot_id' => $slot->id]);
    }
}
