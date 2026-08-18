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
use Goodoneuz\PayUz\Checkout\Contracts\CardBinder;
use Goodoneuz\PayUz\Checkout\Contracts\CheckoutDriver;
use Goodoneuz\PayUz\Checkout\Exceptions\CheckoutException;
use Goodoneuz\PayUz\Checkout\Exceptions\WebhookException;
use Goodoneuz\PayUz\Services\PaymentSystemService;
use Illuminate\Support\Facades\Schema;

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

    // --- card binding (optional capability) ---

    /**
     * The active driver as a {@see CardBinder}.
     *
     * @param string|null $driver
     * @return CardBinder
     * @throws CheckoutException when the driver cannot bind cards
     */
    public function binder($driver = null)
    {
        $instance = $this->driver($driver);

        if (!$instance instanceof CardBinder) {
            throw new CheckoutException(sprintf('Checkout driver "%s" does not support card binding.', $instance->name()));
        }

        return $instance;
    }

    /**
     * Send a card for binding and trigger its confirmation code.
     *
     * @param CardBinding $binding
     * @param string|null $driver
     * @return BindingSession
     */
    public function bindCard(CardBinding $binding, $driver = null)
    {
        return $this->binder($driver)->startBinding($binding);
    }

    /**
     * Submit the confirmation code for a binding.
     *
     * @param string      $bindingId
     * @param int         $verifyId
     * @param string      $code
     * @param string|null $driver
     * @return BindingSession
     */
    public function confirmBinding($bindingId, $verifyId, $code, $driver = null)
    {
        return $this->binder($driver)->confirmBinding($bindingId, $verifyId, $code);
    }

    /**
     * Read the gateway's binding callback — the request that carries the token.
     *
     * Answer it with HTTP 200 and {@see BoundCard::acknowledgement()} even when the
     * reference matches nothing you know: Octo retries three times and then cancels
     * the token, so silence destroys a card the customer bound successfully.
     *
     * @param array       $payload
     * @param string|null $driver
     * @return BoundCard
     */
    public function bindCallback(array $payload, $driver = null)
    {
        return $this->binder($driver)->parseBindCallback($payload);
    }

    /**
     * Revoke a stored token at the gateway.
     *
     * @param string      $token
     * @param string|null $driver
     * @return bool
     */
    public function revokeToken($token, $driver = null)
    {
        return $this->binder($driver)->revokeToken($token);
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
     * A driver's credentials: the config file first, then whatever the control
     * panel holds for the same system.
     *
     * Credentials belong in `payment_system_params` — the same table Payme, Click
     * and Uzum read from — so an operator can enter them in the panel instead of
     * redeploying. The config file stays useful as a default (and for anyone
     * driving the package from env), which is why the two are merged rather than
     * one replacing the other.
     *
     * @param string $name
     * @return array
     */
    protected function driverConfig($name)
    {
        $config = isset($this->config['drivers'][$name]) && is_array($this->config['drivers'][$name])
            ? $this->config['drivers'][$name]
            : [];

        return array_merge($config, $this->storedParams($name));
    }

    /**
     * Control-panel params for a system, or [] when there are none to read.
     *
     * Resolved lazily (a driver is only built when used) and defensively: the
     * package boots before migrations have run on a fresh install, and the
     * Checkout drivers are unit-tested with no framework at all.
     *
     * Blank params are dropped so an empty field in the panel cannot erase a value
     * that is configured in the config file.
     *
     * @param string $name
     * @return array
     */
    protected function storedParams($name)
    {
        try {
            if (!Schema::hasTable('payment_system_params')) {
                return [];
            }

            $params = PaymentSystemService::getPaymentSystemParamsCollect($name);
            $params = is_object($params) && method_exists($params, 'all') ? $params->all() : (array) $params;
        } catch (\Throwable $e) {
            return [];
        }

        return array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });
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
