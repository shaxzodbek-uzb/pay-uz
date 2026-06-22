<?php

namespace Goodoneuz\PayUz\Tests\Checkout;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Checkout\Payment;

/**
 * Payment request value object: tiyin amount + the fluent builder.
 */
class PaymentTest extends TestCase
{
    /** @test */
    public function it_defaults_to_auto_capture_and_uzs()
    {
        $payment = Payment::make(1200000, 'order-1');

        $this->assertSame(1200000, $payment->amount());
        $this->assertSame('order-1', $payment->orderId());
        $this->assertSame('UZS', $payment->currency());
        $this->assertTrue($payment->isAutoCapture());
    }

    /** @test */
    public function the_fluent_builder_sets_every_field()
    {
        $payment = Payment::make(500000, 'order-2', 'USD')
            ->describedAs('Pro plan')
            ->returnTo('https://app/return')
            ->notifyAt('https://app/webhook')
            ->authorizeOnly()
            ->with(['language' => 'uz']);

        $this->assertSame('USD', $payment->currency());
        $this->assertSame('Pro plan', $payment->description());
        $this->assertSame('https://app/return', $payment->returnUrl());
        $this->assertSame('https://app/webhook', $payment->notifyUrl());
        $this->assertFalse($payment->isAutoCapture()); // authorizeOnly -> two-stage
        $this->assertSame(['language' => 'uz'], $payment->extra());
    }
}
