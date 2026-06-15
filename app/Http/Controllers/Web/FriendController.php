<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\Notification;
use App\Models\Referral;
use App\Models\User;
use App\Services\Referral\ReferralService;
use Illuminate\Http\Request;

/**
 * Mutual friends with a request → accept flow. A friendship row is created
 * "pending" by the requester; the recipient accepts to make it "accepted".
 */
class FriendController extends Controller
{
    public function index(ReferralService $referrals)
    {
        $user = auth()->user();
        $me = $user->id;

        // Accepted friendships in either direction → the other user.
        $accepted = Friendship::where('status', 'accepted')
            ->where(fn ($q) => $q->where('user_id', $me)->orWhere('friend_id', $me))
            ->get();
        $friendIds = $accepted->map(fn ($f) => $f->user_id == $me ? $f->friend_id : $f->user_id)->unique();
        $friends = User::whereIn('id', $friendIds)->orderBy('name')->get();

        // Requests waiting for me to accept.
        $incoming = Friendship::with('requester')->where('friend_id', $me)->where('status', 'pending')->get();
        // Requests I've sent that haven't been accepted yet.
        $outgoing = Friendship::with('recipient')->where('user_id', $me)->where('status', 'pending')->get();

        // Referrals (merged in — invite people to KeenPocket).
        $invitees = Referral::where('referrer_id', $me)
            ->leftJoin('users', 'users.id', '=', 'referrals.referred_id')
            ->orderByDesc('referrals.id')
            ->get(['referrals.status', 'referrals.created_at', 'users.name']);
        $referral = [
            'code' => $referrals->codeFor($user),
            'inviteLink' => $referrals->inviteLink($user),
            'whatsappUrl' => $referrals->whatsappShareUrl($user),
            'stats' => $referrals->stats($user),
            'invitees' => $invitees,
        ];

        return view('friends', compact('friends', 'incoming', 'outgoing', 'referral'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['contact' => 'required|string|max:255']);
        $me = auth()->user();
        $c = trim($data['contact']);

        $user = User::where('phone_number', $c)->orWhere('email', $c)->orWhere('username', $c)->first();
        if (!$user) {
            return back()->withErrors(['contact' => 'No KeenPocket user found with that phone, email or username.']);
        }
        if ($user->id === $me->id) {
            return back()->withErrors(['contact' => "You can't add yourself."]);
        }

        // Any existing relationship between the two (either direction)?
        $existing = Friendship::where(function ($q) use ($me, $user) {
            $q->where(['user_id' => $me->id, 'friend_id' => $user->id])
              ->orWhere(['user_id' => $user->id, 'friend_id' => $me->id]);
        })->first();

        if ($existing) {
            if ($existing->status === 'accepted') {
                return back()->with('status', "You're already friends with {$user->name}.");
            }
            // They already requested me → accept it instead of duplicating.
            if ($existing->friend_id === $me->id) {
                $existing->update(['status' => 'accepted']);
                Notification::make($me, $user, $existing, 'Friend request accepted', "{$me->name} accepted your friend request.", 'Friend Accepted');

                return back()->with('status', "You and {$user->name} are now friends.");
            }

            return back()->with('status', "Friend request to {$user->name} is already pending.");
        }

        $friendship = Friendship::create(['user_id' => $me->id, 'friend_id' => $user->id, 'status' => 'pending']);
        Notification::make($me, $user, $friendship, 'New friend request', "{$me->name} sent you a friend request.", 'Friend Request');

        return back()->with('status', "Friend request sent to {$user->name}.");
    }

    public function accept($id)
    {
        $f = Friendship::where('id', $id)->where('friend_id', auth()->id())->where('status', 'pending')->firstOrFail();
        $f->update(['status' => 'accepted']);

        // Tell the requester their request was accepted.
        if ($requester = User::find($f->user_id)) {
            Notification::make(auth()->user(), $requester, $f, 'Friend request accepted', auth()->user()->name.' accepted your friend request.', 'Friend Accepted');
        }

        return back()->with('status', 'Friend request accepted.');
    }

    public function decline($id)
    {
        $f = Friendship::where('id', $id)->where('friend_id', auth()->id())->where('status', 'pending')->firstOrFail();
        $f->delete();

        return back()->with('status', 'Friend request declined.');
    }

    public function cancel($id)
    {
        $f = Friendship::where('id', $id)->where('user_id', auth()->id())->where('status', 'pending')->firstOrFail();
        $f->delete();

        return back()->with('status', 'Friend request cancelled.');
    }

    /** Remove an accepted friend (either direction), identified by the friend's user id. */
    public function remove($userId)
    {
        $me = auth()->id();
        Friendship::where('status', 'accepted')
            ->where(function ($q) use ($me, $userId) {
                $q->where(['user_id' => $me, 'friend_id' => $userId])
                  ->orWhere(['user_id' => $userId, 'friend_id' => $me]);
            })->delete();

        return back()->with('status', 'Friend removed.');
    }
}
