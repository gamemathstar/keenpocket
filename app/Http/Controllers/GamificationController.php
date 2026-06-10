<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Gamification\GamificationService;
use Illuminate\Http\Request;

class GamificationController extends Controller
{
    public function __construct(private GamificationService $game)
    {
    }

    /**
     * The authenticated user's full gamification profile (streak, badges, stats).
     */
    public function me(Request $request)
    {
        if (!$this->game->enabled()) {
            return response(['enabled' => false], 200);
        }

        return response($this->game->profileFor($request->user()->id));
    }

    /**
     * A user's earned badges (for public profiles / directory).
     */
    public function badges($id)
    {
        if (!$this->game->enabled()) {
            return response(['enabled' => false, 'badges' => []], 200);
        }

        if (!User::find($id)) {
            return response(['message' => 'User not found.'], 404);
        }

        return response(['badges' => $this->game->badgesFor($id)]);
    }
}
