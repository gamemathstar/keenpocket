<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Services\Referral\ReferralService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(private ReferralService $referrals)
    {
    }

    /**
     * The current user's referral code + shareable links + stats.
     */
    public function me(Request $request)
    {
        if (!$this->referrals->enabled()) {
            return response(['enabled' => false], 200);
        }

        $user = $request->user();

        return response([
            'enabled' => true,
            'code' => $this->referrals->codeFor($user),
            'invite_link' => $this->referrals->inviteLink($user),
            'whatsapp_url' => $this->referrals->whatsappShareUrl($user),
            'stats' => $this->referrals->stats($user),
        ]);
    }

    /**
     * List the people this user has invited (most recent first).
     */
    public function index(Request $request)
    {
        if (!$this->referrals->enabled()) {
            return response(['enabled' => false], 200);
        }

        $referrals = Referral::where('referrer_id', $request->user()->id)
            ->leftJoin('users', 'users.id', '=', 'referrals.referred_id')
            ->orderByDesc('referrals.id')
            ->get(['referrals.id', 'referrals.status', 'referrals.created_at', 'users.name', 'users.phone_number']);

        return response(['referrals' => $referrals]);
    }
}
