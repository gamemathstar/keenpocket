<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharityProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'pocket_id', 'title', 'description', 'goal_type', 'target_amount', 'status',
    ];

    protected $casts = [
        'target_amount' => 'integer',
    ];

    public function pocket()
    {
        return $this->belongsTo(Pocket::class);
    }

    public function goalItems()
    {
        return $this->hasMany(CharityGoalItem::class);
    }

    public function isItemGoal(): bool
    {
        return $this->goal_type === 'items';
    }
}
