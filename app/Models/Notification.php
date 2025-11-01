<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\PushNotificationService;

class Notification extends Model
{
    use HasFactory;
    const PERSONAL_MESSAGE = "Personal Message";
    const PAYMENT_RECEIVED = "Payment Received";
    const PAYMENT_MADE = "Payment Made";
    const POCKET_INVITATION = "Pocket Invitation";
    const PAYMENT_REMINDER = "Payment Reminder";
    const ITEM_SELECTION = "Item Selection";
    const REQUEST_MADE = "Request Made";
    const REQUEST_APPROVED = "Request Approved";
    const USER_JOINED = "User Joined";

    public static function make(User $sender,User $recipient,Model $model,$title,$body,$type,$do=1)
    {
        $notification = new Notification();
        $notification->user_id = $recipient->id;
        $notification->sender_id = $sender?$sender->id:0;
        $notification->title = $title;
        $notification->body = $body;
        $notification->type = $type;
        $notification->model_id = $model->id;
        $notification->status = 'Not Read';
        $notification->save();

        if($do){
            self::sendPushNotification($recipient,$title,$body);
        }

    }

    public static function joinRequestNotification(User $sender,User $recipient,Model $model)
    {
        $pocket = $model;
        $slot = PocketSlot::where(["pocket_id"=>$model->id,"user_id"=>$sender->id])->first();
        $title = "Join Request";
        $body = ucwords($sender->name)." has requested to join your {$pocket->pocket_type} pocket [ {$pocket->title} ] for {$slot->hand_count} hands.\n {$sender->name} is awaiting acceptance.";
        self::make($sender,$recipient,$model,$title,$body,self::REQUEST_MADE);
    }

    public static function joinNotification(User $sender,User $recipient,Model $model)
    {
        $pocket = $model;//Pocket::find($model->pocket_id);
        $slot = PocketSlot::where(["pocket_id"=>$model->id,"user_id"=>$recipient->id])->first();
        $title = "Invitation Accepted";
        $body = ucwords($sender->name)." has joined your {$pocket->pocket_type} pocket [ {$pocket->title} ] for {$slot->hand_count} hands.";
        self::make($sender,$recipient,$model,$title,$body,self::USER_JOINED);
    }

    public static function acceptNotification(User $sender,User $recipient,Model $model)
    {
        $pocket = $model;
        $slot = PocketSlot::where(["pocket_id"=>$model->id,"user_id"=>$recipient->id])->first();
        $title = "Join Request Accepted";
        $body = ucwords($sender->name)." has accepted your request to join the {$pocket->pocket_type} pocket [ {$pocket->title} ] for {$slot->hand_count} hands.";
        self::make($sender,$recipient,$model,$title,$body,self::REQUEST_APPROVED);
    }

    public static function paymentNotification(User $sender,User $recipient,Model $model)
    {
//        $invoice = Invoice::find($model->id);
        $title = "Invoice Generated.";
        $body = ucwords($sender->name)." has generated payment invoice to pay ₦{$model->amount} with invoice number [ {$model->invoice_no} ].Please verify this payment";
        self::make($sender,$recipient,$model,$title,$body,self::PAYMENT_MADE);
    }

    public static function paymentReceivedNotification(User $sender,User $recipient,Model $model,$status=1)
    {
        $title = $status?"Payment Verified":"Payment Revoked";
        $body = ucwords($sender->name)." has verified that you paid ₦{$model->amount} with invoice number [ {$model->invoice_no} ].You can download your receipt anytime.";
        $body = $status?$body:ucwords($sender->name)." has revoked your payment of ₦{$model->amount} with invoice number [ {$model->invoice_no} ].You can call pocket owner for clarifications.";
        self::make($sender,$recipient,$model,$title,$body,self::PAYMENT_RECEIVED);
    }

    public static function paymentReminderNotification(User $recipient,Model $model,$months)
    {
        $title = "System: Payment Reminder";
        $body = "Dear ".ucwords($recipient->name).", you are due to pay for $months and you payments are yet to be confirmed.\n\r Please if you have made these payments please advise pocket owner to verify.\n\rWarm regards.\n\r System";
        self::make(null,$recipient,$model,$title,$body,self::PAYMENT_REMINDER);
    }

    public static function pocketInvitationNotification(User $sender,User $recipient,Model $model)
    {   $months = ["","JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEP","OCT","NOV","DEC"];
        $title = "Pocket Invitation";
        $body = "You have been invited by {$sender->name} to join the {$model->pocket_type} Pocket, starting {$months[$model->start_month]} of {$model->year} and last for {$model->month_count} months.\n

            Amount/Hand: {$model->amount_per_hand}";
        self::make($sender,$recipient,$model,$title,$body,self::POCKET_INVITATION);
    }

    public static function personalNotification(User $sender,User $recipient,Model $model,$title,$body)
    {
        self::make($sender,$recipient,$model,$title,$body,self::PERSONAL_MESSAGE);
    }

    public static function shoppingItemNotification(User $sender,User $recipient,Model $model)
    {
        $title = "Shopping List";
        $body = "Shopping item selection has been opened by {$sender->name}.Please come and fill your shopping basket with items of your choice.";
        self::make($sender,$recipient,$model,$title,$body,self::ITEM_SELECTION,0);
    }

    public static function sendPushNotification($recipient,$title,$body)
    {
        $service = app(PushNotificationService::class);

        if (is_array($recipient)) {
            $userIds = $recipient;
        } else {
            $userIds = [$recipient->id];
        }

        return $service->sendToUsers($userIds, $title, $body);
    }

    // Adashi-specific notifications
    public static function adashiInvoiceRaised(User $sender, User $recipient, Model $adashi, Model $record, $amount)
    {
        $title = "Adashi Invoice Raised";
        $body = ucwords($sender->name) . " has raised an invoice for ₦" . number_format($amount) . " in Adashi [{$adashi->name}] - Cycle {$record->cycle_number}";
        self::make($sender, $recipient, $adashi, $title, $body, self::PAYMENT_MADE);
    }

    public static function adashiPaymentReceived(User $recipient, Model $adashi, Model $record)
    {
        $title = "Adashi Payment Received";
        $amount = number_format($record->total_collected);
        $body = "Congratulations! You have received ₦{$amount} from Adashi [{$adashi->name}] - Cycle {$record->cycle_number}";
        self::make(null, $recipient, $adashi, $title, $body, self::PAYMENT_RECEIVED);
    }

    public static function adashiNextReceiver(User $recipient, Model $adashi, Model $record)
    {
        $title = "Adashi: You're Next";
        $dueDate = \Carbon\Carbon::parse($record->due_at)->format('F j, Y');
        $body = "You are the next receiver in Adashi [{$adashi->name}]. Expected payout date: {$dueDate}";
        self::make(null, $recipient, $adashi, $title, $body, self::PAYMENT_REMINDER);
    }

    public static function adashiPaymentReminder24h(User $recipient, Model $adashi, Model $record)
    {
        $title = "Adashi: Payment Due in 24 Hours";
        $body = "Dear " . ucwords($recipient->name) . ", your payment of ₦" . number_format($adashi->amount_per_cycle) . " for Adashi [{$adashi->name}] - Cycle {$record->cycle_number} is due in 24 hours.";
        self::make(null, $recipient, $adashi, $title, $body, self::PAYMENT_REMINDER);
    }

    public static function adashiReceiver30Percent(User $recipient, Model $adashi, Model $record)
    {
        $title = "Adashi: Payment Notice (30% Time Remaining)";
        $dueDate = \Carbon\Carbon::parse($record->due_at)->format('F j, Y');
        $body = "You will receive ₦" . number_format($record->total_collected ?: $adashi->amount_per_cycle * $adashi->total_members) . " from Adashi [{$adashi->name}] around {$dueDate}. 30% of cycle time remaining.";
        self::make(null, $recipient, $adashi, $title, $body, self::PAYMENT_REMINDER);
    }

    public static function adashiReceiver24h(User $recipient, Model $adashi, Model $record)
    {
        $title = "Adashi: You Receive Payment Tomorrow";
        $amount = number_format($record->total_collected ?: $adashi->amount_per_cycle * $adashi->total_members);
        $body = "You will receive ₦{$amount} from Adashi [{$adashi->name}] tomorrow. Cycle {$record->cycle_number}";
        self::make(null, $recipient, $adashi, $title, $body, self::PAYMENT_REMINDER);
    }

    public static function adashiOverdueAdminAlert(User $recipient, Model $adashi, Model $record)
    {
        $title = "Adashi: Overdue Payment Alert";
        $dueDate = \Carbon\Carbon::parse($record->due_at)->format('F j, Y');
        $body = "Adashi [{$adashi->name}] - Cycle {$record->cycle_number} payment is overdue (due: {$dueDate}). {$record->paid_members_count}/{$adashi->total_members} members have paid.";
        self::make(null, $recipient, $adashi, $title, $body, self::PAYMENT_REMINDER);
    }

}
