<?php

namespace Goodoneuz\PayUz\Payments\Events;

/**
 * Fired before a payment is accepted — when the gateway has resolved the model
 * but no transaction exists yet (the old `before-pay` listener / `before_pay.php`).
 * For this event `$transaction` carries the requested amount, not a Transaction.
 */
class PaymentBeforePay extends PaymentEvent
{
}
