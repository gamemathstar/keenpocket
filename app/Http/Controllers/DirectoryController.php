<?php

namespace App\Http\Controllers;

use App\Models\Pocket;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    /**
     * Public directory of pockets open to join: not invitation-only
     * (`status` = 1) and with slots still available. `max_keens = 0` means no
     * cap. An optional `q` filters by title.
     *
     * Organizer reputation is intentionally NOT inlined per row (it would be
     * N+1); fetch it from GET /users/{id}/reputation when viewing a listing.
     */
    public function pockets(Request $request)
    {
        if (!config('discovery.enabled', true)) {
            return response(['enabled' => false], 200);
        }

        $perPage = (int) config('discovery.per_page', 20);

        // Correlated subquery for active slots taken — portable across sqlite/MySQL.
        $usedSql = '(SELECT COALESCE(SUM(hand_count),0) FROM pocket_slots '
            .'WHERE pocket_slots.pocket_id = pockets.id AND pocket_slots.status = 1)';

        $query = Pocket::query()
            ->where('pockets.status', 1)
            ->join('users', 'users.id', '=', 'pockets.user_id')
            ->where(function ($q) use ($usedSql) {
                $q->where('pockets.max_keens', 0)
                    ->orWhereRaw("$usedSql < pockets.max_keens");
            })
            ->select([
                'pockets.id', 'pockets.title', 'pockets.pocket_type', 'pockets.description',
                'pockets.amount_per_hand', 'pockets.max_keens', 'pockets.year',
                'pockets.start_month', 'pockets.month_count',
                'pockets.user_id', 'users.name as organizer', 'users.phone_number',
            ])
            ->selectRaw("$usedSql as slots_used")
            ->orderByDesc('pockets.id');

        if ($term = trim((string) $request->query('q', ''))) {
            $query->where('pockets.title', 'LIKE', '%'.$term.'%');
        }

        $page = $query->paginate($perPage);

        $page->getCollection()->transform(function ($p) {
            $used = (int) $p->slots_used;
            $p->slots_used = $used;
            $p->slots_available = $p->max_keens ? max(0, $p->max_keens - $used) : null; // null = uncapped
            $p->organizer_id = $p->user_id;
            $p->organizer_phone = $this->maskPhone($p->phone_number);
            unset($p->phone_number, $p->user_id);

            return $p;
        });

        return response($page);
    }

    private function maskPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }
        $len = strlen($phone);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return substr($phone, 0, 3).str_repeat('*', max(0, $len - 6)).substr($phone, -3);
    }
}
