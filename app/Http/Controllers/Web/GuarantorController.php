<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PocketGuarantor;
use App\Models\PocketSlot;
use Illuminate\Support\Facades\DB;

class GuarantorController extends Controller
{
    /** Requests where the current user has been named as guarantor. */
    public function requests()
    {
        $requests = PocketGuarantor::query()
            ->where('pocket_guarantors.guarantor_id', auth()->id())
            ->join('pockets', 'pockets.id', '=', 'pocket_guarantors.pocket_id')
            ->join('users', 'users.id', '=', 'pocket_guarantors.requester_id')
            ->select([
                'pocket_guarantors.id', 'pocket_guarantors.status', 'pocket_guarantors.created_at',
                'pockets.title as pocket_title', 'pockets.id as pocket_id', 'users.name as requester',
            ])
            ->orderByRaw("pocket_guarantors.status = 'PENDING' desc")
            ->orderByDesc('pocket_guarantors.id')->get();

        return view('guarantor.requests', compact('requests'));
    }

    public function recommend($id)
    {
        $g = PocketGuarantor::where(['id' => $id, 'guarantor_id' => auth()->id()])->firstOrFail();
        if ($g->status === 'PENDING') {
            $g->update(['status' => 'RECOMMENDED']);
        }

        return back()->with('status', 'You recommended this request — the admin can now accept it.');
    }

    public function decline($id)
    {
        $g = PocketGuarantor::where(['id' => $id, 'guarantor_id' => auth()->id()])->firstOrFail();

        DB::transaction(function () use ($g) {
            $g->update(['status' => 'DECLINED']);
            // Withdraw the pending request slot, if still pending.
            if ($g->slot_id) {
                PocketSlot::where(['id' => $g->slot_id, 'status' => 0])->delete();
            }
        });

        return back()->with('status', 'You declined to vouch for this request.');
    }
}
