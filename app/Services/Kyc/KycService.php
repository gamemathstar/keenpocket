<?php

namespace App\Services\Kyc;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Identity verification (BVN / NIN). Config-gated (config/kyc.php) and OFF by
 * default. Privacy-first: the raw BVN/NIN is sent to the provider and then
 * discarded — only the last 4 digits and the provider reference are persisted.
 */
class KycService
{
    public function enabled(): bool
    {
        return (bool) config('kyc.enabled', false);
    }

    public function isVerified(User $user): bool
    {
        return $user->kyc_status === 'verified';
    }

    public function statusFor(User $user): array
    {
        return [
            'enabled' => $this->enabled(),
            'status' => $user->kyc_status ?? 'none',
            'type' => $user->kyc_type,
            'id_last4' => $user->kyc_id_last4,
            'verified_at' => $user->kyc_verified_at,
        ];
    }

    /**
     * Submit an identity number for verification. Returns the resulting status.
     * Never persists the full id number.
     */
    public function submit(User $user, string $type, string $idNumber): array
    {
        if (!$this->enabled()) {
            return ['enabled' => false, 'status' => $user->kyc_status ?? 'none'];
        }

        $type = strtoupper($type);
        $idNumber = preg_replace('/\D/', '', $idNumber);
        $last4 = substr($idNumber, -4);

        $user->kyc_type = $type;
        $user->kyc_id_last4 = $last4;

        $result = $this->verifyWithProvider($type, $idNumber, $user);

        $user->kyc_status = $result['verified'] ? 'verified' : 'failed';
        $user->kyc_reference = $result['reference'] ?? null;
        $user->kyc_verified_at = $result['verified'] ? now() : null;
        $user->save();

        return ['enabled' => true, 'status' => $user->kyc_status];
    }

    private function verifyWithProvider(string $type, string $idNumber, User $user): array
    {
        try {
            switch (config('kyc.provider', 'log')) {
                case 'log':
                    // Dev simulation: any 11-digit number "verifies".
                    return ['verified' => strlen($idNumber) === 11, 'reference' => 'LOG_KYC_'.$type.'_'.substr($idNumber, -4)];

                case 'dojah':
                    $cfg = config('kyc.providers.dojah');
                    $path = $type === 'NIN' ? '/api/v1/kyc/nin' : '/api/v1/kyc/bvn/full';
                    $param = $type === 'NIN' ? 'nin' : 'bvn';
                    $resp = Http::withHeaders([
                        'AppId' => $cfg['app_id'],
                        'Authorization' => $cfg['secret_key'],
                    ])->get(rtrim($cfg['base_url'], '/').$path, [$param => $idNumber]);

                    return [
                        'verified' => $resp->successful() && $resp->json('entity') !== null,
                        'reference' => $resp->json('entity.reference') ?? 'DOJAH_'.substr($idNumber, -4),
                    ];

                default:
                    Log::warning('[kyc] unknown provider '.config('kyc.provider'));
                    return ['verified' => false];
            }
        } catch (\Throwable $e) {
            Log::warning('[kyc] verification failed: '.$e->getMessage());
            return ['verified' => false];
        }
    }
}
