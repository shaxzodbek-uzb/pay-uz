<?php

namespace Goodoneuz\PayUz\Tests\Fiscalization;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Fiscalization\Receipt;
use Goodoneuz\PayUz\Fiscalization\ReceiptItem;
use Goodoneuz\PayUz\Fiscalization\Exceptions\InvalidReceiptException;

/**
 * Receipt value object: totals, cash/card split resolution, validation and the
 * sale/refund factories.
 */
class ReceiptTest extends TestCase
{
    const MXIK = '00702001001000001';

    private function item($price = 12000000, $count = 1)
    {
        return new ReceiptItem('Subscription', self::MXIK, $price, $count);
    }

    /** @test */
    public function total_and_vat_total_sum_the_lines()
    {
        $receipt = Receipt::sale('order-1', [
            $this->item(12000000, 1),
            $this->item(1000000, 2),
        ]);

        $this->assertSame(14000000, $receipt->total());
        // Per-line VAT, each rounded to whole tiyin, then summed:
        //   12_000_000 * 12/112 = 1_285_714.28 -> 1_285_714
        //    2_000_000 * 12/112 =   214_285.71 ->   214_286
        $this->assertSame(1285714 + 214286, $receipt->vatTotal());
    }

    /** @test */
    public function payment_defaults_to_the_whole_total_on_card()
    {
        $receipt = Receipt::sale('order-1', [$this->item()]);
        $this->assertSame([0, 12000000], $receipt->payment());
    }

    /** @test */
    public function pay_by_cash_and_card_set_the_split()
    {
        $cash = Receipt::sale('o', [$this->item()])->payByCash();
        $this->assertSame([12000000, 0], $cash->payment());

        $card = Receipt::sale('o', [$this->item()])->payByCard();
        $this->assertSame([0, 12000000], $card->payment());
    }

    /** @test */
    public function refund_factory_marks_the_receipt_type()
    {
        $receipt = Receipt::refund('order-1', [$this->item()]);
        $this->assertTrue($receipt->isRefund());
        $this->assertSame(Receipt::TYPE_REFUND, $receipt->type());
    }

    /** @test */
    public function add_item_accepts_arrays()
    {
        $receipt = Receipt::sale('order-1');
        $receipt->addItem([
            'title' => 'Coffee',
            'mxik'  => self::MXIK,
            'price' => 2500000,
            'count' => 1,
        ]);

        $this->assertCount(1, $receipt->items());
        $this->assertSame(2500000, $receipt->total());
    }

    /** @test */
    public function assert_valid_rejects_an_empty_receipt()
    {
        $this->expectException(InvalidReceiptException::class);
        Receipt::sale('order-1')->assertValid();
    }

    /** @test */
    public function assert_valid_rejects_a_payment_split_that_does_not_balance()
    {
        $receipt = Receipt::sale('order-1', [$this->item(12000000, 1)])
            ->withPayment(1000000, 1000000); // 2m != 12m

        $this->expectException(InvalidReceiptException::class);
        $receipt->assertValid();
    }

    /** @test */
    public function assert_valid_passes_for_a_balanced_receipt()
    {
        $receipt = Receipt::sale('order-1', [$this->item(12000000, 1)])
            ->withPayment(2000000, 10000000);

        $receipt->assertValid();
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function to_array_carries_type_total_split_and_items()
    {
        $receipt = Receipt::sale('order-9', [$this->item(12000000, 1)])->payByCard()->at(1700000000000);
        $array = $receipt->toArray();

        $this->assertSame(Receipt::TYPE_SALE, $array['type']);
        $this->assertSame('order-9', $array['order_id']);
        $this->assertSame(12000000, $array['total']);
        $this->assertSame(0, $array['cash']);
        $this->assertSame(12000000, $array['card']);
        $this->assertSame(1700000000000, $array['time']);
        $this->assertCount(1, $array['items']);
    }
}
