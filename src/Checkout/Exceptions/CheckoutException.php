<?php

namespace Goodoneuz\PayUz\Checkout\Exceptions;

/**
 * Base exception for the Checkout aggregator layer (hosted checkout + card-on-file
 * acquiring via Octo/ATMOS/Multicard).
 *
 * Like the Subscribe layer, the *synchronous* card/acquiring operations throw on
 * failure (a decline or a gateway error must surface), while an asynchronous
 * gateway outcome delivered by webhook is represented as an unsuccessful
 * {@see \Goodoneuz\PayUz\Checkout\PaymentResult}. The original gateway error code
 * is preserved via getCode().
 */
class CheckoutException extends \RuntimeException
{
    /** @var array full decoded response */
    protected $response;

    /**
     * @param string $message
     * @param int    $code
     * @param array  $response
     */
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
