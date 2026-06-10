<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Referral\ReferralService;
use Tests\TestCase;

class ReferralLinkTest extends TestCase
{
    public function test_generated_code_uses_configured_length_and_unambiguous_alphabet()
    {
        config(['referrals.code_length' => 7]);
        $service = new ReferralService();

        $code = $service->generateCode();

        $this->assertSame(7, strlen($code));
        // No ambiguous characters (0/O/1/I).
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]+$/', $code);
    }

    public function test_invite_link_contains_the_users_code()
    {
        $user = new User(['referral_code' => 'ABC2345']); // already has a code → no DB write
        $link = (new ReferralService())->inviteLink($user);

        $this->assertStringContainsString('ref=ABC2345', $link);
    }

    public function test_whatsapp_url_wraps_the_invite_link()
    {
        $user = new User(['referral_code' => 'ABC2345']);
        $url = (new ReferralService())->whatsappShareUrl($user);

        $this->assertStringStartsWith('https://wa.me/?text=', $url);
        $this->assertStringContainsString('ref%3DABC2345', $url); // '=' is url-encoded
    }
}
