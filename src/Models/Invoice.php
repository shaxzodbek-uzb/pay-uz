<?php

namespace Goodoneuz\PayUz\Models;

use Goodoneuz\PayUz\Services\InvoiceService;
use Goodoneuz\PayUz\Services\TransactionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 */

class Invoice extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'invoiceable_type',
        'invoiceable_id',
        'currency_code',
        'amount',
        'state',
    ];

    /**
     * @var array
     */
    protected $dates    = [
        'deleted_at'
    ];

    /**
     *
     */
    const STATE_CREATED = 'created';

    /**
     *
     */
    const STATE_CANCELED = 'canceled';


    /**
     *
     */
    const STATE_PAID    = 'paid';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function invoiceable()
    {
        return $this->morphTo();
    }

    /**
     * @param $model
     * @param $amount
     * @param $currency_code
     */
    public function createInvoice($model, $amount, $currency_code){
        $model->invoices()->create([
                'amount'        => $amount,
                'currency_code' => $currency_code
        ]);
    }

    /**
     * @param $amount
     * @return bool
     */
    public function isPayable($amount){
        return ($this->amount == $amount) ? true : false;
    }

    /**
     * @param $transaction_id
     * @throws \Exception
     */
    public function pay($transaction_id){

        $transaction = TransactionService::getTransactionById($transaction_id);

        if ($transaction->state == Transaction::STATE_COMPLETED)
        {
            if ($transaction->amount == $this->amount)
            {
                $this->state = self::STATE_PAID;
                $this->update();
            }
        }
    }
}
