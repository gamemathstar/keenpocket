<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\Pocket;
use App\Models\PocketItem;
use App\Models\PocketSlot;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\RegistersSqlFunctions;
use Tests\TestCase;

/**
 * Exercises the core read API endpoints (which use MySQL-only SQL) by shimming
 * FORMAT/DATE_FORMAT/IF onto sqlite. This is the safety net for refactoring the
 * monolithic APIController.
 */
class ReadEndpointsTest extends TestCase
{
    use RefreshDatabase;
    use RegistersSqlFunctions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registerSqlFunctions();
    }

    public function seedScenario(): array
    {
        $user = User::create([
            'name' => 'Reader', 'email' => uniqid().'@example.com',
            'phone_number' => '080'.random_int(10000000, 99999999),
            'username' => uniqid(), 'password' => bcrypt('secret123'),
        ]);

        if (!Item::find(1)) {
            $item = new Item();
            $item->id = 1;
            $item->name = 'Contribution';
            $item->category = 'Paid';
            $item->save();
        }

        $pocket = new Pocket();
        $pocket->user_id = $user->id;
        $pocket->title = 'Read Pocket';
        $pocket->pocket_type = 'Ramadan';
        $pocket->description = '';
        $pocket->year = 2026;
        $pocket->start_month = 1;
        $pocket->month_count = 12;
        $pocket->max_keens = 0;
        $pocket->amount_per_hand = 5000;
        $pocket->per_hand_allowed = '5000';
        $pocket->status = 1;
        $pocket->save();

        $pocketItem = new PocketItem();
        $pocketItem->pocket_id = $pocket->id;
        $pocketItem->item_id = 1;
        $pocketItem->save();

        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $user->id;
        $slot->slot_number = 1;
        $slot->hand_count = 1;
        $slot->amount_paying = 5000;
        $slot->status = 1;
        $slot->comment = '';
        $slot->save();

        $invoice = new Invoice();
        $invoice->pocket_slot_id = $slot->id;
        $invoice->invoice_no = 'KP/001/READ';
        $invoice->amount = 5000;
        $invoice->reference_no = 'KP/001/READ';
        $invoice->payment_status = 'Paid';
        $invoice->paid_through = 'Manual';
        $invoice->payment_date = now();
        $invoice->save();
        InvoiceItem::create(['invoice_id' => $invoice->id, 'item_id' => 1, 'amount' => 5000, 'type' => 'Paid', 'month' => 1]);

        return [$user, $pocket, $invoice];
    }

    public function test_dashboard()
    {
        [$user] = $this->seedScenario();
        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard')->assertStatus(200)->assertJsonStructure(['user', 'pockets']);
    }

    public function test_my_pockets()
    {
        [$user] = $this->seedScenario();
        Sanctum::actingAs($user);

        $this->getJson('/api/my-pockets')->assertStatus(200);
    }

    public function test_invoice_detail()
    {
        [$user, , $invoice] = $this->seedScenario();
        Sanctum::actingAs($user);

        $this->getJson('/api/invoice?id='.$invoice->id)->assertStatus(200)->assertJsonStructure(['invoice_no', 'amount', 'items']);
    }

    public function test_pocket_invoices()
    {
        [$user, $pocket] = $this->seedScenario();
        Sanctum::actingAs($user);

        $this->getJson('/api/pocket/invoices?id='.$pocket->id)->assertStatus(200);
    }

    public function test_notifications_and_posts()
    {
        [$user] = $this->seedScenario();
        $post = new Post();
        $post->title = 'Hi';
        $post->body = 'Welcome';
        $post->featured_image = '';
        $post->save();
        Sanctum::actingAs($user);

        $this->getJson('/api/notifications')->assertStatus(200);
        $this->getJson('/api/posts')->assertStatus(200);
    }
}
