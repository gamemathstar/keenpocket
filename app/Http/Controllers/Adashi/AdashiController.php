<?php

namespace App\Http\Controllers\Adashi;

use App\Http\Controllers\Controller;
use App\Models\Adashi;
use App\Models\AdashiContributor;
use App\Models\AdashiMember;
use App\Models\AdashiRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdashiController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount_per_cycle' => 'required|integer|min:1',
            'cycle_duration_days' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'rotation_mode' => 'required|in:AUTO,MANUAL',
            'members' => 'array',
            'members.*' => 'integer|exists:users,id',
            'admin_id' => 'nullable|integer|exists:users,id',
        ]);
        // return $request->all();

        $admin = $data['admin_id'] ?? auth()->id();
        $members = $data['members'] ?? [];

        return DB::transaction(function () use ($data, $admin, $members) {
            $totalMembers = count($members) + 1; // include admin
            $adashi = Adashi::create([
                'name' => $data['name'],
                'amount_per_cycle' => $data['amount_per_cycle'],
                'total_members' => $totalMembers,
                'start_date' => $data['start_date'],
                'cycle_duration_days' => $data['cycle_duration_days'],
                'current_cycle_number' => 1,
                'admin_id' => $admin,
                'rotation_mode' => $data['rotation_mode'],
                'status' => 'ACTIVE',
            ]);

            // create members (admin first position)
            $position = 1;
            AdashiMember::create([
                'adashi_id' => $adashi->id,
                'user_id' => $admin,
                'position' => $position,
                'has_received' => false,
                'joined_at' => now(),
                'is_active' => true,
            ]);
            foreach ($members as $userId) {
                $position++;
                AdashiMember::create([
                    'adashi_id' => $adashi->id,
                    'user_id' => $userId,
                    'position' => $position,
                    'has_received' => false,
                    'joined_at' => now(),
                    'is_active' => true,
                ]);
            }

            $receiverMember = AdashiMember::where('adashi_id', $adashi->id)->where('position', 1)->first();
            $dueAt = Carbon::parse($adashi->start_date)->addDays($adashi->cycle_duration_days);

            $record = AdashiRecord::create([
                'adashi_id' => $adashi->id,
                'cycle_number' => 1,
                'due_at' => $dueAt,
                'total_collected' => 0,
                'receiver_user_id' => $receiverMember->user_id,
                'receiver_member_id' => $receiverMember->id,
                'paid_members_count' => 0,
                'status' => 'PENDING',
            ]);

            $adashi->load(['members','records']);
            return response()->json(['success' => true, 'adashi' => $adashi]);
        });
    }

    public function show($id)
    {
        $adashi = Adashi::with(['members' => function($q){ $q->orderBy('position'); }, 'records' => function($q){ $q->orderBy('cycle_number','desc'); }])->findOrFail($id);
        $currentRecord = $adashi->records->first();
        return response()->json([
            'adashi' => $adashi,
            'members' => $adashi->members,
            'current_record' => $currentRecord,
            'records' => $adashi->records,
        ]);
    }

    public function join($id, Request $request)
    {
        $data = $request->validate(['user_id' => 'required|integer|exists:users,id']);

        $adashi = Adashi::findOrFail($id);
        $maxPos = AdashiMember::where('adashi_id', $adashi->id)->max('position') ?? 0;
        $member = AdashiMember::firstOrCreate(
            ['adashi_id' => $adashi->id, 'user_id' => $data['user_id']],
            ['position' => $maxPos + 1, 'has_received' => false, 'joined_at' => now(), 'is_active' => true]
        );

        $adashi->increment('total_members');
        return response()->json(['success' => true, 'member' => $member]);
    }

    public function contributorsIndex($id, $memberId)
    {
        $adashi = Adashi::findOrFail($id);
        $member = AdashiMember::where('adashi_id', $adashi->id)->findOrFail($memberId);
        $contributors = AdashiContributor::where('adashi_member_id', $member->id)->get();
        return response()->json(['contributors' => $contributors]);
    }

    public function contributorsStore($id, $memberId, Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'share_amount' => 'required|integer|min:1',
        ]);

        $adashi = Adashi::findOrFail($id);
        $member = AdashiMember::where('adashi_id', $adashi->id)->findOrFail($memberId);

        $contrib = AdashiContributor::firstOrCreate(
            ['adashi_member_id' => $member->id, 'user_id' => $data['user_id']],
            ['share_amount' => $data['share_amount'], 'is_active' => true, 'joined_at' => now()]
        );

        return response()->json(['success' => true, 'contributor' => $contrib]);
    }

    public function records($id)
    {
        $adashi = Adashi::findOrFail($id);
        $records = AdashiRecord::where('adashi_id', $adashi->id)->orderBy('cycle_number','desc')->get();
        return response()->json(['records' => $records]);
    }

    public function nextCycle($id, Request $request)
    {
        $adashi = Adashi::with(['members' => function($q){ $q->orderBy('position'); }])->findOrFail($id);
        $current = AdashiRecord::where('adashi_id', $adashi->id)->orderBy('cycle_number','desc')->first();
        if (!$current) return response()->json(['message' => 'No record'], 404);

        // close current
        $current->status = 'PAID_OUT';
        $current->save();

        // next receiver
        $nextCycle = $current->cycle_number + 1;
        $nextPosition = (($current->cycle_number) % $adashi->members->count()) + 1;
        $receiverMember = $adashi->members->firstWhere('position', $nextPosition);
        $dueAt = Carbon::parse($adashi->start_date)->addDays(($nextCycle) * $adashi->cycle_duration_days);

        $new = AdashiRecord::create([
            'adashi_id' => $adashi->id,
            'cycle_number' => $nextCycle,
            'due_at' => $dueAt,
            'total_collected' => 0,
            'receiver_user_id' => $receiverMember->user_id,
            'receiver_member_id' => $receiverMember->id,
            'paid_members_count' => 0,
            'status' => 'PENDING',
        ]);

        $adashi->current_cycle_number = $nextCycle;
        $adashi->save();

        return response()->json(['success' => true, 'closed_record' => $current, 'new_record' => $new]);
    }

    public function contribute($id, Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|integer|min:1',
            'invoice_items' => 'required|array',
            'invoice_items.*.item_id' => 'required|integer|exists:items,id',
            'invoice_items.*.amount' => 'required|integer|min:1',
            'invoice_items.*.type' => 'required|in:Paid,Donation',
            'adashi_record_id' => 'required|integer|exists:adashi_records,id',
            'adashi_member_id' => 'nullable|integer|exists:adashi_members,id',
        ]);

        $user = User::find($data['user_id']);
        $currentUser = auth()->user();

        $adashi = Adashi::findOrFail($id);
        $record = AdashiRecord::where('adashi_id', $adashi->id)->findOrFail($data['adashi_record_id']);

        // Validate member
        $member = null;
        if ($data['adashi_member_id']) {
            $member = AdashiMember::where('adashi_id', $adashi->id)->findOrFail($data['adashi_member_id']);
        } else {
            $member = AdashiMember::where('adashi_id', $adashi->id)->where('user_id', $user->id)->first();
            if (!$member) {
                return response()->json(['message' => 'User is not a member of this Adashi'], 400);
            }
        }

        // Check authorization
        if ($user->id != $currentUser->id && $adashi->admin_id != $currentUser->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return DB::transaction(function () use ($adashi, $record, $member, $data, $user, $currentUser) {
            $total = $data['amount'];
            $admin = User::find($adashi->admin_id);

            // Create invoice
            $invoice = new Invoice();
            $invoice->pocket_slot_id = null; // Adashi doesn't use pocket slots
            $invoice->adashi_record_id = $record->id;
            $invoice->adashi_member_id = $member->id;
            $invoice->invoice_no = "ADSH/".str_pad($adashi->id,3,'0',STR_PAD_LEFT)."/".date("ymdHi");
            $invoice->amount = $total;
            $invoice->reference_no = $invoice->invoice_no;
            $invoice->payment_status = ($admin->id == $currentUser->id) ? "Paid" : 'Not Paid';
            $invoice->paid_through = ($admin->id == $currentUser->id) ? "Manual" : 'Pending';

            if ($admin->id == $currentUser->id) {
                $invoice->payment_date = now();
            }

            if ($invoice->save()) {
                // Create invoice items
                foreach ($data['invoice_items'] as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'item_id' => $item['item_id'],
                        'amount' => $item['amount'],
                        'type' => $item['type'],
                        'month' => $record->cycle_number, // Use cycle number as month
                    ]);
                }

                // Notify admin when participant raises invoice
                if ($admin->id != $currentUser->id) {
                    Notification::adashiInvoiceRaised($user, $admin, $adashi, $record, $total);
                }

                // If admin marked as paid, update record immediately
                if ($invoice->payment_status == 'Paid') {
                    $this->updateRecordFromInvoice($record, $member, $adashi);
                }

                return response()->json([
                    'success' => true,
                    'invoice' => $invoice,
                    'record' => $record->fresh(),
                ]);
            }

            return response()->json(['message' => 'Failed to create invoice'], 500);
        });
    }

    protected function updateRecordFromInvoice($record, $member, $adashi)
    {
        // Calculate total contributions for this member in this record
        $memberTotal = Invoice::where('adashi_record_id', $record->id)
            ->where('adashi_member_id', $member->id)
            ->where('payment_status', 'Paid')
            ->sum('amount');

        // Check if member has paid their full share (considering contributors)
        $requiredAmount = $adashi->amount_per_cycle;

        if ($memberTotal >= $requiredAmount) {
            // Check all active members
            $activeMembers = AdashiMember::where('adashi_id', $adashi->id)
                ->where('is_active', true)
                ->get();

            $paidCount = 0;
            foreach ($activeMembers as $m) {
                $mTotal = Invoice::where('adashi_record_id', $record->id)
                    ->where('adashi_member_id', $m->id)
                    ->where('payment_status', 'Paid')
                    ->sum('amount');

                if ($mTotal >= $requiredAmount) {
                    $paidCount++;
                }
            }

            $record->paid_members_count = $paidCount;

            // Update total collected
            $record->total_collected = Invoice::where('adashi_record_id', $record->id)
                ->where('payment_status', 'Paid')
                ->sum('amount');

            if ($record->status == 'PENDING') {
                $record->status = 'COLLECTING';
            }

            // Auto-rotate if all paid and AUTO mode
            if ($paidCount >= $activeMembers->count() && $adashi->rotation_mode == 'AUTO') {
                $record->status = 'PAID_OUT';
                $record->save();
                $this->autoRotate($adashi);
            } else {
                $record->save();
            }
        }
    }

    protected function autoRotate($adashi)
    {
        $adashi->load(['members' => function($q){ $q->orderBy('position'); }]);
        $current = AdashiRecord::where('adashi_id', $adashi->id)
            ->where('status', 'PAID_OUT')
            ->orderBy('cycle_number','desc')
            ->first();

        if (!$current) return;

        $receiverMember = AdashiMember::find($current->receiver_member_id);
        $receiverMember->has_received = true;
        $receiverMember->save();

        // Notify receiver they received payment
        $receiverUser = User::find($receiverMember->user_id);
        Notification::adashiPaymentReceived($receiverUser, $adashi, $current);

        // Calculate next receiver
        $nextCycle = $current->cycle_number + 1;
        $activeMembers = $adashi->members->where('is_active', true);
        $nextPosition = (($current->cycle_number) % $activeMembers->count()) + 1;
        $nextReceiverMember = $activeMembers->firstWhere('position', $nextPosition);

        if (!$nextReceiverMember) {
            $nextReceiverMember = $activeMembers->first();
        }

        $dueAt = Carbon::parse($adashi->start_date)->addDays($nextCycle * $adashi->cycle_duration_days);

        $new = AdashiRecord::create([
            'adashi_id' => $adashi->id,
            'cycle_number' => $nextCycle,
            'due_at' => $dueAt,
            'total_collected' => 0,
            'receiver_user_id' => $nextReceiverMember->user_id,
            'receiver_member_id' => $nextReceiverMember->id,
            'paid_members_count' => 0,
            'status' => 'PENDING',
        ]);

        $nextReceiverMember->next_receiver_date = $dueAt;
        $nextReceiverMember->save();

        $adashi->current_cycle_number = $nextCycle;
        $adashi->save();

        // Notify next receiver
        $nextReceiverUser = User::find($nextReceiverMember->user_id);
        Notification::adashiNextReceiver($nextReceiverUser, $adashi, $new);
    }

    public function reconcilePayments($id)
    {
        $adashi = Adashi::findOrFail($id);
        $currentRecord = AdashiRecord::where('adashi_id', $adashi->id)
            ->orderBy('cycle_number','desc')
            ->first();

        if (!$currentRecord || $currentRecord->status == 'PAID_OUT') {
            return response()->json(['message' => 'No active record to reconcile'], 400);
        }

        $activeMembers = AdashiMember::where('adashi_id', $adashi->id)
            ->where('is_active', true)
            ->get();

        $paidCount = 0;

        foreach ($activeMembers as $member) {
            $memberTotal = Invoice::where('adashi_record_id', $currentRecord->id)
                ->where('adashi_member_id', $member->id)
                ->where('payment_status', 'Paid')
                ->sum('amount');

            if ($memberTotal >= $adashi->amount_per_cycle) {
                $paidCount++;
            }
        }

        $totalCollected = Invoice::where('adashi_record_id', $currentRecord->id)
            ->where('payment_status', 'Paid')
            ->sum('amount');

        $currentRecord->paid_members_count = $paidCount;
        $currentRecord->total_collected = $totalCollected;

        if ($paidCount >= $activeMembers->count()) {
            $currentRecord->status = 'PAID_OUT';
            $currentRecord->save();

            if ($adashi->rotation_mode == 'AUTO') {
                $this->autoRotate($adashi);
            }

            return response()->json([
                'success' => true,
                'message' => 'All payments reconciled. Cycle closed.',
                'record' => $currentRecord->fresh(),
            ]);
        } else {
            $currentRecord->status = 'COLLECTING';
            $currentRecord->save();

            return response()->json([
                'success' => true,
                'message' => 'Payments reconciled',
                'record' => $currentRecord->fresh(),
                'remaining' => $activeMembers->count() - $paidCount,
            ]);
        }
    }

    public function dashboard()
    {
        $user = auth()->user();

        $myAdashis = Adashi::where('admin_id', $user->id)
            ->orWhereHas('members', function($q) use ($user) {
                $q->where('user_id', $user->id)->where('is_active', true);
            })
            ->with(['members', 'records' => function($q) {
                $q->orderBy('cycle_number','desc')->limit(1);
            }])
            ->get();

        foreach ($myAdashis as $adashi) {
            $currentRecord = $adashi->records->first();
            if ($currentRecord) {
                $adashi->current_record = $currentRecord;
                $adashi->total_collected = Invoice::where('adashi_record_id', $currentRecord->id)
                    ->where('payment_status', 'Paid')
                    ->sum('amount');
            }
        }

        return response()->json([
            'user' => $user,
            'adashis' => $myAdashis,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('query', '');
        $by = $request->get('by', 'name');

        $adashis = collect();

        if ($by == 'name' && $query) {
            $adashis = Adashi::where('name', 'LIKE', "%{$query}%")
                ->where('status', 'ACTIVE')
                ->with(['members'])
                ->get();
        } elseif ($by == 'admin' && $query) {
            $phonePattern = '/^\+(?:998|996|995|994|993|992|977|976|975|974|973|972|971|970|968|967|966|965|964|963|962|961|960|886|880|856|855|853|852|850|692|691|690|689|688|687|686|685|683|682|681|680|679|678|677|676|675|674|673|672|670|599|598|597|595|593|592|591|590|509|508|507|506|505|504|503|502|501|500|423|421|420|389|387|386|385|383|382|381|380|379|378|377|376|375|374|373|372|371|370|359|358|357|356|355|354|353|352|351|350|299|298|297|291|290|269|268|267|266|265|264|263|262|261|260|258|257|256|255|254|253|252|251|250|249|248|246|245|244|243|242|241|240|239|238|237|236|235|234|233|232|231|230|229|228|227|226|225|224|223|222|221|220|218|216|213|212|211|98|95|94|93|92|91|90|86|84|82|81|66|65|64|63|62|61|60|58|57|56|55|54|53|52|51|49|48|47|46|45|44\D?1624|44\D?1534|44\D?1481|44|43|41|40|39|36|34|33|32|31|30|27|20|7|1\D?939|1\D?876|1\D?869|1\D?868|1\D?849|1\D?829|1\D?809|1\D?787|1\D?784|1\D?767|1\D?758|1\D?721|1\D?684|1\D?671|1\D?670|1\D?664|1\D?649|1\D?473|1\D?441|1\D?345|1\D?340|1\D?284|1\D?268|1\D?264|1\D?246|1\D?242|1)\D?/';
            $phoneNumber = preg_replace($phonePattern, '', $query);
            $userIds = User::where('phone_number', 'LIKE', "%{$phoneNumber}%")->pluck('id');

            if ($userIds->count() > 0) {
                $adashis = Adashi::whereIn('admin_id', $userIds)
                    ->where('status', 'ACTIVE')
                    ->with(['members'])
                    ->get();
            }
        }

        return response()->json(['adashis' => $adashis]);
    }

    public function adminOverride($id, Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:SET_RECEIVER,MARK_PAID_OUT,MARK_DISPUTE,DEACTIVATE_MEMBER,REACTIVATE_MEMBER,ADJUST_CONTRIBUTION',
            'record_id' => 'nullable|integer|exists:adashi_records,id',
            'receiver_user_id' => 'nullable|integer|exists:users,id',
            'member_user_id' => 'nullable|integer|exists:users,id',
            'note' => 'nullable|string',
        ]);

        $adashi = Adashi::findOrFail($id);
        $admin = auth()->user();

        if ($adashi->admin_id != $admin->id) {
            return response()->json(['message' => 'Only admin can perform this action'], 403);
        }

        return DB::transaction(function () use ($adashi, $data, $admin) {
            switch ($data['action']) {
                case 'SET_RECEIVER':
                    if (!$data['record_id'] || !$data['receiver_user_id']) {
                        return response()->json(['message' => 'record_id and receiver_user_id required'], 400);
                    }
                    $record = AdashiRecord::where('adashi_id', $adashi->id)->findOrFail($data['record_id']);
                    $member = AdashiMember::where('adashi_id', $adashi->id)
                        ->where('user_id', $data['receiver_user_id'])
                        ->firstOrFail();
                    $record->receiver_user_id = $member->user_id;
                    $record->receiver_member_id = $member->id;
                    $record->save();
                    return response()->json(['success' => true, 'record' => $record]);

                case 'MARK_PAID_OUT':
                    if (!$data['record_id']) {
                        return response()->json(['message' => 'record_id required'], 400);
                    }
                    $record = AdashiRecord::where('adashi_id', $adashi->id)->findOrFail($data['record_id']);
                    $record->status = 'PAID_OUT';
                    $record->save();
                    if ($adashi->rotation_mode == 'AUTO') {
                        $this->autoRotate($adashi);
                    }
                    return response()->json(['success' => true, 'record' => $record]);

                case 'MARK_DISPUTE':
                    if (!$data['record_id']) {
                        return response()->json(['message' => 'record_id required'], 400);
                    }
                    $record = AdashiRecord::where('adashi_id', $adashi->id)->findOrFail($data['record_id']);
                    $record->status = 'DISPUTE';
                    $record->save();
                    return response()->json(['success' => true, 'record' => $record]);

                case 'DEACTIVATE_MEMBER':
                    if (!$data['member_user_id']) {
                        return response()->json(['message' => 'member_user_id required'], 400);
                    }
                    $member = AdashiMember::where('adashi_id', $adashi->id)
                        ->where('user_id', $data['member_user_id'])
                        ->firstOrFail();
                    $member->is_active = false;
                    $member->save();
                    $adashi->decrement('total_members');
                    return response()->json(['success' => true, 'member' => $member]);

                case 'REACTIVATE_MEMBER':
                    if (!$data['member_user_id']) {
                        return response()->json(['message' => 'member_user_id required'], 400);
                    }
                    $member = AdashiMember::where('adashi_id', $adashi->id)
                        ->where('user_id', $data['member_user_id'])
                        ->firstOrFail();
                    $member->is_active = true;
                    $member->save();
                    $adashi->increment('total_members');
                    return response()->json(['success' => true, 'member' => $member]);

                default:
                    return response()->json(['message' => 'Invalid action'], 400);
            }
        });
    }
}


