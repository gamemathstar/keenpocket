<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'label', 'account_name', 'bank', 'nuban', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** "Main · GTBank · 0123456789" — a compact one-line label. */
    public function display(): string
    {
        return trim(($this->label ? $this->label.' · ' : '').$this->bank.' · '.$this->nuban);
    }
}
