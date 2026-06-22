<?php

namespace Goodoneuz\PayUz\Bnpl;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Goodoneuz\PayUz\Bnpl\Contracts\BnplDriver driver(string $name = null)
 * @method static BnplManager extend(string $name, callable $factory)
 * @method static string defaultDriver()
 * @method static \Goodoneuz\PayUz\Bnpl\ValueObjects\Eligibility checkEligibility(string $phone)
 * @method static \Goodoneuz\PayUz\Bnpl\ValueObjects\InstallmentPlan[] calculate(int $buyerId, array $items)
 * @method static \Goodoneuz\PayUz\Bnpl\ValueObjects\Contract createContract(int $buyerId, string $period, array $items, string $extOrderId = null, string $returnUrl = null)
 * @method static \Goodoneuz\PayUz\Bnpl\ValueObjects\ContractResult confirm(int $contractId)
 * @method static \Goodoneuz\PayUz\Bnpl\ValueObjects\ContractResult cancel(int $orderId)
 * @method static \Goodoneuz\PayUz\Bnpl\ValueObjects\ContractStatus status(int $contractId)
 *
 * @see BnplManager
 */
class BnplFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pay-uz-bnpl';
    }
}
