<?php

namespace Goodoneuz\PayUz\Fiscalization;

/**
 * The outcome of registering a {@see Receipt} with an OFD.
 *
 * On success it carries the fiscal sign (фискальный признак / ФП), the QR payload
 * the customer scans, the public receipt URL (e.g. ofd.soliq.uz/check/…) and the
 * provider's receipt/terminal identifiers. On failure it carries the OFD's error
 * code and message. The raw decoded response is always kept for auditing and is
 * safe to persist into a transaction's `detail` JSON.
 */
class FiscalResult
{
    /** @var bool */
    protected $success;

    /** @var string|null provider receipt id */
    protected $receiptId;

    /** @var string|null fiscal sign / ФП */
    protected $fiscalSign;

    /** @var string|null QR code payload or URL */
    protected $qr;

    /** @var string|null public receipt page URL */
    protected $receiptUrl;

    /** @var string|null terminal / cash-register id */
    protected $terminalId;

    /** @var string|int|null OFD error code (failures only) */
    protected $errorCode;

    /** @var string|null human-readable error message (failures only) */
    protected $errorMessage;

    /** @var array raw decoded provider response */
    protected $raw;

    /**
     * Use the {@see success()} / {@see failure()} factories instead of new.
     *
     * @param bool  $success
     * @param array $attributes
     */
    public function __construct($success, array $attributes = [])
    {
        $this->success      = (bool) $success;
        $this->receiptId    = isset($attributes['receipt_id'])    ? $attributes['receipt_id']    : null;
        $this->fiscalSign   = isset($attributes['fiscal_sign'])   ? $attributes['fiscal_sign']   : null;
        $this->qr           = isset($attributes['qr'])            ? $attributes['qr']            : null;
        $this->receiptUrl   = isset($attributes['receipt_url'])   ? $attributes['receipt_url']   : null;
        $this->terminalId   = isset($attributes['terminal_id'])   ? $attributes['terminal_id']   : null;
        $this->errorCode    = isset($attributes['error_code'])    ? $attributes['error_code']    : null;
        $this->errorMessage = isset($attributes['error_message']) ? $attributes['error_message'] : null;
        $this->raw          = isset($attributes['raw'])           ? (array) $attributes['raw']   : [];
    }

    /**
     * @param array $attributes receipt_id, fiscal_sign, qr, receipt_url, terminal_id, raw
     * @return self
     */
    public static function success(array $attributes = [])
    {
        return new self(true, $attributes);
    }

    /**
     * @param string          $message
     * @param string|int|null $code
     * @param array           $raw
     * @return self
     */
    public static function failure($message, $code = null, array $raw = [])
    {
        return new self(false, [
            'error_message' => $message,
            'error_code'    => $code,
            'raw'           => $raw,
        ]);
    }

    public function isSuccessful()
    {
        return $this->success;
    }

    public function receiptId()
    {
        return $this->receiptId;
    }

    public function fiscalSign()
    {
        return $this->fiscalSign;
    }

    public function qr()
    {
        return $this->qr;
    }

    public function receiptUrl()
    {
        return $this->receiptUrl;
    }

    public function terminalId()
    {
        return $this->terminalId;
    }

    public function errorCode()
    {
        return $this->errorCode;
    }

    public function errorMessage()
    {
        return $this->errorMessage;
    }

    public function raw()
    {
        return $this->raw;
    }

    /**
     * @return array safe to store in Transaction.detail['fiscal']
     */
    public function toArray()
    {
        return [
            'success'       => $this->success,
            'receipt_id'    => $this->receiptId,
            'fiscal_sign'   => $this->fiscalSign,
            'qr'            => $this->qr,
            'receipt_url'   => $this->receiptUrl,
            'terminal_id'   => $this->terminalId,
            'error_code'    => $this->errorCode,
            'error_message' => $this->errorMessage,
        ];
    }
}
