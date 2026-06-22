<?php

namespace Goodoneuz\PayUz\Einvoice;

/**
 * The outcome of an e-invoicing operation (create / sign / accept / reject /
 * cancel / login). Mirrors {@see \Goodoneuz\PayUz\Fiscalization\FiscalResult}:
 * success carries the document id / status / a session token; failure carries the
 * operator's error. The raw response is always kept.
 */
class EinvoiceResult
{
    /** @var bool */
    protected $success;

    /** @var string|null */
    protected $documentId;

    /** @var int|null doc_status */
    protected $status;

    /** @var string|null session token (login) */
    protected $token;

    /** @var string|null */
    protected $errorMessage;

    /** @var array */
    protected $raw;

    public function __construct($success, array $attributes = [])
    {
        $this->success      = (bool) $success;
        $this->documentId   = isset($attributes['document_id'])   ? (string) $attributes['document_id'] : null;
        $this->status       = isset($attributes['status'])        ? (int) $attributes['status']         : null;
        $this->token        = isset($attributes['token'])         ? (string) $attributes['token']       : null;
        $this->errorMessage = isset($attributes['error_message']) ? (string) $attributes['error_message'] : null;
        $this->raw          = isset($attributes['raw'])           ? (array) $attributes['raw']          : [];
    }

    public static function success(array $attributes = [])
    {
        return new self(true, $attributes);
    }

    public static function failure($message, array $raw = [])
    {
        return new self(false, ['error_message' => $message, 'raw' => $raw]);
    }

    public function isSuccessful()
    {
        return $this->success;
    }

    public function documentId()
    {
        return $this->documentId;
    }

    public function status()
    {
        return $this->status;
    }

    public function token()
    {
        return $this->token;
    }

    public function errorMessage()
    {
        return $this->errorMessage;
    }

    public function raw()
    {
        return $this->raw;
    }
}
