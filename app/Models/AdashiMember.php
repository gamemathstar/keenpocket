<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdashiMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'adashi_id', 'user_id', 'position', 'has_received',
        'next_receiver_date', 'joined_at', 'is_active',
    ];

    protected $casts = [
        'has_received' => 'boolean',
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
        'next_receiver_date' => 'datetime',
    ];

    public function adashi()
    {
        return $this->belongsTo(Adashi::class);
    }
}


