<?php

namespace Goodoneuz\PayUz;

use Goodoneuz\PayUz\Services\InvoiceService;
use Goodoneuz\PayUz\Services\PaymentSystemService;
use Goodoneuz\PayUz\Services\TransactionService;

class PayUz
{
    /**
     * PayUz constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param $model
     * @param $amount
     * @param $currency_code
     */
    public function pay($model, $amount, $currency_code){
        $invoice = $this->createInvoice($model,$amount,$currency_code);
    }

    /**
     * @param $model
     * @param $amount
     * @param $currency_code
     * @return mixed
     */
    public function createInvoice($model, $amount, $currency_code){
        return InvoiceService::createInvoice($model,$amount,$currency_code);
    }

}
