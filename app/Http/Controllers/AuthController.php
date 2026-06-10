<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Referral\ReferralService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request, OtpService $otp, ReferralService $referrals)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'password' => 'required|string|min:6',
            'referral_code' => 'nullable|string|max:16',
        ]);

        // When OTP is enabled, the phone must be verified first. While disabled
        // (the default) this check is skipped and registration is unchanged.
        if ($otp->enabled() && !$otp->isVerified($fields['phone_number'])) {
            return response(['message' => 'Please verify your phone number first.'], 422);
        }

        $user = User::where('phone_number', $fields['phone_number'])->first();
        if ($user) {
            // Placeholder account (created via invite) can be claimed once.
            // NOTE: when OTP_ENABLED is true this path is additionally gated by
            // phone verification in the OTP flow (see OtpController).
            if ($user->email == $user->phone_number) {
                $user->email = $fields['email'];
                $user->password = bcrypt($fields['password']);
                $user->save();
            } else {
                // Existing, fully-registered account: enforce uniqueness.
                $request->validate([
                    'email' => 'required|email|unique:users,email',
                    'phone_number' => 'required|string|unique:users,phone_number',
                ]);
            }
        } else {
            $user = User::create([
                'name' => $fields['name'],
                'email' => $fields['email'],
                'username' => $fields['phone_number'],
                'phone_number' => $fields['phone_number'],
                'password' => bcrypt($fields['password']),
            ]);
        }

        // Attribute the signup to a referrer if a code was supplied. Best-effort:
        // a referral failure must never block registration.
        try {
            $referrals->attribute($user, $fields['referral_code'] ?? null);
        } catch (\Throwable $e) {
            Log::warning('Referral attribution failed during registration: '.$e->getMessage());
        }

        $token = $user->createToken('keen_-_pocket')->plainTextToken;

        return response(compact('user', 'token'), 200);
    }

    public function login(Request $request)
    {
        // NOTE: deliberately NOT using `exists:users,phone_number` here — that
        // rule leaks which phone numbers are registered (account enumeration).
        // Both "no such user" and "wrong password" return the same response,
        // keeping the existing client contract (HTTP 200 + generic message).
        $fields = $request->validate([
            'phone_number' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone_number', $fields['phone_number'])->first();
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response(['message' => 'Invalid Credentials'], 200);
        }

        $user->tokens()->delete();
        $token = $user->createToken('keen_-_pocket')->plainTextToken;
        $status = 1;

        return response(compact('status', 'token'), 200);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return true;
    }
}
