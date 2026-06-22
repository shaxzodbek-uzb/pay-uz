<?php

namespace Goodoneuz\PayUz\Tests\Fiscalization;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Fiscalization\Mxik;

/**
 * MXIK / IKPU classification-code helper.
 */
class MxikTest extends TestCase
{
    /** @test */
    public function a_seventeen_digit_code_is_valid()
    {
        $this->assertSame(17, Mxik::LENGTH);
        $this->assertTrue(Mxik::isValid('00702001001000001'));
        $this->assertTrue(Mxik::isValid('12345678901234567'));
    }

    /** @test */
    public function wrong_length_or_non_numeric_is_invalid()
    {
        $this->assertFalse(Mxik::isValid('123'));
        $this->assertFalse(Mxik::isValid('123456789012345678')); // 18 digits
        $this->assertFalse(Mxik::isValid(''));
        $this->assertFalse(Mxik::isValid(null));
    }

    /** @test */
    public function normalize_strips_separators_but_keeps_digits()
    {
        $this->assertSame('00702001001000001', Mxik::normalize('0070-2001 001000001'));
        $this->assertTrue(Mxik::isValid('0070 2001 0010 00001'));
    }

    /** @test */
    public function package_code_must_be_a_positive_integer_string()
    {
        $this->assertTrue(Mxik::isValidPackageCode('1500000'));
        $this->assertTrue(Mxik::isValidPackageCode(1500000));
        $this->assertFalse(Mxik::isValidPackageCode(null));
        $this->assertFalse(Mxik::isValidPackageCode(''));
        $this->assertFalse(Mxik::isValidPackageCode('abc'));
    }
}
