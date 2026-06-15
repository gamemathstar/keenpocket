<?php

namespace App\Services\Coins;

use App\Models\KeenTransaction;
use App\Models\Setting;
use App\Models\User;

/**
 * Keens — the in-app coin. Creating (or cloning) a pocket / adashi / school can
 * cost Keens, configured by the super admin. Pricing is tiered by capacity:
 * a base cost covers up to a tier of participants, scaling up beyond it.
 */
class CoinService
{
    public function enabled(): bool
    {
        return (bool) Setting::get('coins_enabled', '0');
    }

    public function costPocket(): int { return $this->base('pocket'); }
    public function costAdashi(): int { return $this->base('adashi'); }
    public function costSchool(): int { return $this->base('school'); }

    /** Built-in defaults per type: [base, tier size (participants), step per extra tier]. */
    private const DEFAULTS = [
        'pocket' => ['base' => 50,  'tier' => 50,  'step' => 50],
        'adashi' => ['base' => 50,  'tier' => 12,  'step' => 40],
        'school' => ['base' => 100, 'tier' => 100, 'step' => 100],
    ];

    /** Base cost (covers the first tier of participants). */
    public function base(string $type): int
    {
        return (int) Setting::get("cost_{$type}", self::DEFAULTS[$type]['base'] ?? 0);
    }

    /** Tier size — how many participants each pricing tier covers. */
    public function tierSize(string $type): int
    {
        return max(1, (int) Setting::get("{$type}_tier", self::DEFAULTS[$type]['tier'] ?? 50));
    }

    /** Extra Keens added for each tier beyond the first. */
    public function tierStep(string $type): int
    {
        return (int) Setting::get("{$type}_step", self::DEFAULTS[$type]['step'] ?? $this->base($type));
    }

    /**
     * Cost to create/clone for a given participant capacity, using tiered pricing:
     * base covers up to one tier; each further tier adds `step`.
     * e.g. adashi base 50, tier 12, step 40 → ≤12:50, ≤24:90, ≤36:130 …
     */
    public function cost(string $type, int $capacity = 0): int
    {
        if (!$this->enabled() || !isset(self::DEFAULTS[$type])) {
            return 0;
        }
        $units = $capacity <= 0 ? 1 : (int) ceil($capacity / $this->tierSize($type));

        return max(0, $this->base($type) + $this->tierStep($type) * ($units - 1));
    }

    /** A few tier rows for previewing the pricing in the UI: [['upTo' => 12, 'cost' => 50], …]. */
    public function tierPreview(string $type, int $rows = 4): array
    {
        $tier = $this->tierSize($type);
        $out = [];
        for ($i = 1; $i <= $rows; $i++) {
            $out[] = ['upTo' => $tier * $i, 'cost' => $this->base($type) + $this->tierStep($type) * ($i - 1)];
        }

        return $out;
    }

    public function balance(User $user): int
    {
        return (int) ($user->keens ?? 0);
    }

    /** Super admins create for free; otherwise the balance must cover the cost. */
    public function canAfford(User $user, int $amount): bool
    {
        return $user->isSuperAdmin() || $this->balance($user) >= $amount;
    }

    /** Deduct Keens (no-op for super admins / zero cost). Returns false if too poor. */
    public function charge(User $user, int $amount, string $reason): bool
    {
        if ($amount <= 0 || $user->isSuperAdmin()) {
            return true;
        }
        if ($this->balance($user) < $amount) {
            return false;
        }
        $user->keens = $this->balance($user) - $amount;
        $user->save();
        KeenTransaction::create(['user_id' => $user->id, 'amount' => -$amount, 'reason' => $reason]);

        return true;
    }

    public function grant(User $user, int $amount, string $reason): void
    {
        $user->keens = $this->balance($user) + $amount;
        $user->save();
        KeenTransaction::create(['user_id' => $user->id, 'amount' => $amount, 'reason' => $reason]);
    }
}
