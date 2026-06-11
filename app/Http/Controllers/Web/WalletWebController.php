<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletService;

class WalletWebController extends Controller
{
    public function index(WalletService $wallet)
    {
        if (!$wallet->enabled()) {
            return view('wallet', ['enabled' => false, 'balance' => 0, 'transactions' => collect()]);
        }

        $userId = auth()->id();

        return view('wallet', [
            'enabled' => true,
            'balance' => $wallet->balance($userId),
            'transactions' => $wallet->history($userId),
        ]);
    }
}
