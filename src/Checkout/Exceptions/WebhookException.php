<?php

namespace Goodoneuz\PayUz\Checkout\Exceptions;

/**
 * Thrown when an inbound gateway webhook fails verification (bad/missing
 * signature) — the payload must not be trusted or acted upon.
 */
class WebhookException extends CheckoutException
{
}
