<?php

namespace Goodoneuz\PayUz\Tests\Checkout;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Checkout\Payment;
use Goodoneuz\PayUz\Checkout\PaymentResult;
use Goodoneuz\PayUz\Checkout\Drivers\NullDriver;

/**
 * The no-op Checkout driver: simulates the acquiring happy path with no network.
 */
class NullDriverTest extends TestCase
{
    /** @test */
    public function create_payment_returns_a_redirectable_created_result()
    {
        $result = (new NullDriver())->createPayment(Payment::make(1200000, 'order-1'));

        $this->assertSame(PaymentResult::STATUS_CREATED, $result->status());
        $this->assertTrue($result->needsRedirect());
        $this->assertSame(1200000, $result->amount());
    }

    /** @test */
    public function charging_a_token_succeeds_and_refunding_refunds()
    {
        $driver = new NullDriver();

        $charge = $driver->chargeToken('tok', Payment::make(1200000, 'order-1'));
        $this->assertTrue($charge->isSuccessful());
        $this->assertSame('tok', $charge->cardToken());

        $this->assertTrue($driver->refund('p1', 600000)->isRefunded());
        $this->assertTrue($driver->capture('p1')->isSuccessful());
    }

    /** @test */
    public function it_verifies_webhooks_and_maps_their_status()
    {
        $driver = new NullDriver();

        $this->assertTrue($driver->verifyWebhook(['status' => 'succeeded']));
        $result = $driver->parseWebhook(['status' => PaymentResult::STATUS_REFUNDED, 'payment_id' => 'p1']);
        $this->assertTrue($result->isRefunded());
        $this->assertSame('p1', $result->paymentId());
    }

    /** @test */
    public function its_name_is_null()
    {
        $this->assertSame('null', (new NullDriver())->name());
    }
}
