<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeItem extends Model
{
    use HasFactory;

    protected $fillable = ['school_id', 'school_class_id', 'term', 'name', 'amount'];
    protected $casts = ['term' => 'integer', 'amount' => 'integer'];
}
