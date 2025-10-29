<?php

namespace App\Http\Controllers;

use App\Models\BannedUser;
use App\Models\Invitation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\Notification;
use App\Models\Pocket;
use App\Models\PocketItem;
use App\Models\PocketSlot;
use App\Models\Post;
use App\Models\PurchaseItem;
use App\Models\PurchasePreference;
use App\Models\PurchasingItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PHPUnit\Exception;

class APIController extends Controller
{
    //
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $pockets = PocketSlot::join("pockets","pockets.id","=","pocket_slots.pocket_id")
            ->leftJoin('invoices','invoices.pocket_slot_id','=','pocket_slots.id')
            ->join("users","pocket_slots.user_id","=","users.id")
            ->leftJoin('invoice_item','invoice_item.invoice_id','=','invoices.id')
            ->select([
                'pockets.id','pocket_type','title','description','year',
                'month_count','max_keens',DB::raw('FORMAT(amount_per_hand,0) AS amount_per_hand'),'per_hand_allowed',
                'start_month','slot_number','hand_count',DB::raw('FORMAT(amount_paying,0) AS amount_paying')
                ,DB::raw("FORMAT(hand_count*month_count*amount_per_hand,0) AS target_amount"),
                DB::raw("FORMAT(SUM(IF(invoices.payment_status='Paid' AND invoice_item.type='Paid',invoice_item.amount,0)),0) AS contributed_amount"),
                'pocket_slots.created_at AS joined',
                "pocket_slots.id as slot_id",'pockets.status',"users.name","users.phone_number"
            ])
            ->whereRaw(" (pocket_slots.user_id={$user->id} AND pocket_slots.status=1) ")
//            ->where('',1)
            ->groupBy("pockets.id")
            ->get();
        foreach ($pockets  as $pocket){
            $invoices = Invoice::where('pocket_slot_id',$pocket->slot_id)
                ->leftJoin('pocket_slots','invoices.pocket_slot_id','=','pocket_slots.id')
                ->join("users","pocket_slots.user_id","=","users.id")
                ->select([
                    'invoices.id','invoice_no',
                    DB::raw('FORMAT(amount,0) AS amount'),'reference_no',
                    'payment_status',DB::raw("DATE_FORMAT(payment_date,'%D %b, %Y') AS payment_date"),'paid_through',
                    "users.name","users.phone_number"])
                ->get();
            foreach ($invoices as $invoice){
                $invoice->items = InvoiceItem::join("items","items.id","=","invoice_item.id")
                    ->select([
                        'invoice_item.id','items.name as item',
                        'category as item_type',
                        DB::raw('FORMAT(amount,0) AS amount')
                    ])->where('invoice_id',$invoice->id)->get();
            }
            $pocket->invoices = $invoices;
        }
        return compact('user','pockets');

    }

    public function myPockets()
    {
        $user = auth()->user();
        $myPockets = Pocket::leftJoin('pocket_slots', "pocket_slots.pocket_id", "=", "pockets.id")
            ->join("users", "users.id", "=", "pockets.user_id")
            ->select([
                'pockets.id', 'pocket_type', 'title', 'max_keens', DB::raw('FORMAT(amount_per_hand,0) AS amount_per_hand')
                , 'open_purchasing_item', DB::raw('SUM(hand_count) AS slot_used'), "pockets.status", "users.name AS created_by", "users.phone_number", "start_month", "month_count", 'year','bank','nuban'
            ])
            ->whereRaw(" (pocket_slots.user_id={$user->id} AND pocket_slots.status=1) OR (pockets.user_id={$user->id})")->groupBy('pockets.id')->get();
        return $myPockets;
    }

    public function invoice(Request $request)
    {
        return Invoice::find($request->id)->fullInvoice();
    }

    public function search(Request $request)
    {
        $queryString = $request->queryString;
        $pockets = [];
        $by = $request->by;
        if ($by == 'title') {
            //return [$by,$queryString];
            $pockets = Pocket::where('title', "LIKE", "%" . $queryString . "%")
                ->leftJoin('pocket_slots', "pocket_slots.pocket_id", "=", "pockets.id")
                ->join("users", "users.id", "=", "pockets.user_id")
                ->select([
                    'pockets.id', 'pocket_type', 'title', 'max_keens', DB::raw('FORMAT(amount_per_hand,0) AS amount_per_hand'), "start_month", "month_count", 'year'
                    , 'open_purchasing_item', DB::raw('SUM(IF(pocket_slots.status=1,hand_count,0)) AS slot_used'), "pockets.status", "users.name AS created_by", "users.phone_number"
                ])
//                ->where(['pocket_slots.status' => 1])
                ->groupBy('pockets.id')->get();
        } elseif ($by == 'phone_number') {
            $pattern = '/^\+(?:998|996|995|994|993|992|977|976|975|974|973|972|971|970|968|967|966|965|964|963|962|961|960|886|880|856|855|853|852|850|692|691|690|689|688|687|686|685|683|682|681|680|679|678|677|676|675|674|673|672|670|599|598|597|595|593|592|591|590|509|508|507|506|505|504|503|502|501|500|423|421|420|389|387|386|385|383|382|381|380|379|378|377|376|375|374|373|372|371|370|359|358|357|356|355|354|353|352|351|350|299|298|297|291|290|269|268|267|266|265|264|263|262|261|260|258|257|256|255|254|253|252|251|250|249|248|246|245|244|243|242|241|240|239|238|237|236|235|234|233|232|231|230|229|228|227|226|225|224|223|222|221|220|218|216|213|212|211|98|95|94|93|92|91|90|86|84|82|81|66|65|64|63|62|61|60|58|57|56|55|54|53|52|51|49|48|47|46|45|44\D?1624|44\D?1534|44\D?1481|44|43|41|40|39|36|34|33|32|31|30|27|20|7|1\D?939|1\D?876|1\D?869|1\D?868|1\D?849|1\D?829|1\D?809|1\D?787|1\D?784|1\D?767|1\D?758|1\D?721|1\D?684|1\D?671|1\D?670|1\D?664|1\D?649|1\D?473|1\D?441|1\D?345|1\D?340|1\D?284|1\D?268|1\D?264|1\D?246|1\D?242|1)\D?/';
            $phone_number  = preg_replace($pattern, '', $queryString);
            $userIds = User::where("phone_number", "LIKE", "%{$phone_number}%")->pluck('id')->toArray();
            if (!count($userIds)) return [];
            $pockets = Pocket::whereIn('pockets.user_id', $userIds)
                ->leftJoin('pocket_slots', "pocket_slots.pocket_id", "=", "pockets.id")
                ->join("users", "users.id", "=", "pockets.user_id")
                ->select([
                    'pockets.id', 'pocket_type', 'title', 'max_keens', DB::raw('FORMAT(amount_per_hand,0) AS amount_per_hand'), "start_month", "month_count", 'year'
                    , 'open_purchasing_item', DB::raw('SUM(IF(pocket_slots.status=1,hand_count,0)) AS slot_used'), "pockets.status", "users.name AS created_by", "users.phone_number"
                ])
//                ->where(['pocket_slots.status' => 1])
                ->groupBy('pockets.id')->get();
        } elseif ($by == 'items') {
            $itemIds = Item::where('name', 'LIKE', "%".$queryString."%")->pluck('id');
            if (!count($itemIds)) return [];
            $pocketIds = PocketItem::whereIn('item_id', $itemIds)->pluck('pocket_id');
            if (!count($pocketIds)) return [];
            $pockets = Pocket::whereIn('pockets.id', $pocketIds)
                ->join('pocket_slots', "pocket_slots.pocket_id", "=", "pockets.id")
                ->join("users", "users.id", "=", "pockets.user_id")
                ->select([
                    'pockets.id', 'pocket_type', 'title', 'max_keens', DB::raw('FORMAT(amount_per_hand,0) AS amount_per_hand'), "start_month", "month_count", 'year'
                    , 'open_purchasing_item', DB::raw('SUM(IF(pocket_slots.status=1,hand_count,0)) AS slot_used'), "pockets.status", "users.name AS created_by", "users.phone_number"
                ])
//                ->where(['pocket_slots.status' => 1])
                ->groupBy('pockets.id')->get();
        }

        return $pockets;
    }

    public function pocket(Request $request)
    {
        try {
            $pocket = Pocket::select([
                'pockets.id', 'pocket_type', 'title', 'description', 'year', 'month_count', 'max_keens', DB::raw('FORMAT(amount_per_hand,0) AS amount_per_hand'), 'per_hand_allowed'
                , 'open_purchasing_item', "pockets.status", "users.name AS created_by", "users.phone_number", "user_id", 'start_month','bank','nuban'
            ])->join("users", "users.id", "=", "pockets.user_id")
                ->where('pockets.id', $request->id)->first();
            $user = auth()->user();

            if ($pocket) {
//            $pocket

                //isCreator if true if he created the pocket
                $pocket->isCreator = $user->id == $pocket->user_id;
                $slotIds = PocketSlot::where(['pocket_id' => $pocket->id, "status" => 1])->pluck('user_id')->toArray();
                $activeSlotIds = PocketSlot::where(['pocket_id' => $pocket->id, "status" => 1])->pluck('id')->toArray();
                $slotIds[] = 0;
                $slotIdsStr = implode(',', $slotIds);

                $sumSlots = PocketSlot::where(['pocket_id' => $pocket->id, "status" => 1])->sum('hand_count');
                $pocket->isMember = in_array($user->id, $slotIds);
                $pocket->slot_count = count($slotIds);
                $amountPerHand = $pocket->month_count*floatval(str_replace(',','',$pocket->amount_per_hand));
                $pocket->total_target_amount = number_format($sumSlots * $amountPerHand);
                $pocket->total_contributed_amount = number_format(Invoice::whereIn('pocket_slot_id',$activeSlotIds)
                    ->join('invoice_item','invoice_item.invoice_id',"=",'invoices.id')
                    ->where(["invoices.payment_status"=>"Paid","invoice_item.type"=>"Paid"])
                    ->sum("invoice_item.amount"));
                $pocket->total_donated_amount = number_format(Invoice::whereIn('pocket_slot_id',$activeSlotIds)
                    ->join('invoice_item','invoice_item.invoice_id',"=",'invoices.id')
                    ->where(["invoices.payment_status"=>"Paid","invoice_item.type"=>"Donation"])
                    ->sum("invoice_item.amount"));
                $paymentItemsX = PocketItem::where(["pocket_items.pocket_id" => $pocket->id])->pluck('item_id')->toArray();
                $paymentItemsX = count($paymentItemsX)?$paymentItemsX:[1];
                //list of invitation if isCreator for revoking if need be
                if ($pocket->isCreator) {
                    $invitations = Invitation::where("pocket_id", $pocket->id)
                        ->join("users", "users.id", "=", "invitations.user_id")
                        ->select([
                            'invitations.id', 'users.name',
                            'users.phone_number',
                            DB::raw("IF(invitations.user_id IN ($slotIdsStr),'Accepted','Pending') AS invite_status")
                        ])
                        ->orderBy('invitations.created_at','DESC')
                        ->get();
                    $pocket->invitations = $invitations;
                    $pocket->list_load = ["paymentItems" => Item::whereNotIn('id',$paymentItemsX)->get(), "purchaseItems" => PurchaseItem::get()];
//                $pocket->units = ['Bag','Pieces','Kg','Litre','Tear','Dozen','Pack','Gallon','Jerrycan'];
                }

                $pocket->contributed_amount = number_format(0);
                $pocket->target_amount = number_format(0);
                if ($pocket->isMember) {
                    $mySlots = PocketSlot::where(['pocket_id' => $pocket->id, 'user_id' => $user->id])->first();
                    $amountCont = Invoice::where(['pocket_slot_id' => $mySlots->id, 'payment_status' => 'Paid', 'item_id' => 1])
                        ->join('invoice_item', 'invoice_item.invoice_id', '=', 'invoices.id')->sum('invoice_item.amount');
                    $pocket->contributed_amount = number_format($amountCont, 0);
                    $pocket->target_amount = number_format(str_replace(',', '', $pocket->amount_per_hand) * $mySlots->hand_count * $pocket->month_count);
                }
                //list of purchasing items
                $purchasingItems = PurchasingItem::
                join("purchase_items", "purchase_items.id", "=", "purchasing_items.purchase_item_id")
                    ->leftJoin("purchase_preferences", "purchase_preferences.purchasing_item_id", "=", "purchasing_items.id")
                    ->select([
                        'purchasing_items.id', 'name', 'type AS type',
                        DB::raw('FORMAT(unit_price,0) AS unit_price'),
                        'person_count', DB::raw('COUNT(purchasing_item_id)  AS interest'),
                        "available","purchase_item_id","description"
                    ])
                    ->where("purchasing_items.pocket_id",$pocket->id)
                    ->groupBy('purchasing_items.id')
                    ->get();
                $pocket->purchaseItems = $purchasingItems;
                //list of payment items
                $paymentItems = PocketItem::join("items", "items.id", "=", "pocket_items.item_id")
                    ->select(['pocket_items.id', 'items.name AS name', 'category','item_id'])
                    ->where(["pocket_items.pocket_id" => $pocket->id])->get();
                $pocket->paymentItems = $paymentItems;
                //list of slots with Candidate Details
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
                $pocket->pocketSlots = $pocketSlots;
                //Can invite
                $pocket->canInvite = ($pocket->max_keens == 0 ? true : $pocket->max_keens > $sumSlots) && $pocket->isCreator;
                //Can join
                $invited = Invitation::where(["pocket_id" => $pocket->id, "user_id" => $user->id])->first();
                $canJoin = intval($pocket->status && ($pocket->max_keens == 0 ? 1 : $pocket->max_keens > $sumSlots) && (!in_array($user->id, $slotIds)));
                $pocketPending = PocketSlot::where(['pocket_id' => $pocket->id, 'status' => 0, 'user_id' => $user->id])->first();
                $pocket->canJoin = $pocketPending ? 2 : ( $invited && !$pocket->isMember?3:$canJoin );

                return $pocket;
            }
        }catch (\Exception $exception){
            return ['message'=>$exception->getMessage()];
        }
        return [];
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
            return response(['message'=>'Ooops!'.$exception->getMessage()]);
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

    public function searchUser(Request $request)
    {
        $query = $request->searchQuery;
        return User::select(['id', 'name', 'phone_number'])
            ->where("phone_number", "LIKE", "%$query%")->get();

    }

    public function posts(Request $request)
    {
        //$user = auth()->user();
        return Post::select(["title", "body", "featured_image", DB::raw("DATE_FORMAT(created_at,'%D %b, %Y %r') AS date_posted")])->get();
    }

    public function post(Request $request)
    {
        return Post::select(["title", "body", "featured_image", DB::raw("DATE_FORMAT(created_at,'%D %b, %Y %r') AS date_posted")])->find($request->id);
    }

    public function notifications(Request $request)
    {

        $user = Auth::user();
        $date = \Carbon\Carbon::now();
        $lastMonth = $date->subMonth(2)->format('Y-m-d');


        $notifications = Notification::join("users AS receiver", "receiver.id", "=", "notifications.user_id")
            ->leftJoin("users AS sender", "sender.id", "=", "notifications.sender_id")
            ->select([
                'notifications.id', 'user_id',
                'type', 'title', 'body', 'sender_id',
                'model_id', 'status', DB::raw("IF(sender.name,sender.name,'System') AS sender"),
                'receiver.name AS receiver', 'notifications.created_at AS posted_date'
            ])
            ->whereRaw("(notifications.created_at>='$lastMonth' OR status='Not Read') AND user_id=" . $user->id)
            ->orderBy('notifications.created_at','DESC');

        if ($request->id) {
            return $notifications->where('id', '=', $request->id)->first();
        }
        return $notifications->get();
    }

    public function createPocket(Request $request)
    {
        try {
            $user = Auth::user();
            if (BannedUser::where(['user_id' => $user->id])->first()) {
                return response(['message' => 'You are banned and prohibited from creating Pockets. Send an email to us if you think this was a mistake.'], 401);
            }
            //Create Pocket with record fields
            $pocket = new Pocket();
            $pocket->user_id = $user->id;
            $pocket->title = $request->title;
            $pocket->pocket_type = $request->pocket_type;
            $pocket->description = $request->description;
            $pocket->year = $request->year;
            $pocket->start_month = $request->start_month;
            $pocket->month_count = $request->month_count;
            $pocket->max_keens = $request->max_keens;
            $pocket->amount_per_hand = $request->amount_per_hand;
            $pocket->per_hand_allowed = $request->per_hand_allowed;

            //if created Successfully add pocket_item with ID-1 by default
            if ($pocket->save()) {
                $pocketItem = new PocketItem();
                $pocketItem->pocket_id = $pocket->id;
                $pocketItem->item_id = 1;
                $pocketItem->save();

                if ($request->hand_count) {
                    $slot = new PocketSlot();
                    $slot->pocket_id = $pocket->id;
                    $slot->user_id = $user->id;
                    $slot->hand_count = $request->hand_count;
                    $slot->amount_paying = $pocket->amount_per_hand * $request->hand_count;
                    $slot->status = 1;
                    $slot->comment = '';
                    $slot->save();

                }
            }

            return response(['message' => "Pocket Created. Please remember to abide by the terms and conditions YOU accepted on signup.", "pocket_id" => $pocket->id]);
        } catch (\Exception $exception) {
            return response(['message' => "Failed to create pocket."]);
        }
    }

    public function addBankDetails(Request $request)
    {
        try {
            $pocket = Pocket::find($request->id);
            $user = \auth()->user();
            if ($pocket) {
                if ($pocket->user_id != $user->id) {
                    return response(['message' => 'Your access to this pocket is DE... just wait for it ...NIED!.']);
                }

                $pocket->bank = $request->bank;
                $pocket->nuban = $request->nuban;
                if($pocket->save()){
                    return response(['message' => 'Update Successful']);
                }

            }

            return response(['message' => 'Failed to Update Pocket']);
        }catch (\Exception $exception){
            return response(['message' => 'Failed to Update Pocket']);
        }
    }

    public function joinPocket(Request $request)
    {
        try{
            $user = Auth::user();
            $pocket = Pocket::find($request->id);
            if($pocket){
                if(PocketSlot::where(['pocket_id'=>$pocket->id,"user_id"=>$user->id])->first()){
                    return response(['message'=>"Already a member"]);
                }
                $hand_count = $request->hand_count;
                $slotCount = PocketSlot::where(['pocket_id'=>$pocket->id,'status'=>1])->sum('hand_count');
                $available = $pocket->max_keens==0?1:$pocket->max_keens-$slotCount;
                $isOwner = $user->id ==$pocket->user_id?1:0;
                if(!$available) return response(['message'=>"All Slot exhausted."]);
                if($available < $hand_count) return response(['message'=>"There are just {$available} slot".($available>1?"s":"")." available."]);
                $hasInvitation = Invitation::where(['pocket_id'=>$pocket->id,"user_id"=>$user->id])->first();
                if(!$isOwner && !$hasInvitation && !$pocket->status){
                    return response(['message'=>"Pocket is set to invitation only."]);
                }
                $status = 0;
                if($isOwner || $hasInvitation ){
                    $status = 1;
                }
                $slotNumber = PocketSlot::where(['pocket_id'=>$pocket->id,'status'=>1])->count()+1;;
                //Todo:Slot Algorithm for Adashe
                {
                    // A model to hold slot and map to as many months as there are hand_count
                    // Means during join collection month to be chosen per hand
                    // in case of 1/2 hand or No+1/2  it can be filled with a 1/2 or No+1/2  depending on
                    // the subscription hand_count
                }


                $slot = new PocketSlot();
                $slot->pocket_id = $pocket->id;
                $slot->user_id = $user->id;
                $slot->slot_number = $slotNumber;
                $slot->hand_count = $hand_count;
                $slot->amount_paying = $hand_count * $pocket->amount_per_hand;
                $slot->status = $status;
                $slot->comment = '';
                $slot->save();
                //if Zailani was invited update that he has accepted the Invitation
                if($hasInvitation || $isOwner){
                    if(!$isOwner)
                        Notification::joinNotification($user,User::find($pocket->user_id),$pocket);
                    $message = "You are now a member of this pocket.";
//                    $hasInvitation->
                }else{
                //Send Notification to Shamsu either Zailani has joined or has sent request to join
                    Notification::joinRequestNotification($user,User::find($pocket->user_id),$pocket);
                    $message = "Your join request has been sent to the pocket owner.";
                }
                return response(['message'=>$message,"pocket_id"=>$pocket->id]);

            }

            return response(['message'=>"Invalid Pocket"]);

        }catch (\Exception $exception){
            return response(['message' => 'Invalid Pocket.'.$exception->getMessage()]);
        }
    }

    public function inviteUser(Request $request)
    {
        try{
            $user = Auth::user();
            $phone_no = $request->phone_number;
            $pattern = '/^\+(?:998|996|995|994|993|992|977|976|975|974|973|972|971|970|968|967|966|965|964|963|962|961|960|886|880|856|855|853|852|850|692|691|690|689|688|687|686|685|683|682|681|680|679|678|677|676|675|674|673|672|670|599|598|597|595|593|592|591|590|509|508|507|506|505|504|503|502|501|500|423|421|420|389|387|386|385|383|382|381|380|379|378|377|376|375|374|373|372|371|370|359|358|357|356|355|354|353|352|351|350|299|298|297|291|290|269|268|267|266|265|264|263|262|261|260|258|257|256|255|254|253|252|251|250|249|248|246|245|244|243|242|241|240|239|238|237|236|235|234|233|232|231|230|229|228|227|226|225|224|223|222|221|220|218|216|213|212|211|98|95|94|93|92|91|90|86|84|82|81|66|65|64|63|62|61|60|58|57|56|55|54|53|52|51|49|48|47|46|45|44\D?1624|44\D?1534|44\D?1481|44|43|41|40|39|36|34|33|32|31|30|27|20|7|1\D?939|1\D?876|1\D?869|1\D?868|1\D?849|1\D?829|1\D?809|1\D?787|1\D?784|1\D?767|1\D?758|1\D?721|1\D?684|1\D?671|1\D?670|1\D?664|1\D?649|1\D?473|1\D?441|1\D?345|1\D?340|1\D?284|1\D?268|1\D?264|1\D?246|1\D?242|1)\D?/';
            $phone_number  = preg_replace($pattern, '', $phone_no);
            if(!$search_user = User::where("phone_number","LIKE","%$phone_number%")->first()) {
                return response(['message'=>"This user is not registered on KeenPocket."]);
            }
            if(!$pocket = Pocket::find($request->id) ){
                return response(['message'=>"Invalid Pocket."]);
            }
            if($user->id != $pocket->user_id){
                return response(['message'=>"Invalid Pocket."]);
            }
            if(Invitation::where(['pocket_id'=>$pocket->id,'user_id'=>$search_user->id])->first()){
                return response(['message'=>"User was already invited earlier."]);
            }

            $inv = new Invitation();
            $inv->pocket_id = $pocket->id;
            $inv->user_id = $search_user->id;
            $inv->phone_number = $search_user->phone_number;
            if($inv->save()){
                Notification::pocketInvitationNotification($user,$search_user,$pocket);
            }
            return response(['message'=>"Invitation Sent"]);

        }catch (\Exception $exception){
            return response(['message'=>"Invalid Pocket."]);
        }
    }

    public function pocketSwitch(Request $request)
    {
        try{
            $user = Auth::user();

            if(!$pocket = Pocket::find($request->id)){
                return response(["message"=>"Invalid Pocket.-"]);
            }
            if($pocket->user_id !=$user->id ){
                return response(["message"=>"Invalid Pocket.+"]);
            }
            $pocket->status = $request->status;
            $pocket->save();

            return response(["message"=>$pocket->status?"Pocket is opened.":"Pocket is now 'Invite-Only'","pocket_id"=>$pocket->id],200);
        }catch (\Exception $exception){
            return response(["message"=>"Invalid Pocket.".$exception->getMessage()]);
        }
    }

    public function openSelection(Request $request)
    {
        try{
            $user = Auth::user();

            if(!$pocket = Pocket::find($request->id)){
                return response(["message"=>"Invalid Pocket."]);
            }
            if($pocket->user_id !=$user->id ){
                return response(["message"=>"Invalid Pocket."]);
            }
            $pocket->open_purchasing_item = $request->open_purchasing_item;
            $pocket->save();
            $usersIds = PocketSlot::where(['pocket_id'=>$pocket->id,'status'=>1])->pluck('user_id')->toArray();
//            $tokens
            foreach ($usersIds as $id){
                Notification::shoppingItemNotification($user,User::find($id),$pocket);
            }
            $body = "Shopping item selection has been opened by {$user->name}.Please come and fill your shopping basket with items of your choice.";
            $body = $pocket->open_purchasing_item?$body:"Shopping item selection is now closed by {$user->name}.";
            Notification::sendPushNotification($usersIds,"Shopping List",$body);

            return response(["message"=>$pocket->open_purchasing_item?"Shopping list selection is now open.":"Shopping list selection is now close.","pocket_id"=>$pocket->id],200);
        }catch (\Exception $exception){
            return response(["message"=>"Invalid Pocket.".$exception->getMessage()]);
        }
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

            $invIds = Invoice::where(['pocket_slot_id'=>$slot->id,'payment_status'=>'Not Paid'])->pluck('id');
            InvoiceItem::whereIn('invoice_id',$invIds)->delete();
            Invoice::whereIn('id',$invIds)->delete();


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
            if($invoice->save()){
                $replaced = str_replace("[INVID]",$invoice->id,$invJson);
                $itemsList = [];
                foreach (json_decode($replaced) as $v){
                    $itemsList[] = (array)$v;
                }
                InvoiceItem::insert($itemsList);
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
            return response(["message"=>"Invalid Pocket.".$exception->getMessage()]);
        }
    }

    public function savePushNotificationToken(Request $request)
    {
        try {
            auth()->user()->update(['fcm_token'=>$request->token]);
            return response(["message"=>'token saved successfully.']);
        }catch (\Exception $exception){
            return response(["message"=>'Something went wrong']);
        }
    }

    public function cancelInvitation(Request $request)
    {
        try {
            $user = Auth::user();
            if(!$invitation  = Invitation::find($request->id)){
                return response(['message'=>'Invalid invitation']);
            }
            if(!$pocket = Pocket::find($invitation->pocket_id)){
                return response(['message'=>'Invalid Pocket']);
            }
            if($pocket->user_id != $user->id){
                return response(['message'=>'Invalid Pocket']);
            }
            Notification::where(['model_id'=>$invitation->pocket_id,'type'=>Notification::POCKET_INVITATION])->delete();
            if($invitation->delete()){
                return response(['message'=>'Invitation Cancelled','pocket_id'=>$pocket->id]);
            }

            return response(['message'=>'Something went wrong']);
        }catch (\Exception $exception){
            return response(['message'=>'Something went wrong']);
        }
    }

    public function acceptRequest(Request $request)
    {
        try{
            $user = Auth::user();
            if($slot = PocketSlot::find($request->id)){
                if(!$pocket = Pocket::find($slot->pocket_id)){
                    return response(['message'=>'Invalid Pocket']);
                }
                if($user->id != $pocket->user_id){
                    return response(['message'=>'Access Denied']);
                }
                $hand_count = $slot->hand_count;
                $slotCount = PocketSlot::where(['pocket_id'=>$pocket->id,'status'=>1])->sum('hand_count');
                $available = $pocket->max_keens==0?1:$pocket->max_keens-$slotCount;
                if(!$available){
                    return response(['message'=>"All Slots exhausted."]);
                }
                if($available < $hand_count) {
                    return response(['message'=>"There are just {$available} slot".($available>1?"s":"")." available."]);
                }
                $slot->status = 1;
                $slot->save();
                $slotUser = User::find($slot->user_id);
                Notification::acceptNotification($user,$slotUser,$pocket);

                return response(['message'=>"User accepted","slot_id"=>$slot->id]);

            }
            return response(['message'=>'Invalid Request']);

        }catch (\Exception $exception){
            return response(['message'=>'Something went wrong'.$exception->getMessage()]);
        }
    }

    public function addPaymentItem(Request $request)
    {

//        return ['message'=>$request->id];
        try{
            $user = Auth::user();
            if($pocket = Pocket::find($request->id)){

                if($pocket->user_id == $user->id){
                    $items = [];
                    foreach($request->items as $itemId){
                        $items[] = ['pocket_id'=>$pocket->id,"item_id"=>$itemId];
                    }
                    PocketItem::upsert($items,['pocket_id','item_id'],[]);
                    return response(['message'=>"All items added",'pocket_id'=>$pocket->id]);
                }
            }
            return response(["message"=>"Invalid Pocket"]);

        }catch (\Exception $exception){
            return response(['message'=>'Something went wrong'.$exception->getMessage()]);
        }

    }

    public function removePaymentItem(Request $request)
    {

        try{
            $user = Auth::user();
            if($pocket = Pocket::find($request->id)){

                if($pocket->user_id == $user->id){
                    PocketItem::whereIn('id',$request->payment_items)
                        ->where('pocket_id',$pocket->id)
                        ->where('item_id',"<>",1)
//                        ->whereNotIn('item_id',$itemIds)
                        ->delete();
                    return response(['message'=>"All selected items deleted",'pocket_id'=>$pocket->id]);
                }
            }
            return response(["message"=>"Invalid Pocket"]);

        }catch (\Exception $exception){
            return response(['message'=>'Something went wrong'.$exception->getMessage()]);
        }

    }

    public function addShoppingItem(Request $request)
    {
        try{
            $user = Auth::user();
            if($pocket = Pocket::find($request->id)){
                if($pocket->user_id == $user->id){
                    $items = [];
                    foreach($request->items as $itemX){
                        $item = (object)$itemX;
                        $items[] = [
                            'pocket_id'=>$pocket->id,"purchase_item_id"=>$item->purchase_item_id,
                            "unit_price"=>floatval($item->unit_price),"description"=>$item->description,
                            "person_count"=>$item->person_count,"available"=>$item->available
                        ];
                    }
                    PurchasingItem::upsert(
                        $items,['pocket_id','purchase_item_id'],
                        ['unit_price','description','person_count','available']
                    );
                    return response(['message'=>"All items added",'pocket_id'=>$pocket->id]);
                }
                return response(["message"=>"Invalid Pocket"]);
            }
            return response(["message"=>"Invalid Pocket"]);

        }catch (\Exception $exception){
            return response(['message'=>'Something went wrong']);
        }
    }

    public function removeShoppingItem(Request $request)
    {

        try{
            $user = Auth::user();
            foreach ($request->shoppingItems as $shItem){
                if($purchasingItem = PurchasingItem::find($shItem)){
                    $purchaseItem = PurchaseItem::find($purchasingItem->purchase_item_id);
                    $pocket = Pocket::find($purchasingItem->pocket_id);
                    if($pocket->user_id == $user->id){
                        $slotIds = PocketSlot::where(['pocket_id'=>$pocket->id,"status"=>1])->pluck('id');
                        $affectSlots = PurchasePreference::where(['purchasing_item_id'=>$purchasingItem->id])
                            ->where('quantity',">",0)
                            ->whereIn('pocket_slot_id',$slotIds)->pluck('pocket_slot_id');

                        foreach(PocketSlot::whereIn('id',$affectSlots)->get() as $slot){
                            $recipient = User::find($slot->user_id);
                            $title = "Shopping Item";
                            $body =  "We are sorry to inform you that you choice [{$purchaseItem->name} {$purchasingItem->description} @ ₦{$purchasingItem->unit_price}] has been
                        removed from the shopping list by Pocket owner.
                        This may be due to in-availability or change in price.\n Best regards";
                            Notification::personalNotification($user,$recipient,$title,$body);
                        }
                        $purchasingItem->delete();
                    }
                }
            }

            return response(['message'=>"All selected items deleted",'pocket_id'=>$pocket->id]);

        }catch (\Exception $exception){
            return response(['message'=>'Something went wrong'.$exception->getMessage()]);
        }

    }

    public function subscribeShoppingItem(Request $request)
    {
        try{
            $user = \auth()->user();
            $slot = PocketSlot::where(['pocket_id'=>$request->id,'user_id'=>$user->id])->first();

            if($slot){
                $listArr = [];
                foreach ($request->shoppingList as $listX){
                    $list = (object)$listX;
                    $listArr[] = [
                        'purchasing_item_id'=>$list->purchasing_item_id,
                        'pocket_slot_id'=>$slot->id,'quantity'=>$list->quantity
                    ];

                }
                PurchasePreference::upsert($listArr,['purchasing_item_id','pocket_slot_id'],['quantity']);
                return response(['message'=>"Shopping Preference Updated"]);
            }
            return response(['message'=>"Invalid Slot"]);
        }catch (\Exception $exception){
            return response(['message'=>"Something went wrong".$exception->getMessage()]);
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

    public function addKeen(Request $request)
    {
//        return "".;
       try{
           $user = \auth()->user();
           $pocket = Pocket::find($request->id);
           if($pocket){
               if($user->id !=$pocket->user_id){
                   return response(['message'=>"Invalid Pocket."]);
               }
//            name,phone_number,number of hands(slots),create user,create pocket_slot,
               $newUser = User::where('phone_number',$request->phone_number)->first();
               if(!$newUser){
                   $newUser = new User();
                   $newUser->username = $request->phone_number;
                   $newUser->phone_number = $request->phone_number;
                   $newUser->name = $request->name;
                   $newUser->email = $request->phone_number;
                   $newUser->password = '';
                   $newUser->save();
               }
               if($newUser){

                   $ps = PocketSlot::where(['pocket_id'=>$pocket->id,'user_id'=>$newUser->id])->first();
                   if(!$ps){
                       $slotNumber = PocketSlot::where(['pocket_id'=>$pocket->id,'status'=>1])->count()+1;
                       $ps = new PocketSlot();
                       $ps->pocket_id = $pocket->id;
                       $ps->user_id = $newUser->id;
                       $ps->phone_number = $newUser->phone_number;
                       $ps->name = $newUser->name;
                       $ps->slot_number = $slotNumber;
                       $ps->hand_count = $request->slots;
                       $ps->amount_paying = $request->slots * $pocket->amount_per_hand;
                       $ps->status = 1;
                       $ps->comment = '';
                       $ps->save();
                   }else{

//                       $ps->hand_count = $request->slots;
//                       $ps->amount_paying = $request->slots * $pocket->amount_per_hand;
//                       $total = $pocket->month_count * $ps->amount_paying;
//                       $invoiceIds = Invoice::where('pocket_slot_id',$ps->id)->pluck('id');
//                       $paid = Invoice::where(['pocket_slot_id'=>$ps->id,'payment_status'=>'Paid'])->sum('amount');
//                       $invoiceItems = InvoiceItem::whereIn('invoice_id',$invoiceIds)->where(['item_id'=>1])->get();
//                       $addedAmount = 0;
//                       $invItCount = count($invoiceItems);
//                       foreach (range(0,$pocket->month_count-1) as $month){
//                           if($month <= $invItCount && ($addedAmount + $ps->amount_paying <= $paid) ){
//                               $invIt = $invoiceItems[$month];
//                               $invIt->amount = $ps->amount_paying;
//                               $invIt->save();
//                               $addedAmount += $ps->amount_paying;
//                           }else if($month > $invItCount  && ($addedAmount + $ps->amount_paying < $paid) ){
//                               $invId = $invoiceIds->last();
//                               $invIt = new InvoiceItem();
//                               $invIt->invoice_id = $invId;
//                               $invIt->amount = $ps->amount_paying;
//                               $invIt->item_id = 1;
//                               $invIt->month = $month+1;
//                               $invIt->type = 'Paid';
//                               $invIt->save();
//                           }else if($addedAmount >= $paid){
//                                break;
//                           }
//
//                       }



                   }

               }
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

           }

           return response(['message'=>"Invalid Pocket" ]);
       }catch (Exception $exception){
           return response(['message'=>$exception->getMessage()]);
       }
//        must be owner
//        invoice must belong to a pocket you own
    }

}
