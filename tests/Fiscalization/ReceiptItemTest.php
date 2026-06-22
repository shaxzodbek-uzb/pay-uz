<?php

namespace Goodoneuz\PayUz\Tests\Fiscalization;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Fiscalization\Vat;
use Goodoneuz\PayUz\Fiscalization\ReceiptItem;
use Goodoneuz\PayUz\Fiscalization\Exceptions\InvalidReceiptException;

/**
 * Receipt line value object: tiyin-safe totals, VAT extraction, validation and
 * the canonical array shape drivers map from.
 */
class ReceiptItemTest extends TestCase
{
    const MXIK = '00702001001000001';

    /** @test */
    public function subtotal_and_total_are_price_times_count_in_tiyin()
    {
        $item = new ReceiptItem('Subscription', self::MXIK, 12000000, 2);

        $this->assertSame(24000000, $item->subtotal());
        $this->assertSame(24000000, $item->total());
    }

    /** @test */
    public function discount_reduces_the_line_total_but_never_below_zero()
    {
        $item = new ReceiptItem('Item', self::MXIK, 10000, 1, Vat::RATE_STANDARD, null, 1000);
        $this->assertSame(9000, $item->total());

        $over = new ReceiptItem('Item', self::MXIK, 10000, 1, Vat::RATE_STANDARD, null, 999999);
        $this->assertSame(0, $over->total());
    }

    /** @test */
    public function vat_is_extracted_from_the_discounted_total()
    {
        $item = new ReceiptItem('Subscription', self::MXIK, 12000000, 1);
        // 12_000_000 * 12 / 112 = 1_285_714.28 -> 1_285_714
        $this->assertSame(1285714, $item->vatAmount());

        $zero = new ReceiptItem('Exempt', self::MXIK, 12000000, 1, Vat::RATE_ZERO);
        $this->assertSame(0, $zero->vatAmount());
    }

    /** @test */
    public function fractional_quantity_is_supported_for_weight_or_volume()
    {
        $item = new ReceiptItem('Sugar kg', self::MXIK, 1000, 1.5);
        $this->assertSame(1500, $item->subtotal());
    }

    /** @test */
    public function from_array_accepts_common_aliases()
    {
        $item = ReceiptItem::fromArray([
            'name'         => 'Coffee',
            'ikpu'         => self::MXIK,
            'price'        => 2500000,
            'qty'          => 3,
            'vat'          => 0,
            'package_code' => '1500000',
        ]);

        $this->assertSame('Coffee', $item->title());
        $this->assertSame(self::MXIK, $item->mxik());
        $this->assertSame(7500000, $item->subtotal());
        $this->assertSame(0, $item->vatAmount());
        $this->assertSame('1500000', $item->packageCode());
    }

    /** @test */
    public function to_array_exposes_the_canonical_shape()
    {
        $item = new ReceiptItem('Subscription', self::MXIK, 12000000, 1);

        $this->assertSame([
            'title'        => 'Subscription',
            'mxik'         => self::MXIK,
            'package_code' => null,
            'price'        => 12000000,
            'count'        => 1,
            'vat_percent'  => 12,
            'vat_amount'   => 1285714,
            'discount'     => 0,
            'total'        => 12000000,
        ], $item->toArray());
    }

    /** @test */
    public function assert_valid_rejects_a_bad_mxik()
    {
        $this->expectException(InvalidReceiptException::class);
        (new ReceiptItem('Item', '123', 1000, 1))->assertValid();
    }

    /** @test */
    public function assert_valid_rejects_a_non_positive_quantity()
    {
        $this->expectException(InvalidReceiptException::class);
        (new ReceiptItem('Item', self::MXIK, 1000, 0))->assertValid();
    }

    /** @test */
    public function assert_valid_rejects_an_unsupported_vat_rate()
    {
        $this->expectException(InvalidReceiptException::class);
        (new ReceiptItem('Item', self::MXIK, 1000, 1, 15))->assertValid();
    }

    /** @test */
    public function assert_valid_passes_for_a_well_formed_line()
    {
        $item = new ReceiptItem('Item', self::MXIK, 1000, 1);
        $item->assertValid();
        $this->addToAssertionCount(1); // no exception thrown
    }
}
