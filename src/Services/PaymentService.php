<?php
/**
 * Created by PhpStorm.
 * User: Shaxzodbek Qambaraliyev
 */
namespace Goodoneuz\PayUz\Services;


class PaymentService
{

    public static function convertModelToKey($model){
        return $model->id;
    }
    /*
    * $key - key of model
    * returns model or null
    *
    */    
    public static function convertKeyToModel($key){
        return App\User::find($key);
    }

    public static function isProperModelAndAmount($model, $amount){
        return true;
    }

    /*
    * $model - Payable model
    * $amount - amount for pay
    * $action_type - type of action: before-pay, paying, after-pay, cancelled
    */
    public function payListener($model, $amount, $action_type){
        switch($action_type){
            case 'before-pay': 
                Log::info('Before pay:' . $model->id . ' -> ' . $amount);
                break;    
            case 'paying': 
                Log::info('Paying:' . $model->id . ' -> ' . $amount);
                break;

                case 'after-pay': 
                Log::info('After pay:' . $model->id . ' -> ' . $amount);
                break;
                
            case 'after-pay': 
                Log::info('Cancelled:' . $model->id . ' -> ' . $amount);
                break;                
        }
    }
}

