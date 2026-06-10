<?php

namespace App\Http\Controllers;

use App\Models\Adashi;
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

        // Trust gate: when KYC is enabled, only surface verified organizers.
        if (config('kyc.enabled', false) && config('kyc.gate_directory', true)) {
            $query->where('users.kyc_status', 'verified');
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

    /**
     * Public directory of adashis open to join (`is_public`, status ACTIVE).
     * Adashi has no member cap, so there is no "full" filter. KYC-gated on the
     * admin when enabled. An optional `q` filters by name.
     */
    public function adashi(Request $request)
    {
        if (!config('discovery.enabled', true)) {
            return response(['enabled' => false], 200);
        }

        $query = Adashi::query()
            ->where('adashis.is_public', true)
            ->where('adashis.status', 'ACTIVE')
            ->join('users', 'users.id', '=', 'adashis.admin_id')
            ->select([
                'adashis.id', 'adashis.name', 'adashis.amount_per_cycle',
                'adashis.cycle_duration_days', 'adashis.total_members',
                'adashis.current_cycle_number', 'adashis.rotation_mode',
                'adashis.admin_id', 'users.name as admin', 'users.phone_number',
            ])
            ->orderByDesc('adashis.id');

        if ($term = trim((string) $request->query('q', ''))) {
            $query->where('adashis.name', 'LIKE', '%'.$term.'%');
        }

        if (config('kyc.enabled', false) && config('kyc.gate_directory', true)) {
            $query->where('users.kyc_status', 'verified');
        }

        $page = $query->paginate((int) config('discovery.per_page', 20));

        $page->getCollection()->transform(function ($a) {
            $a->admin_id_ref = $a->admin_id;
            $a->admin_phone = $this->maskPhone($a->phone_number);
            unset($a->phone_number, $a->admin_id);

            return $a;
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
