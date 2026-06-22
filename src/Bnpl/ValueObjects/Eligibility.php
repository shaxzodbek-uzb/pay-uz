<?php

namespace Goodoneuz\PayUz\Bnpl\ValueObjects;

/**
 * Result of a buyer eligibility check (Uzum Nasiya `buyers/check-status`).
 *
 * The buyer `statusCode` drives the flow: only {@see STATUS_VERIFIED} (4) may
 * proceed to {@see \Goodoneuz\PayUz\Bnpl\Contracts\BnplDriver::calculate()};
 * the "must register / upload docs" codes should open {@see webviewUrl()}; the
 * blocked codes must stop the flow.
 */
class Eligibility
{
    const STATUS_NOT_FOUND = 0;
    const STATUS_VERIFIED  = 4;

    /** Codes where the buyer must finish onboarding in the Uzum WebView. */
    const REGISTER_CODES = [0, 1, 2, 5, 10, 11, 12];

    /** Codes where the buyer cannot use Nasiya at all. */
    const BLOCKED_CODES = [8, 9, 13, 14, 403];

    /** @var int */
    protected $statusCode;

    /** @var int|null */
    protected $buyerId;

    /** @var string|null hosted onboarding/registration URL */
    protected $webviewUrl;

    /** @var array available installment periods offered to this buyer */
    protected $availablePeriods;

    /** @var bool */
    protected $isBlacklisted;

    /** @var bool */
    protected $hasOverdue;

    /** @var array */
    protected $raw;

    public function __construct(array $data = [])
    {
        $this->statusCode       = isset($data['status']) ? (int) $data['status'] : self::STATUS_NOT_FOUND;
        $this->buyerId          = isset($data['buyer_id']) && $data['buyer_id'] !== '' ? (int) $data['buyer_id'] : null;
        $this->webviewUrl       = isset($data['webview']) && $data['webview'] !== '' ? (string) $data['webview'] : null;
        $this->availablePeriods = isset($data['available_periods']) && is_array($data['available_periods']) ? $data['available_periods'] : [];
        $this->isBlacklisted    = !empty($data['is_in_black_list']);
        $this->hasOverdue       = !empty($data['has_overdue_contracts']);
        $this->raw              = $data;
    }

    /** Buyer is verified and may proceed to calculate/createContract. */
    public function isEligible()
    {
        return $this->statusCode === self::STATUS_VERIFIED;
    }

    /** Buyer must finish onboarding in the WebView before proceeding. */
    public function mustRegister()
    {
        return in_array($this->statusCode, self::REGISTER_CODES, true);
    }

    /** Buyer is blocked / blacklisted / has debt — stop the flow. */
    public function isBlocked()
    {
        return $this->isBlacklisted || in_array($this->statusCode, self::BLOCKED_CODES, true);
    }

    public function statusCode()
    {
        return $this->statusCode;
    }

    public function buyerId()
    {
        return $this->buyerId;
    }

    public function webviewUrl()
    {
        return $this->webviewUrl;
    }

    public function availablePeriods()
    {
        return $this->availablePeriods;
    }

    public function hasOverdue()
    {
        return $this->hasOverdue;
    }

    public function raw()
    {
        return $this->raw;
    }
}
