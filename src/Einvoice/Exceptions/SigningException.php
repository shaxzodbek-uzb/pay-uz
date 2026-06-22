<?php

namespace Goodoneuz\PayUz\Einvoice\Exceptions;

/**
 * Thrown when a document needs an E-IMZO signature but no signer is configured
 * (the default), or the configured signer fails. The package never mints PKCS#7
 * itself — provide a {@see \Goodoneuz\PayUz\Einvoice\Contracts\Signer} or pass a
 * pre-signed blob to the driver.
 */
class SigningException extends EinvoiceException
{
}
