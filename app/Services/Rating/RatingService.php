<?php

namespace App\Services\Rating;

use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\Rating;
use App\Models\User;

/**
 * Peer trust ratings. A member of a pocket/adashi rates the organizer (1–5)
 * after participating — capturing trustworthiness payment data can't show.
 * One rating per member per group (updatable); feeds the reputation profile.
 */
class RatingService
{
    /**
     * @return array{ok: bool, status: int, message: string, rating?: Rating}
     */
    public function submit(User $rater, string $contextType, int $contextId, int $stars, ?string $comment): array
    {
        if (!in_array($contextType, ['pocket', 'adashi'], true)) {
            return ['ok' => false, 'status' => 422, 'message' => 'Invalid context.'];
        }

        $organizerId = $this->organizerId($contextType, $contextId);
        if ($organizerId === null) {
            return ['ok' => false, 'status' => 404, 'message' => ucfirst($contextType).' not found.'];
        }
        if ($organizerId === $rater->id) {
            return ['ok' => false, 'status' => 422, 'message' => 'You cannot rate yourself.'];
        }
        if (!$this->isMember($contextType, $contextId, $rater->id)) {
            return ['ok' => false, 'status' => 403, 'message' => 'Only members of this '.$contextType.' can rate it.'];
        }

        $rating = Rating::updateOrCreate(
            ['rater_id' => $rater->id, 'context_type' => $contextType, 'context_id' => $contextId],
            ['ratee_id' => $organizerId, 'stars' => $stars, 'comment' => $comment]
        );

        return ['ok' => true, 'status' => 200, 'message' => 'Rating saved.', 'rating' => $rating];
    }

    /**
     * Average rating received by a user (as an organizer).
     *
     * @return array{average: ?float, count: int}
     */
    public function averageFor($userId): array
    {
        $query = Rating::where('ratee_id', $userId);
        $count = $query->count();

        return [
            'average' => $count ? round((float) $query->avg('stars'), 2) : null,
            'count' => $count,
        ];
    }

    private function organizerId(string $type, int $id): ?int
    {
        if ($type === 'pocket') {
            $p = Pocket::find($id);
            return $p ? (int) $p->user_id : null;
        }
        $a = Adashi::find($id);
        return $a ? (int) $a->admin_id : null;
    }

    private function isMember(string $type, int $id, int $userId): bool
    {
        if ($type === 'pocket') {
            return PocketSlot::where('pocket_id', $id)->where('user_id', $userId)->where('status', 1)->exists();
        }
        return AdashiMember::where('adashi_id', $id)->where('user_id', $userId)->where('is_active', true)->exists();
    }
}
