<?php

namespace Goodoneuz\PayUz\Einvoice;

/**
 * The single tiyin <-> decimal-som-string boundary for the e-invoicing layer.
 *
 * The package stores money in tiyin (int, 1 som = 100 tiyin), but Didox ЭСФ
 * amounts are decimal SOM strings (e.g. "1.12"). Converting through this one
 * tested helper keeps the 100x bug from creeping in: internal arithmetic stays
 * integer, only the wire is decimal.
 */
class Som
{
    /**
     * tiyin -> decimal som string with 2 places, e.g. 112 -> "1.12".
     *
     * @param int $tiyin
     * @return string
     */
    public static function toWire($tiyin)
    {
        return number_format(((int) $tiyin) / 100, 2, '.', '');
    }

    /**
     * decimal som (string|number) -> tiyin, e.g. "1.12" -> 112.
     *
     * @param string|int|float $som
     * @return int
     */
    public static function fromWire($som)
    {
        return (int) round(((float) $som) * 100);
    }
}
