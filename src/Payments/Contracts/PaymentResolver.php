<?php

namespace Goodoneuz\PayUz\Payments\Contracts;

/**
 * Bridges the package's payment gateways to your application's domain.
 *
 * It replaces the old runtime-writable hook files (app/Http/Controllers/Payments/*.php
 * that PaymentService used to `require`) with versioned application code. Bind an
 * implementation under `payuz.payments.resolver`; the shipped default is
 * {@see \Goodoneuz\PayUz\Payments\DefaultPaymentResolver}.
 *
 * These four operations must return a value, so they are a resolver rather than
 * an event. The fire-and-forget payment lifecycle hooks (before-pay, paying,
 * after-pay, cancel-pay) are Laravel events instead — see
 * {@see \Goodoneuz\PayUz\Payments\Events}.
 */
interface PaymentResolver
{
    /**
     * Map one of your payable models to the key sent to the payment system
     * (e.g. the order id placed in `account[...]` / `merchant_trans_id`).
     *
     * @param  mixed $model
     * @return string|int
     */
    public function convertModelToKey($model);

    /**
     * Resolve the payable model from the key a payment system sent back.
     *
     * @param  mixed $key
     * @return mixed|null the model, or null when it cannot be found
     */
    public function convertKeyToModel($key);

    /**
     * Guard a callback: is this the right model, and does the amount match what
     * it owes? Returning false makes the gateway reject the transaction.
     *
     * @param  mixed $model
     * @param  mixed $amount
     * @return bool
     */
    public function isProperModelAndAmount($model, $amount);

    /**
     * Last chance to adjust a gateway's success-response payload before it is
     * sent back to the payment system. Return the (possibly modified) response.
     *
     * @param  string $context gateway@method label, e.g. "Click@Prepare"
     * @param  mixed  $request the inbound request params
     * @param  array  $response the response payload built by the gateway
     * @return array
     */
    public function beforeResponse($context, $request, array $response);
}
