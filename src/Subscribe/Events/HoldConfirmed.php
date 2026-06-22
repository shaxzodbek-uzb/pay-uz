<?php

namespace Goodoneuz\PayUz\Subscribe\Events;

use Goodoneuz\PayUz\Subscribe\Charge;

/**
 * Fired when a two-stage hold is captured (state 5 → 4 via receipts.confirm_hold).
 */
class HoldConfirmed
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
