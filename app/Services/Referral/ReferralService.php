<?php

namespace App\Services\Referral;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Referral growth loop: codes, attribution, qualification and shareable
 * WhatsApp invite links. Tracking/links work whenever `enabled`; monetary
 * rewards only when `reward_enabled` (off by default — no money is moved here).
 */
class ReferralService
{
    public function enabled(): bool
    {
        return (bool) config('referrals.enabled', true);
    }

    /**
     * Ensure the user has a referral code, generating a unique one if needed.
     */
    public function codeFor(User $user): string
    {
        if (!empty($user->referral_code)) {
            return $user->referral_code;
        }

        $code = $this->generateUniqueCode();
        $user->referral_code = $code;
        $user->save();

        return $code;
    }

    public function inviteLink(User $user): string
    {
        $base = rtrim((string) config('referrals.invite_base_url'), '/');

        return $base.'?ref='.$this->codeFor($user);
    }

    /**
     * Build a wa.me share URL with the invite link baked into the message.
     */
    public function whatsappShareUrl(User $user): string
    {
        $message = str_replace('{link}', $this->inviteLink($user), (string) config('referrals.whatsapp_message'));

        return 'https://wa.me/?text='.rawurlencode($message);
    }

    /**
     * Attribute a newly registered user to the owner of `$code`.
     * Safe + silent: invalid/self/duplicate codes simply produce no attribution.
     */
    public function attribute(User $newUser, ?string $code): ?Referral
    {
        if (!$this->enabled() || empty($code)) {
            return null;
        }

        $referrer = User::where('referral_code', $code)->first();
        if (!$referrer || $referrer->id === $newUser->id) {
            return null;
        }

        // A user can only be referred once.
        return Referral::firstOrCreate(
            ['referred_id' => $newUser->id],
            ['referrer_id' => $referrer->id, 'code' => $code, 'status' => 'pending']
        );
    }

    /**
     * Mark a user's pending referral as qualified (e.g. they joined their first
     * pocket/adashi). Idempotent; grants a reward record only if configured.
     */
    public function qualify(User $user): void
    {
        if (!$this->enabled()) {
            return;
        }

        $referral = Referral::where('referred_id', $user->id)->where('status', 'pending')->first();
        if (!$referral) {
            return;
        }

        $referral->status = 'qualified';
        $referral->qualified_at = now();

        if ((bool) config('referrals.reward_enabled', false) && (int) config('referrals.reward_amount', 0) > 0) {
            // NOTE: records the entitlement only. Actual disbursement (wallet
            // credit / fee waiver) is intentionally not wired to money movement.
            $referral->reward_amount = (int) config('referrals.reward_amount');
            $referral->status = 'rewarded';
            $referral->rewarded_at = now();
        }

        $referral->save();
    }

    public function stats(User $user): array
    {
        $rows = Referral::where('referrer_id', $user->id)->get();

        return [
            'invited' => $rows->count(),
            'qualified' => $rows->whereIn('status', ['qualified', 'rewarded'])->count(),
            'rewarded' => $rows->where('status', 'rewarded')->count(),
        ];
    }

    /**
     * Best-effort qualification hook for use inside business flows: never throws.
     */
    public function qualifyQuietly(User $user): void
    {
        try {
            if (config('referrals.qualify_on', 'join') === 'join') {
                $this->qualify($user);
            }
        } catch (\Throwable $e) {
            Log::warning('Referral qualify failed for user '.$user->id.': '.$e->getMessage());
        }
    }

    public function generateUniqueCode(): string
    {
        do {
            $code = $this->generateCode();
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    public function generateCode(): string
    {
        $length = max(4, (int) config('referrals.code_length', 7));
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no ambiguous 0/O/1/I
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }
}
