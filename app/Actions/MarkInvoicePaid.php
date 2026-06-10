<?php

namespace App\Actions;

use App\Http\Controllers\Adashi\AdashiController;
use App\Models\AdashiRecord;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single source of truth for transitioning an invoice to Paid.
 *
 * Used by the online payment flow (gateway verify / webhook) and reusable by
 * any future automated path. Idempotent: marking an already-paid invoice is a
 * no-op, so duplicate webhook deliveries are safe.
 */
class MarkInvoicePaid
{
    public function execute(Invoice $invoice, string $paidThrough = 'Online'): void
    {
        if ($invoice->payment_status === 'Paid') {
            return; // idempotent — already settled
        }

        DB::transaction(function () use ($invoice, $paidThrough) {
            $invoice->payment_status = 'Paid';
            $invoice->paid_through = $paidThrough;
            $invoice->payment_date = now();
            $invoice->save();
        });

        // Domain side-effects are best-effort and must never undo the payment.
        try {
            if ($invoice->adashi_record_id) {
                $record = AdashiRecord::find($invoice->adashi_record_id);
                if ($record) {
                    // Reuses the existing idempotent reconciliation (updates the
                    // current cycle's collected total / paid count, auto-rotates).
                    app(AdashiController::class)->reconcilePayments($record->adashi_id);
                }
            } elseif ($invoice->pocket_slot_id) {
                $slot = PocketSlot::find($invoice->pocket_slot_id);
                $pocket = $slot ? Pocket::find($slot->pocket_id) : null;
                if ($slot && $pocket) {
                    $owner = User::find($pocket->user_id);
                    $payer = User::find($slot->user_id);
                    if ($owner && $payer) {
                        Notification::paymentReceivedNotification($owner, $payer, $invoice, 1);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Post-payment reconciliation failed for invoice '.$invoice->id.': '.$e->getMessage());
        }
    }
}
