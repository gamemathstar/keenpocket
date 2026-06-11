<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Services\Gamification\GamificationService;
use App\Services\Kyc\KycService;
use App\Services\Reputation\ReputationService;

class ProfileController extends Controller
{
    public function index(ReputationService $reputation, GamificationService $game, KycService $kyc)
    {
        $user = auth()->user();

        $ratings = Rating::where('ratee_id', $user->id)
            ->leftJoin('users', 'users.id', '=', 'ratings.rater_id')
            ->orderByDesc('ratings.id')
            ->get(['ratings.stars', 'ratings.comment', 'ratings.context_type', 'users.name as rater']);

        return view('profile', [
            'user' => $user,
            'rep' => $reputation->forUser($user->id),
            'profile' => $game->enabled() ? $game->profileFor($user->id) : null,
            'ratings' => $ratings,
            'kyc' => $kyc->statusFor($user),
        ]);
    }
}
