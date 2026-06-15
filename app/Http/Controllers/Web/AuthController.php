<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Session (web guard) authentication for the Blade interface. Separate from the
 * token-based API auth — both share the same User model.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // The single "login" field accepts an email, phone number, or username.
        $login = trim($data['login']);
        $user = User::where('email', $login)->orWhere('phone_number', $login)->orWhere('username', $login)->first();

        if (!$user || !\Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['login' => 'Invalid credentials. Check your phone/email/username and password.'])->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // A placeholder account (created when a school/pocket/adashi admin adds
        // someone by phone) has email == phone and can be claimed here.
        $existing = User::where('phone_number', $data['phone_number'])->first();
        $isPlaceholder = $existing && $existing->email === $existing->phone_number;

        if ($existing && !$isPlaceholder) {
            return back()->withErrors(['phone_number' => 'This phone number is already registered. Try logging in.'])->withInput();
        }
        if (User::where('email', $data['email'])->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
            return back()->withErrors(['email' => 'That email is already in use.'])->withInput();
        }

        if ($isPlaceholder) {
            // Claim it — keep their groups/children, set real email + password.
            $existing->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
            ]);
            $user = $existing;
            $welcome = 'Welcome to KeenPocket! Your account is now set up.';
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['phone_number'],
                'phone_number' => $data['phone_number'],
                'password' => bcrypt($data['password']),
            ]);
            $welcome = 'Welcome to KeenPocket!';
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', $welcome);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
