<?php

namespace Goodoneuz\PayUz\Subscribe\Events;

use Goodoneuz\PayUz\Subscribe\Charge;

/**
 * Fired when a recurring charge is paid (state 4). Listen to fulfil the order /
 * extend the subscription.
 */
class ChargePaid
{
    /** @var Charge */
    public $charge;

    /** @var string driver name */
    public $driver;

    public function __construct(Charge $charge, $driver)
    {
        $this->charge = $charge;
        $this->driver = (string) $driver;
    }
}
