<?php

namespace App\Http\Controllers\Web;

use App\Actions\MarkInvoicePaid;
use App\Exceptions\InsufficientFundsException;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Services\Charity\CharityService;
use App\Services\Contribution\ContributionPlanner;
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

        $project = $this->charityProject($pocket);
        $monthly = (int) $slot->amount_paying;

        return view('invoices.create', compact('pocket', 'project', 'monthly'));
    }

    /**
     * Step 2 — compute (and let the member edit) how the amount is split:
     * an optional donation, then the rest spread across the next unpaid months.
     */
    public function preview(Request $request, $pocketId)
    {
        $pocket = Pocket::findOrFail($pocketId);
        $slot = $this->activeSlot($pocket->id, auth()->id());
        abort_unless($slot, 403, 'You are not an active member of this pocket.');

        $data = $request->validate([
            'amount' => 'required|integer|min:1',
            'donation' => 'nullable|integer|min:0',
        ]);

        $project = $this->charityProject($pocket);
        $donation = $project ? min((int) ($data['donation'] ?? 0), $data['amount']) : 0;
        $contribution = $data['amount'] - $donation;

        $plan = app(ContributionPlanner::class)->plan($pocket, $slot, $contribution);
        $total = (int) $data['amount'];

        return view('invoices.preview', compact('pocket', 'project', 'plan', 'donation', 'total'));
    }

    /** Step 3 — persist the (possibly edited) allocation as one invoice + items. */
    public function store(Request $request, $pocketId)
    {
        $pocket = Pocket::findOrFail($pocketId);
        $user = auth()->user();
        $slot = $this->activeSlot($pocket->id, $user->id);
        abort_unless($slot, 403, 'You are not an active member of this pocket.');

        $data = $request->validate([
            'balance' => 'required|integer|min:1',
            'donation' => 'nullable|integer|min:0',
            'months' => 'nullable|array',
            'months.*' => 'integer|min:1|max:60',
            'amounts' => 'nullable|array',
            'amounts.*' => 'integer|min:0',
        ]);

        $project = $this->charityProject($pocket);
        $donation = $project ? (int) ($data['donation'] ?? 0) : 0;
        $months = $data['months'] ?? [];
        $amounts = $data['amounts'] ?? [];

        // Never let the edited allocation exceed the amount the member is paying.
        $allocated = $donation + array_sum(array_map('intval', $amounts));
        if ($allocated > (int) $data['balance']) {
            return back()->withErrors(['amount' => 'Allocation (₦'.number_format($allocated).') exceeds your balance of ₦'.number_format($data['balance']).'.'])->withInput();
        }

        // One invoice; a Paid item per month plus an optional Donation item.
        $lines = [];
        $total = 0;
        foreach ($months as $i => $m) {
            $amt = (int) ($amounts[$i] ?? 0);
            if ($amt <= 0) {
                continue;
            }
            $lines[] = ['item_id' => 1, 'amount' => $amt, 'type' => 'Paid', 'month' => (int) $m, 'charity_project_id' => null];
            $total += $amt;
        }
        if ($donation > 0 && $project) {
            $lines[] = ['item_id' => 1, 'amount' => $donation, 'type' => 'Donation', 'month' => 0, 'charity_project_id' => $project->id];
            $total += $donation;
        }
        if (empty($lines)) {
            return back()->withErrors(['amount' => 'Nothing to record — enter an amount.']);
        }

        // Owner self-records as Paid; a member's request waits for owner approval.
        $isOwner = $pocket->user_id == $user->id;

        DB::transaction(function () use ($pocket, $slot, $lines, $total, $isOwner) {
            $invoice = new Invoice();
            $invoice->pocket_slot_id = $slot->id;
            $invoice->invoice_no = 'KP/'.str_pad($pocket->id, 3, '0', STR_PAD_LEFT).'/'.date('ymdHis');
            $invoice->amount = $total;
            $invoice->reference_no = $invoice->invoice_no;
            $invoice->payment_status = $isOwner ? 'Paid' : 'Not Paid';
            $invoice->paid_through = $isOwner ? 'Manual' : 'Pending';
            $invoice->payment_date = $isOwner ? now() : null;
            $invoice->save();

            foreach ($lines as $line) {
                InvoiceItem::create(array_merge(['invoice_id' => $invoice->id], $line));
            }
        });

        return redirect()->route('pockets.show', $pocket->id)->with('status', 'Contribution invoice created.')->with('celebrate', true);
    }

    /** The pocket's active charity project, or null when charity is off. */
    private function charityProject(Pocket $pocket)
    {
        $charity = app(CharityService::class);

        return ($charity->enabled() && $pocket->charity_enabled) ? $charity->activeProject($pocket) : null;
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

        return back()->with('status', 'Payment approved.')->with('celebrate', true);
    }

    /** Pocket owner declines (removes) a member's pending invoice. */
    public function decline($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $pocket = $this->pocketForInvoice($invoice);
        abort_unless($pocket && $pocket->user_id == auth()->id(), 403, 'Only the pocket owner can decline payments.');
        abort_if($invoice->payment_status === 'Paid', 422, 'A paid invoice cannot be declined.');

        DB::transaction(function () use ($invoice) {
            InvoiceItem::where('invoice_id', $invoice->id)->delete();
            $invoice->delete();
        });

        return back()->with('status', 'Payment request declined.');
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

        return back()->with('status', 'Invoice paid from wallet.')->with('celebrate', true);
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
