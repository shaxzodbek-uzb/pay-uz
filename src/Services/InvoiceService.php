<?php
/**
 * Created by PhpStorm.
 * User: Azizbek Eshonaliyev
 * Date: 2/22/2019
 * Time: 8:20 PM
 */

namespace Goodoneuz\PayUz\Services;

use Goodoneuz\PayUz\Models\Invoice;

class InvoiceService
{
    /**
     * @param $model
     * @param $amount
     * @param $currency_code
     * @return mixed
     */
    public static function createInvoice($model, $amount, $currency_code){
        return  Invoice::create([
            'invoiceable_type'  => get_class($model),
            'invoiceable_id'    => $model->id,
            'amount'            => floatval($amount),
            'currency_code'     => intval($currency_code),
            'state'             => Invoice::STATE_CREATED,
        ]);
    }
}
