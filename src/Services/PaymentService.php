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

    public static function convertModelToKey($model){
        require base_path('/app/Http/Controllers/Payments/logics/model_key.php');
    }
    /*
    * $key - key of model
    * returns model or null
    *
    */    
    public static function convertKeyToModel($key){
        require base_path('/app/Http/Controllers/Payments/logics/key_model.php');
    }

    public static function isProperModelAndAmount($model, $amount){
        require base_path('/app/Http/Controllers/Payments/logics/is_proper.php');
    }

    /*
    * $model - Payable model
    * $amount - amount for pay
    * $action_type - type of action: before-pay, paying, after-pay, cancelled
    */
    public static function payListener($model, $transaction, $action_type){
        switch($action_type){
            case 'before-pay': 
                require base_path('/app/Http/Controllers/Payments/listeners/before_pay.php');
                break;    

            case 'paying': 
                require base_path('/app/Http/Controllers/Payments/listeners/paying.php');
            break;

            case 'after-pay': 
                require base_path('/app/Http/Controllers/Payments/listeners/after_pay.php');
                break;
                
            case 'cancel-pay': 
                require base_path('/app/Http/Controllers/Payments/listeners/cancel_pay.php');
                break;                
        }
    }
}

