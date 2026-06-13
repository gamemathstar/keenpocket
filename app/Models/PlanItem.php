<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id', 'name', 'quantity', 'unit', 'unit_price', 'status',
        'claimed_by', 'priority', 'note', 'created_by', 'purchased_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'integer',
        'priority' => 'boolean',
        'purchased_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function claimer()
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    /** Line value (quantity × unit price), 0 when no price set. */
    public function lineValue(): int
    {
        return (int) $this->quantity * (int) ($this->unit_price ?? 0);
    }
}
