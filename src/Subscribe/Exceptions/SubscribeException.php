<?php

namespace Goodoneuz\PayUz\Subscribe\Exceptions;

use Goodoneuz\PayUz\Support\Http\JsonRpcException;

/**
 * Base exception for the card-tokenization / recurring-charge layer.
 *
 * Card operations are interactive (a decline must be shown to the user), so the
 * drivers THROW typed exceptions on failure and return value objects on success
 * — unlike the fire-and-forget fiscalization layer, which returns a result.
 *
 * The original gateway error code is preserved via getCode(); only the codes that
 * are verified in the Subscribe/Merchant docs are mapped to specific subclasses.
 * Everything else (including card-decline codes, whose exact numbers are not
 * publicly documented) surfaces as a plain SubscribeException carrying the raw
 * code/message — inspect getCode() to branch on a specific decline.
 */
class SubscribeException extends \RuntimeException
{
    /** @var mixed gateway error.data (often the offending field) */
    protected $data;

    /** @var array full decoded response */
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
     * Map a JSON-RPC error to the most specific Subscribe exception we can verify.
     *
     * @param JsonRpcException $e
     * @return SubscribeException
     */
    public static function fromJsonRpc(JsonRpcException $e)
    {
        $code = $e->getCode();
        $args = [$e->getMessage(), $code, $e->data(), $e->response()];

        if ($code === -32504) {
            return new AuthorizationException(...$args);
        }
        if ($code === -31001) {
            return new InvalidAmountException(...$args);
        }
        if ($code === -31003) {
            return new ReceiptNotFoundException(...$args);
        }
        if ($code === -31007) {
            return new CancellationException(...$args);
        }
        if ($code === -31008) {
            return new OperationException(...$args);
        }
        if ($code <= -31050 && $code >= -31099) {
            return new AccountException(...$args);
        }

        return new self(...$args);
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
