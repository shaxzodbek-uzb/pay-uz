<?php

namespace Goodoneuz\PayUz\Fiscalization;

use Illuminate\Support\Facades\Facade;

/**
 * @method static FiscalizationManager extend(string $name, callable $factory)
 * @method static \Goodoneuz\PayUz\Fiscalization\Contracts\FiscalDriver driver(string $name = null)
 * @method static FiscalResult fiscalize(Receipt $receipt, string $driver = null)
 * @method static string defaultDriver()
 *
 * @see FiscalizationManager
 */
class FiscalizerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pay-uz-fiscalizer';
    }
}
