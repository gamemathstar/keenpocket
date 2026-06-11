<?php

namespace Database\Seeders;

use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\AdashiRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\Pocket;
use App\Models\PocketItem;
use App\Models\PocketSlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Clickable demo data for the web interface.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Log in with phone 08000000001 (or ...002 / ...003), password: "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Default contribution item (pocket_items reference item_id = 1).
        Item::firstOrCreate(['id' => 1], ['name' => 'Contribution', 'category' => 'Paid']);

        $users = collect([
            ['Amina Yusuf', '08000000001'],
            ['Bashir Lawal', '08000000002'],
            ['Chika Obi', '08000000003'],
        ])->map(fn ($u) => User::firstOrCreate(
            ['phone_number' => $u[1]],
            ['name' => $u[0], 'email' => str_replace(' ', '.', strtolower($u[0])).'@demo.test',
             'username' => $u[1], 'password' => bcrypt('password')]
        ));

        [$amina, $bashir, $chika] = [$users[0], $users[1], $users[2]];

        // ---- Pocket owned by Amina, Bashir is a member ----
        $pocket = Pocket::firstOrCreate(
            ['title' => 'Demo Ramadan Pocket', 'user_id' => $amina->id],
            ['pocket_type' => 'Ramadan', 'description' => 'A demo savings pocket.',
             'year' => (int) date('Y'), 'start_month' => 1, 'month_count' => 12, 'max_keens' => 0,
             'amount_per_hand' => 5000, 'per_hand_allowed' => '5000', 'status' => 1]
        );
        PocketItem::firstOrCreate(['pocket_id' => $pocket->id, 'item_id' => 1]);

        foreach ([$amina, $bashir] as $u) {
            $slot = PocketSlot::firstOrCreate(
                ['pocket_id' => $pocket->id, 'user_id' => $u->id],
                ['hand_count' => 1, 'amount_paying' => 5000, 'status' => 1, 'comment' => '']
            );

            // One paid + one pending invoice each (only on first seed).
            if (Invoice::where('pocket_slot_id', $slot->id)->exists()) {
                continue;
            }
            foreach (['Paid', 'Not Paid'] as $i => $status) {
                $inv = Invoice::create([
                    'pocket_slot_id' => $slot->id,
                    'invoice_no' => 'KP/'.str_pad($pocket->id, 3, '0', STR_PAD_LEFT).'/'.$u->id.$i,
                    'amount' => 5000, 'reference_no' => 'REF'.$u->id.$i,
                    'payment_status' => $status, 'paid_through' => $status === 'Paid' ? 'Manual' : 'Pending',
                    'payment_date' => $status === 'Paid' ? now() : null,
                ]);
                InvoiceItem::create(['invoice_id' => $inv->id, 'item_id' => 1, 'amount' => 5000, 'type' => 'Paid', 'month' => $i + 1]);
            }
        }

        // ---- Adashi admined by Amina, all three are members, one open cycle ----
        $adashi = Adashi::firstOrCreate(
            ['name' => 'Demo Family Adashi', 'admin_id' => $amina->id],
            ['amount_per_cycle' => 20000, 'total_members' => 3, 'start_date' => date('Y-m-d'),
             'cycle_duration_days' => 30, 'current_cycle_number' => 1, 'rotation_mode' => 'MANUAL',
             'status' => 'ACTIVE', 'is_public' => true]
        );

        $pos = 0;
        foreach ([$amina, $bashir, $chika] as $u) {
            $pos++;
            AdashiMember::firstOrCreate(
                ['adashi_id' => $adashi->id, 'user_id' => $u->id],
                ['position' => $pos, 'has_received' => false, 'joined_at' => now(), 'is_active' => true]
            );
        }
        $firstMember = AdashiMember::where('adashi_id', $adashi->id)->orderBy('position')->first();
        AdashiRecord::firstOrCreate(
            ['adashi_id' => $adashi->id, 'cycle_number' => 1],
            ['due_at' => Carbon::now()->addDays(30), 'total_collected' => 0,
             'receiver_user_id' => $firstMember->user_id, 'receiver_member_id' => $firstMember->id,
             'paid_members_count' => 0, 'status' => 'PENDING']
        );

        $this->command?->info('Demo data ready. Login: 08000000001 / password');
    }
}
