<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Pocket;
use App\Models\PocketItem;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PocketController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $memberOf = Pocket::query()
            ->join('pocket_slots', 'pocket_slots.pocket_id', '=', 'pockets.id')
            ->where('pocket_slots.user_id', $user->id)->where('pocket_slots.status', 1)
            ->select('pockets.*')->distinct()->orderByDesc('pockets.id')->get();

        $owned = Pocket::where('user_id', $user->id)->orderByDesc('id')->get();

        return view('pockets.index', compact('memberOf', 'owned'));
    }

    public function create()
    {
        return view('pockets.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'pocket_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'year' => 'required|integer|min:2020|max:2100',
            'start_month' => 'required|integer|min:1|max:12',
            'month_count' => 'required|integer|min:1|max:60',
            'max_keens' => 'required|integer|min:0',
            'amount_per_hand' => 'required|integer|min:1',
            'hand_count' => 'required|integer|min:1',
        ]);

        $user = auth()->user();

        $pocket = DB::transaction(function () use ($data, $user) {
            $pocket = new Pocket();
            $pocket->user_id = $user->id;
            $pocket->title = $data['title'];
            $pocket->pocket_type = $data['pocket_type'];
            $pocket->description = $data['description'] ?? '';
            $pocket->year = $data['year'];
            $pocket->start_month = $data['start_month'];
            $pocket->month_count = $data['month_count'];
            $pocket->max_keens = $data['max_keens'];
            $pocket->amount_per_hand = $data['amount_per_hand'];
            $pocket->per_hand_allowed = (string) $data['amount_per_hand'];
            $pocket->status = 1;
            $pocket->save();

            $item = new PocketItem();
            $item->pocket_id = $pocket->id;
            $item->item_id = 1;
            $item->save();

            $slot = new PocketSlot();
            $slot->pocket_id = $pocket->id;
            $slot->user_id = $user->id;
            $slot->hand_count = $data['hand_count'];
            $slot->amount_paying = $data['amount_per_hand'] * $data['hand_count'];
            $slot->status = 1;
            $slot->comment = '';
            $slot->save();

            return $pocket;
        });

        return redirect()->route('pockets.show', $pocket->id)->with('status', 'Pocket created.');
    }

    public function show($id)
    {
        $pocket = Pocket::findOrFail($id);
        $user = auth()->user();

        $members = PocketSlot::query()
            ->join('users', 'users.id', '=', 'pocket_slots.user_id')
            ->where('pocket_slots.pocket_id', $pocket->id)
            ->select(['pocket_slots.id', 'pocket_slots.hand_count', 'pocket_slots.status', 'users.name', 'users.id as user_id'])
            ->get();

        $isMember = $members->where('user_id', $user->id)->where('status', 1)->isNotEmpty();
        $isOwner = $pocket->user_id == $user->id;

        $invoices = Invoice::query()
            ->join('pocket_slots', 'pocket_slots.id', '=', 'invoices.pocket_slot_id')
            ->where('pocket_slots.pocket_id', $pocket->id)
            ->where('pocket_slots.user_id', $user->id)
            ->select('invoices.*')->orderByDesc('invoices.id')->get();

        $owner = User::find($pocket->user_id);
        $walletEnabled = app(\App\Services\Wallet\WalletService::class)->enabled();

        return view('pockets.show', compact('pocket', 'members', 'isMember', 'isOwner', 'invoices', 'owner', 'walletEnabled'));
    }

    public function join(Request $request, $id)
    {
        $data = $request->validate(['hand_count' => 'required|integer|min:1']);
        $user = auth()->user();
        $pocket = Pocket::findOrFail($id);

        if (PocketSlot::where(['pocket_id' => $pocket->id, 'user_id' => $user->id])->exists()) {
            return back()->with('status', 'You are already a member of this pocket.');
        }
        if (!$pocket->status && $pocket->user_id != $user->id) {
            return back()->withErrors(['hand_count' => 'This pocket is invitation-only.']);
        }

        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $user->id;
        $slot->hand_count = $data['hand_count'];
        $slot->amount_paying = $data['hand_count'] * $pocket->amount_per_hand;
        $slot->status = 1;
        $slot->comment = '';
        $slot->save();

        try {
            app(\App\Services\Referral\ReferralService::class)->qualifyQuietly($user);
        } catch (\Throwable $e) {
        }

        return redirect()->route('pockets.show', $pocket->id)->with('status', 'You joined this pocket.');
    }
}
