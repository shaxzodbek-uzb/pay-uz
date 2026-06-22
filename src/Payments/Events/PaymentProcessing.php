<?php

namespace Goodoneuz\PayUz\Payments\Events;

/**
 * Fired when a transaction has been created and payment is in progress — before
 * it is confirmed (the old `paying` listener / `paying.php`).
 */
class PaymentProcessing extends PaymentEvent
{
}
