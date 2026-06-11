<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Services\Referral\ReferralService;

class ReferralWebController extends Controller
{
    public function index(ReferralService $referrals)
    {
        $user = auth()->user();

        $invitees = Referral::where('referrer_id', $user->id)
            ->leftJoin('users', 'users.id', '=', 'referrals.referred_id')
            ->orderByDesc('referrals.id')
            ->get(['referrals.status', 'referrals.created_at', 'users.name']);

        return view('referrals', [
            'code' => $referrals->codeFor($user),
            'inviteLink' => $referrals->inviteLink($user),
            'whatsappUrl' => $referrals->whatsappShareUrl($user),
            'stats' => $referrals->stats($user),
            'invitees' => $invitees,
        ]);
    }
}
