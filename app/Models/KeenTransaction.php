<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeenTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'amount', 'reason'];
    protected $casts = ['amount' => 'integer'];
}
