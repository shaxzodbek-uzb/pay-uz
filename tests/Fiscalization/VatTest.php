<?php

namespace Goodoneuz\PayUz\Tests\Fiscalization;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Fiscalization\Vat;

/**
 * VAT helper: rate validation and VAT-inclusive extraction.
 *
 * Uzbek prices are quoted gross (VAT inclusive); the receipt carries the VAT
 * amount extracted as gross * rate / (100 + rate), rounded to whole tiyin.
 */
class VatTest extends TestCase
{
    /** @test */
    public function accepts_only_the_legal_rate_set()
    {
        $this->assertTrue(Vat::isValidRate(0));
        $this->assertTrue(Vat::isValidRate(12));
        $this->assertTrue(Vat::isValidRate('12'));   // numeric string from config/env
        $this->assertTrue(Vat::isValidRate(12.0));

        $this->assertFalse(Vat::isValidRate(15));    // pre-2023 rate, no longer valid
        $this->assertFalse(Vat::isValidRate(20));
        $this->assertFalse(Vat::isValidRate('abc'));
        $this->assertFalse(Vat::isValidRate(null));
    }

    /** @test */
    public function extracts_vat_from_a_gross_amount()
    {
        // 112_000 tiyin gross at 12% contains exactly 12_000 tiyin of VAT.
        $this->assertSame(12000, Vat::fromGross(112000, 12));
    }

    /** @test */
    public function rounds_extracted_vat_to_whole_tiyin()
    {
        // 100_000 * 12 / 112 = 10714.2857… -> 10714
        $this->assertSame(10714, Vat::fromGross(100000, 12));
    }

    /** @test */
    public function zero_rate_yields_no_vat()
    {
        $this->assertSame(0, Vat::fromGross(112000, 0));
        $this->assertSame(0, Vat::fromGross(0, 12));
    }
}
