<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BannedUser;
use App\Models\Invitation;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Notification;
use App\Models\Pocket;
use App\Models\PocketItem;
use App\Models\PocketSlot;
use App\Models\PurchaseItem;
use App\Models\PurchasingItem;
use App\Models\User;
use App\Services\Referral\ReferralService;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PocketController extends Controller
{
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
                $invoice->items = \App\Models\InvoiceItem::join("items","items.id","=","invoice_item.id")
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

    public function search(Request $request)
    {
        $queryString = $request->queryString;
        $pockets = [];
        $by = $request->by;
        if ($by == 'title') {
            //return [$by,$queryString];
            $pockets = Pocket::where('title', "LIKE", "%" . $queryString . "%")
                // Closed (invite-only) pockets are not discoverable — only your own.
                ->where(fn ($w) => $w->where('pockets.status', 1)->orWhere('pockets.user_id', auth()->id()))
                ->leftJoin('pocket_slots', "pocket_slots.pocket_id", "=", "pockets.id")
                ->join("users", "users.id", "=", "pockets.user_id")
                ->select([
                    'pockets.id', 'pocket_type', 'title', 'max_keens', DB::raw('FORMAT(amount_per_hand,0) AS amount_per_hand'), "start_month", "month_count", 'year'
                    , 'open_purchasing_item', DB::raw('SUM(IF(pocket_slots.status=1,hand_count,0)) AS slot_used'), "pockets.status", "users.name AS created_by", "users.phone_number"
                ])
//                ->where(['pocket_slots.status' => 1])
                ->groupBy('pockets.id')->get();
        } elseif ($by == 'phone_number') {
            $phone_number  = PhoneNumber::normalize($queryString);
            $userIds = User::where("phone_number", "LIKE", "%{$phone_number}%")->pluck('id')->toArray();
            if (!count($userIds)) return [];
            $pockets = Pocket::whereIn('pockets.user_id', $userIds)
                ->where(fn ($w) => $w->where('pockets.status', 1)->orWhere('pockets.user_id', auth()->id()))
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
                ->where(fn ($w) => $w->where('pockets.status', 1)->orWhere('pockets.user_id', auth()->id()))
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
            return ['message'=>'Operation failed.'];
        }
        return [];
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
            // Create the pocket, its default item and the owner's slot atomically.
            DB::transaction(function () use ($pocket, $user, $request) {
                $pocket->save();

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
            });

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
            return DB::transaction(function () use ($request) {
            $user = Auth::user();
            // Lock the pocket row so two concurrent joins can't oversubscribe slots.
            $pocket = Pocket::where('id', $request->id)->lockForUpdate()->first();
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

                // Growth loop: a referred user joining a pocket qualifies their referral.
                app(ReferralService::class)->qualifyQuietly($user);

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
            });

        }catch (\Exception $exception){
            \Illuminate\Support\Facades\Log::warning('joinPocket failed: '.$exception->getMessage());
            return response(['message' => 'Invalid Pocket.']);
        }
    }

    public function inviteUser(Request $request)
    {
        try{
            $user = Auth::user();
            $phone_number  = PhoneNumber::normalize($request->phone_number);
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
            return response(["message"=>"Invalid Pocket."]);
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
            return response(["message"=>"Invalid Pocket."]);
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
            return response(['message'=>'Something went wrong']);
        }
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
       }catch (\Exception $exception){
           return response(['message'=>'Operation failed.']);
       }
//        must be owner
//        invoice must belong to a pocket you own
    }
}
