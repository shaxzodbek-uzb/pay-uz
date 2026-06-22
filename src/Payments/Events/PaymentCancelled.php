<?php

namespace Goodoneuz\PayUz\Payments\Events;

/**
 * Fired when a payment is cancelled or reversed (the old `cancel-pay` listener /
 * `cancel_pay.php`). Use it to release a reservation, refund, or revert order state.
 */
class PaymentCancelled extends PaymentEvent
{
}
