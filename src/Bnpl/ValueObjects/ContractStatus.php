<?php

namespace Goodoneuz\PayUz\Bnpl\ValueObjects;

/**
 * Polled state of an installment contract (Uzum Nasiya `contracts/check-status`).
 * Nasiya has no signed server-to-server callback, so status is poll-only.
 */
class ContractStatus
{
    const NOT_CONFIRMED = 0;
    const ACTIVE        = 1;
    const MODERATION    = 2;
    const OVERDUE_60    = 3;
    const OVERDUE_30    = 4;
    const CANCELLED     = 5;
    const CLOSED        = 9;

    /** @var int */
    protected $code;

    /** @var bool */
    protected $isSigned;

    /** @var array */
    protected $raw;

    public function __construct(array $data = [])
    {
        $this->code     = isset($data['contract_status']) ? (int) $data['contract_status'] : self::NOT_CONFIRMED;
        $this->isSigned = !empty($data['is_signed']);
        $this->raw      = $data;
    }

    public function code()
    {
        return $this->code;
    }

    public function isSigned()
    {
        return $this->isSigned;
    }

    public function isActive()
    {
        return $this->code === self::ACTIVE;
    }

    public function isCancelled()
    {
        return $this->code === self::CANCELLED;
    }

    public function isClosed()
    {
        return $this->code === self::CLOSED;
    }

    public function isOverdue()
    {
        return $this->code === self::OVERDUE_30 || $this->code === self::OVERDUE_60;
    }

    public function raw()
    {
        return $this->raw;
    }
}
