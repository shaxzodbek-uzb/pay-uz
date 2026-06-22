<?php

namespace Goodoneuz\PayUz\Einvoice\Signers;

use Goodoneuz\PayUz\Einvoice\Contracts\Signer;
use Goodoneuz\PayUz\Einvoice\Exceptions\SigningException;

/**
 * Default signer — refuses to sign, because the package ships no crypto. Configure
 * a real {@see Signer} (or a {@see CallableSigner}) for the convenience
 * sign-and-submit flow, or fetch the to-sign payload, sign it yourself, and pass
 * the pre-signed blob to the driver.
 */
class NullSigner implements Signer
{
    public function sign($base64Payload)
    {
        throw new SigningException('No E-IMZO signer configured; sign the to-sign payload yourself and pass the pre-signed blob to the driver.');
    }
}
