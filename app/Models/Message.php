<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['context_type', 'context_id', 'user_id', 'body'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Recent messages (oldest→newest) for a context, with the sender's name. */
    public static function recentFor(string $type, int $id, int $limit = 60)
    {
        return static::where(['context_type' => $type, 'context_id' => $id])
            ->join('users', 'users.id', '=', 'messages.user_id')
            ->select('messages.id', 'messages.body', 'messages.user_id', 'messages.created_at', 'users.name')
            ->latest('messages.id')->limit($limit)->get()->reverse()->values();
    }
}
