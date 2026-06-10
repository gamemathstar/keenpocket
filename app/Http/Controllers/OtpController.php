<?php

namespace App\Http\Controllers;

use App\Services\Otp\OtpService;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function __construct(private OtpService $otp)
    {
    }

    /**
     * Report whether OTP verification is active. Lets the mobile client decide
     * whether to show the verification screen without hardcoding a build flag.
     */
    public function status()
    {
        return response(['enabled' => $this->otp->enabled()]);
    }

    /**
     * Request an OTP for a phone number.
     */
    public function request(Request $request)
    {
        $data = $request->validate([
            'phone_number' => 'required|string|max:20',
            'purpose' => 'nullable|in:verify,login,reset',
        ]);

        if (!$this->otp->enabled()) {
            return response(['enabled' => false, 'message' => 'OTP verification is currently disabled.'], 200);
        }

        $result = $this->otp->send($data['phone_number'], $data['purpose'] ?? 'verify');

        if (($result['sent'] ?? false) === false && ($result['reason'] ?? null) === 'cooldown') {
            return response([
                'message' => 'Please wait before requesting another code.',
                'retry_after' => $result['retry_after'],
            ], 429);
        }

        return response(['message' => 'Verification code sent.'] + $result, 200);
    }

    /**
     * Verify a submitted OTP.
     */
    public function verify(Request $request)
    {
        $data = $request->validate([
            'phone_number' => 'required|string|max:20',
            'code' => 'required|string',
            'purpose' => 'nullable|in:verify,login,reset',
        ]);

        if (!$this->otp->enabled()) {
            return response(['enabled' => false, 'verified' => true], 200);
        }

        $ok = $this->otp->verify($data['phone_number'], $data['code'], $data['purpose'] ?? 'verify');

        return response(['verified' => $ok], $ok ? 200 : 422);
    }
}
