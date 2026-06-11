<?php

namespace App\Http\Controllers\Web;

use App\Actions\MarkInvoicePaid;
use App\Exceptions\InsufficientFundsException;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function create($pocketId)
    {
        $pocket = Pocket::findOrFail($pocketId);
        $slot = $this->activeSlot($pocket->id, auth()->id());
        abort_unless($slot, 403, 'You are not an active member of this pocket.');

        return view('invoices.create', compact('pocket'));
    }

    public function store(Request $request, $pocketId)
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:1',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $pocket = Pocket::findOrFail($pocketId);
        $user = auth()->user();
        $slot = $this->activeSlot($pocket->id, $user->id);
        abort_unless($slot, 403, 'You are not an active member of this pocket.');

        DB::transaction(function () use ($pocket, $slot, $data) {
            $invoice = new Invoice();
            $invoice->pocket_slot_id = $slot->id;
            $invoice->invoice_no = 'KP/'.str_pad($pocket->id, 3, '0', STR_PAD_LEFT).'/'.date('ymdHis');
            $invoice->amount = $data['amount'];
            $invoice->reference_no = $invoice->invoice_no;
            $invoice->payment_status = 'Not Paid';
            $invoice->paid_through = 'Pending';
            $invoice->save();

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_id' => 1, // default contribution item
                'amount' => $data['amount'],
                'type' => 'Paid',
                'month' => $data['month'],
            ]);
        });

        return redirect()->route('pockets.show', $pocket->id)->with('status', 'Contribution invoice created.');
    }

    /** Pocket owner approves a member's invoice. */
    public function markPaid($invoiceId, MarkInvoicePaid $markPaid)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $pocket = $this->pocketForInvoice($invoice);
        abort_unless($pocket && $pocket->user_id == auth()->id(), 403, 'Only the pocket owner can approve payments.');

        if ($invoice->payment_status !== 'Paid') {
            $markPaid->execute($invoice, 'Manual');
        }

        return back()->with('status', 'Payment approved.');
    }

    /** Member pays their own invoice from wallet balance. */
    public function payWallet($invoiceId, WalletService $wallet, MarkInvoicePaid $markPaid)
    {
        if (!$wallet->enabled()) {
            return back()->withErrors(['wallet' => 'Wallet is not enabled.']);
        }

        $invoice = Invoice::findOrFail($invoiceId);
        $slot = PocketSlot::find($invoice->pocket_slot_id);
        abort_unless($slot && $slot->user_id == auth()->id(), 403, 'This is not your invoice.');

        if ($invoice->payment_status === 'Paid') {
            return back()->with('status', 'Already paid.');
        }

        $amount = (int) round((float) $invoice->amount);

        try {
            DB::transaction(function () use ($wallet, $amount, $invoice, $markPaid) {
                $wallet->debit(auth()->id(), $amount, 'contribution', 'INVPAY_'.$invoice->id);
                $markPaid->execute($invoice, 'Wallet');
            });
        } catch (InsufficientFundsException $e) {
            return back()->withErrors(['wallet' => 'Insufficient wallet balance.']);
        }

        return back()->with('status', 'Invoice paid from wallet.');
    }

    private function activeSlot($pocketId, $userId): ?PocketSlot
    {
        return PocketSlot::where(['pocket_id' => $pocketId, 'user_id' => $userId, 'status' => 1])->first();
    }

    private function pocketForInvoice(Invoice $invoice): ?Pocket
    {
        $slot = $invoice->pocket_slot_id ? PocketSlot::find($invoice->pocket_slot_id) : null;

        return $slot ? Pocket::find($slot->pocket_id) : null;
    }
}
