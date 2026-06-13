<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adashi extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'amount_per_cycle', 'total_members', 'start_date',
        'cycle_duration_days', 'current_cycle_number', 'admin_id',
        'rotation_mode', 'status', 'is_public',
        'bank', 'nuban', 'account_name',
    ];

    protected $casts = [
        'start_date' => 'date',
        'is_public' => 'boolean',
    ];

    public function members()
    {
        return $this->hasMany(AdashiMember::class);
    }

    public function records()
    {
        return $this->hasMany(AdashiRecord::class);
    }
}


