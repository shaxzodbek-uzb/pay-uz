<?php

namespace Goodoneuz\PayUz\Fiscalization\Events;

use Goodoneuz\PayUz\Fiscalization\Receipt;
use Goodoneuz\PayUz\Fiscalization\FiscalResult;

/**
 * Fired after a receipt is successfully registered with an OFD. Listen for this
 * to persist the fiscal sign/QR onto your order or transaction, notify the
 * customer, etc.
 *
 * Plain public properties (no Laravel base class) so the event also works when
 * the package is used outside a full framework boot.
 */
class ReceiptFiscalized
{
    /** @var Receipt */
    public $receipt;

    /** @var FiscalResult */
    public $result;

    /** @var string driver name that produced the result */
    public $driver;

    public function __construct(Receipt $receipt, FiscalResult $result, $driver)
    {
        $this->receipt = $receipt;
        $this->result  = $result;
        $this->driver  = (string) $driver;
    }
}
