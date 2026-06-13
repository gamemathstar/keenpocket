<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Pocket;
use App\Models\PocketGuarantor;
use App\Models\PocketItem;
use App\Models\PocketSlot;
use App\Models\User;
use App\Support\PhoneNumber;
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
        $hasPending = $members->where('user_id', $user->id)->where('status', 0)->isNotEmpty();
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
            ->selectRaw('users.name as name, COUNT(*) as total') // count of contributions, not amount
            ->orderByDesc('total')->limit(10)->get();

        // Charity drive (Sadaqah) — privacy-aware summary for this viewer.
        $charity = null;
        $charitySvc = app(\App\Services\Charity\CharityService::class);
        if ($charitySvc->enabled() && $pocket->charity_enabled) {
            if ($project = $charitySvc->activeProject($pocket)) {
                $charity = $charitySvc->summary($pocket, $project, $user, $isOwner);
            }
        }
        $charityEnabled = $charitySvc->enabled();

        // Admin rating (members rate the pocket admin).
        $adminRating = app(\App\Services\Rating\RatingService::class)->averageFor($pocket->user_id);
        $myRating = \App\Models\Rating::where([
            'rater_id' => $user->id, 'context_type' => 'pocket', 'context_id' => $pocket->id,
        ])->value('stars');

        return view('pockets.show', compact('pocket', 'members', 'isMember', 'hasPending', 'isOwner', 'invoices', 'owner', 'walletEnabled', 'shoppingItems', 'contributed', 'target', 'progress', 'contributors', 'charity', 'charityEnabled', 'adminRating', 'myRating'));
    }

    /** A member rates the pocket admin (1–5 stars). */
    public function rateAdmin(Request $request, $id)
    {
        $data = $request->validate([
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $result = app(\App\Services\Rating\RatingService::class)
            ->submit($request->user(), 'pocket', (int) $id, (int) $data['stars'], $data['comment'] ?? null);

        if (!$result['ok']) {
            return back()->withErrors(['rating' => $result['message'] ?? 'Could not save your rating.']);
        }

        return back()->with('status', 'Thanks for rating the admin.');
    }

    public function join(Request $request, $id)
    {
        $user = auth()->user();
        $pocket = Pocket::findOrFail($id);
        $isOwner = $pocket->user_id == $user->id;

        if (PocketSlot::where(['pocket_id' => $pocket->id, 'user_id' => $user->id])->exists()) {
            return back()->with('status', 'You already have a slot or pending request in this pocket.');
        }
        // Closed (invite-only) pockets can't be joined by request — admin must invite.
        if (!$pocket->status && !$isOwner) {
            return back()->withErrors(['hand_count' => 'This pocket is invitation-only — ask the admin to invite you.']);
        }

        $guarantorRequired = config('guarantor.enabled', true) && $pocket->guarantor_required && !$isOwner;
        $rules = ['hand_count' => 'required|integer|min:1'];
        if ($guarantorRequired) {
            $rules['guarantor_contact'] = 'required|string|max:255';
        }
        $data = $request->validate($rules);

        // Resolve the guarantor (must be an existing user, not self / not the admin).
        $guarantor = null;
        if ($guarantorRequired) {
            $contact = trim($data['guarantor_contact']);
            $guarantor = User::where('email', $contact)->first()
                ?? User::where('phone_number', 'LIKE', '%'.PhoneNumber::normalize($contact).'%')->first();
            if (!$guarantor) {
                return back()->withErrors(['guarantor_contact' => 'No KeenPocket user found with that phone or email.'])->withInput();
            }
            if ($guarantor->id == $user->id || $guarantor->id == $pocket->user_id) {
                return back()->withErrors(['guarantor_contact' => 'Choose a guarantor other than yourself or the admin.'])->withInput();
            }
        }

        // Owner is active immediately; everyone else creates a pending request.
        $status = $isOwner ? 1 : 0;

        DB::transaction(function () use ($pocket, $user, $data, $status, $guarantor) {
            $slot = new PocketSlot();
            $slot->pocket_id = $pocket->id;
            $slot->user_id = $user->id;
            $slot->hand_count = $data['hand_count'];
            $slot->amount_paying = $data['hand_count'] * $pocket->amount_per_hand;
            $slot->status = $status;
            $slot->comment = '';
            $slot->save();

            if ($guarantor) {
                PocketGuarantor::create([
                    'pocket_id' => $pocket->id, 'slot_id' => $slot->id,
                    'requester_id' => $user->id, 'guarantor_id' => $guarantor->id, 'status' => 'PENDING',
                ]);
            }
        });

        if ($isOwner) {
            return redirect()->route('pockets.show', $pocket->id)->with('status', 'You joined this pocket.')->with('celebrate', true);
        }
        if ($guarantor) {
            return back()->with('status', 'Request sent — '.$guarantor->name.' must recommend you before the admin can accept.');
        }

        return back()->with('status', 'Join request sent to the admin.');
    }

    /** Owner accepts a pending join request (requires guarantor recommendation if enabled). */
    public function acceptMember(Request $request, $id)
    {
        $pocket = Pocket::findOrFail($id);
        abort_unless($pocket->user_id == auth()->id(), 403, 'Only the pocket owner can accept members.');

        $slot = PocketSlot::where('pocket_id', $pocket->id)->findOrFail($request->slot_id);

        if (config('guarantor.enabled', true) && $pocket->guarantor_required) {
            $g = PocketGuarantor::where(['pocket_id' => $pocket->id, 'slot_id' => $slot->id])->latest('id')->first();
            if (!$g || $g->status !== 'RECOMMENDED') {
                return back()->withErrors(['accept' => 'This request still needs a guarantor recommendation.']);
            }
        }

        $slot->status = 1;
        $slot->save();

        try {
            app(\App\Services\Referral\ReferralService::class)->qualifyQuietly(User::find($slot->user_id));
        } catch (\Throwable $e) {
        }
        try {
            \App\Models\Notification::acceptNotification($pocket->user_id ? User::find($pocket->user_id) : null, User::find($slot->user_id), $pocket);
        } catch (\Throwable $e) {
        }

        return back()->with('status', 'Member accepted.')->with('celebrate', true);
    }

    /** Owner declines a pending join request. */
    public function declineMember(Request $request, $id)
    {
        $pocket = Pocket::findOrFail($id);
        abort_unless($pocket->user_id == auth()->id(), 403, 'Only the pocket owner can decline requests.');

        $slot = PocketSlot::where(['pocket_id' => $pocket->id, 'status' => 0])->findOrFail($request->slot_id);

        DB::transaction(function () use ($pocket, $slot) {
            PocketGuarantor::where(['pocket_id' => $pocket->id, 'slot_id' => $slot->id])->delete();
            $slot->delete();
        });

        return back()->with('status', 'Request declined.');
    }

    /** Owner toggles whether join requests need a guarantor recommendation. */
    public function toggleGuarantor($id)
    {
        $pocket = Pocket::findOrFail($id);
        abort_unless($pocket->user_id == auth()->id(), 403, 'Only the pocket owner can do this.');
        $pocket->guarantor_required = !$pocket->guarantor_required;
        $pocket->save();

        return back()->with('status', $pocket->guarantor_required
            ? 'New members must now be vouched for by a guarantor.'
            : 'Guarantor requirement turned off.');
    }

    /** Owner updates the pocket's collection account details. */
    public function saveBankDetails(Request $request, $id)
    {
        $pocket = Pocket::findOrFail($id);
        abort_unless($pocket->user_id == auth()->id(), 403, 'Only the pocket owner can edit account details.');

        $data = $request->validate([
            'account_name' => 'nullable|string|max:255',
            'bank' => 'nullable|string|max:255',
            'nuban' => 'nullable|string|max:32',
        ]);
        $pocket->account_name = $data['account_name'] ?? null;
        $pocket->bank = $data['bank'] ?? null;
        $pocket->nuban = $data['nuban'] ?? null;
        $pocket->save();

        return back()->with('status', 'Account details updated.');
    }

    /** Owner toggles whether members may suggest shopping-list items. */
    public function toggleSelection($id)
    {
        $pocket = Pocket::findOrFail($id);
        abort_unless($pocket->user_id == auth()->id(), 403, 'Only the pocket owner can do this.');
        $pocket->open_purchasing_item = !$pocket->open_purchasing_item;
        $pocket->save();

        return back()->with('status', $pocket->open_purchasing_item
            ? 'Members can now suggest shopping items.'
            : 'Shopping suggestions are now closed.');
    }

    /** Owner: manage members + open/close. */
    public function manage($id)
    {
        $pocket = Pocket::findOrFail($id);
        abort_unless($pocket->user_id == auth()->id(), 403, 'Only the pocket owner can manage it.');

        $members = PocketSlot::query()
            ->join('users', 'users.id', '=', 'pocket_slots.user_id')
            ->where('pocket_slots.pocket_id', $pocket->id)->where('pocket_slots.status', 1)
            ->select(['pocket_slots.hand_count', 'pocket_slots.status', 'users.name', 'users.phone_number'])
            ->orderBy('users.name')->get();

        // Pending join requests (status 0), with their guarantor recommendation state.
        $requests = PocketSlot::query()
            ->join('users', 'users.id', '=', 'pocket_slots.user_id')
            ->where('pocket_slots.pocket_id', $pocket->id)->where('pocket_slots.status', 0)
            ->select(['pocket_slots.id as slot_id', 'pocket_slots.hand_count', 'users.name', 'users.phone_number'])
            ->orderBy('pocket_slots.id')->get();

        $guarantors = PocketGuarantor::where('pocket_id', $pocket->id)
            ->whereIn('slot_id', $requests->pluck('slot_id'))
            ->get()->keyBy('slot_id');

        return view('pockets.manage', compact('pocket', 'members', 'requests', 'guarantors'));
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
