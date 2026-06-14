<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory;

    protected $fillable = ['school_id', 'name'];

    public function feeItems() { return $this->hasMany(FeeItem::class); }
    public function students() { return $this->hasMany(Student::class); }

    /** Total fee for a given term = sum of this class's fee items for that term. */
    public function termFee(int $term): int
    {
        return (int) $this->feeItems()->where('term', $term)->sum('amount');
    }
}
