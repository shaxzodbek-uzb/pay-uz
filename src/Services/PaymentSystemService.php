<?php
/**
 * Created by PhpStorm.
 * User: Azizbek Eshonaliyev
 * Date: 2/22/2019
 * Time: 8:31 PM
 */

namespace Goodoneuz\PayUz\Services;


use Illuminate\Support\Facades\DB;
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
                PaymentSystemParam::create([
                    'system'    => $payment_system->system,
                    'label'     => $param['label'],
                    'name'      => $param['name'],
                    'value'     => $param['value']    
                ]);
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
        {
            DB::table('payment_system_params')
            ->where('system',$request['system'])
            ->delete();

            self::storeParams($request['params'],$payment_system);
        }
        return $payment_system;
    }

    /**
     * @param $driver
     * @return array
     */
    public static function getPaymentSystemParamsCollect($driver)
    {
        $params = PaymentSystemParam::where('system',$driver)->get();

        if (count($params)>0)
        
            return $params->mapWithKeys(function ($item) {
                return [$item['name'] => $item['value']];
            });

        return [];
    }
}
