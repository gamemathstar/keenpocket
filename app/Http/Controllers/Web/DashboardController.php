<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Adashi;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Services\Gamification\GamificationService;
use App\Services\Reputation\ReputationService;
use App\Services\Wallet\WalletService;

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

        return view('dashboard', compact('user', 'pockets', 'adashis', 'rep', 'profile', 'walletBalance', 'ownedPockets'));
    }
}
