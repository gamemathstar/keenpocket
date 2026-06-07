<?php

namespace App\Console\Commands;

use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\AdashiRecord;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdashiScheduler extends Command
{
    protected $signature = 'adashi:process';
    protected $description = 'Process Adashi auto-rotation, reminders, and overdue alerts';

    public function handle()
    {
        $this->info('Processing Adashi schedules...');

        // Get all active Adashis
        $adashis = Adashi::where('status', 'ACTIVE')->get();

        foreach ($adashis as $adashi) {
            $currentRecord = AdashiRecord::where('adashi_id', $adashi->id)
                ->orderBy('cycle_number', 'desc')
                ->first();

            if (!$currentRecord || $currentRecord->status == 'PAID_OUT') {
                continue;
            }

            $now = Carbon::now();
            $dueAt = Carbon::parse($currentRecord->due_at);
            $recordStartDate = $dueAt->copy()->subDays($adashi->cycle_duration_days);
            $cycleDuration = $adashi->cycle_duration_days * 24 * 60 * 60; // in seconds
            $timeRemaining = max(0, $dueAt->diffInSeconds($now, false));
            $timeElapsed = $now->diffInSeconds($recordStartDate, false);
            $percentRemaining = $cycleDuration > 0 ? ($timeRemaining / $cycleDuration) * 100 : 0;

            // Auto-reconcile and rotate if AUTO mode and due date passed
            if ($adashi->rotation_mode == 'AUTO' && $dueAt->isPast()) {
                $this->reconcileAndRotate($adashi, $currentRecord);
                continue;
            }

            // Only process if record is not already PAID_OUT
            if ($currentRecord->status == 'PAID_OUT') {
                continue;
            }

            // 24h before payment reminder for participants (only if not overdue)
            if (!$dueAt->isPast() && $dueAt->diffInHours($now) <= 24 && $dueAt->diffInHours($now) > 23.8) {
                $this->send24hReminders($adashi, $currentRecord);
            }

            // 30% time remaining notification for next receiver
            if (!$dueAt->isPast() && $percentRemaining <= 30 && $percentRemaining > 29) {
                $receiverUser = User::find($currentRecord->receiver_user_id);
                if ($receiverUser) {
                    Notification::adashiReceiver30Percent($receiverUser, $adashi, $currentRecord);
                }
            }

            // 24h before receiver gets payment
            if (!$dueAt->isPast() && $dueAt->diffInHours($now) <= 24 && $dueAt->diffInHours($now) > 23.8) {
                $receiverUser = User::find($currentRecord->receiver_user_id);
                if ($receiverUser) {
                    Notification::adashiReceiver24h($receiverUser, $adashi, $currentRecord);
                }
            }

            // Admin alert 24h after due date if not paid
            $overdueCheck = $dueAt->copy()->addHours(24);
            if ($overdueCheck->isPast() && $currentRecord->status != 'PAID_OUT') {
                $admin = User::find($adashi->admin_id);
                if ($admin) {
                    Notification::adashiOverdueAdminAlert($admin, $adashi, $currentRecord);
                }
            }
        }

        $this->info('Adashi scheduling processed.');
        return 0;
    }

    protected function reconcileAndRotate($adashi, $record)
    {
        $activeMembers = AdashiMember::where('adashi_id', $adashi->id)
            ->where('is_active', true)
            ->get();

        $paidCount = 0;
        foreach ($activeMembers as $member) {
            $memberTotal = Invoice::where('adashi_record_id', $record->id)
                ->where('adashi_member_id', $member->id)
                ->where('payment_status', 'Paid')
                ->sum('amount');
            
            if ($memberTotal >= $adashi->amount_per_cycle) {
                $paidCount++;
            }
        }

        $record->paid_members_count = $paidCount;
        $record->total_collected = Invoice::where('adashi_record_id', $record->id)
            ->where('payment_status', 'Paid')
            ->sum('amount');

        if ($paidCount >= $activeMembers->count()) {
            $record->status = 'PAID_OUT';
            $record->save();
            $this->autoRotate($adashi);
        } else {
            $record->status = 'COLLECTING';
            $record->save();
        }
    }

    protected function send24hReminders($adashi, $record)
    {
        $activeMembers = AdashiMember::where('adashi_id', $adashi->id)
            ->where('is_active', true)
            ->where('user_id', '!=', $record->receiver_user_id) // Don't remind the receiver
            ->get();

        foreach ($activeMembers as $member) {
            $memberTotal = Invoice::where('adashi_record_id', $record->id)
                ->where('adashi_member_id', $member->id)
                ->where('payment_status', 'Paid')
                ->sum('amount');

            if ($memberTotal < $adashi->amount_per_cycle) {
                $user = User::find($member->user_id);
                if ($user) {
                    Notification::adashiPaymentReminder24h($user, $adashi, $record);
                }
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

        $receiverUser = User::find($receiverMember->user_id);
        if ($receiverUser) {
            Notification::adashiPaymentReceived($receiverUser, $adashi, $current);
        }

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

        $nextReceiverUser = User::find($nextReceiverMember->user_id);
        if ($nextReceiverUser) {
            Notification::adashiNextReceiver($nextReceiverUser, $adashi, $new);
        }
    }
}

