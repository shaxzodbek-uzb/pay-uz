<?php

namespace Goodoneuz\PayUz\Einvoice\Exceptions;

/**
 * Thrown when a {@see \Goodoneuz\PayUz\Einvoice\Document} is structurally invalid
 * (no items, missing seller/buyer TIN, bad MXIK) — a client-side error to fix
 * before anything is sent to the operator.
 */
class InvalidDocumentException extends EinvoiceException
{
}
