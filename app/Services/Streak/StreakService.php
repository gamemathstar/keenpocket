<?php

namespace App\Services\Streak;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Weekly contribution streak with auto-consumed streak-freezes.
 *
 * The streak is computed on the fly from the weeks a user contributed (ISO
 * "oW" keys). A missed week breaks the streak — unless the user has a freeze,
 * which is automatically spent to bridge that single week. Spent freezes are
 * persisted (streak_frozen_weeks) so the bridge is permanent and idempotent.
 */
class StreakService
{
    /**
     * @param  array<int,string>  $activeWeeks  ISO "oW" keys the user contributed in
     * @return array{streak:int, this_week_met:bool, freezes:int}
     */
    public function evaluate(User $user, array $activeWeeks): array
    {
        $active = array_flip($activeWeeks);
        $frozen = array_flip($user->streak_frozen_weeks ?? []);
        $freezes = (int) ($user->streak_freezes ?? 0);
        $covered = $active + $frozen;

        $now = Carbon::now();
        $thisWeekMet = isset($active[$now->format('oW')]);

        $cursor = $now->copy()->startOfWeek();
        // The in-progress week not yet met doesn't break the streak.
        if (!isset($covered[$cursor->format('oW')])) {
            $cursor->subWeek();
        }

        $streak = 0;
        $newlyFrozen = [];
        while (true) {
            $key = $cursor->format('oW');
            if (isset($covered[$key])) {
                $streak++;
                $cursor->subWeek();
                continue;
            }
            // Gap: bridge it with a freeze only if the streak continues earlier.
            if ($freezes > 0 && $this->hasOlderActiveWeek($active, $key)) {
                $freezes--;
                $newlyFrozen[] = $key;
                $covered[$key] = true;
                $streak++;
                $cursor->subWeek();
                continue;
            }
            break;
        }

        if ($newlyFrozen) {
            $user->streak_freezes = $freezes;
            $user->streak_frozen_weeks = array_values(array_unique(array_merge($user->streak_frozen_weeks ?? [], $newlyFrozen)));
            $user->save();
        }

        return ['streak' => $streak, 'this_week_met' => $thisWeekMet, 'freezes' => $freezes];
    }

    private function hasOlderActiveWeek(array $active, string $cursorKey): bool
    {
        foreach (array_keys($active) as $wk) {
            if ((string) $wk < $cursorKey) {
                return true;
            }
        }

        return false;
    }
}
