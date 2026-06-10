<?php

namespace App\Http\Controllers;

use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\Invoice;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    public function status()
    {
        return response([
            'enabled' => $this->payments->enabled(),
            'provider' => $this->payments->provider(),
            'currency' => config('payments.currency', 'NGN'),
        ]);
    }

    /**
     * Begin online payment for an invoice. Returns a gateway checkout URL.
     */
    public function initialize(Request $request)
    {
        $data = $request->validate(['invoice_id' => 'required|integer']);

        if (!$this->payments->enabled()) {
            return response(['enabled' => false, 'message' => 'Online payments are currently disabled.'], 200);
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

        $result = $this->payments->initialize($invoice, $request->user());

        return response(['message' => 'Payment initialized.'] + $result, 200);
    }

    /**
     * Verify a payment by reference (called from the app after redirect).
     */
    public function verify(Request $request)
    {
        $data = $request->validate(['reference' => 'required|string|max:64']);

        if (!$this->payments->enabled()) {
            return response(['enabled' => false], 200);
        }

        $paid = $this->payments->verify($data['reference']);

        return response(['paid' => $paid], $paid ? 200 : 422);
    }

    /**
     * Gateway webhook. Public, but signature-verified inside the service.
     * Always returns 200 so the gateway does not retry indefinitely.
     */
    public function webhook(string $provider, Request $request)
    {
        $this->payments->handleWebhook($provider, $request->getContent(), $request->headers->all());

        return response(['received' => true], 200);
    }

    /**
     * Authorization: payer must be the invoice's own member/slot user, or the
     * pocket owner / adashi admin acting on their behalf.
     */
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
