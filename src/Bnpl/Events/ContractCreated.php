<?php

namespace Goodoneuz\PayUz\Bnpl\Events;

use Goodoneuz\PayUz\Bnpl\ValueObjects\Contract;

/**
 * Fired when an installment contract is created (before the buyer signs it).
 * Listen to persist the contract id / order id and the webview path.
 */
class ContractCreated
{
    /** @var Contract */
    public $contract;

    /** @var string driver name */
    public $driver;

    public function __construct(Contract $contract, $driver)
    {
        $this->contract = $contract;
        $this->driver   = (string) $driver;
    }
}
