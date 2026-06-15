<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Setting;
use App\Models\User;
use App\Services\Coins\CoinService;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    private function guard(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Super admins only.');
    }

    public function index(Request $request)
    {
        $this->guard();
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q, fn ($w) => $w->where('name', 'LIKE', "%$q%")->orWhere('email', 'LIKE', "%$q%")->orWhere('phone_number', 'LIKE', "%$q%"))
            ->orderByDesc('can_create_school')->orderBy('name')->limit(100)->get();

        $schools = School::query()
            ->join('users', 'users.id', '=', 'schools.owner_id')
            ->select('schools.id', 'schools.name', 'users.name as owner')
            ->orderByDesc('schools.id')->get();

        $coins = app(CoinService::class);
        $coinCfg = ['enabled' => $coins->enabled(), 'types' => []];
        foreach (['pocket' => 'hands', 'adashi' => 'members', 'school' => 'students'] as $type => $unit) {
            $coinCfg['types'][$type] = [
                'base' => $coins->base($type),
                'tier' => $coins->tierSize($type),
                'step' => $coins->tierStep($type),
                'unit' => $unit,
                'preview' => $coins->tierPreview($type),
            ];
        }

        return view('super-admin', compact('users', 'schools', 'q', 'coinCfg'));
    }

    public function saveCoins(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'cost_pocket' => 'required|integer|min:0',
            'cost_adashi' => 'required|integer|min:0',
            'cost_school' => 'required|integer|min:0',
            'pocket_tier' => 'nullable|integer|min:1',
            'adashi_tier' => 'nullable|integer|min:1',
            'school_tier' => 'nullable|integer|min:1',
            'pocket_step' => 'nullable|integer|min:0',
            'adashi_step' => 'nullable|integer|min:0',
            'school_step' => 'nullable|integer|min:0',
        ]);
        Setting::set('coins_enabled', $request->boolean('coins_enabled') ? '1' : '0');
        foreach (['pocket', 'adashi', 'school'] as $type) {
            Setting::set("cost_{$type}", $data["cost_{$type}"]);
            // Tier size & step are optional — only overwrite when provided.
            if (isset($data["{$type}_tier"])) {
                Setting::set("{$type}_tier", $data["{$type}_tier"]);
            }
            if (isset($data["{$type}_step"])) {
                Setting::set("{$type}_step", $data["{$type}_step"]);
            }
        }

        return back()->with('status', 'Coin settings saved.');
    }

    /** Grant Keens to a user (top-up), found by phone / email / username. */
    public function grantKeens(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'contact' => 'required|string|max:255',
            'amount' => 'required|integer|min:1|max:1000000',
        ]);
        $c = trim($data['contact']);
        $user = User::where('phone_number', $c)->orWhere('email', $c)->orWhere('username', $c)->first();
        if (!$user) {
            return back()->withErrors(['contact' => 'No user found with that phone, email or username.']);
        }

        app(CoinService::class)->grant($user, (int) $data['amount'], 'Super-admin top-up');

        return back()->with('status', "Granted {$data['amount']} Keens to {$user->name} (new balance {$user->fresh()->keens}).");
    }

    public function grant($id)
    {
        $this->guard();
        $u = User::findOrFail($id);
        $u->can_create_school = true;
        $u->save();

        return back()->with('status', $u->name.' can now create a school.');
    }

    public function revoke($id)
    {
        $this->guard();
        $u = User::findOrFail($id);
        $u->can_create_school = false;
        $u->save();

        return back()->with('status', 'School access revoked for '.$u->name.'.');
    }
}
