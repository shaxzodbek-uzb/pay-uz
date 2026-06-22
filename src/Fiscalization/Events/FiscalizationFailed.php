<?php

namespace Goodoneuz\PayUz\Fiscalization\Events;

use Goodoneuz\PayUz\Fiscalization\Receipt;
use Goodoneuz\PayUz\Fiscalization\FiscalResult;

/**
 * Fired when a receipt could not be fiscalized — either the OFD rejected it
 * (an unsuccessful {@see FiscalResult}) or the call threw. Listen for this to
 * queue a retry or alert.
 */
class FiscalizationFailed
{
    /** @var Receipt */
    public $receipt;

    /** @var FiscalResult */
    public $result;

    /** @var string driver name */
    public $driver;

    public function __construct(Receipt $receipt, FiscalResult $result, $driver)
    {
        $this->receipt = $receipt;
        $this->result  = $result;
        $this->driver  = (string) $driver;
    }
}
