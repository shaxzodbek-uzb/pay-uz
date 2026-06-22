<?php

namespace Goodoneuz\PayUz\Tests\Fiscalization;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Fiscalization\Receipt;
use Goodoneuz\PayUz\Fiscalization\ReceiptItem;
use Goodoneuz\PayUz\Fiscalization\Drivers\NullDriver;
use Goodoneuz\PayUz\Fiscalization\Exceptions\InvalidReceiptException;

/**
 * The no-op default driver: validates the receipt and returns a deterministic
 * synthetic sign without contacting an OFD.
 */
class NullDriverTest extends TestCase
{
    const MXIK = '00702001001000001';

    private function receipt()
    {
        return Receipt::sale('order-1', [
            new ReceiptItem('Subscription', self::MXIK, 12000000, 1),
        ])->payByCard();
    }

    /** @test */
    public function it_returns_a_successful_deterministic_result()
    {
        $driver = new NullDriver();

        $a = $driver->fiscalize($this->receipt());
        $b = $driver->fiscalize($this->receipt());

        $this->assertTrue($a->isSuccessful());
        $this->assertSame('null-order-1', $a->receiptId());
        $this->assertNotEmpty($a->fiscalSign());
        // Deterministic: same receipt -> same sign.
        $this->assertSame($a->fiscalSign(), $b->fiscalSign());
    }

    /** @test */
    public function it_validates_the_receipt_before_succeeding()
    {
        $driver = new NullDriver();
        $bad = Receipt::sale('order-1', [new ReceiptItem('Item', '123', 1000, 1)]);

        $this->expectException(InvalidReceiptException::class);
        $driver->fiscalize($bad);
    }

    /** @test */
    public function its_name_is_null()
    {
        $this->assertSame('null', (new NullDriver())->name());
    }
}
