<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Adashi;
use App\Models\Pocket;
use Illuminate\Http\Request;

class DiscoverController extends Controller
{
    public function index(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $kycGate = config('kyc.enabled', false) && config('kyc.gate_directory', true);

        $usedSql = '(SELECT COALESCE(SUM(hand_count),0) FROM pocket_slots WHERE pocket_slots.pocket_id = pockets.id AND pocket_slots.status = 1)';
        $pockets = Pocket::query()
            ->where('pockets.status', 1)
            ->join('users', 'users.id', '=', 'pockets.user_id')
            ->where(fn ($q) => $q->where('pockets.max_keens', 0)->orWhereRaw("$usedSql < pockets.max_keens"))
            ->when($term, fn ($q) => $q->where('pockets.title', 'LIKE', "%$term%"))
            ->when($kycGate, fn ($q) => $q->where('users.kyc_status', 'verified'))
            ->select(['pockets.*', 'users.name as organizer'])
            ->orderByDesc('pockets.id')->limit(30)->get();

        $adashis = Adashi::query()
            ->where('adashis.is_public', true)->where('adashis.status', 'ACTIVE')
            ->join('users', 'users.id', '=', 'adashis.admin_id')
            ->when($term, fn ($q) => $q->where('adashis.name', 'LIKE', "%$term%"))
            ->when($kycGate, fn ($q) => $q->where('users.kyc_status', 'verified'))
            ->select(['adashis.*', 'users.name as admin'])
            ->orderByDesc('adashis.id')->limit(30)->get();

        return view('discover', compact('pockets', 'adashis', 'term'));
    }
}
