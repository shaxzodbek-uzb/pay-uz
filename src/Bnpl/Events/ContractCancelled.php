<?php

namespace Goodoneuz\PayUz\Bnpl\Events;

use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractResult;

/**
 * Fired when an installment contract is cancelled.
 */
class ContractCancelled
{
    /** @var ContractResult */
    public $result;

    /** @var string driver name */
    public $driver;

    public function __construct(ContractResult $result, $driver)
    {
        $this->result = $result;
        $this->driver = (string) $driver;
    }
}
