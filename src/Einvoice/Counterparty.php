<?php

namespace Goodoneuz\PayUz\Einvoice;

/**
 * A party to an e-document — seller or buyer — identified by TIN (INN) / PINFL.
 */
class Counterparty
{
    /** @var string TIN or PINFL */
    protected $tin;

    /** @var string|null */
    protected $name;

    public function __construct($tin, $name = null)
    {
        $this->tin  = (string) $tin;
        $this->name = $name !== null ? (string) $name : null;
    }

    public function tin()
    {
        return $this->tin;
    }

    public function name()
    {
        return $this->name;
    }
}
