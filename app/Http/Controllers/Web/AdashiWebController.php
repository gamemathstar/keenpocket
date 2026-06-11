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
        return view('adashi.create');
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
        ]);

        $user = auth()->user();

        $adashi = DB::transaction(function () use ($data, $user) {
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
                'is_public' => $request->boolean('is_public'),
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

        return redirect()->route('adashi.show', $adashi->id)->with('status', 'Adashi created.');
    }

    public function show($id)
    {
        $adashi = Adashi::findOrFail($id);

        $members = AdashiMember::query()
            ->join('users', 'users.id', '=', 'adashi_members.user_id')
            ->where('adashi_members.adashi_id', $adashi->id)
            ->select(['adashi_members.position', 'adashi_members.has_received', 'adashi_members.is_active', 'users.name'])
            ->orderBy('adashi_members.position')->get();

        $records = AdashiRecord::where('adashi_id', $adashi->id)->orderByDesc('cycle_number')->get();
        $currentRecord = $records->first();
        $isAdmin = $adashi->admin_id == auth()->id();
        $myMember = AdashiMember::where(['adashi_id' => $adashi->id, 'user_id' => auth()->id(), 'is_active' => 1])->first();

        return view('adashi.show', compact('adashi', 'members', 'records', 'currentRecord', 'isAdmin', 'myMember'));
    }

    /** A member records a contribution for the current cycle. */
    public function contribute(Request $request, $id)
    {
        $data = $request->validate(['amount' => 'required|integer|min:1']);

        $adashi = Adashi::findOrFail($id);
        $user = auth()->user();
        $member = AdashiMember::where(['adashi_id' => $adashi->id, 'user_id' => $user->id, 'is_active' => 1])->first();
        abort_unless($member, 403, 'You are not an active member of this adashi.');

        $record = AdashiRecord::where('adashi_id', $adashi->id)->orderByDesc('cycle_number')->first();
        abort_unless($record, 422, 'No active cycle.');

        DB::transaction(function () use ($adashi, $record, $member, $data) {
            $invoice = new \App\Models\Invoice();
            $invoice->pocket_slot_id = null;
            $invoice->adashi_record_id = $record->id;
            $invoice->adashi_member_id = $member->id;
            $invoice->invoice_no = 'ADSH/'.str_pad($adashi->id, 3, '0', STR_PAD_LEFT).'/'.date('ymdHis');
            $invoice->amount = $data['amount'];
            $invoice->reference_no = $invoice->invoice_no;
            $invoice->payment_status = 'Paid';   // self-recorded manual contribution
            $invoice->paid_through = 'Manual';
            $invoice->payment_date = now();
            $invoice->save();

            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id, 'item_id' => 1, 'amount' => $data['amount'],
                'type' => 'Paid', 'month' => $record->cycle_number,
            ]);
        });

        // Recompute the cycle (and auto-rotate when fully collected).
        try {
            app(\App\Http\Controllers\Adashi\AdashiController::class)->reconcilePayments($adashi->id);
        } catch (\Throwable $e) {
        }

        return back()->with('status', 'Contribution recorded.');
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

        $params = $request->only(['action', 'record_id', 'receiver_user_id', 'member_user_id', 'note']);
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
        ]);

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
