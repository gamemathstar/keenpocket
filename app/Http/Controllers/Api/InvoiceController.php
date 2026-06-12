<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Notification;
use App\Models\Pocket;
use App\Models\PocketSlot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function invoice(Request $request)
    {
        return Invoice::find($request->id)->fullInvoice();
    }

    public function pocketInvoices(Request $request)
    {
        try {
            $user = \auth()->user();
            if($pocket = Pocket::find($request->id)){
                if($pocket->user_id == $user->id){
                    return Invoice::join('pocket_slots', 'pocket_slots.id', '=', 'invoices.pocket_slot_id')
                        ->join("users","pocket_slots.user_id","=","users.id")
                        ->where(['pocket_slots.pocket_id' => $request->id])
                        ->select([
                            'invoices.id', 'invoice_no', DB::raw('FORMAT(amount,0) AS amount'),
                            'reference_no', 'reference_no', 'payment_date',"amount as amt",
                            'paid_through', 'payment_status',"users.name","users.phone_number"
                        ])
                        ->orderBy("payment_status","DESC")
                        ->orderBy("amt","DESC")
                        ->get();
                }else{
                    return Invoice::join('pocket_slots', 'pocket_slots.id', '=', 'invoices.pocket_slot_id')
                        ->join("users","pocket_slots.user_id","=","users.id")
                        ->where(['pocket_slots.pocket_id' => $request->id,"pocket_slots.user_id"=>$user->id])
                        ->select([
                            'invoices.id', 'invoice_no', DB::raw('FORMAT(amount,0) AS amount'),
                            'reference_no', 'reference_no', 'payment_date',"amount as amt",
                            'paid_through', 'payment_status',"users.name","users.phone_number"
                        ])
                        ->orderBy("payment_status","DESC")
                        ->orderBy("amt","DESC")
                        ->get();
                }


            }
            return response(['message'=>"Invalid Pocket."]);
        }catch (\Exception $exception){
            return response(['message'=>'Ooops!']);
        }

    }

    public function pocketMonthInvoices(Request $request)
    {
        $user = User::find($request->user_id);
        $user = $user?$user:auth()->user();
        $curr = auth()->user();

        $id = $request->id;

        $ps = PocketSlot::where(['pocket_id' => $id, "user_id" => $user->id, 'status' => 1])->first();
        $months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];


        if ($ps) {
            $pocket = Pocket::find($id);
            $invItemIdPayments = Invoice::where(['invoices.pocket_slot_id'=>$ps->id,"invoice_item.type"=>"Paid"])
                ->join("invoice_item","invoice_item.invoice_id","=","invoices.id")
                ->groupBy("invoice_item.month")
                ->select(["invoice_item.month",DB::raw("{$ps->amount_paying} - SUM(invoice_item.amount) AS amount")])
                ->orderBy("invoice_item.month","ASC")
                ->get();
            $amountPaying = [];
            $amountPayingList = [];

            if($user->id!=$curr->id && $pocket->user_id!=$curr->id){
                return response(['message'=>"Invalid pocket"]);
            }
            $pocket_month = [];
            for ($i = 0; $i < $pocket->month_count; $i++) {
                $pocket_month[] = $months[($i + $pocket->start_month - 1) % count($months)];
                if(isset($invItemIdPayments[$i]->month)){
                    $amountPayingList[] = $invItemIdPayments[$i]->amount;
                }else{
                    $amountPayingList[] = $ps->amount_paying;
                }

            }

            $amountPaying = $ps->amount_paying;

            $invoiceIds = Invoice::where(['pocket_slot_id' => $ps->id, 'payment_status' => 'Paid'])->pluck('id');
            $invoiceMonths = InvoiceItem::whereIn('invoice_id', $invoiceIds)
                ->select(['invoice_id', DB::raw('SUM(amount) AS amount_paid'), 'month'])
                ->where(['item_id' => 1])
                ->groupBy(['invoice_id', 'month'])
                ->orderBy('month', 'DESC')->get();
            $completeMonths = InvoiceItem::whereIn('invoice_id', $invoiceIds)
                ->select(['invoice_id', DB::raw('SUM(amount) AS amount_paid'), 'month'])
                ->where(['item_id' => 1])
                ->having('amount_paid', '=', $ps->amount_paying)
                ->groupBy(['invoice_id', 'month'])
                ->orderBy('month', 'DESC')
                ->pluck('month');
//            $firstInv =
            $nextMonth = isset($invoiceMonths[0]) ? ($invoiceMonths[0]->amount_paid == $amountPaying ? $invoiceMonths[0]->month + 1 : $invoiceMonths[0]->month) : 1;
            return [
                'monthInvoice' => $invoiceMonths, 'nextMonth' => $nextMonth,
                "amount_paying" => $amountPaying ,'amount_paying_list'=>$amountPayingList,
                'month_order' => $pocket_month, 'paid_months' => $completeMonths
            ];
        }

        return [];
    }

    public function createInvoice(Request $request)
    {

//        return  ;
        try{
            $user = User::find($request->user_id);
            $user = $user?$user:Auth::user();
            $curr = auth()->user();
            if(!$pocket = Pocket::find($request->id)){
                return response(["message"=>"Invalid Pocket."]);
            }
            if($user->id!=$curr->id && $pocket->user_id!=$curr->id){
                return response(["message"=>"Invalid Pocket."]);
            }
//            return $request;
            $owner = User::find($pocket->user_id);
            $slot = PocketSlot::where(['pocket_id'=>$pocket->id,"status"=>1,"user_id"=>$user->id])->first();
            if(!$slot ){
                return response(["message"=>"You're not an active member of this pocket."]);
            }

            $total = 0;
            $items = $request->items;
            $months = $request->months;
            $amounts = $request->amounts;
            $types = $request->types;
            $invArr = [];
            for($i=0;$i< count($items);$i++){
                $total += $amounts[$i];
                $invArr[] = [
                    "invoice_id"=>"[INVID]","item_id"=>$items[$i],
                    "month"=>$months[$i],"amount"=>$amounts[$i],"type"=>$types[$i]
                ];
            }
            $invJson = json_encode($invArr);

            // Atomically replace any pending invoices for this slot with the new one,
            // so a failure can never leave an invoice without its items (or vice versa).
            $invoice = DB::transaction(function () use ($slot, $pocket, $owner, $curr, $total, $invJson) {
                $invIds = Invoice::where(['pocket_slot_id'=>$slot->id,'payment_status'=>'Not Paid'])->pluck('id');
                InvoiceItem::whereIn('invoice_id',$invIds)->delete();
                Invoice::whereIn('id',$invIds)->delete();

                $invoice = new Invoice();
                $invoice->pocket_slot_id = $slot->id;
                $invoice->invoice_no = "KP/".str_pad($pocket->id,3,'0',STR_PAD_LEFT)."/".date("ymdHi");
                $invoice->amount = $total;
                $invoice->reference_no = $invoice->invoice_no;
                $invoice->payment_status = $owner->id == $curr->id?"Paid":'Not Paid';
                $invoice->paid_through = $owner->id == $curr->id?"Manual":'Pending';
                if($owner->id == $curr->id){
                    $invoice->payment_date = now();
                }
                $invoice->save();

                $replaced = str_replace("[INVID]",$invoice->id,$invJson);
                $itemsList = [];
                foreach (json_decode($replaced) as $v){
                    $itemsList[] = (array)$v;
                }
                InvoiceItem::insert($itemsList);

                return $invoice;
            });

            {
                if($owner->id == $curr->id){
                    if($curr->id!=$user->id){
                        Notification::paymentReceivedNotification($owner,$user,$invoice);
                    }

                }else{
                    Notification::paymentNotification($user,$owner,$invoice);
                }
                if($owner->id == $curr->id){
                    $pocketSlots = PocketSlot::where(['pocket_id' => $pocket->id])
                        ->leftJoin("users", "pocket_slots.user_id", "=", "users.id")
                        ->leftJoin("invoices","invoices.pocket_slot_id","=","pocket_slots.id")
                        ->leftJoin("invoice_item","invoice_item.invoice_id","=","invoices.id")
                        ->select([
                            'pocket_slots.id', 'users.name AS user',"users.id AS user_id",
                            'pocket_slots.status','pocket_slots.hand_count','users.phone_number',
                            DB::raw("FORMAT(SUM(IF((invoices.payment_status='Paid' AND invoice_item.item_id=1),invoice_item.amount,0)),0) AS contributed_amount"),
                            DB::raw("SUM(IF((invoices.payment_status='Paid' AND invoice_item.item_id=1),invoice_item.amount,0)) AS contributed_amount2")
                        ])
                        ->orderBy('contributed_amount2','DESC')
                        ->groupBy("pocket_slots.user_id")
                        ->get();
                    return ['message'=>"Successfully updated",'keens'=>$pocketSlots];
                }else{
                return response(["message"=>"Invoice generation successful.","invoice_id"=>$invoice->id],200);
                }
            }
            return response(["message"=>"Failed to create Invoice"]);
        }catch (\Exception $exception){
            return response(["message"=>"Invalid Pocket."]);
        }
    }

    public function changePaymentStatus(Request $request)
    {
        $user = \auth()->user();
        if($invoice = Invoice::find($request->id)){
            if($pocket_slot = PocketSlot::find($invoice->pocket_slot_id)){
                if($pocket = Pocket::find($pocket_slot->pocket_id)){
                    if($pocket->user_id == $user->id){
                        $target = User::find($pocket_slot->user_id);
                        $invoice->payment_status = $request->status?"Paid":"Not Paid";
                        $invoice->payment_date = $request->status?now():null;
                        $invoice->save();
                        Notification::paymentReceivedNotification($user,$target,$invoice,$request->status);
                        return response(['message'=>"Payment status updated"]);

                    }
                    return response(['message'=>"Unauthorized access"]);
                }
            }
        }

        return response(['message'=>"Invalid Invoice"]);
//        must be owner
//        invoice must belong to a pocket you own
    }
}
