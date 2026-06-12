<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        $notifications = Notification::where('user_id', auth()->id())
            ->when($filter === 'unread', fn ($q) => $q->where('status', 'Not Read'))
            ->orderByDesc('id')->limit(100)->get();

        $unreadCount = Notification::where('user_id', auth()->id())->where('status', 'Not Read')->count();

        return view('notifications', compact('notifications', 'filter', 'unreadCount'));
    }

    /** Mark a notification read and deep-link to its pocket/adashi. */
    public function open($id)
    {
        $n = \App\Models\Notification::where('user_id', auth()->id())->find($id);
        if (!$n) {
            return redirect()->route('notifications.index');
        }
        $n->update(['status' => 'Read']);

        $uid = auth()->id();
        $mid = $n->model_id;
        if ($mid) {
            $inPocket = \App\Models\Pocket::where('id', $mid)->where('user_id', $uid)->exists()
                || \App\Models\PocketSlot::where(['pocket_id' => $mid, 'user_id' => $uid])->exists();
            if ($inPocket) {
                return redirect()->route('pockets.show', $mid);
            }
            $inAdashi = \App\Models\Adashi::where('id', $mid)->where('admin_id', $uid)->exists()
                || \App\Models\AdashiMember::where(['adashi_id' => $mid, 'user_id' => $uid])->exists();
            if ($inAdashi) {
                return redirect()->route('adashi.show', $mid);
            }
        }

        return redirect()->route('notifications.index');
    }

    public function readOne($id)
    {
        Notification::where('user_id', auth()->id())->where('id', $id)
            ->update(['status' => 'Read']);

        return back();
    }

    public function readAll()
    {
        Notification::where('user_id', auth()->id())
            ->where('status', 'Not Read')
            ->update(['status' => 'Read']);

        return back()->with('status', 'All notifications marked as read.');
    }
}
