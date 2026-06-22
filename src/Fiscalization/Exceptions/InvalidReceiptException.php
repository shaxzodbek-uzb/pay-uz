<?php

namespace Goodoneuz\PayUz\Fiscalization\Exceptions;

/**
 * Thrown when a {@see \Goodoneuz\PayUz\Fiscalization\Receipt} or one of its items
 * is structurally invalid (empty receipt, bad MXIK, unsupported VAT rate,
 * payment split that does not match the total, …) — i.e. a client-side error
 * that must be fixed before anything is sent to the OFD.
 */
class InvalidReceiptException extends FiscalizationException
{
}
