<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\Pocket;
use App\Services\Payouts\PayoutService;
use Illuminate\Http\Request;

class PayoutsController extends Controller
{
    public function index(PayoutService $payouts)
    {
        $user = auth()->user();

        return view('payouts', [
            'user' => $user,
            'ownedPockets' => Pocket::where('user_id', $user->id)->orderByDesc('id')->get(),
            'received' => Payout::where('recipient_user_id', $user->id)->orderByDesc('id')->get(),
            'enabled' => $payouts->enabled(),
        ]);
    }

    /** Save the member's personal payout destination (for receiving Adashi payouts). */
    public function saveBankAccount(Request $request)
    {
        $data = $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_code' => 'required|string|max:16',
            'account_number' => 'required|string|max:20',
        ]);

        $user = $request->user();
        $user->payout_bank_name = $data['bank_name'];
        $user->payout_bank_code = $data['bank_code'];
        $user->payout_account_number = $data['account_number'];
        $user->save();

        return back()->with('status', 'Payout account saved.');
    }

    /** Save the collection bank details for a pocket the user organises. */
    public function savePocketBank(Request $request, $id)
    {
        $data = $request->validate([
            'bank' => 'required|string|max:255',
            'nuban' => 'required|string|max:20',
        ]);

        $pocket = Pocket::findOrFail($id);
        abort_unless($pocket->user_id == $request->user()->id, 403, 'Only the pocket owner can set bank details.');

        $pocket->bank = $data['bank'];
        $pocket->nuban = $data['nuban'];
        $pocket->save();

        return back()->with('status', 'Pocket bank details updated.');
    }
}
