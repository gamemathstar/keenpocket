<?php

namespace App\Http\Controllers;

use App\Services\Kyc\KycService;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function __construct(private KycService $kyc)
    {
    }

    public function status(Request $request)
    {
        return response($this->kyc->statusFor($request->user()));
    }

    /**
     * Submit a BVN/NIN for verification. The raw number is never stored.
     */
    public function submit(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:BVN,NIN,bvn,nin',
            'id_number' => 'required|string|min:10|max:11',
        ]);

        if (!$this->kyc->enabled()) {
            return response(['enabled' => false, 'message' => 'Identity verification is currently disabled.'], 200);
        }

        $result = $this->kyc->submit($request->user(), $data['type'], $data['id_number']);

        return response([
            'status' => $result['status'],
            'verified' => $result['status'] === 'verified',
        ], $result['status'] === 'verified' ? 200 : 422);
    }
}
