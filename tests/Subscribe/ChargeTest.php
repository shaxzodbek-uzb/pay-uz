<?php

namespace Goodoneuz\PayUz\Tests\Subscribe;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Subscribe\Charge;

/**
 * Charge (receipt) value object: state helpers and result parsing.
 */
class ChargeTest extends TestCase
{
    /** @test */
    public function paid_state_is_recognised()
    {
        $charge = Charge::fromResult(['receipt' => [
            '_id'    => 'r1',
            'state'  => Charge::STATE_PAID,
            'amount' => 1200000,
            'card'   => ['number' => '8600****0000'],
        ]]);

        $this->assertSame('r1', $charge->id());
        $this->assertTrue($charge->isPaid());
        $this->assertFalse($charge->isHeld());
        $this->assertFalse($charge->isCancelled());
        $this->assertSame(1200000, $charge->amount());
        $this->assertSame('8600****0000', $charge->cardNumber());
    }

    /** @test */
    public function held_state_is_recognised()
    {
        $charge = Charge::fromResult(['receipt' => ['_id' => 'r2', 'state' => Charge::STATE_HELD]]);

        $this->assertTrue($charge->isHeld());
        $this->assertFalse($charge->isPaid());
    }

    /** @test */
    public function cancelled_and_cancel_queued_both_count_as_cancelled()
    {
        $this->assertTrue(Charge::fromResult(['_id' => 'r', 'state' => Charge::STATE_CANCELLED])->isCancelled());
        $this->assertTrue(Charge::fromResult(['_id' => 'r', 'state' => Charge::STATE_CANCEL_QUEUED])->isCancelled());
    }

    /** @test */
    public function defaults_to_created_state_with_no_card()
    {
        $charge = Charge::fromResult(['receipt' => ['_id' => 'r3']]);

        $this->assertSame(Charge::STATE_CREATED, $charge->state());
        $this->assertNull($charge->cardNumber());
    }
}
