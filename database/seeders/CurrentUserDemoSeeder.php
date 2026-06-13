<?php

namespace Database\Seeders;

use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\AdashiRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Pocket;
use App\Models\PocketItem;
use App\Models\PocketSlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a ready-to-explore Pocket and Adashi (with members + contributions) owned
 * by the current user, so the dashboard isn't empty in dev.
 *
 *   php artisan db:seed --class=Database\\Seeders\\CurrentUserDemoSeeder
 *
 * Idempotent: re-running won't duplicate (it keys off the owner + titles).
 */
class CurrentUserDemoSeeder extends Seeder
{
    private const OWNER_EMAIL = 'aiabubakar3@gmail.com';

    /** Monotonic counter to keep generated invoice numbers unique. */
    private int $seq = 0;

    public function run(): void
    {
        $owner = User::where('email', self::OWNER_EMAIL)->first();
        if (!$owner) {
            $owner = $this->makeUser('Abubakar', self::OWNER_EMAIL, '08030000001', 'abubakar');
        }

        // A few fellow members (created once, reused).
        $members = collect([
            $this->makeUser('Aisha Bello', 'aisha.demo@example.com', '08030000002', 'aisha_demo'),
            $this->makeUser('Musa Sani', 'musa.demo@example.com', '08030000003', 'musa_demo'),
            $this->makeUser('Zainab Idris', 'zainab.demo@example.com', '08030000004', 'zainab_demo'),
        ]);

        DB::transaction(function () use ($owner, $members) {
            $this->seedPocket($owner, $members);
            $this->seedAdashi($owner, $members);
        });
    }

    private function seedPocket(User $owner, $members): void
    {
        if (Pocket::where(['user_id' => $owner->id, 'title' => '2026 Ramadan Pocket'])->exists()) {
            return;
        }

        $pocket = new Pocket();
        $pocket->user_id = $owner->id;
        $pocket->title = '2026 Ramadan Pocket';
        $pocket->description = 'Monthly Ramadan savings for the family circle.';
        $pocket->year = 2026;
        $pocket->start_month = 1;
        $pocket->month_count = 12;
        $pocket->max_keens = 0;            // unlimited
        $pocket->amount_per_hand = 10000;
        $pocket->per_hand_allowed = '10000';
        $pocket->status = 1;               // open (visible in search, request-to-join)
        $pocket->open_purchasing_item = 1; // shopping suggestions open
        $pocket->bank = 'GTBank';
        $pocket->nuban = '0123456789';
        $pocket->save();

        $item = new PocketItem();
        $item->pocket_id = $pocket->id;
        $item->item_id = 1;
        $item->save();

        // Owner + members get active slots; owner & first two have some paid months.
        $people = collect([$owner])->merge($members);
        foreach ($people as $i => $person) {
            $slot = $this->pocketSlot($pocket, $person, $i + 1);
            $paidMonths = [3, 2, 1, 0][$i] ?? 0; // owner pays 3 months, etc.
            for ($m = 1; $m <= $paidMonths; $m++) {
                $this->paidPocketInvoice($pocket, $slot, $m);
            }
        }
    }

    private function seedAdashi(User $owner, $members): void
    {
        if (Adashi::where(['admin_id' => $owner->id, 'name' => 'Family Monthly Adashi'])->exists()) {
            return;
        }

        $adashi = new Adashi();
        $adashi->name = 'Family Monthly Adashi';
        $adashi->amount_per_cycle = 50000;
        $adashi->total_members = 4;
        $adashi->start_date = Carbon::now()->startOfMonth()->toDateString();
        $adashi->cycle_duration_days = 30;
        $adashi->current_cycle_number = 1;
        $adashi->admin_id = $owner->id;
        $adashi->rotation_mode = 'AUTO';
        $adashi->status = 'ACTIVE';
        if (\Illuminate\Support\Facades\Schema::hasColumn('adashis', 'is_public')) {
            $adashi->is_public = true;
        }
        $adashi->save();

        $people = collect([$owner])->merge($members);
        $firstMember = null;
        foreach ($people as $i => $person) {
            $m = new AdashiMember();
            $m->adashi_id = $adashi->id;
            $m->user_id = $person->id;
            $m->position = $i + 1;
            $m->has_received = false;
            $m->joined_at = now();
            $m->is_active = true;
            $m->save();
            $firstMember = $firstMember ?? $m;
        }

        $record = new AdashiRecord();
        $record->adashi_id = $adashi->id;
        $record->cycle_number = 1;
        $record->due_at = Carbon::parse($adashi->start_date)->addDays($adashi->cycle_duration_days);
        $record->total_collected = 0;
        $record->receiver_user_id = $owner->id;
        $record->receiver_member_id = $firstMember->id;
        $record->paid_members_count = 0;
        $record->status = 'PENDING';
        $record->save();

        // First two members have already contributed to cycle 1.
        foreach ($people->take(2) as $person) {
            $member = AdashiMember::where(['adashi_id' => $adashi->id, 'user_id' => $person->id])->first();
            $this->paidAdashiInvoice($adashi, $record, $member);
        }
        $record->total_collected = (int) Invoice::where(['adashi_record_id' => $record->id, 'payment_status' => 'Paid'])->sum('amount');
        $record->paid_members_count = Invoice::where(['adashi_record_id' => $record->id, 'payment_status' => 'Paid'])->count();
        $record->save();
    }

    private function pocketSlot(Pocket $pocket, User $user, int $n): PocketSlot
    {
        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $user->id;
        $slot->slot_number = $n;
        $slot->hand_count = 1;
        $slot->amount_paying = $pocket->amount_per_hand;
        $slot->status = 1;
        $slot->comment = '';
        $slot->save();

        return $slot;
    }

    private function paidPocketInvoice(Pocket $pocket, PocketSlot $slot, int $month): void
    {
        $invoice = new Invoice();
        $invoice->pocket_slot_id = $slot->id;
        $invoice->invoice_no = 'KP/'.str_pad($pocket->id, 3, '0', STR_PAD_LEFT).'/'.date('mdHis').(++$this->seq);
        $invoice->amount = $pocket->amount_per_hand;
        $invoice->reference_no = $invoice->invoice_no;
        $invoice->payment_status = 'Paid';
        $invoice->paid_through = 'Manual';
        $invoice->payment_date = now();
        $invoice->save();

        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'item_id' => 1, 'amount' => $pocket->amount_per_hand,
            'type' => 'Paid', 'month' => $month,
        ]);
    }

    private function paidAdashiInvoice(Adashi $adashi, AdashiRecord $record, AdashiMember $member): void
    {
        $invoice = new Invoice();
        $invoice->pocket_slot_id = null;
        $invoice->adashi_record_id = $record->id;
        $invoice->adashi_member_id = $member->id;
        $invoice->invoice_no = 'ADSH/'.str_pad($adashi->id, 3, '0', STR_PAD_LEFT).'/'.date('mdHis').(++$this->seq);
        $invoice->amount = $adashi->amount_per_cycle;
        $invoice->reference_no = $invoice->invoice_no;
        $invoice->payment_status = 'Paid';
        $invoice->paid_through = 'Manual';
        $invoice->payment_date = now();
        $invoice->save();

        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'item_id' => 1, 'amount' => $adashi->amount_per_cycle,
            'type' => 'Paid', 'month' => $record->cycle_number,
        ]);
    }

    private function makeUser(string $name, string $email, string $phone, string $username): User
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            return $user;
        }
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->phone_number = $phone;
        $user->username = $username;
        $user->password = bcrypt('password');
        $user->save();

        return $user;
    }
}
