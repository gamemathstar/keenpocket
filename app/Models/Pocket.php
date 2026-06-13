<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pocket extends Model
{
    use HasFactory;

    protected $casts = [
        'charity_enabled' => 'boolean',
        'charity_donors_visible' => 'boolean',
    ];

    public function charityProjects()
    {
        return $this->hasMany(CharityProject::class);
    }

    /** The current active charity project, if any. */
    public function activeCharityProject()
    {
        return $this->charityProjects()->where('status', 'ACTIVE')->latest('id')->first();
    }
}
