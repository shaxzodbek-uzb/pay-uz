<?php
/**
 * Created by PhpStorm.
 * User: Azizbek Eshonaliyev
 * Date: 2/26/2019
 * Time: 1:07 PM
 */

namespace Goodoneuz\PayUz\Services;


use Goodoneuz\PayUz\Models\PaymentSystem;
use Illuminate\Support\Facades\View;

class PaymentSystemParamService
{
    /**
     * @param $driver
     * @return string
     */
    public static function render($driver){

        $payment_system = PaymentSystem::where('system',$driver)->first();

        if (is_null($payment_system))
            return '';

        $params = $payment_system->params;

        if (count($params) == 0)
            return '';

        return View::make('pay-uz::fields.input',compact('params'))->render();
    }

}
