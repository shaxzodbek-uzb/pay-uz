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
        require __DIR__ . './editable/logics/key_model.php';
    }
    /*
    * $key - key of model
    * returns model or null
    *
    */    
    public static function convertKeyToModel($key){
        require __DIR__ . './editable/logics/model_key.php';
    }

    public static function isProperModelAndAmount($model, $amount){
        require __DIR__ . './editable/logics/is_proper.php';
    }

    /*
    * $model - Payable model
    * $amount - amount for pay
    * $action_type - type of action: before-pay, paying, after-pay, cancelled
    */
    public static function payListener($model, $transaction, $action_type){
        switch($action_type){
            case 'before-pay': 
                require __DIR__ . './editable/listeners/before_pay.php';
                break;    

            case 'paying': 
                require __DIR__ . './editable/listeners/paying.php';
            break;

            case 'after-pay': 
                require __DIR__ . './editable/listeners/after_pay.php';
                break;
                
            case 'cancel-pay': 
                require __DIR__ . './editable/listeners/cancel_pay.php';
                break;                
        }
    }
}

