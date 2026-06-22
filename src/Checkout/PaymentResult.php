<?php

namespace Goodoneuz\PayUz\Checkout;

/**
 * The normalized outcome of a checkout operation (create / charge / capture /
 * refund / status / webhook), with a vendor-agnostic status so application code
 * does not branch on each gateway's raw strings — the driver maps its own status
 * into one of the STATUS_* constants and keeps the untouched response on raw().
 *
 * Amounts are in tiyin. On a freshly created hosted payment, {@see payUrl()} is
 * where the customer is redirected; {@see needsRedirect()} reflects that.
 */
class PaymentResult
{
    const STATUS_CREATED   = 'created';   // awaiting customer action (redirect)
    const STATUS_PENDING   = 'pending';   // in progress
    const STATUS_HELD      = 'held';      // authorized, awaiting capture
    const STATUS_SUCCEEDED = 'succeeded'; // paid / captured
    const STATUS_FAILED    = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED  = 'refunded';

    /** @var string normalized status */
    protected $status;

    /** @var string gateway payment id / uuid (use for capture/refund) */
    protected $paymentId;

    /** @var string merchant order id / shop_transaction_id (use for status) */
    protected $orderId;

    /** @var string|null hosted-checkout URL (create only) */
    protected $payUrl;

    /** @var int amount, tiyin */
    protected $amount;

    /** @var string|null saved card token */
    protected $cardToken;

    /** @var string|null masked card number */
    protected $maskedCard;

    /** @var string|int|null gateway error code (failures) */
    protected $errorCode;

    /** @var string|null error message */
    protected $errorMessage;

    /** @var array raw gateway response */
    protected $raw;

    public function __construct($status, array $attributes = [])
    {
        $this->status       = (string) $status;
        $this->paymentId    = isset($attributes['payment_id'])    ? (string) $attributes['payment_id'] : '';
        $this->orderId      = isset($attributes['order_id'])      ? (string) $attributes['order_id']   : '';
        $this->payUrl       = isset($attributes['pay_url'])       ? (string) $attributes['pay_url']    : null;
        $this->amount       = isset($attributes['amount'])        ? (int) $attributes['amount']        : 0;
        $this->cardToken    = isset($attributes['card_token'])    ? (string) $attributes['card_token'] : null;
        $this->maskedCard   = isset($attributes['masked_card'])   ? (string) $attributes['masked_card'] : null;
        $this->errorCode    = isset($attributes['error_code'])    ? $attributes['error_code']          : null;
        $this->errorMessage = isset($attributes['error_message']) ? (string) $attributes['error_message'] : null;
        $this->raw          = isset($attributes['raw'])           ? (array) $attributes['raw']         : [];
    }

    public function status()
    {
        return $this->status;
    }

    public function paymentId()
    {
        return $this->paymentId;
    }

    /**
     * Merchant order id (shop_transaction_id) — the identifier {@see status()}
     * expects, as opposed to {@see paymentId()} for capture/refund.
     *
     * @return string
     */
    public function orderId()
    {
        return $this->orderId;
    }

    public function payUrl()
    {
        return $this->payUrl;
    }

    public function needsRedirect()
    {
        return $this->payUrl !== null && $this->payUrl !== '';
    }

    public function amount()
    {
        return $this->amount;
    }

    public function cardToken()
    {
        return $this->cardToken;
    }

    public function maskedCard()
    {
        return $this->maskedCard;
    }

    public function isSuccessful()
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }

    public function isHeld()
    {
        return $this->status === self::STATUS_HELD;
    }

    public function isRefunded()
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED || $this->status === self::STATUS_CANCELLED;
    }

    public function errorCode()
    {
        return $this->errorCode;
    }

    public function errorMessage()
    {
        return $this->errorMessage;
    }

    public function raw()
    {
        return $this->raw;
    }

    /**
     * @return array safe to persist alongside an order
     */
    public function toArray()
    {
        return [
            'status'        => $this->status,
            'payment_id'    => $this->paymentId,
            'order_id'      => $this->orderId,
            'pay_url'       => $this->payUrl,
            'amount'        => $this->amount,
            'card_token'    => $this->cardToken,
            'masked_card'   => $this->maskedCard,
            'error_code'    => $this->errorCode,
            'error_message' => $this->errorMessage,
        ];
    }
}
