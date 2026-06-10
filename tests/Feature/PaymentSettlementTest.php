<?php

namespace Tests\Feature;

use App\Actions\MarkInvoicePaid;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exercises the core settlement action against the (now-migrated) invoices
 * table. Previously impossible — the invoices schema had no migration.
 */
class PaymentSettlementTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(): Invoice
    {
        $invoice = new Invoice();
        $invoice->pocket_slot_id = null; // standalone invoice — isolates the flip
        $invoice->invoice_no = 'KP/TEST/1';
        $invoice->amount = 5000;
        $invoice->reference_no = 'KP/TEST/1';
        $invoice->payment_status = 'Not Paid';
        $invoice->paid_through = 'Pending';
        $invoice->save();

        return $invoice;
    }

    public function test_mark_invoice_paid_settles_the_invoice()
    {
        $invoice = $this->makeInvoice();

        app(MarkInvoicePaid::class)->execute($invoice);

        $fresh = Invoice::find($invoice->id);
        $this->assertSame('Paid', $fresh->payment_status);
        $this->assertSame('Online', $fresh->paid_through);
        $this->assertNotNull($fresh->payment_date);
    }

    public function test_mark_invoice_paid_is_idempotent()
    {
        $invoice = $this->makeInvoice();

        app(MarkInvoicePaid::class)->execute($invoice);
        $firstPaidAt = Invoice::find($invoice->id)->payment_date;

        // A duplicate webhook / re-verify must not change an already-paid invoice.
        app(MarkInvoicePaid::class)->execute(Invoice::find($invoice->id));

        $this->assertSame('Paid', Invoice::find($invoice->id)->payment_status);
        $this->assertEquals($firstPaidAt, Invoice::find($invoice->id)->payment_date);
    }
}
