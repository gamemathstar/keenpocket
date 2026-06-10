<?php

namespace App\Services\Wallet;

use App\Exceptions\InsufficientFundsException;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * In-app wallet ledger. Every mutation is atomic and row-locked
 * (SELECT ... FOR UPDATE) to prevent races / double-spend, records a ledger row
 * with the running balance, and credits are idempotent on `reference`.
 */
class WalletService
{
    public function enabled(): bool
    {
        return (bool) config('wallet.enabled', false);
    }

    public function walletFor($userId): Wallet
    {
        $userId = is_object($userId) ? $userId->id : $userId;

        return Wallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0, 'currency' => config('wallet.currency', 'NGN')]
        );
    }

    public function balance($userId): int
    {
        return (int) $this->walletFor($userId)->balance;
    }

    /**
     * Add funds. Idempotent when a `reference` is supplied (a repeated gateway
     * callback credits once).
     */
    public function credit($userId, int $amount, string $reason, ?string $reference = null, array $meta = []): WalletTransaction
    {
        $this->assertPositive($amount);
        $this->walletFor($userId); // ensure the row exists before locking

        return DB::transaction(function () use ($userId, $amount, $reason, $reference, $meta) {
            if ($reference) {
                $existing = WalletTransaction::where('reference', $reference)->first();
                if ($existing) {
                    return $existing; // already applied
                }
            }

            $wallet = Wallet::where('user_id', is_object($userId) ? $userId->id : $userId)->lockForUpdate()->first();
            $wallet->balance += $amount;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'reason' => $reason,
                'reference' => $reference,
                'meta' => $meta ? json_encode($meta) : null,
            ]);
        });
    }

    /**
     * Remove funds. Throws InsufficientFundsException if the balance is too low.
     */
    public function debit($userId, int $amount, string $reason, ?string $reference = null): WalletTransaction
    {
        $this->assertPositive($amount);
        $this->walletFor($userId);

        return DB::transaction(function () use ($userId, $amount, $reason, $reference) {
            $wallet = Wallet::where('user_id', is_object($userId) ? $userId->id : $userId)->lockForUpdate()->first();

            if ($wallet->balance < $amount) {
                throw new InsufficientFundsException();
            }

            $wallet->balance -= $amount;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'reason' => $reason,
                'reference' => $reference,
            ]);
        });
    }

    public function history($userId, int $perPage = 20)
    {
        $wallet = $this->walletFor($userId);

        return WalletTransaction::where('wallet_id', $wallet->id)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    private function assertPositive(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive.');
        }
    }
}
