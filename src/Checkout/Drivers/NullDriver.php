<?php

namespace Goodoneuz\PayUz\Checkout\Drivers;

use Goodoneuz\PayUz\Checkout\Payment;
use Goodoneuz\PayUz\Checkout\PaymentResult;
use Goodoneuz\PayUz\Checkout\Contracts\CheckoutDriver;

/**
 * A no-op Checkout driver — the safe default on a fresh install. It simulates the
 * acquiring happy path without a real gateway: createPayment returns a synthetic
 * pay URL, token charges succeed, refunds refund, and webhooks "verify". Use it
 * for local development and tests; switch `checkout.default` to a real driver in
 * production.
 */
class NullDriver implements CheckoutDriver
{
    public function createPayment(Payment $payment)
    {
        $id = $this->fakeId($payment->orderId());

        return new PaymentResult(PaymentResult::STATUS_CREATED, [
            'payment_id' => $id,
            'pay_url'    => 'https://null.checkout/pay/'.$id,
            'amount'     => $payment->amount(),
        ]);
    }

    public function chargeToken($token, Payment $payment)
    {
        return new PaymentResult(PaymentResult::STATUS_SUCCEEDED, [
            'payment_id'  => $this->fakeId($payment->orderId()),
            'amount'      => $payment->amount(),
            'card_token'  => (string) $token,
            'masked_card' => '8600********0000',
        ]);
    }

    public function capture($paymentId, $amount = null)
    {
        return new PaymentResult(PaymentResult::STATUS_SUCCEEDED, [
            'payment_id' => (string) $paymentId,
            'amount'     => $amount !== null ? (int) $amount : 0,
        ]);
    }

    public function refund($paymentId, $amount = null)
    {
        return new PaymentResult(PaymentResult::STATUS_REFUNDED, [
            'payment_id' => (string) $paymentId,
            'amount'     => $amount !== null ? (int) $amount : 0,
        ]);
    }

    public function status($reference)
    {
        return new PaymentResult(PaymentResult::STATUS_SUCCEEDED, ['order_id' => (string) $reference]);
    }

    public function verifyWebhook(array $payload, array $headers = [])
    {
        return true;
    }

    public function parseWebhook(array $payload)
    {
        $status = isset($payload['status']) ? (string) $payload['status'] : PaymentResult::STATUS_SUCCEEDED;

        return new PaymentResult($status, [
            'payment_id' => isset($payload['payment_id']) ? (string) $payload['payment_id'] : '',
            'raw'        => $payload,
        ]);
    }

    public function name()
    {
        return 'null';
    }

    private function fakeId($orderId)
    {
        return 'null-'.substr(md5((string) $orderId), 0, 20);
    }
}
