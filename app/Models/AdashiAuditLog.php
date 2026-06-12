<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdashiAuditLog extends Model
{
    use HasFactory;

    protected $fillable = ['adashi_id', 'user_id', 'action', 'meta'];
}
