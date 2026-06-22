<?php

namespace Goodoneuz\PayUz\Checkout\Contracts;

use Goodoneuz\PayUz\Checkout\Payment;
use Goodoneuz\PayUz\Checkout\PaymentResult;

/**
 * A card-acquiring aggregator (Octo is the first driver; ATMOS / Multicard
 * follow). Unlike the Subscribe layer, the gateway hosts the card form — you
 * create a payment and redirect the customer to {@see PaymentResult::payUrl()},
 * then receive the final outcome by webhook. A previously saved card can be
 * charged server-side with {@see chargeToken()} (no redirect).
 *
 * Synchronous calls throw a
 * {@see \Goodoneuz\PayUz\Checkout\Exceptions\CheckoutException} on a gateway/
 * transport fault and return a {@see PaymentResult} otherwise. Amounts are tiyin.
 */
interface CheckoutDriver
{
    /**
     * Create a hosted-checkout payment; the result carries the pay URL.
     *
     * @param Payment $payment
     * @return PaymentResult
     */
    public function createPayment(Payment $payment);

    /**
     * Charge a previously saved card token (no customer redirect).
     *
     * @param string  $token
     * @param Payment $payment
     * @return PaymentResult
     */
    public function chargeToken($token, Payment $payment);

    /**
     * Capture a previously authorized (held) payment.
     *
     * @param string   $paymentId gateway payment id, i.e. {@see PaymentResult::paymentId()}
     * @param int|null $amount    tiyin (some gateways require it explicitly)
     * @return PaymentResult
     */
    public function capture($paymentId, $amount = null);

    /**
     * Refund a payment, full or partial.
     *
     * @param string   $paymentId gateway payment id, i.e. {@see PaymentResult::paymentId()}
     * @param int|null $amount    tiyin
     * @return PaymentResult
     */
    public function refund($paymentId, $amount = null);

    /**
     * Fetch the current state of a payment.
     *
     * NOTE the identifier is GATEWAY-SPECIFIC — drivers key status differently:
     * Octo on the merchant order id ({@see PaymentResult::orderId()} /
     * shop_transaction_id); Multicard on the gateway payment uuid
     * ({@see PaymentResult::paymentId()}). Pass the value the active driver
     * documents.
     *
     * @param string $reference gateway-specific payment reference (see the driver)
     * @return PaymentResult
     */
    public function status($reference);

    /**
     * Verify an inbound webhook's authenticity (signature).
     *
     * @param array $payload
     * @param array $headers
     * @return bool
     */
    public function verifyWebhook(array $payload, array $headers = []);

    /**
     * Normalize an inbound webhook payload into a PaymentResult.
     *
     * SECURITY: a gateway signature typically covers only the payment id + status,
     * NOT the amount or card. Treat {@see PaymentResult::amount()} from a webhook
     * as untrusted — reconcile it against {@see status()} before granting value.
     *
     * @param array $payload
     * @return PaymentResult
     */
    public function parseWebhook(array $payload);

    /**
     * @return string driver name (e.g. 'octo', 'null')
     */
    public function name();
}
