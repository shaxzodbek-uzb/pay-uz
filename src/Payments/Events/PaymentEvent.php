<?php

namespace Goodoneuz\PayUz\Payments\Events;

/**
 * Base for the payment lifecycle events fired by {@see \Goodoneuz\PayUz\Services\PaymentService}.
 *
 * These replace the old `payListener()` hook files: instead of writing PHP that
 * the package `require`s at runtime, subscribe to these events from your
 * application's EventServiceProvider.
 *
 * Plain public properties (no Laravel base class) so the events also work when
 * the package is used outside a full framework boot — mirroring the
 * Fiscalization events.
 */
abstract class PaymentEvent
{
    /** @var mixed the payable model (may be null — some gateways only know the transaction at this stage) */
    public $model;

    /** @var mixed the {@see \Goodoneuz\PayUz\Models\Transaction} (or, for before-pay, the amount) */
    public $transaction;

    /**
     * @param mixed $model
     * @param mixed $transaction
     */
    public function __construct($model = null, $transaction = null)
    {
        $this->model       = $model;
        $this->transaction = $transaction;
    }
}
