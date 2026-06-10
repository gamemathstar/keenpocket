<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'adashi_record_id', 'recipient_user_id', 'amount', 'currency',
        'provider', 'reference', 'transfer_code', 'status',
        'failure_reason', 'gateway_response', 'disbursed_at',
    ];

    protected $casts = [
        'disbursed_at' => 'datetime',
    ];

    public function isSettled(): bool
    {
        return in_array($this->status, ['pending', 'success'], true);
    }
}
