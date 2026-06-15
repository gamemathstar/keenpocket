<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A friendship between two users. `user_id` is the requester, `friend_id` the
 * recipient. status is "pending" until the recipient accepts ("accepted").
 */
class Friendship extends Model
{
    protected $fillable = ['user_id', 'friend_id', 'status'];

    public function requester()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'friend_id');
    }
}
