<?php

namespace App\Services\Reputation;

use App\Models\AdashiMember;
use App\Models\AdashiRecord;
use App\Models\Invoice;
use App\Models\PocketSlot;
use App\Models\Rating;

/**
 * Member reputation, computed on the fly from existing activity (no new tables).
 * Surfaced before someone joins a group so strangers can assess trust.
 *
 * Score (0–100): payment reliability (≤70) + activity (≤30). Bands give a
 * human label. score()/band() are pure so they unit-test without a database.
 */
class ReputationService
{
    public function forUser($userId): array
    {
        $userId = is_object($userId) ? $userId->id : $userId;

        $slotIds = PocketSlot::where('user_id', $userId)->pluck('id');
        $memberIds = AdashiMember::where('user_id', $userId)->pluck('id');

        $totalInvoices = Invoice::whereIn('pocket_slot_id', $slotIds)->count()
            + Invoice::whereIn('adashi_member_id', $memberIds)->count();
        $paidInvoices = Invoice::whereIn('pocket_slot_id', $slotIds)->where('payment_status', 'Paid')->count()
            + Invoice::whereIn('adashi_member_id', $memberIds)->where('payment_status', 'Paid')->count();

        $reliability = $totalInvoices > 0 ? (int) round($paidInvoices / $totalInvoices * 100) : null;

        $pocketsJoined = PocketSlot::where('user_id', $userId)->where('status', 1)->distinct()->count('pocket_id');
        $adashisJoined = AdashiMember::where('user_id', $userId)->where('is_active', true)->count();
        $cyclesCompleted = AdashiRecord::where('receiver_user_id', $userId)->where('status', 'PAID_OUT')->count();

        $activity = $pocketsJoined + $adashisJoined + $cyclesCompleted;
        $hasHistory = $totalInvoices > 0 || $activity > 0;
        $score = self::score($reliability ?? 0, $activity);

        $ratingCount = Rating::where('ratee_id', $userId)->count();
        $ratingAvg = $ratingCount ? round((float) Rating::where('ratee_id', $userId)->avg('stars'), 2) : null;

        return [
            'score' => $score,
            'band' => self::band($score, $hasHistory),
            'payment_reliability' => $reliability, // null when no invoices yet
            'invoices_total' => $totalInvoices,
            'invoices_paid' => $paidInvoices,
            'pockets_joined' => $pocketsJoined,
            'adashis_joined' => $adashisJoined,
            'cycles_completed' => $cyclesCompleted,
            'rating_average' => $ratingAvg, // peer trust rating (null if none)
            'rating_count' => $ratingCount,
        ];
    }

    /**
     * Pure scoring: reliability worth up to 70, activity up to 30.
     */
    public static function score(int $reliabilityPercent, int $activityCount): int
    {
        $reliabilityPercent = max(0, min(100, $reliabilityPercent));
        $rel = (int) round($reliabilityPercent * 0.7);
        $act = min($activityCount * 3, 30);

        return $rel + $act;
    }

    public static function band(int $score, bool $hasHistory): string
    {
        if (!$hasHistory) {
            return 'New';
        }
        if ($score >= 80) {
            return 'Gold';
        }
        if ($score >= 60) {
            return 'Silver';
        }
        if ($score >= 40) {
            return 'Bronze';
        }

        return 'Building';
    }
}
