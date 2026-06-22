<?php

namespace Goodoneuz\PayUz\Tests\Subscribe;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Subscribe\Drivers\NullDriver;

/**
 * The no-op Subscribe driver: simulates the happy path with no network and no PAN.
 */
class NullDriverTest extends TestCase
{
    /** @test */
    public function it_mints_an_unverified_recurrent_token_then_verifies_it()
    {
        $driver = new NullDriver();

        $card = $driver->createCard('8600123412340000', '0399', true);
        $this->assertNotEmpty($card->token());
        $this->assertTrue($card->isRecurrent());
        $this->assertFalse($card->isVerified());
        $this->assertStringNotContainsString('8600123412340000', $card->number()); // masked, no PAN

        $verified = $driver->verifyCard($card->token(), '666666');
        $this->assertTrue($verified->isVerified());
    }

    /** @test */
    public function charging_a_receipt_comes_back_paid_and_a_hold_comes_back_held()
    {
        $driver = new NullDriver();

        $receipt = $driver->createReceipt(1200000, ['order_id' => 1]);
        $this->assertSame(0, $receipt->state());

        $this->assertTrue($driver->payReceipt($receipt->id(), 'tok')->isPaid());
        $this->assertTrue($driver->payReceipt($receipt->id(), 'tok', ['hold' => true])->isHeld());
    }

    /** @test */
    public function its_name_is_null()
    {
        $this->assertSame('null', (new NullDriver())->name());
    }
}
