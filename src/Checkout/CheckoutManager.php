<?php

namespace Goodoneuz\PayUz\Checkout;

use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Support\Http\CurlHttpClient;
use Goodoneuz\PayUz\Checkout\Drivers\OctoDriver;
use Goodoneuz\PayUz\Checkout\Drivers\NullDriver;
use Goodoneuz\PayUz\Checkout\Drivers\MulticardDriver;
use Goodoneuz\PayUz\Checkout\Events\PaymentFailed;
use Goodoneuz\PayUz\Checkout\Events\PaymentRefunded;
use Goodoneuz\PayUz\Checkout\Events\PaymentSucceeded;
use Goodoneuz\PayUz\Checkout\Contracts\CheckoutDriver;
use Goodoneuz\PayUz\Checkout\Exceptions\CheckoutException;
use Goodoneuz\PayUz\Checkout\Exceptions\WebhookException;

/**
 * Resolves and drives Checkout (acquiring aggregator) drivers — the entry point
 * behind the `Checkout` facade. Mirrors the Fiscalization/Subscribe managers: a
 * configurable driver registry with extend(), plus helpers that emit events.
 *
 *   $result = Checkout::pay(Payment::make(1_200_000, $order->id)->returnTo($url)->notifyAt($hook));
 *   return redirect($result->payUrl());
 *   // ... later, in your webhook route:
 *   $result = Checkout::webhook(request()->all(), request()->headers->all()); // verifies + emits events
 */
class CheckoutManager
{
    /** @var array the `checkout` config block */
    protected $config;

    /** @var HttpClient */
    protected $http;

    /** @var \Illuminate\Contracts\Events\Dispatcher|null */
    protected $dispatcher;

    /** @var CheckoutDriver[] */
    protected $drivers = [];

    /** @var callable[] */
    protected $customCreators = [];

    /**
     * @param array           $config
     * @param HttpClient|null $http
     * @param mixed           $dispatcher
     */
    public function __construct(array $config = [], ?HttpClient $http = null, $dispatcher = null)
    {
        $this->config     = $config;
        $this->http       = $http ?: new CurlHttpClient();
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string|null $name
     * @return CheckoutDriver
     * @throws CheckoutException for an unknown driver
     */
    public function driver($name = null)
    {
        $name = $name ?: $this->defaultDriver();

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->resolve($name);
        }

        return $this->drivers[$name];
    }

    /**
     * @param string   $name
     * @param callable $factory function(array $driverConfig, HttpClient $http): CheckoutDriver
     * @return self
     */
    public function extend($name, callable $factory)
    {
        $this->customCreators[$name] = $factory;
        unset($this->drivers[$name]);

        return $this;
    }

    /**
     * @return string
     */
    public function defaultDriver()
    {
        return isset($this->config['default']) && $this->config['default']
            ? $this->config['default']
            : 'null';
    }

    // --- helpers ---

    /**
     * Create a hosted-checkout payment (redirect the customer to its pay URL).
     *
     * @param Payment     $payment
     * @param string|null $driver
     * @return PaymentResult
     */
    public function pay(Payment $payment, $driver = null)
    {
        return $this->driver($driver)->createPayment($payment);
    }

    /**
     * Charge a saved card token (no redirect) and emit on success.
     *
     * @param string      $token
     * @param Payment     $payment
     * @param string|null $driver
     * @return PaymentResult
     */
    public function charge($token, Payment $payment, $driver = null)
    {
        $instance = $this->driver($driver);
        $result   = $instance->chargeToken($token, $payment);
        $this->emit($result, $instance->name());

        return $result;
    }

    /**
     * Capture a held payment and emit on success.
     *
     * @param string      $paymentId
     * @param int|null    $amount
     * @param string|null $driver
     * @return PaymentResult
     */
    public function capture($paymentId, $amount = null, $driver = null)
    {
        $instance = $this->driver($driver);
        $result   = $instance->capture($paymentId, $amount);
        $this->emit($result, $instance->name());

        return $result;
    }

    /**
     * Refund a payment and emit on success.
     *
     * @param string      $paymentId
     * @param int|null    $amount
     * @param string|null $driver
     * @return PaymentResult
     */
    public function refund($paymentId, $amount = null, $driver = null)
    {
        $instance = $this->driver($driver);
        $result   = $instance->refund($paymentId, $amount);
        $this->emit($result, $instance->name());

        return $result;
    }

    /**
     * Poll a payment's current state. The reference is GATEWAY-SPECIFIC — pass
     * what the active driver documents (Octo: the merchant order id /
     * {@see PaymentResult::orderId()}; Multicard: the payment uuid /
     * {@see PaymentResult::paymentId()}). Emits the matching event if the poll
     * finds a terminal outcome — emit() stays silent for created/pending/held — so
     * a reconciliation poll fires the same events as a webhook.
     *
     * @param string      $reference gateway-specific payment reference
     * @param string|null $driver
     * @return PaymentResult
     */
    public function status($reference, $driver = null)
    {
        $instance = $this->driver($driver);
        $result   = $instance->status($reference);
        $this->emit($result, $instance->name());

        return $result;
    }

    /**
     * Verify and normalize an inbound webhook, emitting the matching event. A
     * payload that fails signature verification raises a WebhookException — never
     * act on an unverified callback.
     *
     * SECURITY: the signature usually covers only the payment id + status, so the
     * emitted event type is trustworthy but the result's AMOUNT/card are not.
     * Before granting value, reconcile the amount via {@see status()} (keyed on
     * {@see PaymentResult::orderId()}) — do not trust the webhook amount alone.
     *
     * @param array       $payload
     * @param array       $headers
     * @param string|null $driver
     * @return PaymentResult
     * @throws WebhookException
     */
    public function webhook(array $payload, array $headers = [], $driver = null)
    {
        $instance = $this->driver($driver);

        if (!$instance->verifyWebhook($payload, $headers)) {
            throw new WebhookException('Checkout webhook signature verification failed.');
        }

        $result = $instance->parseWebhook($payload);
        $this->emit($result, $instance->name());

        return $result;
    }

    // --- internals ---

    /**
     * Emit the event matching a result's terminal status (no event for pending/
     * created/held — those are not terminal outcomes).
     *
     * @param PaymentResult $result
     * @param string        $driver
     */
    protected function emit(PaymentResult $result, $driver)
    {
        if ($result->isSuccessful()) {
            $this->dispatch(new PaymentSucceeded($result, $driver));
        } elseif ($result->isRefunded()) {
            $this->dispatch(new PaymentRefunded($result, $driver));
        } elseif ($result->isFailed()) {
            $this->dispatch(new PaymentFailed($result, $driver));
        }
    }

    /**
     * @param string $name
     * @return CheckoutDriver
     * @throws CheckoutException
     */
    protected function resolve($name)
    {
        $driverConfig = $this->driverConfig($name);

        if (isset($this->customCreators[$name])) {
            return call_user_func($this->customCreators[$name], $driverConfig, $this->http);
        }

        switch ($name) {
            case 'octo':
                return new OctoDriver($driverConfig, $this->http);
            case 'multicard':
                return new MulticardDriver($driverConfig, $this->http);
            case 'rahmat':
                // "Rahmat Pay" is not a separate processor — it is the Multicard
                // acquiring rail (its hosted checkout renders on app.rhmt.uz). This
                // alias resolves to the Multicard driver using the `multicard`
                // config so `Checkout::driver('rahmat')` just works.
                return new MulticardDriver($this->driverConfig('multicard'), $this->http);
            case 'null':
                return new NullDriver();
        }

        throw new CheckoutException(sprintf('Checkout driver "%s" is not supported.', $name));
    }

    /**
     * @param string $name
     * @return array
     */
    protected function driverConfig($name)
    {
        return isset($this->config['drivers'][$name]) && is_array($this->config['drivers'][$name])
            ? $this->config['drivers'][$name]
            : [];
    }

    /**
     * @param object $event
     */
    protected function dispatch($event)
    {
        if ($this->dispatcher !== null) {
            $this->dispatcher->dispatch($event);
        }
    }
}
