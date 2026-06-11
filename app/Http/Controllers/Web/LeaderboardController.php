<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period') === 'all' ? 'all' : 'week';
        $weekStart = Carbon::now()->startOfWeek();

        // Ranked by NUMBER of paid contributions (consistency) — never by amount,
        // to keep individual savings private. "week" = paid since Monday.
        $pocketTotals = Invoice::where('invoices.payment_status', 'Paid')
            ->join('pocket_slots', 'pocket_slots.id', '=', 'invoices.pocket_slot_id')
            ->when($period === 'week', fn ($q) => $q->where('invoices.payment_date', '>=', $weekStart))
            ->groupBy('pocket_slots.user_id')
            ->selectRaw('pocket_slots.user_id as uid, COUNT(*) as total')
            ->pluck('total', 'uid');

        $adashiTotals = Invoice::where('invoices.payment_status', 'Paid')
            ->join('adashi_members', 'adashi_members.id', '=', 'invoices.adashi_member_id')
            ->when($period === 'week', fn ($q) => $q->where('invoices.payment_date', '>=', $weekStart))
            ->groupBy('adashi_members.user_id')
            ->selectRaw('adashi_members.user_id as uid, COUNT(*) as total')
            ->pluck('total', 'uid');

        $totals = [];
        foreach ($pocketTotals as $uid => $t) {
            $totals[$uid] = ($totals[$uid] ?? 0) + (int) $t;
        }
        foreach ($adashiTotals as $uid => $t) {
            $totals[$uid] = ($totals[$uid] ?? 0) + (int) $t;
        }
        arsort($totals);

        $top = array_slice($totals, 0, 20, true);
        $users = User::whereIn('id', array_keys($top))->get(['id', 'name'])->keyBy('id');

        $rows = [];
        $rank = 0;
        foreach ($top as $uid => $total) {
            $rank++;
            $rows[] = [
                'rank' => $rank,
                'name' => $users[$uid]->name ?? 'Member',
                'user_id' => $uid,
                'total' => $total,
                'is_me' => $uid == auth()->id(),
            ];
        }

        // The current user's own standing (even if outside the top 20).
        $myRank = array_search(auth()->id(), array_keys($totals), true);
        $myStanding = $myRank === false ? null : ['rank' => $myRank + 1, 'total' => $totals[auth()->id()]];

        return view('leaderboard', compact('rows', 'myStanding', 'period'));
    }
}
