<?php

namespace App\Http\Controllers;

use App\Actions\MarkInvoicePaid;
use App\Exceptions\InsufficientFundsException;
use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\Invoice;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function __construct(private WalletService $wallet)
    {
    }

    public function balance(Request $request)
    {
        if (!$this->wallet->enabled()) {
            return response(['enabled' => false], 200);
        }

        return response([
            'enabled' => true,
            'balance' => $this->wallet->balance($request->user()->id),
            'currency' => config('wallet.currency', 'NGN'),
        ]);
    }

    public function history(Request $request)
    {
        if (!$this->wallet->enabled()) {
            return response(['enabled' => false], 200);
        }

        return response($this->wallet->history($request->user()->id));
    }

    /**
     * Fund the wallet. Real money-in goes through the payment gateway; with the
     * dev/`log` provider the top-up is credited immediately for testing.
     */
    public function topup(Request $request)
    {
        $data = $request->validate(['amount' => 'required|integer|min:1']);

        if (!$this->wallet->enabled()) {
            return response(['enabled' => false, 'message' => 'Wallet is currently disabled.'], 200);
        }

        $realGateway = config('payments.enabled') && config('payments.provider') !== 'log';
        if ($realGateway) {
            // Production funding should initialize a gateway payment and credit on
            // webhook; not wired in this build.
            return response([
                'pending' => true,
                'message' => 'Fund your wallet via the payment gateway.',
            ], 200);
        }

        $txn = $this->wallet->credit(
            $request->user()->id,
            (int) $data['amount'],
            'topup',
            'TOPUP_'.$request->user()->id.'_'.uniqid()
        );

        return response([
            'message' => 'Wallet funded.',
            'balance' => (int) $txn->balance_after,
        ]);
    }

    /**
     * Settle an invoice directly from wallet balance — the frictionless path
     * for recurring contributions.
     */
    public function payInvoice(Request $request, MarkInvoicePaid $markPaid)
    {
        $data = $request->validate(['invoice_id' => 'required|integer']);

        if (!$this->wallet->enabled()) {
            return response(['enabled' => false, 'message' => 'Wallet is currently disabled.'], 200);
        }

        $invoice = Invoice::find($data['invoice_id']);
        if (!$invoice) {
            return response(['message' => 'Invalid invoice.'], 404);
        }
        if ($invoice->payment_status === 'Paid') {
            return response(['message' => 'This invoice is already paid.'], 422);
        }
        if (!$this->canPay($request->user(), $invoice)) {
            return response(['message' => 'You are not allowed to pay this invoice.'], 403);
        }

        $amount = (int) round((float) $invoice->amount);

        try {
            DB::transaction(function () use ($request, $amount, $invoice, $markPaid) {
                $this->wallet->debit($request->user()->id, $amount, 'contribution', 'INVPAY_'.$invoice->id);
                $markPaid->execute($invoice, 'Wallet');
            });
        } catch (InsufficientFundsException $e) {
            return response(['message' => 'Insufficient wallet balance.'], 422);
        }

        return response([
            'message' => 'Invoice paid from wallet.',
            'balance' => $this->wallet->balance($request->user()->id),
        ]);
    }

    private function canPay($user, Invoice $invoice): bool
    {
        if ($invoice->adashi_member_id) {
            $member = AdashiMember::find($invoice->adashi_member_id);
            if (!$member) {
                return false;
            }
            $adashi = Adashi::find($member->adashi_id);

            return $member->user_id == $user->id || ($adashi && $adashi->admin_id == $user->id);
        }

        if ($invoice->pocket_slot_id) {
            $slot = PocketSlot::find($invoice->pocket_slot_id);
            if (!$slot) {
                return false;
            }
            $pocket = Pocket::find($slot->pocket_id);

            return $slot->user_id == $user->id || ($pocket && $pocket->user_id == $user->id);
        }

        return false;
    }
}
