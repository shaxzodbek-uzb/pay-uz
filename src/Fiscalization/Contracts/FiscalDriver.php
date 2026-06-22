<?php

namespace Goodoneuz\PayUz\Fiscalization\Contracts;

use Goodoneuz\PayUz\Fiscalization\Receipt;
use Goodoneuz\PayUz\Fiscalization\FiscalResult;

/**
 * A fiscalization driver turns a {@see Receipt} into a registered fiscal receipt
 * with an Uzbek Fiscal Data Operator (OFD) and returns the fiscal sign / QR.
 *
 * Drivers are intentionally thin: all amount/VAT/IKPU normalisation lives in the
 * receipt value objects, so a driver only maps the canonical receipt to its
 * provider's wire format, performs the call and parses the response into a
 * {@see FiscalResult}. A driver MUST NOT throw for a *business* failure (the OFD
 * rejected the receipt) — it returns an unsuccessful FiscalResult for those — and
 * MAY throw {@see \Goodoneuz\PayUz\Fiscalization\Exceptions\FiscalizationException}
 * only for transport/configuration faults.
 */
interface FiscalDriver
{
    /**
     * Register the receipt with the OFD. The receipt's type (sale / refund)
     * selects the operation.
     *
     * @param Receipt $receipt
     * @return FiscalResult
     */
    public function fiscalize(Receipt $receipt);

    /**
     * Machine name of the driver (e.g. 'ofd', 'null').
     *
     * @return string
     */
    public function name();
}
