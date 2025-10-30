<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdashiRecord extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function adashi()
    {
        return $this->belongsTo(Adashi::class);
    }
}


