<?php

namespace App\Http\Controllers\Adashi;

use App\Http\Controllers\Controller;
use App\Models\Adashi;
use App\Models\AdashiContributor;
use App\Models\AdashiMember;
use App\Models\AdashiRecord;
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
        return $request->all();

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
}


