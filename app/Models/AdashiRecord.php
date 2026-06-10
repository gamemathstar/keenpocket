<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdashiRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'adashi_id', 'cycle_number', 'due_at', 'total_collected',
        'receiver_user_id', 'receiver_member_id', 'paid_members_count', 'status',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function adashi()
    {
        return $this->belongsTo(Adashi::class);
    }
}


