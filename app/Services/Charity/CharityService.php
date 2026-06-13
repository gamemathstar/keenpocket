<?php

namespace App\Services\Charity;

use App\Models\CharityGoalItem;
use App\Models\CharityProject;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Charity donations (Sadaqah / fi-sabilillah) attached to a pocket.
 *
 * Donations are recorded as `invoice_item` rows with type = 'Donation' linked to
 * a CharityProject (and optionally a CharityGoalItem for item drives), so they
 * ride the same rails as contributions. Privacy: members only ever see their OWN
 * total and the GROUP total; per-member amounts are visible to the admin only.
 */
class CharityService
{
    public function enabled(): bool
    {
        return (bool) config('charity.enabled', true);
    }

    /** Active charity project for a pocket (or null). */
    public function activeProject(Pocket $pocket): ?CharityProject
    {
        if (!$this->enabled() || !$pocket->charity_enabled) {
            return null;
        }

        return CharityProject::where('pocket_id', $pocket->id)
            ->where('status', 'ACTIVE')->latest('id')->first();
    }

    /** Total raised (paid money donations) for a project. */
    public function raised(CharityProject $project): int
    {
        return (int) $this->donationItems($project->id)->sum('invoice_item.amount');
    }

    /** A single member's total donated amount to a project. */
    public function myTotal(CharityProject $project, int $userId): int
    {
        return (int) $this->donationItems($project->id)
            ->where('pocket_slots.user_id', $userId)
            ->sum('invoice_item.amount');
    }

    /** Per-goal-item progress: collected quantity + raised amount vs target. */
    public function itemProgress(CharityProject $project): array
    {
        $rows = [];
        foreach ($project->goalItems()->get() as $item) {
            $agg = $this->donationItems($project->id)
                ->where('invoice_item.charity_goal_item_id', $item->id)
                ->selectRaw('COALESCE(SUM(invoice_item.quantity),0) as qty, COALESCE(SUM(invoice_item.amount),0) as amt')
                ->first();
            $collected = (int) ($agg->qty ?? 0);
            $target = (int) $item->target_quantity;
            $rows[] = [
                'id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'target_quantity' => $target,
                'collected_quantity' => $collected,
                'raised_amount' => (int) ($agg->amt ?? 0),
                'percent' => $target > 0 ? min(100, (int) round($collected / $target * 100)) : 0,
            ];
        }

        return $rows;
    }

    /** Admin-only: per-member donation totals (the others stay anonymous). */
    public function donorBreakdown(CharityProject $project): array
    {
        return $this->donationItems($project->id)
            ->join('users', 'users.id', '=', 'pocket_slots.user_id')
            ->groupBy('pocket_slots.user_id', 'users.name')
            ->selectRaw('users.name as name, COALESCE(SUM(invoice_item.amount),0) as total, COALESCE(SUM(invoice_item.quantity),0) as items')
            ->orderByDesc('total')
            ->get()->toArray();
    }

    /**
     * Admin: create or update a pocket's charity drive + goal items. $data keys:
     * enabled, donors_visible, title, description, goal_type, target_amount, items[].
     */
    public function configureProject(Pocket $pocket, array $data): CharityProject
    {
        return DB::transaction(function () use ($pocket, $data) {
            $pocket->charity_enabled = (bool) ($data['enabled'] ?? false);
            $pocket->charity_donors_visible = (bool) ($data['donors_visible'] ?? false);
            $pocket->save();

            $project = CharityProject::where('pocket_id', $pocket->id)->where('status', 'ACTIVE')->latest('id')->first()
                ?? new CharityProject(['pocket_id' => $pocket->id]);

            $goalType = ($data['goal_type'] ?? 'amount') === 'items' ? 'items' : 'amount';
            $project->fill([
                'pocket_id' => $pocket->id,
                'title' => $data['title'] ?? ($project->title ?? 'Charity drive'),
                'description' => $data['description'] ?? null,
                'goal_type' => $goalType,
                'target_amount' => $goalType === 'amount' ? (int) ($data['target_amount'] ?? 0) : null,
                'status' => 'ACTIVE',
            ]);
            $project->save();

            $project->goalItems()->delete();
            if ($goalType === 'items') {
                foreach (($data['items'] ?? []) as $row) {
                    if (empty($row['name'])) {
                        continue;
                    }
                    CharityGoalItem::create([
                        'charity_project_id' => $project->id,
                        'name' => $row['name'],
                        'unit' => $row['unit'] ?? null,
                        'target_quantity' => (int) ($row['target_quantity'] ?? 0),
                        'unit_price' => isset($row['unit_price']) ? (int) $row['unit_price'] : null,
                    ]);
                }
            }

            return $project->fresh();
        });
    }

    /**
     * Record a member's donation. $amount is a free money gift (0 allowed);
     * $itemDonations is [['goal_item_id' => int, 'quantity' => int], ...].
     */
    public function recordDonation(Pocket $pocket, CharityProject $project, User $user, int $amount, array $itemDonations = [], ?string $note = null): Invoice
    {
        $slot = PocketSlot::where(['pocket_id' => $pocket->id, 'user_id' => $user->id, 'status' => 1])->first();
        abort_unless($slot, 403, 'You must be an active member of this pocket to donate.');

        return DB::transaction(function () use ($pocket, $project, $slot, $amount, $itemDonations, $note) {
            $total = $amount;
            $lines = [];

            if ($amount > 0) {
                $lines[] = [
                    'item_id' => 1, 'amount' => $amount, 'type' => 'Donation', 'month' => 0,
                    'charity_project_id' => $project->id, 'charity_goal_item_id' => null, 'quantity' => null,
                ];
            }

            foreach ($itemDonations as $d) {
                $qty = (int) ($d['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $goal = CharityGoalItem::where('charity_project_id', $project->id)->find($d['goal_item_id'] ?? 0);
                if (!$goal) {
                    continue;
                }
                $value = (int) ($goal->unit_price ?? 0) * $qty;
                $total += $value;
                $lines[] = [
                    'item_id' => 1, 'amount' => $value, 'type' => 'Donation', 'month' => 0,
                    'charity_project_id' => $project->id, 'charity_goal_item_id' => $goal->id, 'quantity' => $qty,
                ];
            }

            abort_if(empty($lines), 422, 'Nothing to donate.');

            $invoice = new Invoice();
            $invoice->pocket_slot_id = $slot->id;
            $invoice->invoice_no = 'CHR/'.str_pad($pocket->id, 3, '0', STR_PAD_LEFT).'/'.date('ymdHis');
            $invoice->amount = $total;
            $invoice->reference_no = $invoice->invoice_no;
            $invoice->payment_status = 'Paid';   // self-recorded, like a manual contribution
            $invoice->paid_through = 'Manual';
            $invoice->payment_date = now();
            $invoice->save();

            foreach ($lines as $line) {
                InvoiceItem::create(array_merge(['invoice_id' => $invoice->id], $line));
            }

            // Auto-complete an amount goal once the target is met.
            if (!$project->isItemGoal() && $project->target_amount && $this->raised($project) >= $project->target_amount) {
                $project->update(['status' => 'COMPLETED']);
            }

            return $invoice;
        });
    }

    /** Assemble a privacy-aware summary for a viewer. */
    public function summary(Pocket $pocket, CharityProject $project, User $viewer, bool $isAdmin): array
    {
        $data = [
            'project' => $project,
            'goal_type' => $project->goal_type,
            'target_amount' => (int) $project->target_amount,
            'raised' => $this->raised($project),
            'group_total' => $this->raised($project),
            'my_total' => $this->myTotal($project, $viewer->id),
            'items' => $this->itemProgress($project),
            'donors_visible' => (bool) $pocket->charity_donors_visible,
        ];
        $data['percent'] = $data['target_amount'] > 0
            ? min(100, (int) round($data['raised'] / $data['target_amount'] * 100)) : 0;

        // Per-member breakdown only for the admin, or if the admin published it.
        if ($isAdmin || $pocket->charity_donors_visible) {
            $data['breakdown'] = $this->donorBreakdown($project);
        }

        return $data;
    }

    /** Base query: paid donation lines for a project, joined to the donor's slot. */
    private function donationItems(int $projectId)
    {
        return InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_item.invoice_id')
            ->join('pocket_slots', 'pocket_slots.id', '=', 'invoices.pocket_slot_id')
            ->where('invoice_item.type', 'Donation')
            ->where('invoice_item.charity_project_id', $projectId)
            ->where('invoices.payment_status', 'Paid');
    }
}
