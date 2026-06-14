<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'name', 'address', 'contact', 'logo', 'background_image',
        'bank', 'nuban', 'account_name',
    ];

    public function classes() { return $this->hasMany(SchoolClass::class); }
    public function students() { return $this->hasMany(Student::class); }
    public function feeItems() { return $this->hasMany(FeeItem::class); }
}
