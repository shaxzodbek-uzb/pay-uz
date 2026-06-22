<?php

namespace Goodoneuz\PayUz\Fiscalization;

/**
 * VAT (НДС / QQS) helpers for fiscal receipts.
 *
 * Uzbek retail prices are quoted VAT-inclusive, while the fiscal receipt must
 * carry the VAT *amount* extracted from that gross price. The standard rate has
 * been 12% since 2023 (down from 15%); 0% covers exempt / zero-rated goods.
 *
 * All money is in tiyin (1 som = 100 tiyin); the extracted amount is rounded to
 * a whole tiyin so a receipt's line VAT amounts sum back to the receipt total.
 */
class Vat
{
    /** Standard VAT rate in Uzbekistan (per cent). */
    const RATE_STANDARD = 12;

    /** Zero / exempt rate. */
    const RATE_ZERO = 0;

    /** Rates accepted on a fiscal receipt. */
    const RATES = [0, 12];

    /**
     * @param int|float $rate per-cent value
     * @return bool
     */
    public static function isValidRate($rate)
    {
        // Compare loosely on numeric value (12 == 12.0) but reject non-numerics.
        if (!is_numeric($rate)) {
            return false;
        }

        foreach (self::RATES as $valid) {
            if ((float) $rate === (float) $valid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the VAT amount contained in a VAT-inclusive (gross) tiyin amount.
     *
     *   vat = gross * rate / (100 + rate)
     *
     * e.g. 112_000 tiyin gross at 12% -> 12_000 tiyin VAT.
     *
     * Computed with pure-integer, round-half-up arithmetic (no floats) so the
     * result is deterministic and matches what the OFD back-end re-validates —
     * round(a/b) == floor((2a + b) / 2b).
     *
     * @param int       $grossTiyin gross amount, VAT inclusive, in tiyin
     * @param int|float $ratePercent 0 or 12
     * @return int VAT amount in tiyin
     */
    public static function fromGross($grossTiyin, $ratePercent)
    {
        $rate = (int) $ratePercent;

        if ($rate <= 0) {
            return 0;
        }

        $gross = (int) $grossTiyin;
        $den   = 100 + $rate;

        return intdiv(2 * $gross * $rate + $den, 2 * $den);
    }
}
