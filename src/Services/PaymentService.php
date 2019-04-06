<?php
/**
 * Created by PhpStorm.
 * User: Shaxzodbek Qambaraliyev
 */
namespace Goodoneuz\PayUz\Services;

use App\User;
use Illuminate\Support\Facades\Log;


class PaymentService
{
    /*
    *   return string
    */
    public static function convertModelToKey($model){
        return require base_path('/app/Http/Controllers/Payments/model_key.php');
    }
    /*
    * $key - key of model
    * returns model or null
    *
    */    
    public static function convertKeyToModel($key){
        return require base_path('/app/Http/Controllers/Payments/key_model.php');
    }
    /*
    * returns true/false 
    */
    public static function isProperModelAndAmount($model, $amount){
        return require base_path('/app/Http/Controllers/Payments/is_proper.php');
    }

    /*
    * $model - Payable model
    * $amount - amount for pay
    * $action_type - type of action: before-pay, paying, after-pay, cancelled
    */
    public static function payListener($model, $transaction, $action_type){
        switch($action_type){
            case 'before-pay':
                require base_path('/app/Http/Controllers/Payments/before_pay.php');
                break;

            case 'paying': 
                require base_path('/app/Http/Controllers/Payments/paying.php');
            break;

            case 'after-pay': 
                require base_path('/app/Http/Controllers/Payments/after_pay.php');
                break;
                
            case 'cancel-pay': 
                require base_path('/app/Http/Controllers/Payments/cancel_pay.php');
                break;                
        }
    }
}

