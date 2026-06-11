<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Kyc\KycService;
use Illuminate\Http\Request;

class KycWebController extends Controller
{
    public function submit(Request $request, KycService $kyc)
    {
        if (!$kyc->enabled()) {
            return back()->with('status', 'Identity verification is not required yet.');
        }

        $data = $request->validate([
            'type' => 'required|in:BVN,NIN',
            'id_number' => 'required|string|min:10|max:11',
        ]);

        $result = $kyc->submit($request->user(), $data['type'], $data['id_number']);

        return back()->with('status', $result['status'] === 'verified'
            ? 'Identity verified ✓'
            : 'Verification failed — please check the number and try again.');
    }
}
