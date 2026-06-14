<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolPayment extends Model
{
    use HasFactory;

    protected $fillable = ['school_id', 'student_id', 'term', 'amount', 'note', 'recorded_by'];
    protected $casts = ['term' => 'integer', 'amount' => 'integer'];
}
