<?php

namespace App\Services\Contribution;

use App\Models\InvoiceItem;
use App\Models\Pocket;
use App\Models\PocketSlot;

/**
 * Spreads a contribution amount across a member's next UNPAID cycle months.
 *
 * Cycle months are 1..month_count; the calendar label is derived from the
 * pocket's start_month. A month is "owed" up to the member's monthly amount
 * (hand_count × amount_per_hand = slot.amount_paying); the final month may be a
 * partial payment. Only approved (Paid) contribution items count as paid, so a
 * member can't keep paying the same month over and over.
 */
class ContributionPlanner
{
    private array $months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];

    public function monthlyAmount(PocketSlot $slot): int
    {
        return (int) $slot->amount_paying;
    }

    /** Calendar month name for a 1-based cycle index, offset by the pocket start. */
    public function monthLabel(Pocket $pocket, int $cycleIndex): string
    {
        $calendar = (((int) $pocket->start_month - 1 + ($cycleIndex - 1)) % 12 + 12) % 12;

        return $this->months[$calendar];
    }

    /** Map of cycle month => amount already Paid for this slot (contribution only). */
    public function paidByMonth(int $slotId): array
    {
        return InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_item.invoice_id')
            ->where('invoices.pocket_slot_id', $slotId)
            ->where('invoices.payment_status', 'Paid')
            ->where('invoice_item.type', 'Paid')
            ->groupBy('invoice_item.month')
            ->selectRaw('invoice_item.month as month, COALESCE(SUM(invoice_item.amount),0) as paid')
            ->pluck('paid', 'month')->toArray();
    }

    /**
     * Allocate $amount across the next unpaid months.
     * Returns rows: ['month' => int, 'label' => string, 'amount' => int, 'owed' => int, 'monthly' => int].
     */
    public function plan(Pocket $pocket, PocketSlot $slot, int $amount): array
    {
        $monthly = max(1, $this->monthlyAmount($slot));
        $paid = $this->paidByMonth($slot->id);
        $remaining = max(0, $amount);
        $monthCount = (int) $pocket->month_count;
        $plan = [];

        for ($m = 1; $m <= $monthCount && $remaining > 0; $m++) {
            $owed = $monthly - (int) ($paid[$m] ?? 0);
            if ($owed <= 0) {
                continue; // month already fully paid
            }
            $alloc = min($remaining, $owed);
            $plan[] = [
                'month' => $m, 'label' => $this->monthLabel($pocket, $m),
                'amount' => $alloc, 'owed' => $owed, 'monthly' => $monthly,
            ];
            $remaining -= $alloc;
        }

        // Overpayment beyond the final month — add the excess to the last line.
        if ($remaining > 0) {
            if ($plan) {
                $plan[count($plan) - 1]['amount'] += $remaining;
            } else {
                $plan[] = [
                    'month' => max(1, $monthCount), 'label' => $this->monthLabel($pocket, max(1, $monthCount)),
                    'amount' => $remaining, 'owed' => 0, 'monthly' => $monthly,
                ];
            }
        }

        return $plan;
    }
}
