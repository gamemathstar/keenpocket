<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    use HasFactory;

    protected $fillable = ['school_id', 'student_id', 'mode', 'percent', 'min_monthly', 'note', 'status'];
    protected $casts = ['percent' => 'integer', 'min_monthly' => 'integer'];

    /** The agreed minimum a parent must pay toward a term fee under this plan. */
    public function minimumFor(int $termFee): int
    {
        if ($this->mode === 'min_monthly') {
            return (int) $this->min_monthly;
        }
        return (int) round($termFee * (($this->percent ?: 100) / 100));
    }
}
