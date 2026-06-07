<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adashi extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function members()
    {
        return $this->hasMany(AdashiMember::class);
    }

    public function records()
    {
        return $this->hasMany(AdashiRecord::class);
    }
}


