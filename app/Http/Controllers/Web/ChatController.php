<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Adashi;
use App\Models\AdashiMember;
use App\Models\Message;
use App\Models\Pocket;
use App\Models\PocketSlot;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /** Post a message to a pocket's or adashi's group chat. */
    public function post(Request $request, $type, $id)
    {
        abort_unless(config('chat.enabled', true) && in_array($type, ['pocket', 'adashi'], true), 404);
        $data = $request->validate(['body' => 'required|string|max:1000']);

        abort_unless($this->canAccess($type, (int) $id, $request->user()->id), 403, 'Only members of this group can post.');

        $message = Message::create([
            'context_type' => $type, 'context_id' => (int) $id,
            'user_id' => $request->user()->id, 'body' => $data['body'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $message->id, 'body' => $message->body, 'mine' => true, 'name' => 'You', 'ago' => 'just now',
            ]);
        }

        return back()->withFragment('chat');
    }

    /** JSON feed of messages after a given id (for live polling). */
    public function feed(Request $request, $type, $id)
    {
        abort_unless(config('chat.enabled', true) && in_array($type, ['pocket', 'adashi'], true), 404);
        abort_unless($this->canAccess($type, (int) $id, $request->user()->id), 403);

        $after = (int) $request->query('after', 0);
        $uid = $request->user()->id;

        $rows = Message::where(['context_type' => $type, 'context_id' => $id])
            ->when($after, fn ($q) => $q->where('messages.id', '>', $after))
            ->join('users', 'users.id', '=', 'messages.user_id')
            ->select('messages.id', 'messages.body', 'messages.user_id', 'messages.created_at', 'users.name')
            ->orderBy('messages.id')->limit(100)->get();

        return response()->json($rows->map(fn ($m) => [
            'id' => $m->id,
            'body' => $m->body,
            'mine' => $m->user_id == $uid,
            'name' => $m->user_id == $uid ? 'You' : $m->name,
            'ago' => \Illuminate\Support\Carbon::parse($m->created_at)->diffForHumans(null, true).' ago',
        ])->values());
    }

    /** Member (active) or admin of the pocket/adashi. */
    public static function canAccess(string $type, int $id, int $userId): bool
    {
        if ($type === 'pocket') {
            $pocket = Pocket::find($id);
            if (!$pocket) {
                return false;
            }

            return $pocket->user_id == $userId
                || PocketSlot::where(['pocket_id' => $id, 'user_id' => $userId, 'status' => 1])->exists();
        }

        $adashi = Adashi::find($id);
        if (!$adashi) {
            return false;
        }

        return $adashi->admin_id == $userId
            || AdashiMember::where(['adashi_id' => $id, 'user_id' => $userId, 'is_active' => 1])->exists();
    }
}
