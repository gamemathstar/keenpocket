<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdashiContributor extends Model
{
    use HasFactory;

    protected $fillable = [
        'adashi_member_id', 'user_id', 'share_amount', 'is_active', 'joined_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
    ];
}


