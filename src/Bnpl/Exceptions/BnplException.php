<?php

namespace Goodoneuz\PayUz\Bnpl\Exceptions;

/**
 * Base exception for the BNPL / installments layer (Uzum Nasiya is the first
 * driver). Thrown for transport/auth/validation faults; a *business* contract
 * outcome (confirm/cancel response codes) is carried on a
 * {@see \Goodoneuz\PayUz\Bnpl\ValueObjects\ContractResult} instead.
 */
class BnplException extends \RuntimeException
{
    /** @var array decoded response body */
    protected $response;

    public function __construct($message, $code = 0, array $response = [])
    {
        parent::__construct($message, (int) $code);
        $this->response = $response;
    }

    /**
     * @return array
     */
    public function response()
    {
        return $this->response;
    }
}
