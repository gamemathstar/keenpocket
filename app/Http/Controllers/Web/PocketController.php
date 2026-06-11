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
use Illuminate\Support\Str;

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
        $shoppingItems = \App\Models\ShoppingItem::where('pocket_id', $pocket->id)->orderBy('id')->get();

        // The viewing member's contribution progress toward their target.
        $mySlot = PocketSlot::where(['pocket_id' => $pocket->id, 'user_id' => $user->id, 'status' => 1])->first();
        $contributed = (int) $invoices->where('payment_status', 'Paid')->sum('amount');
        $target = $mySlot ? (int) $mySlot->hand_count * (int) $pocket->month_count * (int) $pocket->amount_per_hand : 0;
        $progress = $target > 0 ? (int) round($contributed / $target * 100) : 0;

        // Per-pocket top contributors.
        $contributors = Invoice::query()
            ->where('invoices.payment_status', 'Paid')
            ->join('pocket_slots', 'pocket_slots.id', '=', 'invoices.pocket_slot_id')
            ->join('users', 'users.id', '=', 'pocket_slots.user_id')
            ->where('pocket_slots.pocket_id', $pocket->id)
            ->groupBy('pocket_slots.user_id', 'users.name')
            ->selectRaw('users.name as name, SUM(invoices.amount) as total')
            ->orderByDesc('total')->limit(10)->get();

        return view('pockets.show', compact('pocket', 'members', 'isMember', 'isOwner', 'invoices', 'owner', 'walletEnabled', 'shoppingItems', 'contributed', 'target', 'progress', 'contributors'));
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

        return redirect()->route('pockets.show', $pocket->id)->with('status', 'You joined this pocket.')->with('celebrate', true);
    }

    /** Owner: manage members + open/close. */
    public function manage($id)
    {
        $pocket = Pocket::findOrFail($id);
        abort_unless($pocket->user_id == auth()->id(), 403, 'Only the pocket owner can manage it.');

        $members = PocketSlot::query()
            ->join('users', 'users.id', '=', 'pocket_slots.user_id')
            ->where('pocket_slots.pocket_id', $pocket->id)
            ->select(['pocket_slots.hand_count', 'pocket_slots.status', 'users.name', 'users.phone_number'])
            ->orderByDesc('pocket_slots.status')->get();

        return view('pockets.manage', compact('pocket', 'members'));
    }

    /** Owner adds a member by phone (creating a placeholder account if needed). */
    public function addMember(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:20',
            'hand_count' => 'required|integer|min:1',
        ]);

        $pocket = Pocket::findOrFail($id);
        abort_unless($pocket->user_id == auth()->id(), 403, 'Only the pocket owner can add members.');

        $user = User::where('phone_number', $data['phone_number'])->first();
        if (!$user) {
            if (empty($data['name'])) {
                return back()->withErrors(['name' => 'Enter a name for the new member (not on KeenPocket yet).'])->withInput();
            }
            $user = User::create([
                'name' => $data['name'], 'email' => $data['phone_number'],
                'username' => $data['phone_number'], 'phone_number' => $data['phone_number'],
                'password' => bcrypt(Str::random(16)),
            ]);
        }

        if (PocketSlot::where(['pocket_id' => $pocket->id, 'user_id' => $user->id])->exists()) {
            return back()->with('status', $user->name.' is already a member.');
        }

        $slot = new PocketSlot();
        $slot->pocket_id = $pocket->id;
        $slot->user_id = $user->id;
        $slot->hand_count = $data['hand_count'];
        $slot->amount_paying = $data['hand_count'] * $pocket->amount_per_hand;
        $slot->status = 1;
        $slot->comment = '';
        $slot->save();

        return redirect()->route('pockets.manage', $pocket->id)->with('status', $user->name.' added to the pocket.');
    }

    /** Owner exports all of the pocket's invoices as CSV. */
    public function exportInvoices($id)
    {
        $pocket = Pocket::findOrFail($id);
        abort_unless($pocket->user_id == auth()->id(), 403, 'Only the pocket owner can export invoices.');

        $rows = Invoice::query()
            ->join('pocket_slots', 'pocket_slots.id', '=', 'invoices.pocket_slot_id')
            ->join('users', 'users.id', '=', 'pocket_slots.user_id')
            ->where('pocket_slots.pocket_id', $pocket->id)
            ->orderByDesc('invoices.id')
            ->get(['invoices.invoice_no', 'users.name', 'invoices.amount', 'invoices.payment_status', 'invoices.paid_through', 'invoices.payment_date']);

        $filename = 'pocket-'.$pocket->id.'-invoices.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice No', 'Member', 'Amount', 'Status', 'Paid Through', 'Payment Date']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->invoice_no, $r->name, $r->amount, $r->payment_status, $r->paid_through, $r->payment_date]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Owner toggles the pocket open/closed (open = others can join). */
    public function toggleStatus($id)
    {
        $pocket = Pocket::findOrFail($id);
        abort_unless($pocket->user_id == auth()->id(), 403);

        $pocket->status = $pocket->status ? 0 : 1;
        $pocket->save();

        return back()->with('status', 'Pocket is now '.($pocket->status ? 'open to join' : 'closed').'.');
    }
}
