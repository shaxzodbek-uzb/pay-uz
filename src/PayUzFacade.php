<?php

namespace Goodoneuz\PayUz;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Goodone\PayUz\Skeleton\SkeletonClass
 */
class PayUzFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pay-uz';
    }
}
