<?php

namespace Goodoneuz\PayUz\Models;

use Illuminate\Database\Eloquent\Model;
use Goodoneuz\PayUz\Http\Classes\DataFormat;

class Transaction extends Model
{
    protected $fillable = [
        'payment_system', //varchar 191
        'system_transaction_id', // varchar 191
        'amount', // double (15,5)
        'currency_code', // int(11)
        'payable_type', // varchar 191
        'payable_id', // int(11)
        'state', // int(11)
        'create_time', //datetime
        'cancel_time', //datetime
        'perform_time', //datetime
        'system_time_datetime', // date_time
        'comment', // varchar 191
        'detail' // varchar 191
    ];
    const TIMEOUT = 43200000;

    const STATE_CREATED = 1;
    const STATE_COMPLETED = 2;
    const STATE_CANCELLED = -1;
    const STATE_CANCELLED_AFTER_COMPLETE = -2;

    const REASON_RECEIVERS_NOT_FOUND = 1;
    const REASON_PROCESSING_EXECUTION_FAILED = 2;
    const REASON_EXECUTION_FAILED = 3;
    const REASON_CANCELLED_BY_TIMEOUT = 4;
    const REASON_FUND_RETURNED = 5;
    const REASON_UNKNOWN = 10;

    const PAYME     = 'payme';
    const CLICK     = 'click';
    const UPAY      = 'upay';
    const UZCARD    = 'uzcard';
    const MBANK     = 'mbank';
    const OSON      = 'oson';
    const VISA      = 'visa';
    const AGR       = 'agr';
    const PAYNET    = 'paynet';
    const CASH      = 'cash';
    const TERMINAL  = 'terminal';


    const CURRENCY_CODE_UZS = 860;
    const CURRENCY_CODE_RUB = 643;
    const CURRENCY_CODE_USD = 840;
    const CURRENCY_CODE_EUR = 978;

    public function cancel($reason)
    {
        $this->cancel_time = DataFormat::timestamp(true);

        if ($this->state == self::STATE_COMPLETED) {
            // Scenario: CreateTransaction -> PerformTransaction -> CancelTransaction
            $this->state = self::STATE_CANCELLED_AFTER_COMPLETE;
        } else {
            // Scenario: CreateTransaction -> CancelTransaction
            $this->state = self::STATE_CANCELLED;
        }

        $this->comment = $reason;

        $this->update();
    }
    public function isExpired()
    {
        return $this->state == self::STATE_CREATED && DataFormat::datetime2timestamp($this->create_time) - time() > self::TIMEOUT;
    }
    public function order()
    {
        return $this->belongsTo(Invoice::class,'payable_id','id');
    }
}
