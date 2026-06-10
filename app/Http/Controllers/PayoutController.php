<?php

namespace App\Http\Controllers;

use App\Models\Adashi;
use App\Models\AdashiRecord;
use App\Services\Payouts\PayoutService;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function __construct(private PayoutService $payouts)
    {
    }

    public function status()
    {
        return response([
            'enabled' => $this->payouts->enabled(),
            'provider' => $this->payouts->provider(),
            'currency' => config('payouts.currency', 'NGN'),
        ]);
    }

    /**
     * Save the authenticated user's payout destination (bank account).
     */
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

        return response(['message' => 'Payout account saved.']);
    }

    /**
     * Admin-triggered payout for an Adashi's latest (closed) cycle. Used for
     * MANUAL-rotation adashis; AUTO ones disburse automatically on cycle close.
     */
    public function initiate(Request $request, $id)
    {
        if (!$this->payouts->enabled()) {
            return response(['enabled' => false, 'message' => 'Payouts are currently disabled.'], 200);
        }

        $adashi = Adashi::find($id);
        if (!$adashi) {
            return response(['message' => 'Adashi not found.'], 404);
        }
        if ($adashi->admin_id != $request->user()->id) {
            return response(['message' => 'Only the Adashi admin can initiate a payout.'], 403);
        }

        $record = AdashiRecord::where('adashi_id', $adashi->id)
            ->orderByDesc('cycle_number')
            ->first();
        if (!$record) {
            return response(['message' => 'No cycle to pay out.'], 422);
        }

        $payout = $this->payouts->attemptForRecord($record);
        if (!$payout) {
            return response(['message' => 'Nothing to disburse for this cycle.'], 422);
        }

        return response(['message' => 'Payout '.$payout->status, 'payout' => $payout]);
    }

    /**
     * Provider transfer webhook. Public; signature-verified inside the service.
     */
    public function webhook(string $provider, Request $request)
    {
        $this->payouts->handleWebhook($provider, $request->getContent(), $request->headers->all());

        return response(['received' => true], 200);
    }
}
