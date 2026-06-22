<?php

namespace Goodoneuz\PayUz\Subscribe;

use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Support\Http\CurlHttpClient;
use Goodoneuz\PayUz\Subscribe\Drivers\NullDriver;
use Goodoneuz\PayUz\Subscribe\Drivers\PaymeDriver;
use Goodoneuz\PayUz\Subscribe\Drivers\AtmosDriver;
use Goodoneuz\PayUz\Subscribe\Events\CardVerified;
use Goodoneuz\PayUz\Subscribe\Events\ChargePaid;
use Goodoneuz\PayUz\Subscribe\Events\HoldConfirmed;
use Goodoneuz\PayUz\Subscribe\Events\ChargeCancelled;
use Goodoneuz\PayUz\Subscribe\Contracts\SubscribeDriver;
use Goodoneuz\PayUz\Subscribe\Exceptions\SubscribeException;

/**
 * Resolves and drives card-tokenization / recurring-charge drivers — the entry
 * point behind the `Subscribe` facade. Mirrors {@see \Goodoneuz\PayUz\Fiscalization\FiscalizationManager}:
 * a configurable driver registry with extend(), plus a few high-level helpers
 * (verify / charge / authorize / capture / release) that emit events on success.
 *
 *   $card   = Subscribe::driver('payme')->createCard($number, $expire);
 *   Subscribe::driver('payme')->sendVerifyCode($card->token());
 *   $card   = Subscribe::verify($card->token(), $smsCode);          // CardVerified
 *   $charge = Subscribe::charge($card->token(), 1200000, ['order_id' => 42]); // ChargePaid
 */
class SubscribeManager
{
    /** @var array the `subscribe` config block */
    protected $config;

    /** @var HttpClient */
    protected $http;

    /** @var \Illuminate\Contracts\Events\Dispatcher|null */
    protected $dispatcher;

    /** @var SubscribeDriver[] */
    protected $drivers = [];

    /** @var callable[] */
    protected $customCreators = [];

    /**
     * @param array           $config
     * @param HttpClient|null $http
     * @param mixed           $dispatcher event dispatcher with dispatch(), or null
     */
    public function __construct(array $config = [], ?HttpClient $http = null, $dispatcher = null)
    {
        $this->config     = $config;
        $this->http       = $http ?: new CurlHttpClient();
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string|null $name
     * @return SubscribeDriver
     * @throws SubscribeException for an unknown driver
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
     * @param callable $factory function(array $driverConfig, HttpClient $http): SubscribeDriver
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

    // --- high-level helpers (emit events on success) ---

    /**
     * Confirm a card's OTP and emit CardVerified.
     *
     * @param string      $token
     * @param string      $code
     * @param string|null $driver
     * @return Card
     */
    public function verify($token, $code, $driver = null)
    {
        $instance = $this->driver($driver);
        $card     = $instance->verifyCard($token, $code);

        if ($card->isVerified()) {
            $this->dispatch(new CardVerified($card, $instance->name()));
        }

        return $card;
    }

    /**
     * Charge a verified token in one call (create + pay) and emit ChargePaid.
     *
     * @param string      $token
     * @param int         $amount  tiyin
     * @param array       $account merchant account keys
     * @param array       $options 'description', 'detail', 'payer'
     * @param string|null $driver
     * @return Charge
     */
    public function charge($token, $amount, array $account, array $options = [], $driver = null)
    {
        unset($options['hold']); // charge() always captures; use authorize() to hold

        $instance = $this->driver($driver);

        $receipt = $instance->createReceipt($amount, $account, $options);
        $charge  = $this->payOrCancel($instance, $receipt->id(), $token, $options);

        if ($charge->isPaid()) {
            $this->dispatch(new ChargePaid($charge, $instance->name()));
        }

        return $charge;
    }

    /**
     * Two-stage authorize (hold) — create + pay with hold=true. Funds are blocked
     * (state 5), not captured; call {@see capture()} to take them.
     *
     * @param string      $token
     * @param int         $amount  tiyin
     * @param array       $account
     * @param array       $options
     * @param string|null $driver
     * @return Charge
     */
    public function authorize($token, $amount, array $account, array $options = [], $driver = null)
    {
        $options['hold'] = true;
        $instance = $this->driver($driver);

        $receipt = $instance->createReceipt($amount, $account, $options);

        return $this->payOrCancel($instance, $receipt->id(), $token, $options);
    }

    /**
     * Capture a held receipt and emit HoldConfirmed.
     *
     * @param string      $receiptId
     * @param string|null $driver
     * @return Charge
     */
    public function capture($receiptId, $driver = null)
    {
        $instance = $this->driver($driver);
        $charge   = $instance->confirmHold($receiptId);

        if ($charge->isPaid()) {
            $this->dispatch(new HoldConfirmed($charge, $instance->name()));
        }

        return $charge;
    }

    /**
     * Cancel a receipt / release a hold and emit ChargeCancelled.
     *
     * @param string      $receiptId
     * @param string|null $driver
     * @return Charge
     */
    public function release($receiptId, $driver = null)
    {
        $instance = $this->driver($driver);
        $charge   = $instance->cancelReceipt($receiptId);

        if ($charge->isCancelled()) {
            $this->dispatch(new ChargeCancelled($charge, $instance->name()));
        }

        return $charge;
    }

    // --- internals ---

    /**
     * Pay a freshly-created receipt; if the payment is declined (throws a typed
     * SubscribeException) cancel the now-orphaned unpaid receipt best-effort, then
     * re-throw. A transport failure is NOT cleaned up — the charge may have
     * succeeded server-side, so voiding it could drop a real payment.
     *
     * @param SubscribeDriver $instance
     * @param string          $receiptId
     * @param string          $token
     * @param array           $options
     * @return Charge
     */
    protected function payOrCancel($instance, $receiptId, $token, array $options)
    {
        try {
            return $instance->payReceipt($receiptId, $token, $options);
        } catch (SubscribeException $e) {
            try {
                $instance->cancelReceipt($receiptId);
            } catch (\Throwable $ignored) {
                // best-effort cleanup; surface the original decline
            }

            throw $e;
        }
    }

    /**
     * @param string $name
     * @return SubscribeDriver
     * @throws SubscribeException
     */
    protected function resolve($name)
    {
        $driverConfig = $this->driverConfig($name);

        if (isset($this->customCreators[$name])) {
            return call_user_func($this->customCreators[$name], $driverConfig, $this->http);
        }

        switch ($name) {
            case 'payme':
                return new PaymeDriver($driverConfig, $this->http);
            case 'atmos':
                return new AtmosDriver($driverConfig, $this->http);
            case 'null':
                return new NullDriver();
        }

        throw new SubscribeException(sprintf('Subscribe driver "%s" is not supported.', $name));
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
