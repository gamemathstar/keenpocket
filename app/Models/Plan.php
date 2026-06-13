<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'title', 'month', 'budget', 'status'];

    protected $casts = [
        'budget' => 'integer',
    ];

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
