<?php

namespace App\Services\Gamification;

use App\Models\AdashiMember;
use App\Models\Invoice;
use App\Models\PocketSlot;
use App\Models\Referral;
use App\Models\User;
use App\Services\Reputation\ReputationService;

/**
 * Streaks + achievement badges, computed from existing activity (no new state).
 * `evaluateBadges()` is pure (metrics in, badges out) so it unit-tests without
 * a database; gathering the metrics is the only DB-touching part.
 */
class GamificationService
{
    public function __construct(private ReputationService $reputation)
    {
    }

    public function enabled(): bool
    {
        return (bool) config('gamification.enabled', true);
    }

    public function profileFor($userId): array
    {
        $userId = is_object($userId) ? $userId->id : $userId;
        $metrics = $this->gatherMetrics($userId);

        return [
            'streak' => $metrics['payment_streak'],
            'total_contributed' => $metrics['total_contributed'],
            'badges' => self::evaluateBadges($metrics),
            'metrics' => $metrics,
        ];
    }

    /**
     * Just the earned badges for a user (for public profiles).
     */
    public function badgesFor($userId): array
    {
        $userId = is_object($userId) ? $userId->id : $userId;
        $earned = array_filter(self::evaluateBadges($this->gatherMetrics($userId)), fn ($b) => $b['earned']);

        return array_values($earned);
    }

    private function gatherMetrics(int $userId): array
    {
        $rep = $this->reputation->forUser($userId);

        $invoices = $this->userInvoices($userId);
        $totalContributed = (int) (clone $invoices)->where('payment_status', 'Paid')->sum('amount');

        $referralsQualified = Referral::where('referrer_id', $userId)
            ->whereIn('status', ['qualified', 'rewarded'])->count();

        $kycVerified = (User::find($userId)?->kyc_status) === 'verified';

        return [
            'pockets_joined' => $rep['pockets_joined'],
            'adashis_joined' => $rep['adashis_joined'],
            'cycles_completed' => $rep['cycles_completed'],
            'payment_reliability' => $rep['payment_reliability'] ?? 0,
            'invoices_total' => $rep['invoices_total'],
            'rating_average' => $rep['rating_average'] ?? 0,
            'rating_count' => $rep['rating_count'],
            'referrals_qualified' => $referralsQualified,
            'kyc_verified' => $kycVerified,
            'total_contributed' => $totalContributed,
            'payment_streak' => $this->currentStreak($userId),
        ];
    }

    /**
     * Current run of consecutive Paid invoices (most recent backwards).
     */
    private function currentStreak(int $userId): int
    {
        $statuses = $this->userInvoices($userId)->orderByDesc('id')->pluck('payment_status');
        $streak = 0;
        foreach ($statuses as $status) {
            if ($status === 'Paid') {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    private function userInvoices(int $userId)
    {
        $slotIds = PocketSlot::where('user_id', $userId)->pluck('id')->all();
        $memberIds = AdashiMember::where('user_id', $userId)->pluck('id')->all();

        return Invoice::where(function ($q) use ($slotIds, $memberIds) {
            $q->whereIn('pocket_slot_id', $slotIds)->orWhereIn('adashi_member_id', $memberIds);
        });
    }

    /**
     * Pure badge evaluation from a metrics array. Returns every badge with an
     * `earned` flag (so clients can show locked + unlocked).
     */
    public static function evaluateBadges(array $m): array
    {
        $t = config('gamification.thresholds');

        $defs = [
            ['slug' => 'first_pocket', 'label' => 'First Pocket', 'description' => 'Joined your first pocket',
                'earned' => ($m['pockets_joined'] ?? 0) >= 1],
            ['slug' => 'adashi_member', 'label' => 'Adashi Member', 'description' => 'Joined an adashi',
                'earned' => ($m['adashis_joined'] ?? 0) >= 1],
            ['slug' => 'reliable_payer', 'label' => 'Reliable Payer', 'description' => 'Consistently pays on time',
                'earned' => ($m['invoices_total'] ?? 0) >= $t['reliable_payer']['min_invoices']
                    && ($m['payment_reliability'] ?? 0) >= $t['reliable_payer']['min_reliability']],
            ['slug' => 'cycle_champion', 'label' => 'Cycle Champion', 'description' => 'Completed an adashi payout cycle',
                'earned' => ($m['cycles_completed'] ?? 0) >= 1],
            ['slug' => 'top_organizer', 'label' => 'Top Organizer', 'description' => 'Highly rated as an organizer',
                'earned' => ($m['rating_count'] ?? 0) >= $t['top_organizer']['min_ratings']
                    && ($m['rating_average'] ?? 0) >= $t['top_organizer']['min_rating']],
            ['slug' => 'recruiter', 'label' => 'Recruiter', 'description' => 'Brought friends to KeenPocket',
                'earned' => ($m['referrals_qualified'] ?? 0) >= $t['recruiter']['min_referrals']],
            ['slug' => 'verified', 'label' => 'Verified', 'description' => 'Identity verified (KYC)',
                'earned' => (bool) ($m['kyc_verified'] ?? false)],
            ['slug' => 'big_saver', 'label' => 'Big Saver', 'description' => 'Contributed a significant amount',
                'earned' => ($m['total_contributed'] ?? 0) >= $t['big_saver']['min_contributed']],
        ];

        return $defs;
    }
}
