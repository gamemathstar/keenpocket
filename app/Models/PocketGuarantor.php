<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PocketGuarantor extends Model
{
    use HasFactory;

    protected $fillable = [
        'pocket_id', 'slot_id', 'requester_id', 'guarantor_id', 'status', 'note',
    ];

    public function pocket()
    {
        return $this->belongsTo(Pocket::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function guarantor()
    {
        return $this->belongsTo(User::class, 'guarantor_id');
    }
}
