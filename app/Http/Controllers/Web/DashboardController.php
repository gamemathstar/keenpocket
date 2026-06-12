<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\Invoice;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Services\Gamification\GamificationService;
use App\Services\Reputation\ReputationService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(ReputationService $reputation, GamificationService $game, WalletService $wallet)
    {
        $user = auth()->user();

        $pockets = Pocket::query()
            ->join('pocket_slots', 'pocket_slots.pocket_id', '=', 'pockets.id')
            ->where('pocket_slots.user_id', $user->id)
            ->where('pocket_slots.status', 1)
            ->select(['pockets.*', 'pocket_slots.hand_count', 'pocket_slots.amount_paying'])
            ->orderByDesc('pockets.id')
            ->get();

        $adashis = Adashi::query()
            ->join('adashi_members', 'adashi_members.adashi_id', '=', 'adashis.id')
            ->where('adashi_members.user_id', $user->id)
            ->where('adashi_members.is_active', 1)
            ->select('adashis.*')
            ->orderByDesc('adashis.id')
            ->get();

        $rep = $reputation->forUser($user->id);
        $profile = $game->enabled() ? $game->profileFor($user->id) : null;
        $walletBalance = $wallet->enabled() ? $wallet->balance($user->id) : null;

        $ownedPockets = Pocket::where('user_id', $user->id)->count();

        // Contribution trend — paid amounts per month over the last 6 months.
        $slotIds = PocketSlot::where('user_id', $user->id)->pluck('id')->all();
        $memberIds = AdashiMember::where('user_id', $user->id)->pluck('id')->all();
        $paid = Invoice::where('payment_status', 'Paid')
            ->where(fn ($q) => $q->whereIn('pocket_slot_id', $slotIds)->orWhereIn('adashi_member_id', $memberIds))
            ->get(['amount', 'payment_date', 'created_at']);

        $buckets = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->startOfMonth()->subMonths($i);
            $buckets[$m->format('Y-m')] = ['label' => $m->format('M'), 'total' => 0];
        }
        foreach ($paid as $inv) {
            $date = $inv->payment_date ?? $inv->created_at;
            if (!$date) {
                continue;
            }
            $key = Carbon::parse($date)->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['total'] += (int) $inv->amount;
            }
        }
        $chartLabels = array_column($buckets, 'label');
        $chartData = array_column($buckets, 'total');
        $totalSaved = (int) $paid->sum('amount');

        // Weekly goal + streak (freezes auto-bridge a missed week — see StreakService).
        $weeks = [];
        foreach ($paid as $inv) {
            $d = $inv->payment_date ?? $inv->created_at;
            if ($d) {
                $weeks[Carbon::parse($d)->format('oW')] = true;
            }
        }
        $streak = app(\App\Services\Streak\StreakService::class)->evaluate($user, array_keys($weeks));
        $thisWeekMet = $streak['this_week_met'];
        $weekStreak = $streak['streak'];
        $streakFreezes = $streak['freezes'];

        return view('dashboard', compact('user', 'pockets', 'adashis', 'rep', 'profile', 'walletBalance', 'ownedPockets', 'chartLabels', 'chartData', 'totalSaved', 'thisWeekMet', 'weekStreak', 'streakFreezes'));
    }
}
