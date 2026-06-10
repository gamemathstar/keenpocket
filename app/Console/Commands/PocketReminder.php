<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Nudges active pocket members who have unpaid invoices. Delivered multi-channel
 * (push + SMS when SMS is enabled) via the PAYMENT_REMINDER notification path.
 *
 * Scoped to existing unpaid invoices (concrete) rather than inferred per-month
 * due dates — pockets have no per-installment due date yet (see roadmap).
 */
class PocketReminder extends Command
{
    protected $signature = 'pockets:remind';
    protected $description = 'Remind active pocket members who have unpaid invoices';

    public function handle()
    {
        $sent = 0;

        PocketSlot::where('status', 1)->chunkById(200, function ($slots) use (&$sent) {
            foreach ($slots as $slot) {
                $unpaid = Invoice::where('pocket_slot_id', $slot->id)
                    ->where('payment_status', 'Not Paid')
                    ->count();

                if ($unpaid === 0) {
                    continue;
                }

                $user = User::find($slot->user_id);
                $pocket = Pocket::find($slot->pocket_id);
                if (!$user || !$pocket) {
                    continue;
                }

                Notification::paymentReminderNotification(
                    $user,
                    $pocket,
                    $unpaid.' pending invoice'.($unpaid > 1 ? 's' : '')
                );
                $sent++;
            }
        });

        $this->info("Pocket reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
