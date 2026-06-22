<?php

namespace Goodoneuz\PayUz\Einvoice\Events;

use Goodoneuz\PayUz\Einvoice\EinvoiceResult;

/**
 * Fired when a submitted document is cancelled.
 */
class DocumentCancelled
{
    /** @var EinvoiceResult */
    public $result;

    /** @var string driver name */
    public $driver;

    public function __construct(EinvoiceResult $result, $driver)
    {
        $this->result = $result;
        $this->driver = (string) $driver;
    }
}
