<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharityGoalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'charity_project_id', 'name', 'unit', 'target_quantity', 'unit_price',
    ];

    protected $casts = [
        'target_quantity' => 'integer',
        'unit_price' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(CharityProject::class, 'charity_project_id');
    }
}
