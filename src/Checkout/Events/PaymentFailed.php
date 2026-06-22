<?php

namespace Goodoneuz\PayUz\Checkout\Events;

use Goodoneuz\PayUz\Checkout\PaymentResult;

/**
 * Fired when an acquiring payment ends failed/cancelled.
 */
class PaymentFailed
{
    /** @var PaymentResult */
    public $result;

    /** @var string driver name */
    public $driver;

    public function __construct(PaymentResult $result, $driver)
    {
        $this->result = $result;
        $this->driver = (string) $driver;
    }
}
