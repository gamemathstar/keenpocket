<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'username',
        'fcm_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function __allPockets()
    {
        return PocketSlot::join("pockets","pockets.id","=","pocket_slots.pocket_id")
            ->leftJoin('invoices','invoices.pocket_slot_id','=','pocket_slots.id')
            ->leftJoin('invoice_item','invoice_item.invoice_id','=','invoices.id')
            ->select([
                'pockets.id','pocket_type','title','description','year',
                'month_count','max_keens',DB::raw('FORMAT(amount_per_hand,0) AS amount_per_hand'),'per_hand_allowed',
                'start_month','slot_number','hand_count',DB::raw('FORMAT(amount_paying,0) AS amount_paying')
                ,DB::raw("FORMAT(hand_count*month_count*amount_per_hand,0) AS target_amount"),
                DB::raw('FORMAT(SUM(invoice_item.amount),0) AS contributed_amount'),
                'pocket_slots.created_at AS joined',"pocket_slots.id as slot_id",'pockets.status'
            ])
            ->where(['pocket_slots.user_id'=>$this->id,"invoice_item.item_id"=>1])
            ->groupBy("pockets.id");
    }

    public function __subscribedPockets()
    {
        return $this->__allPockets()->where('pocket_slots.status',1);
    }

    public function __pendingPockets()
    {
        return $this->__allPockets()->where('pocket_slots.status',0);
    }

    public function myPockets()
    {
        return $this->__allPockets()->get();
    }

    public function activePockets()
    {
        return $this->__subscribedPockets()->get();
    }

    public function activePocketsWithInvoices()
    {
        $pockets = $this->activePockets();

        foreach ($pockets  as $pocket){
            $invoices = Invoice::where('pocket_slot_id',$pocket->slot_id)
                ->select(['invoices.id','invoice_no',DB::raw('FORMAT(amount,0) AS amount'),'reference_no',
                    'payment_status','payment_date','paid_through',])
                ->get();
            $items = [];
            foreach ($invoices as $invoice){
                $invoice->items = InvoiceItem::join("items","items.id","=","invoice_item.id")
                    ->select([
                        'invoice_item.id','items.name as item','category as item_type',DB::raw('FORMAT(amount,0) AS amount')
                    ])->where('invoice_id',$invoice->id)->get();
            }
            $pocket->invoices = $invoices;
        }
        $user = $this;
        return compact('user','pockets');
    }
}
