<?php

namespace Goodoneuz\PayUz\Payments\Events;

/**
 * Fired after a payment completes successfully (the old `after-pay` listener /
 * `after_pay.php`). This is where you mark the order paid, fulfil it, fiscalize
 * the receipt, notify the customer, etc.
 */
class PaymentPaid extends PaymentEvent
{
}
