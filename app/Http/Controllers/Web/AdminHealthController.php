<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\AdashiRecord;
use App\Models\Invoice;
use App\Models\Pocket;
use App\Models\PocketSlot;

class AdminHealthController extends Controller
{
    /** Health snapshot of every pocket/adashi the user administers. */
    public function index()
    {
        $userId = auth()->id();

        $pockets = Pocket::where('user_id', $userId)->orderByDesc('id')->get()->map(function ($p) {
            $slotIds = PocketSlot::where(['pocket_id' => $p->id, 'status' => 1])->pluck('id');
            $hands = (int) PocketSlot::where(['pocket_id' => $p->id, 'status' => 1])->sum('hand_count');
            $target = $hands * (int) $p->month_count * (int) $p->amount_per_hand;
            $collected = (int) Invoice::whereIn('pocket_slot_id', $slotIds)
                ->join('invoice_item', 'invoice_item.invoice_id', '=', 'invoices.id')
                ->where(['invoices.payment_status' => 'Paid', 'invoice_item.type' => 'Paid'])
                ->sum('invoice_item.amount');
            // Members who have never made a verified contribution.
            $paidUserIds = Invoice::whereIn('invoices.pocket_slot_id', $slotIds)
                ->where('invoices.payment_status', 'Paid')
                ->join('pocket_slots', 'pocket_slots.id', '=', 'invoices.pocket_slot_id')
                ->distinct()->pluck('pocket_slots.user_id');
            $atRisk = PocketSlot::where(['pocket_id' => $p->id, 'status' => 1])
                ->whereNotIn('user_id', $paidUserIds)->count();

            return (object) [
                'pocket' => $p,
                'members' => $slotIds->count(),
                'collected' => $collected,
                'target' => $target,
                'percent' => $target > 0 ? min(100, (int) round($collected / $target * 100)) : 0,
                'at_risk' => $atRisk,
            ];
        });

        $adashis = Adashi::where('admin_id', $userId)->orderByDesc('id')->get()->map(function ($a) {
            $record = AdashiRecord::where('adashi_id', $a->id)->orderByDesc('cycle_number')->first();
            $active = AdashiMember::where(['adashi_id' => $a->id, 'is_active' => 1])->count();
            $pending = $record ? Invoice::where(['adashi_record_id' => $record->id, 'payment_status' => 'Not Paid'])->count() : 0;
            $cycleTarget = (int) $a->amount_per_cycle * max(1, $active);

            return (object) [
                'adashi' => $a,
                'members' => $active,
                'paid' => $record ? (int) $record->paid_members_count : 0,
                'collected' => $record ? (int) $record->total_collected : 0,
                'cycle_target' => $cycleTarget,
                'percent' => $record && $cycleTarget > 0 ? min(100, (int) round($record->total_collected / $cycleTarget * 100)) : 0,
                'pending' => $pending,
            ];
        });

        return view('admin-health', compact('pockets', 'adashis'));
    }
}
