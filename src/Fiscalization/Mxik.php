<?php

namespace Goodoneuz\PayUz\Fiscalization;

/**
 * IKPU / MXIK (ИКПУ / МХИК) product-classification code helper.
 *
 * Every line on an Uzbek fiscal receipt must carry the product's MXIK code from
 * the national classifier (tasnif.soliq.uz). The code is a fixed-length numeric
 * string; an optional "package code" (упаковка / o`rom kodi) further pins the
 * unit of sale for that MXIK.
 *
 * Length is exposed as a constant so a single edit tracks any future change to
 * the classifier format.
 */
class Mxik
{
    /** Number of digits in an MXIK / IKPU code. */
    const LENGTH = 17;

    /**
     * @param string|int|null $code
     * @return bool true when $code is exactly self::LENGTH digits.
     */
    public static function isValid($code)
    {
        $code = self::normalize($code);

        return $code !== '' && strlen($code) === self::LENGTH;
    }

    /**
     * Strip spaces/separators and cast to string. Does not pad — a too-short or
     * too-long value is returned as-is so {@see isValid()} can reject it.
     *
     * @param string|int|null $code
     * @return string digits only
     */
    public static function normalize($code)
    {
        if ($code === null) {
            return '';
        }

        // Keep digits only; MXIK codes are purely numeric.
        return preg_replace('/\D+/', '', (string) $code);
    }

    /**
     * @param string|int|null $packageCode
     * @return bool true when present and numeric (package codes are short ints).
     */
    public static function isValidPackageCode($packageCode)
    {
        if ($packageCode === null || $packageCode === '') {
            return false;
        }

        return ctype_digit((string) $packageCode);
    }
}
