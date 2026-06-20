<?php

namespace Goodoneuz\PayUz\Http\Classes\Uzum;

/**
 * Parses an Uzum Bank Merchant API request body.
 *
 * The body is JSON, e.g.:
 *   { "serviceId": 123, "timestamp": 171890..., "transId": "uuid",
 *     "amount": 100000, "params": { "type": "order", "id": "9932" } }
 *
 * `amount` is in tiyin (1 som = 100 tiyin). `params` carries the account fields;
 * the configured `key` param name selects which one identifies the order/model.
 */
class Request
{
    /** @var array decoded request payload */
    public $payload;

    /** @var int|string|null */
    public $serviceId;

    /** @var int|string|null */
    public $timestamp;

    /** @var string|null Uzum transaction id */
    public $transId;

    /** @var int|null amount in tiyin */
    public $amount;

    /** @var array account params */
    public $params;

    /** @var string|null */
    public $paymentSource;

    /** @var string|null */
    public $tariff;

    /** @var string|null */
    public $processingReferenceNumber;

    public $response;

    public function __construct($response)
    {
        $this->response = $response;

        // Octane/RoadRunner safe body read (see issue #71).
        $request_body = request()->getContent();
        if (app()->runningUnitTests()) {
            $request_body = request()->all()['request'] ?? $request_body;
        }

        $this->payload = json_decode($request_body, true);
        if (!is_array($this->payload)) {
            $this->response->error(Response::ERROR_PARSE_JSON);
        }

        $this->serviceId = $this->payload['serviceId'] ?? null;
        $this->timestamp = $this->payload['timestamp'] ?? null;
        $this->transId   = $this->payload['transId'] ?? null;
        $this->amount    = isset($this->payload['amount']) ? 1 * $this->payload['amount'] : null;
        $this->params    = isset($this->payload['params']) && is_array($this->payload['params'])
            ? $this->payload['params'] : [];
        $this->paymentSource             = $this->payload['paymentSource'] ?? null;
        $this->tariff                    = $this->payload['tariff'] ?? null;
        $this->processingReferenceNumber = $this->payload['processingReferenceNumber'] ?? null;

        // Let the response echo the serviceId back on every reply (incl. errors).
        $this->response->setServiceId($this->serviceId);
    }

    /**
     * Read an account parameter (e.g. the order id) from params.
     * @param  string $param
     * @return mixed|null
     */
    public function account($param)
    {
        return $this->params[$param] ?? null;
    }
}
