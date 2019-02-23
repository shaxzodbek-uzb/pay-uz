<?php

namespace Goodoneuz\PayUz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 */

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoiceable_type',
        'invoiceable_id',
        'currency_code',
        'amount',
        'state',
    ];

    protected $dates    = [
        'deleted_at'
    ];

    const STATE_CREATED = 'created';

    const STATE_CANCELED = 'canceled';

    const STATE_COMPLETED  = 'completed';

    public function invoiceable()
    {
        return $this->morphTo();
    }

    public function createInvoice($model,$amount,$currency_code){
        $model->invoices()->create([
                'amount'        => $amount,
                'currency_code' => $currency_code
        ]);
    }
}
