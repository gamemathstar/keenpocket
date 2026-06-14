<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdashiMember;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Pocket;
use App\Models\PocketSlot;

class InsightsController extends Controller
{
    /** A personal "year in review" style summary of the member's saving. */
    public function index()
    {
        $userId = auth()->id();

        $slotIds = PocketSlot::where('user_id', $userId)->pluck('id');
        $base = fn () => InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_item.invoice_id')
            ->whereIn('invoices.pocket_slot_id', $slotIds)
            ->where('invoices.payment_status', 'Paid');

        $pocketContributed = (int) $base()->where('invoice_item.type', 'Paid')->sum('invoice_item.amount');
        $donated = (int) $base()->where('invoice_item.type', 'Donation')->sum('invoice_item.amount');
        $thisYear = (int) $base()->where('invoice_item.type', 'Paid')
            ->whereYear('invoices.payment_date', (int) date('Y'))->sum('invoice_item.amount');
        $contributions = (int) $base()->where('invoice_item.type', 'Paid')->count();

        // Adashi contributions (verified) the member has made.
        $adashiMemberIds = AdashiMember::where('user_id', $userId)->pluck('id');
        $adashiContributed = (int) Invoice::whereIn('adashi_member_id', $adashiMemberIds)
            ->where('payment_status', 'Paid')->sum('amount');

        $totalSaved = $pocketContributed + $adashiContributed;

        $stats = [
            'total_saved' => $totalSaved,
            'this_year' => $thisYear,
            'donated' => $donated,
            'contributions' => $contributions,
            'pockets' => PocketSlot::where(['user_id' => $userId, 'status' => 1])->distinct('pocket_id')->count('pocket_id')
                + Pocket::where('user_id', $userId)->count(),
            'adashis' => AdashiMember::where(['user_id' => $userId, 'is_active' => 1])->count(),
        ];

        return view('insights', compact('stats'));
    }
}
