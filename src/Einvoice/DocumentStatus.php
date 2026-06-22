<?php

namespace Goodoneuz\PayUz\Einvoice;

/**
 * The state of an e-document. Didox exposes a numeric `doc_status`; the full
 * int->name map is not authoritatively documented, so this ALWAYS carries the raw
 * integer and exposes a best-effort label — business logic should branch on
 * {@see code()}, not on a guessed name.
 *
 * TODO: confirm the full doc_status map against the partner spec.
 */
class DocumentStatus
{
    const DRAFT             = 0;   // created, not signed
    const SIGNED_ONE_PARTY  = 1;   // signed by one party
    const REJECTED          = 3;   // rejected / cancelled
    const WAIT_FOR_AGENT    = 60;  // sent to agent

    /** @var string|null */
    protected $documentId;

    /** @var int */
    protected $code;

    /** @var array */
    protected $raw;

    public function __construct(array $data = [])
    {
        $this->documentId = isset($data['document_id']) && $data['document_id'] !== '' ? (string) $data['document_id']
            : (isset($data['_id']) ? (string) $data['_id'] : null);
        $this->code       = isset($data['doc_status']) ? (int) $data['doc_status'] : self::DRAFT;
        $this->raw        = $data;
    }

    public function documentId()
    {
        return $this->documentId;
    }

    public function code()
    {
        return $this->code;
    }

    public function isDraft()
    {
        return $this->code === self::DRAFT;
    }

    public function isRejected()
    {
        return $this->code === self::REJECTED;
    }

    /**
     * Best-effort human label for the raw code (do not branch on this).
     *
     * @return string
     */
    public function label()
    {
        switch ($this->code) {
            case self::DRAFT:
                return 'draft';
            case self::SIGNED_ONE_PARTY:
                return 'signed_one_party';
            case self::REJECTED:
                return 'rejected';
            case self::WAIT_FOR_AGENT:
                return 'wait_for_agent';
            default:
                return 'status_'.$this->code;
        }
    }

    public function raw()
    {
        return $this->raw;
    }
}
