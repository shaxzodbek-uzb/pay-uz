<?php

namespace Goodoneuz\PayUz\Bnpl\ValueObjects;

/**
 * One calculated installment tariff (a row of Uzum Nasiya `orders/calculate`).
 *
 * All money is in tiyin — the driver has already converted from Nasiya's decimal
 * som. {@see tariffId()} is the value passed back as `period` to createContract.
 */
class InstallmentPlan
{
    /** @var string tariff id, e.g. "12 Default" */
    protected $tariffId;

    /** @var int */
    protected $periodMonths;

    /** @var int total payable, tiyin */
    protected $totalTiyin;

    /** @var int principal before markup, tiyin */
    protected $originTiyin;

    /** @var int monthly payment, tiyin */
    protected $monthlyTiyin;

    /** @var int upfront deposit, tiyin */
    protected $depositTiyin;

    /** @var string|null */
    protected $firstPaymentDate;

    /** @var bool */
    protected $isAvailable;

    /** @var bool */
    protected $isMiniLoan;

    /** @var array */
    protected $raw;

    /**
     * @param array $data already-tiyin values (the driver converts som->tiyin)
     */
    public function __construct(array $data = [])
    {
        $this->tariffId         = isset($data['tariff_id']) ? (string) $data['tariff_id'] : '';
        $this->periodMonths     = isset($data['period_months']) ? (int) $data['period_months'] : 0;
        $this->totalTiyin       = isset($data['total']) ? (int) $data['total'] : 0;
        $this->originTiyin      = isset($data['origin']) ? (int) $data['origin'] : 0;
        $this->monthlyTiyin     = isset($data['monthly']) ? (int) $data['monthly'] : 0;
        $this->depositTiyin     = isset($data['deposit']) ? (int) $data['deposit'] : 0;
        $this->firstPaymentDate = isset($data['first_payment_date']) && $data['first_payment_date'] !== '' ? (string) $data['first_payment_date'] : null;
        $this->isAvailable      = !empty($data['is_available']);
        $this->isMiniLoan       = !empty($data['is_mini_loan']);
        $this->raw              = isset($data['raw']) ? (array) $data['raw'] : $data;
    }

    public function tariffId()
    {
        return $this->tariffId;
    }

    public function periodMonths()
    {
        return $this->periodMonths;
    }

    public function totalTiyin()
    {
        return $this->totalTiyin;
    }

    public function monthlyTiyin()
    {
        return $this->monthlyTiyin;
    }

    public function depositTiyin()
    {
        return $this->depositTiyin;
    }

    public function originTiyin()
    {
        return $this->originTiyin;
    }

    public function firstPaymentDate()
    {
        return $this->firstPaymentDate;
    }

    public function isAvailable()
    {
        return $this->isAvailable;
    }

    public function isMiniLoan()
    {
        return $this->isMiniLoan;
    }

    public function raw()
    {
        return $this->raw;
    }
}
