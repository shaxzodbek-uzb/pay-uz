<?php

namespace Goodoneuz\PayUz\Subscribe;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Goodoneuz\PayUz\Subscribe\Contracts\SubscribeDriver driver(string $name = null)
 * @method static SubscribeManager extend(string $name, callable $factory)
 * @method static string defaultDriver()
 * @method static Card verify(string $token, string $code, string $driver = null)
 * @method static Charge charge(string $token, int $amount, array $account, array $options = [], string $driver = null)
 * @method static Charge authorize(string $token, int $amount, array $account, array $options = [], string $driver = null)
 * @method static Charge capture(string $receiptId, string $driver = null)
 * @method static Charge release(string $receiptId, string $driver = null)
 *
 * @see SubscribeManager
 */
class SubscribeFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pay-uz-subscribe';
    }
}
