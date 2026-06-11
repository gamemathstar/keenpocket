<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Referral\ReferralService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

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

        // Shape per API_REFERENCE §2.2: message=null on success, optional keens.
        return response(['user' => $user, 'token' => $token, 'message' => null, 'keens' => []], 200);
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
            // Shape per API_REFERENCE §2.1 error example (status 0, empty token).
            return response(['status' => 0, 'token' => '', 'message' => 'Invalid Credentials'], 200);
        }

        $user->tokens()->delete();
        $token = $user->createToken('access')->plainTextToken;
        $refresh_token = $user->createToken('refresh')->plainTextToken;
        $status = 1;

        return response(compact('status', 'token', 'refresh_token'), 200);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->json(true);
    }

    /**
     * Exchange a refresh token for a fresh access/refresh pair.
     */
    public function refreshToken(Request $request)
    {
        $data = $request->validate(['refresh_token' => 'required|string']);

        $token = PersonalAccessToken::findToken($data['refresh_token']);
        if (!$token || $token->name !== 'refresh') {
            return response(['status' => 0, 'token' => '', 'message' => 'Invalid refresh token'], 200);
        }

        $user = $token->tokenable;
        $user->tokens()->delete(); // rotate the whole pair

        return response([
            'status' => 1,
            'token' => $user->createToken('access')->plainTextToken,
            'refresh_token' => $user->createToken('refresh')->plainTextToken,
        ], 200);
    }

    /**
     * Change the authenticated user's password. Returns a boolean (client contract).
     */
    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6',
            'password_confirmation' => 'required|string',
        ]);

        if ($data['new_password'] !== $data['password_confirmation']) {
            return response()->json(false);
        }

        $user = $request->user();
        if (!Hash::check($data['old_password'], $user->password)) {
            return response()->json(false);
        }

        $user->password = bcrypt($data['new_password']);
        $user->save();

        return response()->json(true);
    }

    /**
     * Email-based reset/verification token request. Returns true regardless of
     * whether the email exists (no account enumeration). Token is delivered via
     * the log channel in dev — wire to mail/SMS for production.
     */
    public function requestToken(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        $user = User::where('email', $data['email'])->first();
        if ($user) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            DB::table('password_resets')->updateOrInsert(
                ['email' => $data['email']],
                ['token' => $code, 'created_at' => now()]
            );
            Log::info("[reset-token] {$data['email']}: {$code}");
        }

        return response()->json(true);
    }

    /**
     * Verify a previously issued email token (valid for 30 minutes).
     */
    public function verifyToken(Request $request)
    {
        $data = $request->validate(['token' => 'required|string']);

        $row = DB::table('password_resets')->where('token', $data['token'])->first();
        $valid = $row && Carbon::parse($row->created_at)->gt(now()->subMinutes(30));

        return response()->json((bool) $valid);
    }
}
