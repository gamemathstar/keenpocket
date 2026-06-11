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
