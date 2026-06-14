<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['school_id', 'school_class_id', 'parent_id', 'name'];

    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function parent() { return $this->belongsTo(User::class, 'parent_id'); }
    public function payments() { return $this->hasMany(SchoolPayment::class); }
    public function plan() { return $this->hasOne(PaymentPlan::class)->where('status', 'ACTIVE'); }

    public function paidForTerm(int $term): int
    {
        return (int) $this->payments()->where('term', $term)->sum('amount');
    }
}
