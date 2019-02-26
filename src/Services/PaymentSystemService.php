<?php
/**
 * Created by PhpStorm.
 * User: Azizbek Eshonaliyev
 * Date: 2/22/2019
 * Time: 8:31 PM
 */

namespace Goodoneuz\PayUz\Services;


use Goodoneuz\PayUz\Models\PaymentSystem;
use Goodoneuz\PayUz\Models\PaymentSystemParam;

class PaymentSystemService
{
    /**
     * @param $request
     * @return mixed
     */
    public static function createPaymentSystem($request)
    {
        $payment_system = PaymentSystem::create($request->all());

        if (isset($request['params']) && is_array($request['params']))

            self::storeParams($request['params'],$payment_system);

        return $payment_system;
    }

    /**
     * @param array $params
     * @param $payment_system
     */
    public static function storeParams(array $params, $payment_system)
    {
        if (is_array($params) && count($params)>0)
            foreach ($params as $param)
            {
                $attr = PaymentSystemParam::where('system',$payment_system->system)->where('name',$param['name'])->first();
                if (is_null($attr))
                {
                    $attr = new PaymentSystemParam();
                }
                $attr['system'] = $payment_system->system;
                $attr['label']  = $param['label'];
                $attr['name']  = $param['name'];
                $attr['value']  = $param['value'];
                $attr->save();
                $attr=null;
            }
    }

    public static function updatePaymentSystem(\Illuminate\Http\Request $request,$payment_system)
    {
        $payment_system->update([
            'name'      => $request['name'],
            'system'    => $request['system'],
            'status'    => $request['status']
        ]);

        if (isset($request['params']) && is_array($request['params']))

            self::storeParams($request['params'],$payment_system);

        return $payment_system;
    }
}
