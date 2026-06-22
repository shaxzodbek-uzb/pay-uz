<?php

namespace Goodoneuz\PayUz\Subscribe\Events;

use Goodoneuz\PayUz\Subscribe\Charge;

/**
 * Fired when a charge is cancelled / a hold is released (receipts.cancel).
 */
class ChargeCancelled
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
