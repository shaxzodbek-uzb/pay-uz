<?php

namespace Goodoneuz\PayUz\Einvoice\Exceptions;

/**
 * Base exception for the e-invoicing (ЭСФ / e-document) layer. Thrown for
 * transport/auth faults and unknown drivers; a *business* rejection by Didox is
 * an unsuccessful {@see \Goodoneuz\PayUz\Einvoice\EinvoiceResult}.
 */
class EinvoiceException extends \RuntimeException
{
    /** @var array decoded response body */
    protected $response;

    public function __construct($message, $code = 0, array $response = [])
    {
        parent::__construct($message, (int) $code);
        $this->response = $response;
    }

    public function response()
    {
        return $this->response;
    }
}
