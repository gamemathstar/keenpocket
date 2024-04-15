<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use HasFactory;

    public function fullInvoice()
    {
        $months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
        $invoice = Invoice::select([
            'invoice_no',DB::raw('FORMAT(amount,0) AS amount'),'reference_no',
            'reference_no',DB::raw('DATE_FORMAT(payment_date,"%h:%i %p") as payment_time'),
            DB::raw('DATE_FORMAT(payment_date,"%a. %D %b, %Y") as payment_date'),
            'paid_through','invoices.id',"payment_status","pocket_slot_id","users.name","users.phone_number"
        ])
            ->join("pocket_slots","pocket_slots.id","=","invoices.pocket_slot_id")
            ->join("users","pocket_slots.user_id","=","users.id")
            ->find($this->id);
        $pocket_slot = PocketSlot::find($invoice->pocket_slot_id);
        $pocket = Pocket::find($pocket_slot->pocket_id);
        $items = InvoiceItem::join("items","items.id","=","invoice_item.item_id")
            ->select([
                'invoice_item.id','items.name as item','category as item_type',DB::raw('FORMAT(invoice_item.amount,0) AS amount'),DB::raw("(month -1 + {$pocket->start_month})%12 AS month")
            ])->where('invoice_id',$invoice->id)->orderBy('month','ASC')->get();
        $year = $pocket->year;
        $itemsArr = [];
        foreach ($items as $item){
            if($pocket->start_month != 1 && $item->month==1)
                $year++;
            $item->month = $months[$item->month-1];
            $item->year = $year;
            $itemsArr[] = $item;
        }
        $inv = $invoice->toArray();
        $inv['items']=$itemsArr;
        return $inv;
    }
}
