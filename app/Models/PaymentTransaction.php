<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'user_id', 'provider', 'reference', 'amount',
        'currency', 'status', 'authorization_url', 'gateway_response', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
}
