<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Phone number and email are locked (account identity) — only the name is
        // editable here; phone/email changes must go through support.
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user->name = $data['name'];
        $user->save();

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();
        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        }

        $user->password = bcrypt($data['new_password']);
        $user->save();

        return back()->with('status', 'Password changed.');
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();
        $user->notify_push = $request->boolean('notify_push');
        $user->notify_sms = $request->boolean('notify_sms');
        $user->notify_whatsapp = $request->boolean('notify_whatsapp');
        $user->save();

        return back()->with('status', 'Notification preferences saved.');
    }

    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'label' => 'nullable|string|max:60',
            'account_name' => 'required|string|max:255',
            'bank' => 'required|string|max:255',
            'nuban' => 'required|string|max:32',
            'is_default' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $makeDefault = $request->boolean('is_default') || $user->bankAccounts()->count() === 0;

        $account = $user->bankAccounts()->create([
            'label' => $data['label'] ?? null,
            'account_name' => $data['account_name'],
            'bank' => $data['bank'],
            'nuban' => $data['nuban'],
            'is_default' => $makeDefault,
        ]);

        if ($makeDefault) {
            $user->bankAccounts()->where('id', '!=', $account->id)->update(['is_default' => false]);
        }

        return back()->with('status', 'Bank account saved.');
    }

    public function defaultAccount(Request $request, $id)
    {
        $user = $request->user();
        $account = $user->bankAccounts()->findOrFail($id);
        $user->bankAccounts()->update(['is_default' => false]);
        $account->update(['is_default' => true]);

        return back()->with('status', 'Default account updated.');
    }

    public function deleteAccount(Request $request, $id)
    {
        $request->user()->bankAccounts()->findOrFail($id)->delete();

        return back()->with('status', 'Bank account removed.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|max:2048']); // ≤ 2 MB

        $user = $request->user();

        // Remove the previous upload (ignore external URLs).
        if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->avatar = $request->file('avatar')->store('avatars', 'public');
        $user->save();

        return back()->with('status', 'Profile photo updated.');
    }
}

