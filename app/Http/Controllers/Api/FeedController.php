<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    public function searchUser(Request $request)
    {
        $query = $request->searchQuery;
        return User::select(['id', 'name', 'phone_number'])
            ->where("phone_number", "LIKE", "%$query%")->get();

    }

    public function posts(Request $request)
    {
        //$user = auth()->user();
        return Post::select(["title", "body", "featured_image", DB::raw("DATE_FORMAT(created_at,'%D %b, %Y %r') AS date_posted")])->get();
    }

    public function post(Request $request)
    {
        return Post::select(["title", "body", "featured_image", DB::raw("DATE_FORMAT(created_at,'%D %b, %Y %r') AS date_posted")])->find($request->id);
    }

    public function notifications(Request $request)
    {

        $user = Auth::user();
        $date = \Carbon\Carbon::now();
        $lastMonth = $date->subMonth(2)->format('Y-m-d');


        $notifications = Notification::join("users AS receiver", "receiver.id", "=", "notifications.user_id")
            ->leftJoin("users AS sender", "sender.id", "=", "notifications.sender_id")
            ->select([
                'notifications.id', 'user_id',
                'type', 'title', 'body', 'sender_id',
                'model_id', 'status', DB::raw("IF(sender.name,sender.name,'System') AS sender"),
                'receiver.name AS receiver', 'notifications.created_at AS posted_date'
            ])
            ->whereRaw("(notifications.created_at>='$lastMonth' OR status='Not Read') AND user_id=" . $user->id)
            ->orderBy('notifications.created_at','DESC');

        if ($request->id) {
            return $notifications->where('id', '=', $request->id)->first();
        }
        return $notifications->get();
    }

    public function savePushNotificationToken(Request $request)
    {
        try {
            auth()->user()->update(['fcm_token'=>$request->token]);
            return response(["message"=>'token saved successfully.']);
        }catch (\Exception $exception){
            return response(["message"=>'Something went wrong']);
        }
    }
}
