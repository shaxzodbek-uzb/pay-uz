<?php

namespace Goodoneuz\PayUz\Checkout;

/**
 * A payment request handed to a {@see Contracts\CheckoutDriver}.
 *
 * Amounts are in tiyin (1 som = 100 tiyin), consistent with the rest of the
 * package — each driver converts to its gateway's unit (Octo, for instance, bills
 * in decimal som). Build it fluently:
 *
 *   $payment = Payment::make(1_200_000, $order->id)
 *       ->describedAs('Pro plan')
 *       ->returnTo(route('checkout.return'))
 *       ->notifyAt(route('checkout.webhook'));
 *
 * (Charging a previously saved token uses {@see Contracts\CheckoutDriver::chargeToken()};
 * acquiring that token is gateway-specific — for Octo it is the separate
 * /bind_card flow, which is out of this contract's scope.)
 */
class Payment
{
    /** @var int amount in tiyin */
    protected $amount;

    /** @var string merchant order / transaction id */
    protected $orderId;

    /** @var string ISO currency, e.g. UZS */
    protected $currency;

    /** @var string|null */
    protected $description;

    /** @var string|null browser return URL after the hosted checkout */
    protected $returnUrl;

    /** @var string|null server-to-server callback URL */
    protected $notifyUrl;

    /** @var bool capture immediately (false = two-stage authorize/hold) */
    protected $autoCapture = true;

    /** @var array driver-specific extras (basket/items, language, ttl, …) */
    protected $extra = [];

    /**
     * @param int    $amount  tiyin
     * @param string $orderId
     * @param string $currency
     */
    public function __construct($amount, $orderId, $currency = 'UZS')
    {
        $this->amount   = (int) $amount;
        $this->orderId  = (string) $orderId;
        $this->currency = (string) $currency;
    }

    /**
     * @param int    $amount  tiyin
     * @param string $orderId
     * @param string $currency
     * @return self
     */
    public static function make($amount, $orderId, $currency = 'UZS')
    {
        return new self($amount, $orderId, $currency);
    }

    public function describedAs($description)
    {
        $this->description = (string) $description;

        return $this;
    }

    public function returnTo($url)
    {
        $this->returnUrl = (string) $url;

        return $this;
    }

    public function notifyAt($url)
    {
        $this->notifyUrl = (string) $url;

        return $this;
    }

    /**
     * Two-stage: authorize/hold now, capture later.
     *
     * @return self
     */
    public function authorizeOnly()
    {
        $this->autoCapture = false;

        return $this;
    }

    public function with(array $extra)
    {
        $this->extra = array_merge($this->extra, $extra);

        return $this;
    }

    // --- accessors ---

    public function amount()
    {
        return $this->amount;
    }

    public function orderId()
    {
        return $this->orderId;
    }

    public function currency()
    {
        return $this->currency;
    }

    public function description()
    {
        return $this->description;
    }

    public function returnUrl()
    {
        return $this->returnUrl;
    }

    public function notifyUrl()
    {
        return $this->notifyUrl;
    }

    public function isAutoCapture()
    {
        return $this->autoCapture;
    }

    public function extra()
    {
        return $this->extra;
    }
}
