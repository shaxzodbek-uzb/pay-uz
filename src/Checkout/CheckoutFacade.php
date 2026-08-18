<?php

namespace Goodoneuz\PayUz\Checkout;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Goodoneuz\PayUz\Checkout\Contracts\CheckoutDriver driver(string $name = null)
 * @method static CheckoutManager extend(string $name, callable $factory)
 * @method static string defaultDriver()
 * @method static PaymentResult pay(Payment $payment, string $driver = null)
 * @method static PaymentResult charge(string $token, Payment $payment, string $driver = null)
 * @method static PaymentResult capture(string $paymentId, int $amount = null, string $driver = null)
 * @method static PaymentResult refund(string $paymentId, int $amount = null, string $driver = null)
 * @method static PaymentResult status(string $reference, string $driver = null)
 * @method static PaymentResult webhook(array $payload, array $headers = [], string $driver = null)
 * @method static \Goodoneuz\PayUz\Checkout\Contracts\CardBinder binder(string $driver = null)
 * @method static BindingSession bindCard(CardBinding $binding, string $driver = null)
 * @method static BindingSession confirmBinding(string $bindingId, int $verifyId, string $code, string $driver = null)
 * @method static BoundCard bindCallback(array $payload, string $driver = null)
 * @method static bool revokeToken(string $token, string $driver = null)
 *
 * @see CheckoutManager
 */
class CheckoutFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pay-uz-checkout';
    }
}
