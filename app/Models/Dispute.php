<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'context_type', 'context_id', 'raised_by', 'subject', 'body',
        'status', 'resolution', 'resolved_by', 'resolved_at',
    ];

    protected $casts = ['resolved_at' => 'datetime'];

    public function raiser()
    {
        return $this->belongsTo(User::class, 'raised_by');
    }
}
