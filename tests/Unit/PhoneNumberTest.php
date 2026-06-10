<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_strips_nigerian_country_code()
    {
        $this->assertSame('8012345678', PhoneNumber::normalize('+2348012345678'));
    }

    public function test_strips_us_country_code()
    {
        $this->assertSame('5551234567', PhoneNumber::normalize('+15551234567'));
    }

    public function test_leaves_local_number_untouched()
    {
        $this->assertSame('08012345678', PhoneNumber::normalize('08012345678'));
    }

    public function test_handles_null_and_whitespace()
    {
        $this->assertSame('', PhoneNumber::normalize(null));
        $this->assertSame('8012345678', PhoneNumber::normalize('  +2348012345678  '));
    }
}
