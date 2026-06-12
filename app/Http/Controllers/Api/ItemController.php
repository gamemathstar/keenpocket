<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Pocket;
use App\Models\PocketItem;
use App\Models\PocketSlot;
use App\Models\PurchaseItem;
use App\Models\PurchasePreference;
use App\Models\PurchasingItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
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
            return response(['message'=>'Something went wrong']);
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
            return response(['message'=>'Something went wrong']);
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
            return response(['message'=>'Something went wrong']);
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
            return response(['message'=>"Something went wrong"]);
        }
    }
}
