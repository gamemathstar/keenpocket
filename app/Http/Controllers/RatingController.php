<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Services\Rating\RatingService;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function __construct(private RatingService $ratings)
    {
    }

    /**
     * Rate the organizer of a pocket/adashi you belong to (1–5 stars).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'context_type' => 'required|in:pocket,adashi',
            'context_id' => 'required|integer',
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $result = $this->ratings->submit(
            $request->user(),
            $data['context_type'],
            (int) $data['context_id'],
            (int) $data['stars'],
            $data['comment'] ?? null
        );

        return response(
            $result['ok']
                ? ['message' => $result['message'], 'rating' => $result['rating']]
                : ['message' => $result['message']],
            $result['status']
        );
    }

    /**
     * Ratings received by a user (most recent first).
     */
    public function forUser($id)
    {
        $ratings = Rating::where('ratee_id', $id)
            ->leftJoin('users', 'users.id', '=', 'ratings.rater_id')
            ->orderByDesc('ratings.id')
            ->get(['ratings.id', 'ratings.stars', 'ratings.comment', 'ratings.context_type', 'ratings.created_at', 'users.name as rater']);

        return response([
            'summary' => $this->ratings->averageFor($id),
            'ratings' => $ratings,
        ]);
    }
}
