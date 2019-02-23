<?php

namespace Goodoneuz\PayUz;

use Goodoneuz\PayUz\Http\Classes\Click\Click;
use Goodoneuz\PayUz\Http\Classes\Payme\Payme;
use Goodoneuz\PayUz\Models\Transaction;
use Goodoneuz\PayUz\Services\InvoiceService;

class PayUz
{

    protected $driverClass = null;

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
     * @throws \Exception
     */
    public function pay($model, $amount, $currency_code){
        $invoice = $this->createInvoice($model,$amount,$currency_code);
    }

    /**
     * @param $model
     * @param $amount
     * @param $currency_code
     * @return mixed
     * @throws \Exception
     */
    public function createInvoice($model, $amount, $currency_code){
        $this->validateModel($model, $amount, $currency_code);
        return InvoiceService::createInvoice($model,$amount,$currency_code);
    }


    /**
     * Select payment driver
     * @param null $driver
     * @return $this
     */
    public function driver($driver = null){
        switch ($driver){
            case Transaction::PAYME:
                $this->driverClass = Payme::class;
                break;
            case Transaction::CLICK:
                $this->driverClass = Click::class;
                break;
        }
        return $this;
    }

    /**
     * Redirect to payment system
     * @param $model
     * @param $amount
     * @param int $currency_code
     * @return PayUz
     * @throws \Exception
     */
    public function redirect($model, $amount, $currency_code = 860){
        $this->validateDriver();
        $invoice = $this->createInvoice($model, $amount, $currency_code);
        (new $this->driverClass)::getRedirectParams($invoice);
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function handle(){
        $this->validateDriver();
        (new $this->driverClass)->run();
        return $this;
    }

    /**
     * @param $model
     * @param $amount
     * @param $currency_code
     * @throws \Exception
     */
    public function validateModel($model, $amount, $currency_code){
        if (is_null($model))
            throw new \Exception('Modal can\'t be null');
        if (is_null($amount) || $amount == 0)
            throw new \Exception('Amount can\'t be null or 0');
        if (is_null($currency_code))
            throw new \Exception('Currency code can\'t be null');
    }

    /**
     * @throws \Exception
     */
    public function validateDriver(){
        if (is_null($this->driverClass))
            throw new \Exception('Driver not selected');
    }
}
