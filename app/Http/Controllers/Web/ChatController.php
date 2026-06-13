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

        Message::create([
            'context_type' => $type, 'context_id' => (int) $id,
            'user_id' => $request->user()->id, 'body' => $data['body'],
        ]);

        return back()->withFragment('chat');
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
