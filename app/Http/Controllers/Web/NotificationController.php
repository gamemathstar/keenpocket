<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderByDesc('id')->limit(100)->get();

        return view('notifications', compact('notifications'));
    }

    public function readAll()
    {
        Notification::where('user_id', auth()->id())
            ->where('status', 'Not Read')
            ->update(['status' => 'Read']);

        return back()->with('status', 'All notifications marked as read.');
    }
}
