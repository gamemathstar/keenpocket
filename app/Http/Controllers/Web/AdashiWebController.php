<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\AdashiRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdashiWebController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $adashis = Adashi::query()
            ->join('adashi_members', 'adashi_members.adashi_id', '=', 'adashis.id')
            ->where('adashi_members.user_id', $user->id)->where('adashi_members.is_active', 1)
            ->select('adashis.*')->distinct()->orderByDesc('adashis.id')->get();

        return view('adashi.index', compact('adashis'));
    }

    public function create()
    {
        $coins = app(\App\Services\Coins\CoinService::class);
        $keens = [
            'enabled' => $coins->enabled(),
            'balance' => $coins->balance(auth()->user()),
            'exempt' => auth()->user()->isSuperAdmin(),
            'base' => $coins->base('adashi'),
            'tier' => $coins->tierSize('adashi'),
            'step' => $coins->tierStep('adashi'),
        ];

        return view('adashi.create', compact('keens'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount_per_cycle' => 'required|integer|min:1',
            'cycle_duration_days' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'rotation_mode' => 'required|in:AUTO,MANUAL,auto,manual',
            'is_public' => 'nullable|boolean',
            'member_capacity' => 'nullable|integer|min:1|max:1000',
            'bank' => 'nullable|string|max:255',
            'nuban' => 'nullable|string|max:32',
            'account_name' => 'nullable|string|max:255',
            'accept_terms' => 'accepted',
        ], ['accept_terms.accepted' => 'Please confirm you understand and accept the terms.']);

        $user = auth()->user();
        $isPublic = $request->boolean('is_public');

        $coins = app(\App\Services\Coins\CoinService::class);
        // Cost is tiered by how many members the organiser expects.
        $cost = $coins->cost('adashi', (int) ($data['member_capacity'] ?? 1));
        if (!$coins->canAfford($user, $cost)) {
            return back()->withErrors(['name' => "This adashi costs {$cost} Keens — you have {$coins->balance($user)}."])->withInput();
        }

        $adashi = DB::transaction(function () use ($data, $user, $isPublic) {
            $adashi = Adashi::create([
                'name' => $data['name'],
                'amount_per_cycle' => $data['amount_per_cycle'],
                'total_members' => 1,
                'start_date' => $data['start_date'],
                'cycle_duration_days' => $data['cycle_duration_days'],
                'current_cycle_number' => 1,
                'admin_id' => $user->id,
                'rotation_mode' => strtoupper($data['rotation_mode']),
                'status' => 'ACTIVE',
                'is_public' => $isPublic,
                'bank' => $data['bank'] ?? null,
                'nuban' => $data['nuban'] ?? null,
                'account_name' => $data['account_name'] ?? null,
            ]);

            $member = AdashiMember::create([
                'adashi_id' => $adashi->id, 'user_id' => $user->id, 'position' => 1,
                'has_received' => false, 'joined_at' => now(), 'is_active' => true,
            ]);

            AdashiRecord::create([
                'adashi_id' => $adashi->id, 'cycle_number' => 1,
                'due_at' => Carbon::parse($adashi->start_date)->addDays($adashi->cycle_duration_days),
                'total_collected' => 0, 'receiver_user_id' => $user->id,
                'receiver_member_id' => $member->id, 'paid_members_count' => 0, 'status' => 'PENDING',
            ]);

            return $adashi;
        });

        $coins->charge($user, $cost, 'Create adashi: '.$adashi->name);

        return redirect()->route('adashi.show', $adashi->id)->with('status', 'Adashi created.'.($cost ? " ({$cost} Keens)" : ''));
    }

    /** Admin updates the adashi's collection account details. */
    public function saveBank(Request $request, $id)
    {
        $adashi = Adashi::findOrFail($id);
        abort_unless($adashi->admin_id == auth()->id(), 403, 'Only the admin can edit account details.');

        $data = $request->validate([
            'account_name' => 'nullable|string|max:255',
            'bank' => 'nullable|string|max:255',
            'nuban' => 'nullable|string|max:32',
        ]);
        $adashi->update($data);

        return back()->with('status', 'Account details updated.');
    }

    public function show($id)
    {
        $adashi = Adashi::findOrFail($id);

        $members = AdashiMember::query()
            ->join('users', 'users.id', '=', 'adashi_members.user_id')
            ->where('adashi_members.adashi_id', $adashi->id)
            ->select(['adashi_members.user_id', 'adashi_members.position', 'adashi_members.has_received', 'adashi_members.is_active', 'users.name'])
            ->orderBy('adashi_members.position')->get();

        $records = AdashiRecord::where('adashi_id', $adashi->id)->orderByDesc('cycle_number')->get();
        $currentRecord = $records->first();
        $isAdmin = $adashi->admin_id == auth()->id();
        $myMember = AdashiMember::where(['adashi_id' => $adashi->id, 'user_id' => auth()->id(), 'is_active' => 1])->first();

        // Payout timeline: projected payout date per member (cycle p ≈ start + p × duration).
        $start = \Illuminate\Support\Carbon::parse($adashi->start_date);
        $timeline = $members->where('is_active', 1)->values()->map(fn ($m) => [
            'position' => $m->position,
            'name' => $m->name,
            'has_received' => (bool) $m->has_received,
            'is_current' => (int) $m->position === (int) $adashi->current_cycle_number && $adashi->status === 'ACTIVE',
            'is_me' => $m->user_id == auth()->id(),
            'payout_date' => $start->copy()->addDays((int) $m->position * (int) $adashi->cycle_duration_days)->format('M j, Y'),
        ]);

        $contributors = \App\Models\Invoice::query()
            ->where('invoices.payment_status', 'Paid')
            ->join('adashi_members', 'adashi_members.id', '=', 'invoices.adashi_member_id')
            ->join('users', 'users.id', '=', 'adashi_members.user_id')
            ->where('adashi_members.adashi_id', $adashi->id)
            ->groupBy('adashi_members.user_id', 'users.name')
            ->selectRaw('users.name as name, COUNT(*) as total') // count of contributions, not amount
            ->orderByDesc('total')->limit(10)->get();

        $adminRating = app(\App\Services\Rating\RatingService::class)->averageFor($adashi->admin_id);
        $myRating = \App\Models\Rating::where([
            'rater_id' => auth()->id(), 'context_type' => 'adashi', 'context_id' => $adashi->id,
        ])->value('stars');

        // Pending (unverified) contributions for the current cycle + the viewer's remaining owed.
        $pending = collect();
        $myOwed = 0;
        if ($currentRecord) {
            $pending = \App\Models\Invoice::where('invoices.adashi_record_id', $currentRecord->id)
                ->where('invoices.payment_status', 'Not Paid')
                ->join('adashi_members', 'adashi_members.id', '=', 'invoices.adashi_member_id')
                ->join('users', 'users.id', '=', 'adashi_members.user_id')
                ->select('invoices.id', 'invoices.amount', 'users.name', 'adashi_members.user_id')
                ->orderBy('invoices.id')->get();

            if ($myMember) {
                $submitted = (int) \App\Models\Invoice::where(['adashi_record_id' => $currentRecord->id, 'adashi_member_id' => $myMember->id])
                    ->whereIn('payment_status', ['Paid', 'Not Paid'])->sum('amount');
                $myOwed = max(0, (int) $adashi->amount_per_cycle - $submitted);
            }
        }

        // Bank accounts: the member's own (to choose a payout account) + the
        // current receiver's chosen account (shown to the admin who disburses).
        $myAccounts = $myMember ? auth()->user()->bankAccounts : collect();
        $receiverAccount = null;
        if ($isAdmin) {
            $recvMember = AdashiMember::where(['adashi_id' => $adashi->id, 'position' => $adashi->current_cycle_number])->first();
            if ($recvMember && $recvMember->bank_account_id) {
                $receiverAccount = \App\Models\BankAccount::find($recvMember->bank_account_id);
            }
        }

        // Group chat (members + admin only).
        $canChat = config('chat.enabled', true) && ($isAdmin || $myMember);
        $messages = $canChat ? \App\Models\Message::recentFor('adashi', $adashi->id) : collect();

        // Public view facts (admin trust) for non-members.
        $admin = \App\Models\User::find($adashi->admin_id);
        $reputation = app(\App\Services\Reputation\ReputationService::class)->forUser($adashi->admin_id);
        $alreadyIn = (bool) AdashiMember::where(['adashi_id' => $adashi->id, 'user_id' => auth()->id()])->exists();

        // Disputes (members see their own; the admin sees all).
        $canDispute = config('disputes.enabled', true) && ($isAdmin || $myMember);
        $disputes = $canDispute ? \App\Models\Dispute::where(['context_type' => 'adashi', 'context_id' => $adashi->id])
            ->leftJoin('users', 'users.id', '=', 'disputes.raised_by')
            ->when(!$isAdmin, fn ($q) => $q->where('disputes.raised_by', auth()->id()))
            ->select('disputes.*', 'users.name as raiser_name')
            ->orderByRaw("disputes.status = 'OPEN' desc")->orderByDesc('disputes.id')->get() : collect();

        return view('adashi.show', compact('adashi', 'members', 'records', 'currentRecord', 'isAdmin', 'myMember', 'contributors', 'timeline', 'adminRating', 'myRating', 'pending', 'myOwed', 'myAccounts', 'receiverAccount', 'canChat', 'messages', 'canDispute', 'disputes', 'admin', 'reputation', 'alreadyIn'));
    }

    /** Clone an adashi's settings + chosen members into a fresh adashi (cycle 1). */
    public function cloneAdashi(Request $request, $id)
    {
        $src = Adashi::findOrFail($id);
        $owner = auth()->user();
        abort_unless($src->admin_id == $owner->id, 403, 'Only the admin can clone this adashi.');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount_per_cycle' => 'required|integer|min:1',
            'cycle_duration_days' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'members' => 'nullable|array',
            'members.*' => 'integer',
        ]);

        $keep = collect($data['members'] ?? []);
        $sourceMembers = AdashiMember::where(['adashi_id' => $src->id, 'is_active' => 1])
            ->when($keep->isNotEmpty(), fn ($q) => $q->whereIn('user_id', $keep))
            ->orderBy('position')->get();
        // The admin is always carried over.
        if (!$sourceMembers->firstWhere('user_id', $owner->id)) {
            $sourceMembers = AdashiMember::where(['adashi_id' => $src->id, 'is_active' => 1])
                ->where(fn ($q) => $q->whereIn('user_id', $keep)->orWhere('user_id', $owner->id))
                ->orderBy('position')->get();
        }
        $capacity = max(1, $sourceMembers->count());

        $coins = app(\App\Services\Coins\CoinService::class);
        $cost = $coins->cost('adashi', $capacity);
        if (!$coins->canAfford($owner, $cost)) {
            return back()->withErrors(['clone' => "Cloning costs {$cost} Keens — you have {$coins->balance($owner)}."]);
        }

        $new = DB::transaction(function () use ($src, $data, $sourceMembers) {
            $a = Adashi::create([
                'name' => $data['name'],
                'amount_per_cycle' => $data['amount_per_cycle'],
                'total_members' => 1,
                'start_date' => $data['start_date'],
                'cycle_duration_days' => $data['cycle_duration_days'],
                'current_cycle_number' => 1,
                'admin_id' => $src->admin_id,
                'rotation_mode' => $src->rotation_mode,
                'status' => 'ACTIVE',
                'is_public' => $src->is_public,
                'bank' => $src->bank, 'nuban' => $src->nuban, 'account_name' => $src->account_name,
                'payout_visible' => $src->payout_visible, 'rules' => $src->rules,
            ]);

            $first = null;
            $pos = 1;
            foreach ($sourceMembers as $m) {
                $nm = AdashiMember::create([
                    'adashi_id' => $a->id, 'user_id' => $m->user_id, 'position' => $pos++,
                    'has_received' => false, 'joined_at' => now(), 'is_active' => true,
                ]);
                $first = $first ?? $nm;
            }
            $a->update(['total_members' => max(1, $pos - 1)]);

            AdashiRecord::create([
                'adashi_id' => $a->id, 'cycle_number' => 1,
                'due_at' => Carbon::parse($a->start_date)->addDays($a->cycle_duration_days),
                'total_collected' => 0, 'receiver_user_id' => $first?->user_id ?? $src->admin_id,
                'receiver_member_id' => $first?->id, 'paid_members_count' => 0, 'status' => 'PENDING',
            ]);

            return $a;
        });

        $coins->charge($owner, $cost, 'Clone adashi: '.$new->name);

        return redirect()->route('adashi.show', $new->id)->with('status', 'Adashi cloned.'.($cost ? " ({$cost} Keens)" : ''));
    }

    /** Admin sets the group rules (free-form contract members agree to). */
    public function saveRules(Request $request, $id)
    {
        $adashi = Adashi::findOrFail($id);
        abort_unless($adashi->admin_id == auth()->id(), 403, 'Only the admin can set rules.');
        $data = $request->validate(['rules' => 'nullable|string|max:5000']);
        $adashi->update(['rules' => $data['rules'] ?? null]);

        return back()->with('status', 'Group rules updated.');
    }

    /** Admin toggles whether members can see the full payout order (who gets what cycle). */
    public function togglePayoutVisibility($id)
    {
        $adashi = Adashi::findOrFail($id);
        abort_unless($adashi->admin_id == auth()->id(), 403, 'Only the admin can do this.');
        $adashi->payout_visible = !$adashi->payout_visible;
        $adashi->save();

        return back()->with('status', $adashi->payout_visible
            ? 'Members can now see the full payout order.'
            : 'The payout order is now private (members see positions only).');
    }

    /** A member picks which of their saved accounts to receive this adashi's payout into. */
    public function setAccount(Request $request, $id)
    {
        $data = $request->validate(['bank_account_id' => 'nullable|integer']);
        $adashi = Adashi::findOrFail($id);
        $member = AdashiMember::where(['adashi_id' => $adashi->id, 'user_id' => auth()->id(), 'is_active' => 1])->firstOrFail();

        $accountId = $data['bank_account_id'] ?: null;
        if ($accountId) {
            // Must be one of the current user's own accounts.
            abort_unless($request->user()->bankAccounts()->whereKey($accountId)->exists(), 422, 'Unknown account.');
        }
        $member->update(['bank_account_id' => $accountId]);

        return back()->with('status', 'Payout account updated.');
    }

    /** A member rates the adashi admin (1–5 stars). */
    public function rateAdmin(Request $request, $id)
    {
        $data = $request->validate([
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $result = app(\App\Services\Rating\RatingService::class)
            ->submit($request->user(), 'adashi', (int) $id, (int) $data['stars'], $data['comment'] ?? null);

        if (!$result['ok']) {
            return back()->withErrors(['rating' => $result['message'] ?? 'Could not save your rating.']);
        }

        return back()->with('status', 'Thanks for rating the admin.');
    }

    /** A user joins a public adashi themselves (after accepting the terms). */
    public function join(Request $request, $id)
    {
        $adashi = Adashi::findOrFail($id);
        abort_unless($adashi->is_public, 403, 'This adashi is private — ask the admin to add you.');
        $user = auth()->user();

        if (AdashiMember::where(['adashi_id' => $adashi->id, 'user_id' => $user->id])->exists()) {
            return back()->with('status', 'You are already a member of this adashi.');
        }

        $request->validate(['accept_terms' => 'accepted'], ['accept_terms.accepted' => 'Please confirm you understand and accept the terms.']);

        DB::transaction(function () use ($adashi, $user) {
            $position = (AdashiMember::where('adashi_id', $adashi->id)->max('position') ?? 0) + 1;
            AdashiMember::create([
                'adashi_id' => $adashi->id, 'user_id' => $user->id, 'position' => $position,
                'has_received' => false, 'joined_at' => now(), 'is_active' => true,
            ]);
            $adashi->increment('total_members');
        });

        try {
            app(\App\Services\Referral\ReferralService::class)->qualifyQuietly($user);
        } catch (\Throwable $e) {
        }

        return redirect()->route('adashi.show', $adashi->id)->with('status', 'You joined this adashi.')->with('celebrate', true);
    }

    /** A member submits a contribution for the current cycle (pending admin verification). */
    public function contribute(Request $request, $id)
    {
        $data = $request->validate(['amount' => 'required|integer|min:1']);

        $adashi = Adashi::findOrFail($id);
        $user = auth()->user();
        $member = AdashiMember::where(['adashi_id' => $adashi->id, 'user_id' => $user->id, 'is_active' => 1])->first();
        abort_unless($member, 403, 'You are not an active member of this adashi.');

        $record = AdashiRecord::where('adashi_id', $adashi->id)->orderByDesc('cycle_number')->first();
        abort_unless($record, 422, 'No active cycle.');

        $owed = $this->remainingOwed($adashi, $record, $member);
        if ($owed <= 0) {
            return back()->withErrors(['amount' => 'You have already paid your full ₦'.number_format($adashi->amount_per_cycle).' for this cycle.']);
        }
        if ($data['amount'] > $owed) {
            return back()->withErrors(['amount' => 'You can contribute at most ₦'.number_format($owed).' more for this cycle.'])->withInput();
        }

        $this->recordPendingContribution($adashi, $record, $member, (int) $data['amount']);

        return back()->with('status', 'Contribution submitted — the admin will verify it.');
    }

    /** Admin records a contribution on behalf of a member (still pending until verified). */
    public function addContribution(Request $request, $id)
    {
        $adashi = Adashi::findOrFail($id);
        abort_unless($adashi->admin_id == auth()->id(), 403, 'Only the admin can add contributions.');

        $data = $request->validate([
            'member_user_id' => 'required|integer',
            'amount' => 'required|integer|min:1',
        ]);

        $member = AdashiMember::where(['adashi_id' => $adashi->id, 'user_id' => $data['member_user_id'], 'is_active' => 1])->first();
        abort_unless($member, 422, 'That member is not active in this adashi.');

        $record = AdashiRecord::where('adashi_id', $adashi->id)->orderByDesc('cycle_number')->first();
        abort_unless($record, 422, 'No active cycle.');

        $owed = $this->remainingOwed($adashi, $record, $member);
        if ($data['amount'] > $owed) {
            return back()->withErrors(['amount' => 'That member can take at most ₦'.number_format($owed).' more for this cycle.'])->withInput();
        }

        $this->recordPendingContribution($adashi, $record, $member, (int) $data['amount']);

        return back()->with('status', 'Contribution added — verify it to count it toward the cycle.');
    }

    /** Admin verifies a pending contribution; it then counts toward the cycle. */
    public function verifyContribution($invoiceId)
    {
        $invoice = \App\Models\Invoice::whereNotNull('adashi_record_id')->findOrFail($invoiceId);
        $record = AdashiRecord::findOrFail($invoice->adashi_record_id);
        $adashi = Adashi::findOrFail($record->adashi_id);
        abort_unless($adashi->admin_id == auth()->id(), 403, 'Only the admin can verify contributions.');

        if ($invoice->payment_status !== 'Paid') {
            $invoice->payment_status = 'Paid';
            $invoice->paid_through = 'Manual';
            $invoice->payment_date = now();
            $invoice->save();
        }

        // Recompute the cycle (and auto-rotate when fully collected).
        try {
            app(\App\Http\Controllers\Adashi\AdashiController::class)->reconcilePayments($adashi->id);
        } catch (\Throwable $e) {
        }

        return back()->with('status', 'Contribution verified.')->with('celebrate', true);
    }

    /** Admin declines (removes) a pending contribution. */
    public function declineContribution($invoiceId)
    {
        $invoice = \App\Models\Invoice::whereNotNull('adashi_record_id')->findOrFail($invoiceId);
        $record = AdashiRecord::findOrFail($invoice->adashi_record_id);
        $adashi = Adashi::findOrFail($record->adashi_id);
        abort_unless($adashi->admin_id == auth()->id(), 403, 'Only the admin can decline contributions.');
        abort_if($invoice->payment_status === 'Paid', 422, 'A verified contribution cannot be declined here.');

        DB::transaction(function () use ($invoice) {
            \App\Models\InvoiceItem::where('invoice_id', $invoice->id)->delete();
            $invoice->delete();
        });

        return back()->with('status', 'Pending contribution removed.');
    }

    /** Amount a member may still pay toward the current cycle (counts paid + pending). */
    private function remainingOwed(Adashi $adashi, AdashiRecord $record, AdashiMember $member): int
    {
        $submitted = (int) \App\Models\Invoice::where(['adashi_record_id' => $record->id, 'adashi_member_id' => $member->id])
            ->whereIn('payment_status', ['Paid', 'Not Paid'])->sum('amount');

        return max(0, (int) $adashi->amount_per_cycle - $submitted);
    }

    private function recordPendingContribution(Adashi $adashi, AdashiRecord $record, AdashiMember $member, int $amount): void
    {
        DB::transaction(function () use ($adashi, $record, $member, $amount) {
            $invoice = new \App\Models\Invoice();
            $invoice->pocket_slot_id = null;
            $invoice->adashi_record_id = $record->id;
            $invoice->adashi_member_id = $member->id;
            $invoice->invoice_no = 'ADSH/'.str_pad($adashi->id, 3, '0', STR_PAD_LEFT).'/'.date('ymdHis').random_int(10, 99);
            $invoice->amount = $amount;
            $invoice->reference_no = $invoice->invoice_no;
            $invoice->payment_status = 'Not Paid';   // awaiting admin verification
            $invoice->paid_through = 'Pending';
            $invoice->save();

            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id, 'item_id' => 1, 'amount' => $amount,
                'type' => 'Paid', 'month' => $record->cycle_number,
            ]);
        });
    }

    /** Admin: manage members screen. */
    public function membersForm($id)
    {
        $adashi = Adashi::findOrFail($id);
        abort_unless($adashi->admin_id == auth()->id(), 403, 'Only the admin can manage members.');

        $members = AdashiMember::query()
            ->join('users', 'users.id', '=', 'adashi_members.user_id')
            ->where('adashi_members.adashi_id', $adashi->id)
            ->select(['adashi_members.user_id', 'adashi_members.position', 'adashi_members.is_active', 'users.name', 'users.phone_number'])
            ->orderBy('adashi_members.position')->get();

        $currentRecord = AdashiRecord::where('adashi_id', $adashi->id)->orderByDesc('cycle_number')->first();

        return view('adashi.members', compact('adashi', 'members', 'currentRecord'));
    }

    /**
     * Admin override actions (set receiver, mark paid out / dispute, de/reactivate
     * member). Routed through the existing tested AdashiController::adminOverride.
     */
    public function adminAction(Request $request, $id)
    {
        $adashi = Adashi::findOrFail($id);
        abort_unless($adashi->admin_id == auth()->id(), 403, 'Only the admin can perform this action.');

        $params = $request->only(['action', 'record_id', 'receiver_user_id', 'member_user_id', 'amount', 'position', 'note']);
        $apiRequest = \Illuminate\Http\Request::create('/', 'POST', $params);

        try {
            app(\App\Http\Controllers\Adashi\AdashiController::class)->adminOverride($id, $apiRequest);
        } catch (\Throwable $e) {
            return back()->withErrors(['action' => 'Could not apply that action — check your selection.']);
        }

        return back()->with('status', 'Admin action applied.');
    }

    /** Admin adds a member by phone (creating a placeholder account if needed). */
    public function addMember(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:20',
            'accept_terms' => 'accepted',
        ], ['accept_terms.accepted' => 'Please confirm you personally know this member.']);

        $adashi = Adashi::findOrFail($id);
        abort_unless($adashi->admin_id == auth()->id(), 403, 'Only the admin can add members.');

        $user = User::where('phone_number', $data['phone_number'])->first();
        if (!$user) {
            if (empty($data['name'])) {
                return back()->withErrors(['name' => 'Enter a name for the new member (they are not on KeenPocket yet).'])->withInput();
            }
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['phone_number'],          // placeholder — claimed on signup
                'username' => $data['phone_number'],
                'phone_number' => $data['phone_number'],
                'password' => bcrypt(Str::random(16)),
            ]);
        }

        if (AdashiMember::where(['adashi_id' => $adashi->id, 'user_id' => $user->id])->exists()) {
            return back()->with('status', $user->name.' is already a member.');
        }

        DB::transaction(function () use ($adashi, $user) {
            $maxPos = AdashiMember::where('adashi_id', $adashi->id)->max('position') ?? 0;
            AdashiMember::create([
                'adashi_id' => $adashi->id, 'user_id' => $user->id, 'position' => $maxPos + 1,
                'has_received' => false, 'joined_at' => now(), 'is_active' => true,
            ]);
            $adashi->increment('total_members');
        });

        try {
            app(\App\Services\Referral\ReferralService::class)->qualifyQuietly($user);
        } catch (\Throwable $e) {
        }

        return redirect()->route('adashi.members', $adashi->id)->with('status', $user->name.' added to the adashi.');
    }

    /** Admin exports the adashi's cycle records as CSV. */
    public function exportRecords($id)
    {
        $adashi = Adashi::findOrFail($id);
        abort_unless($adashi->admin_id == auth()->id(), 403, 'Only the admin can export records.');

        $records = AdashiRecord::query()
            ->leftJoin('users', 'users.id', '=', 'adashi_records.receiver_user_id')
            ->where('adashi_records.adashi_id', $adashi->id)
            ->orderBy('adashi_records.cycle_number')
            ->get(['adashi_records.cycle_number', 'adashi_records.due_at', 'adashi_records.total_collected', 'users.name as receiver', 'adashi_records.paid_members_count', 'adashi_records.status']);

        $filename = 'adashi-'.$adashi->id.'-records.csv';

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Cycle', 'Due', 'Collected', 'Receiver', 'Paid members', 'Status']);
            foreach ($records as $r) {
                fputcsv($out, [$r->cycle_number, $r->due_at, $r->total_collected, $r->receiver, $r->paid_members_count, $r->status]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Admin reconciles the current cycle (closes + rotates when fully collected). */
    public function reconcile(Request $request, $id)
    {
        $adashi = Adashi::findOrFail($id);
        abort_unless($adashi->admin_id == auth()->id(), 403, 'Only the admin can reconcile.');

        try {
            app(\App\Http\Controllers\Adashi\AdashiController::class)->reconcilePayments($adashi->id);
        } catch (\Throwable $e) {
        }

        return back()->with('status', 'Cycle reconciled.');
    }
}
