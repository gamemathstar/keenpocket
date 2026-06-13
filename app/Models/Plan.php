<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'title', 'month', 'period_type', 'budget', 'status'];

    protected $casts = [
        'budget' => 'integer',
    ];

    /** Human label for the plan's period (a month like "June 2026" or a year). */
    public function periodLabel(): string
    {
        if (!$this->month) {
            return 'No period set';
        }
        if ($this->period_type === 'year') {
            return $this->month.' · whole year';
        }
        try {
            return \Illuminate\Support\Carbon::createFromFormat('Y-m', $this->month)->format('F Y');
        } catch (\Throwable $e) {
            return $this->month;
        }
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function items()
    {
        return $this->hasMany(PlanItem::class);
    }

    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'plan_collaborators');
    }

    /** Can this user view/edit the plan (owner or collaborator)? */
    public function accessibleBy(int $userId): bool
    {
        return $this->owner_id == $userId
            || $this->collaborators()->where('users.id', $userId)->exists();
    }
}
