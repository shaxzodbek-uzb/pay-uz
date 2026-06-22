<?php

namespace Goodoneuz\PayUz\Support\Http;

/**
 * Thrown when a JSON-RPC endpoint returns an `error` member. Carries the JSON-RPC
 * error code and the optional `data` payload alongside the message, so callers
 * can map specific codes (e.g. Payme's card/insufficient-funds errors) to their
 * own domain exceptions.
 */
class JsonRpcException extends \RuntimeException
{
    /** @var mixed JSON-RPC error.data (often the offending field name) */
    protected $data;

    /** @var array the full decoded response body */
    protected $response;

    /**
     * @param string $message
     * @param int    $code
     * @param mixed  $data
     * @param array  $response
     */
    public function __construct($message, $code = 0, $data = null, array $response = [])
    {
        parent::__construct($message, (int) $code);
        $this->data     = $data;
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function response()
    {
        return $this->response;
    }
}
