<?php

namespace Goodoneuz\PayUz;

use Illuminate\Support\Facades\View;
use Goodoneuz\PayUz\Models\Transaction;
use Goodoneuz\PayUz\Models\PaymentSystem;
use Goodoneuz\PayUz\Http\Classes\Payme\Payme;
use Goodoneuz\PayUz\Http\Classes\Click\Click;
use Goodoneuz\PayUz\Http\Classes\Paynet\Paynet;
use Goodoneuz\PayUz\Http\Classes\Stripe\Stripe;
use Goodoneuz\PayUz\Http\Classes\Uzum\Uzum;
use Goodoneuz\PayUz\Http\Classes\PaymentException;

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
     * Payment systems that have a working redirect/callback gateway.
     *
     * PaymentSystem declares more constants than this (oson, uzcard, upay,
     * mbank, visa, agr, cash, terminal). Those are transaction *labels* — there
     * is no gateway behind them — so selecting one must fail here rather than
     * later with a misleading message.
     *
     * @return array<string, class-string>
     */
    public static function supportedDrivers()
    {
        return [
            PaymentSystem::PAYME  => Payme::class,
            PaymentSystem::CLICK  => Click::class,
            PaymentSystem::PAYNET => Paynet::class,
            PaymentSystem::STRIPE => Stripe::class,
            PaymentSystem::UZUM   => Uzum::class,
        ];
    }

    /**
     * Select payment driver
     *
     * @param  string|null $driver a PaymentSystem constant
     * @return $this
     * @throws \InvalidArgumentException when the system has no gateway
     */
    public function driver($driver = null)
    {
        $drivers = self::supportedDrivers();

        if (!isset($drivers[$driver])) {
            // Previously an unknown driver left driverClass null and surfaced
            // later as "Driver not selected" — which is wrong twice over: the
            // caller did select one, and the message names neither what they
            // asked for nor what is available.
            throw new \InvalidArgumentException(sprintf(
                'Payment system [%s] has no gateway in pay-uz. Supported: %s.',
                is_scalar($driver) ? (string) $driver : gettype($driver),
                implode(', ', array_keys($drivers))
            ));
        }

        $class = $drivers[$driver];
        $this->driverClass = new $class;

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
    public function redirect($model, $amount, $currency_code = Transaction::CURRENCY_CODE_UZS, $url = null)
    {
        $this->validateDriver();
        $driver = $this->driverClass;
        $params = $driver->getRedirectParams($model, $amount, $currency_code, $url);
        $view = 'pay-uz::merchant.index';
        if (!empty($driver::CUSTOM_FORM))
            $view = $driver::CUSTOM_FORM;
        echo view($view, compact('params'));
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function handle()
    {
        $this->validateDriver();
        try {
            return $this->driverClass->run();
        } catch (PaymentException $e) {
            return $e->response();
        }

        return $this;
    }

    /**
     * @param $model
     * @param $amount
     * @param $currency_code
     * @throws \Exception
     */
    public function validateModel($model, $amount, $currency_code)
    {
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
    public function validateDriver()
    {
        if (is_null($this->driverClass))
            throw new \Exception('Driver not selected');
    }
    public function setDescription($hasDescription)
    {
        $this->driverClass->setDescription($hasDescription);
        return $this;
    }
}