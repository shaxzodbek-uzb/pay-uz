<?php

namespace Goodoneuz\PayUz\Tests\Checkout;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Checkout\PaymentResult;

/**
 * PaymentResult: normalized status helpers and the redirect/token surface.
 */
class PaymentResultTest extends TestCase
{
    /** @test */
    public function a_created_result_needs_a_redirect()
    {
        $result = new PaymentResult(PaymentResult::STATUS_CREATED, [
            'payment_id' => 'p1',
            'pay_url'    => 'https://pay/p1',
            'amount'     => 1200000,
        ]);

        $this->assertSame('p1', $result->paymentId());
        $this->assertTrue($result->needsRedirect());
        $this->assertSame('https://pay/p1', $result->payUrl());
        $this->assertFalse($result->isSuccessful());
    }

    /** @test */
    public function status_helpers_reflect_the_normalized_status()
    {
        $this->assertTrue((new PaymentResult(PaymentResult::STATUS_SUCCEEDED))->isSuccessful());
        $this->assertTrue((new PaymentResult(PaymentResult::STATUS_HELD))->isHeld());
        $this->assertTrue((new PaymentResult(PaymentResult::STATUS_REFUNDED))->isRefunded());
        $this->assertTrue((new PaymentResult(PaymentResult::STATUS_FAILED))->isFailed());
        $this->assertTrue((new PaymentResult(PaymentResult::STATUS_CANCELLED))->isFailed());
        $this->assertFalse((new PaymentResult(PaymentResult::STATUS_SUCCEEDED))->needsRedirect());
    }

    /** @test */
    public function it_carries_a_saved_token_and_is_persistable()
    {
        $array = (new PaymentResult(PaymentResult::STATUS_SUCCEEDED, [
            'payment_id'  => 'p2',
            'card_token'  => 'tok_x',
            'masked_card' => '8600****0000',
        ]))->toArray();

        $this->assertSame('tok_x', $array['card_token']);
        $this->assertSame('8600****0000', $array['masked_card']);
        $this->assertSame(PaymentResult::STATUS_SUCCEEDED, $array['status']);
        $this->assertArrayNotHasKey('raw', $array);
    }
}
