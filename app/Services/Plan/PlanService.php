<?php

namespace App\Services\Plan;

use App\Models\Plan;
use App\Models\PlanItem;

/**
 * Home planning: a monthly grocery plan a user builds (and optionally shares with
 * a spouse). Items are marked purchased or deferred; deferred items can be carried
 * over into the next plan and flagged as priority.
 */
class PlanService
{
    /** Budget / progress summary for a plan. */
    public function summary(Plan $plan): array
    {
        $items = $plan->items()->get();

        $spent = $items->where('status', 'purchased')->sum(fn (PlanItem $i) => $i->lineValue());
        // Planned spend = everything not deferred (pending + purchased).
        $estimated = $items->where('status', '!=', 'deferred')->sum(fn (PlanItem $i) => $i->lineValue());
        $budget = (int) ($plan->budget ?? 0);

        return [
            'total' => $items->count(),
            'purchased' => $items->where('status', 'purchased')->count(),
            'pending' => $items->where('status', 'pending')->count(),
            'deferred' => $items->where('status', 'deferred')->count(),
            'spent' => (int) $spent,
            'estimated' => (int) $estimated,
            'budget' => $budget,
            'remaining' => $budget > 0 ? $budget - (int) $spent : null,
            'over_budget' => $budget > 0 && $estimated > $budget,
            'percent_spent' => $budget > 0 ? min(100, (int) round($spent / $budget * 100)) : 0,
        ];
    }

    /**
     * Copy the deferred items from $from into $to as fresh, prioritised pending
     * items. Returns the number carried over.
     */
    public function carryOverDeferred(Plan $from, Plan $to, int $userId): int
    {
        $deferred = $from->items()->where('status', 'deferred')->get();
        $n = 0;
        foreach ($deferred as $d) {
            PlanItem::create([
                'plan_id' => $to->id,
                'name' => $d->name,
                'quantity' => $d->quantity,
                'unit' => $d->unit,
                'unit_price' => $d->unit_price,
                'status' => 'pending',
                'priority' => true,          // deferred last time → prioritise now
                'note' => $d->note,
                'created_by' => $userId,
            ]);
            $n++;
        }

        return $n;
    }

    /** The user's most recent OTHER plan that still has deferred items. */
    public function lastPlanWithDeferred(int $userId, int $excludePlanId): ?Plan
    {
        return Plan::where('owner_id', $userId)
            ->where('id', '!=', $excludePlanId)
            ->whereHas('items', fn ($q) => $q->where('status', 'deferred'))
            ->latest('id')->first();
    }
}
