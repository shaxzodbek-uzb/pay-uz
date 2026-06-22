<?php

namespace Goodoneuz\PayUz\Bnpl\ValueObjects;

/**
 * Outcome of a confirm() / cancel() call. Nasiya returns these as a
 * `response_code` (0 = OK) even on a business failure (HTTP 400), so this carries
 * the code rather than throwing. {@see isRetryable()} is true for a transient
 * technical error (code 1000) or any 5xx — the caller may retry those.
 */
class ContractResult
{
    const CODE_OK             = 0;
    const CODE_NOT_FOUND      = 4004;
    const CODE_WRONG_STATUS   = 4009;
    const CODE_ALREADY_ACTIVE = 4010;
    const CODE_TECHNICAL      = 1000;

    /** @var bool */
    protected $ok;

    /** @var int */
    protected $responseCode;

    /** @var string|null signed act PDF URL (confirm) */
    protected $actPdfUrl;

    /** @var bool */
    protected $retryable;

    /** @var array */
    protected $raw;

    public function __construct($ok, $responseCode, array $attributes = [])
    {
        $this->ok           = (bool) $ok;
        $this->responseCode = (int) $responseCode;
        $this->actPdfUrl    = isset($attributes['act_pdf_url']) && $attributes['act_pdf_url'] !== '' ? (string) $attributes['act_pdf_url'] : null;
        $this->retryable    = !empty($attributes['retryable']);
        $this->raw          = isset($attributes['raw']) ? (array) $attributes['raw'] : [];
    }

    /**
     * Build from a confirm/cancel response body + HTTP status.
     *
     * @param array $body
     * @param int   $httpStatus
     * @return self
     */
    public static function fromResponse(array $body, $httpStatus)
    {
        $code      = isset($body['response_code']) ? (int) $body['response_code'] : self::CODE_TECHNICAL;
        $data      = isset($body['data']) && is_array($body['data']) ? $body['data'] : [];
        $retryable = $code === self::CODE_TECHNICAL || (int) $httpStatus >= 500;

        return new self($code === self::CODE_OK, $code, [
            'act_pdf_url' => isset($data['client_act_pdf']) ? $data['client_act_pdf'] : null,
            'retryable'   => $retryable,
            'raw'         => $body,
        ]);
    }

    public function isOk()
    {
        return $this->ok;
    }

    public function responseCode()
    {
        return $this->responseCode;
    }

    public function actPdfUrl()
    {
        return $this->actPdfUrl;
    }

    public function isRetryable()
    {
        return $this->retryable;
    }

    public function raw()
    {
        return $this->raw;
    }
}
