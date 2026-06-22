<?php

namespace Goodoneuz\PayUz\Einvoice\Contracts;

/**
 * The E-IMZO signing seam. Uzbek e-documents must be signed with the taxpayer's
 * ЭЦП into a timestamped PKCS#7 — a crypto operation this package deliberately
 * does NOT perform. A host app implements this (e.g. calling the E-IMZO browser
 * plugin / CAPIWS / an E-IMZO server) and the driver relays the result.
 *
 * @see \Goodoneuz\PayUz\Einvoice\Signers\CallableSigner to wrap a closure.
 */
interface Signer
{
    /**
     * Sign a base64 to-be-signed payload and return the (timestamped) PKCS#7 blob.
     *
     * @param string $base64Payload the canonical payload from the driver
     * @return string the signature blob to hand back to the operator
     * @throws \Goodoneuz\PayUz\Einvoice\Exceptions\SigningException on failure
     */
    public function sign($base64Payload);
}
