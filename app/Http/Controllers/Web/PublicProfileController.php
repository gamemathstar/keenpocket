<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Adashi;
use App\Models\Pocket;
use App\Models\Rating;
use App\Models\User;
use App\Services\Gamification\GamificationService;
use App\Services\Reputation\ReputationService;

class PublicProfileController extends Controller
{
    public function show($id, ReputationService $reputation, GamificationService $game)
    {
        $user = User::findOrFail($id);

        $ratings = Rating::where('ratee_id', $user->id)
            ->leftJoin('users', 'users.id', '=', 'ratings.rater_id')
            ->orderByDesc('ratings.id')->limit(20)
            ->get(['ratings.stars', 'ratings.comment', 'ratings.context_type', 'users.name as rater']);

        $openPockets = Pocket::where('user_id', $user->id)->where('status', 1)->orderByDesc('id')->get();
        $openAdashis = Adashi::where('admin_id', $user->id)->where('is_public', true)->where('status', 'ACTIVE')->orderByDesc('id')->get();

        return view('users.show', [
            'profileUser' => $user,
            'isMe' => $user->id === auth()->id(),
            'rep' => $reputation->forUser($user->id),
            'badges' => $game->enabled() ? $game->badgesFor($user->id) : [],
            'ratings' => $ratings,
            'openPockets' => $openPockets,
            'openAdashis' => $openAdashis,
        ]);
    }
}
