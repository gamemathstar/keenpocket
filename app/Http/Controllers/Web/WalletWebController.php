<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;

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

    public function topup(Request $request, WalletService $wallet)
    {
        if (!$wallet->enabled()) {
            return back()->withErrors(['amount' => 'Wallet is not enabled.']);
        }

        $data = $request->validate(['amount' => 'required|integer|min:1']);

        // Real money-in goes through the gateway; the dev (`log`) provider credits now.
        if (config('payments.enabled') && config('payments.provider') !== 'log') {
            return back()->with('status', 'Complete your top-up via the payment gateway (coming soon on web).');
        }

        $txn = $wallet->credit(auth()->id(), (int) $data['amount'], 'topup', 'TOPUP_'.auth()->id().'_'.uniqid());

        return back()->with('status', 'Wallet funded. New balance: ₦'.number_format($txn->balance_after));
    }
}
