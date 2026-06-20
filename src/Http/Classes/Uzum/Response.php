<?php

namespace Goodoneuz\PayUz\Http\Classes\Uzum;

use Goodoneuz\PayUz\Http\Classes\DataFormat;
use Goodoneuz\PayUz\Http\Classes\PaymentException;

/**
 * Uzum Bank "Merchant API" (Model A) response builder.
 *
 * The bank's processing centre calls the merchant endpoints (check / create /
 * confirm / reverse / status). Every response carries the serviceId and a
 * timestamp; errors use HTTP 400 (or 401 for auth) with status "FAILED" and an
 * errorCode from the table below.
 *
 * Like the other drivers in this package, success()/error() short-circuit the
 * flow by throwing a PaymentException that PayUz::handle() turns into output.
 */
class Response
{
    // --- Error codes (Uzum Merchant API) ---
    const ERROR_AUTH                  = 10001; // authorization failed
    const ERROR_PARSE_JSON            = 10002; // error parsing the request JSON
    const ERROR_UNKNOWN_OPERATION     = 10003; // unknown operation
    const ERROR_NOT_ENOUGH_PARAMS     = 10005; // not enough params in request
    const ERROR_INVALID_SERVICE_ID    = 10006; // invalid serviceId
    const ERROR_ALREADY_PROCESSED     = 10007; // payment already processed / confirmed
    const ERROR_TRANSACTION_NOT_FOUND = 10008; // transaction / account not found
    const ERROR_PAYMENT_CANCELLED     = 10009; // payment cancelled
    const ERROR_CHECK_PAYMENT_DATA    = 99999; // generic validation failure (e.g. amount)

    // --- Status literals ---
    const STATUS_OK        = 'OK';
    const STATUS_FAILED    = 'FAILED';
    const STATUS_CREATED   = 'CREATED';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_REVERSED  = 'REVERSED';

    /** @var array */
    public $response;

    /** @var int|string|null */
    private $serviceId;

    /** @var int */
    private $httpCode = 200;

    public function __construct()
    {
        $this->response = [];
    }

    public function setServiceId($serviceId)
    {
        $this->serviceId = $serviceId;
        return $this;
    }

    public function send()
    {
        if (! app()->runningUnitTests()) {
            http_response_code($this->httpCode);
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode($this->response);
    }

    /**
     * @param  int   $code     one of the ERROR_* codes
     * @param  int   $httpCode HTTP status (400 business error, 401 auth)
     * @param  array $extra    extra fields (e.g. transId) to merge in
     * @throws PaymentException
     */
    public function error($code, $httpCode = 400, array $extra = [])
    {
        $this->httpCode = $httpCode;
        $this->response = array_merge([
            'serviceId' => $this->serviceId,
            'timestamp' => DataFormat::timestamp(true),
            'status'    => self::STATUS_FAILED,
            'errorCode' => $code,
        ], $extra);
        throw new PaymentException($this);
    }

    /**
     * @param  array $result fields to return alongside serviceId/timestamp
     * @throws PaymentException
     */
    public function success(array $result)
    {
        $this->httpCode = 200;
        $this->response = array_merge([
            'serviceId' => $this->serviceId,
            'timestamp' => DataFormat::timestamp(true),
        ], $result);
        throw new PaymentException($this);
    }
}
