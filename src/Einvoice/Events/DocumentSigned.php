<?php

namespace Goodoneuz\PayUz\Einvoice\Events;

use Goodoneuz\PayUz\Einvoice\EinvoiceResult;

/**
 * Fired when an outgoing document is signed/submitted (delivered) or an incoming one is accepted.
 */
class DocumentSigned
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
