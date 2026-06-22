<?php

/**
 * Created by PhpStorm.
 * User: Shaxzodbek Qambaraliyev
 */

namespace Goodoneuz\PayUz\Services;

use Goodoneuz\PayUz\Payments\DefaultPaymentResolver;
use Goodoneuz\PayUz\Payments\Events\PaymentPaid;
use Goodoneuz\PayUz\Payments\Events\PaymentBeforePay;
use Goodoneuz\PayUz\Payments\Events\PaymentCancelled;
use Goodoneuz\PayUz\Payments\Events\PaymentProcessing;
use Goodoneuz\PayUz\Payments\Contracts\PaymentResolver;

/**
 * The bridge the gateways call into for application-specific behaviour.
 *
 * Historically each method `require`d a PHP file under
 * app/Http/Controllers/Payments that the bundled "code editor" could overwrite
 * at runtime — i.e. write-arbitrary-PHP-then-execute (CVE-2026-31843). That
 * mechanism is gone. The value-returning operations now delegate to a
 * configured {@see PaymentResolver}; the fire-and-forget lifecycle hooks now
 * dispatch Laravel events ({@see \Goodoneuz\PayUz\Payments\Events}).
 *
 * The public API stays static so existing call sites keep working. The resolver
 * and dispatcher are looked up through the framework by default but can be
 * overridden ({@see useResolver()} / {@see useDispatcher()}) for testing.
 */
class PaymentService
{
    /** Maps the legacy payListener() action types to their event classes. */
    const ACTION_EVENTS = [
        'before-pay' => PaymentBeforePay::class,
        'paying'     => PaymentProcessing::class,
        'after-pay'  => PaymentPaid::class,
        'cancel-pay' => PaymentCancelled::class,
    ];

    /** @var PaymentResolver|null explicit override; null means resolve from the container */
    protected static $resolver;

    /** @var object|null explicit event-dispatcher override (must expose dispatch()) */
    protected static $dispatcher;

    /**
     * @return string|int
     */
    public static function convertModelToKey($model)
    {
        return static::resolver()->convertModelToKey($model);
    }

    /**
     * @param  mixed $key key of model
     * @return mixed|null model or null
     */
    public static function convertKeyToModel($key)
    {
        return static::resolver()->convertKeyToModel($key);
    }

    /**
     * @return bool
     */
    public static function isProperModelAndAmount($model, $amount)
    {
        return static::resolver()->isProperModelAndAmount($model, $amount);
    }

    /**
     * Dispatch the lifecycle event matching $action_type. Unknown action types
     * are ignored (forward-compatible with new gateway stages).
     *
     * @param mixed  $model       Payable model (may be null)
     * @param mixed  $transaction Transaction, or the amount for 'before-pay'
     * @param string $action_type before-pay | paying | after-pay | cancel-pay
     */
    public static function payListener($model, $transaction, $action_type)
    {
        if (! isset(self::ACTION_EVENTS[$action_type])) {
            return;
        }

        $event = self::ACTION_EVENTS[$action_type];

        static::dispatch(new $event($model, $transaction));
    }

    /**
     * @param string $context  response context, e.g. "Click@Prepare"
     * @param mixed  $request  request params
     * @param array  $response response payload
     * @return array
     */
    public static function beforeResponse($context, $request, array $response)
    {
        return static::resolver()->beforeResponse($context, $request, $response);
    }

    /**
     * Override the resolver (mainly for tests). Pass null to restore container
     * resolution.
     */
    public static function useResolver(?PaymentResolver $resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * Override the event dispatcher (mainly for tests). The object only needs a
     * dispatch($event) method. Pass null to restore the framework's dispatcher.
     *
     * @param object|null $dispatcher
     */
    public static function useDispatcher($dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * Clear any test overrides.
     */
    public static function reset()
    {
        static::$resolver   = null;
        static::$dispatcher = null;
    }

    /**
     * Resolve the active resolver: an explicit override if set, otherwise the
     * one bound in the container, otherwise the shipped default.
     *
     * @return PaymentResolver
     */
    public static function resolver()
    {
        if (static::$resolver instanceof PaymentResolver) {
            return static::$resolver;
        }

        if (function_exists('app')) {
            try {
                $resolver = app(PaymentResolver::class);
                if ($resolver instanceof PaymentResolver) {
                    return $resolver;
                }
            } catch (\Throwable $e) {
                // No container / binding (e.g. used outside a framework boot) —
                // fall back to the default resolver below.
            }
        }

        return new DefaultPaymentResolver();
    }

    /**
     * @param object $event
     */
    protected static function dispatch($event)
    {
        if (static::$dispatcher !== null) {
            static::$dispatcher->dispatch($event);
            return;
        }

        if (function_exists('event')) {
            event($event);
        }
    }
}
