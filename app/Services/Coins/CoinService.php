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

    public function costPocket(): int { return (int) Setting::get('cost_pocket', 50); }
    public function costAdashi(): int { return (int) Setting::get('cost_adashi', 50); }
    public function costSchool(): int { return (int) Setting::get('cost_school', 100); }

    /** Cost to create/clone, given the participant capacity. */
    public function cost(string $type, int $capacity = 0): int
    {
        if (!$this->enabled()) {
            return 0;
        }
        // Tier sizes: 50 hands per unit (pocket/adashi), 100 students per unit (school).
        switch ($type) {
            case 'pocket': return (int) ceil(max(1, $capacity) / 50) * $this->costPocket();
            case 'adashi': return (int) ceil(max(1, $capacity) / 50) * $this->costAdashi();
            case 'school': return (int) ceil(max(1, $capacity ?: 1) / 100) * $this->costSchool();
        }

        return 0;
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
