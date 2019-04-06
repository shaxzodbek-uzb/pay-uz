<?php
/**
 * Created by PhpStorm.
 * User: Azizbek Eshonaliyev
 * Date: 2/15/2019
 * Time: 5:01 PM
 */

namespace Goodoneuz\PayUz\Http\Controllers;


use App\Http\Controllers\Controller;

class PageController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function dashboard(){
        return view('pay-uz::dashboard');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editors(){

        $converters = [];
        $listeners = [];
        
        $listeners['before_pay']['content']    = file_get_contents(base_path('/app/Http/Controllers/Payments/before_pay.php'));
        $listeners['before_pay']['title']      = 'Before pay: payListener($model, $transaction = null, $action_type = \'before-pay\')';
        
        $listeners['after_pay']['content']     = file_get_contents(base_path('/app/Http/Controllers/Payments/after_pay.php'));
        $listeners['after_pay']['title']       = 'After pay: payListener($model = null, $transaction, $action_type = \'after-pay\')';
        
        $listeners['paying']['content']        = file_get_contents(base_path('/app/Http/Controllers/Payments/paying.php'));
        $listeners['paying']['title']          = 'Paying: payListener($model, $transaction, $action_type = \'paying\')';
        
        $listeners['cancel_pay']['content']    = file_get_contents(base_path('/app/Http/Controllers/Payments/cancel_pay.php'));
        $listeners['cancel_pay']['title']      = 'Cancel pay: payListener($model = null, $transaction, $action_type = \'cancel-pay\')';
        
        $converters['key_model']['content']     = file_get_contents(base_path('/app/Http/Controllers/Payments/key_model.php'));
        $converters['key_model']['title']       = 'Key to Model: convertKeyToModel($key), returns Elequent model or null';
        
        $converters['model_key']['content']     = file_get_contents(base_path('/app/Http/Controllers/Payments/model_key.php'));
        $converters['model_key']['title']       = 'Model to Key: convertModelToKey($model), returns string';
        
        $converters['is_proper']['content']     = file_get_contents(base_path('/app/Http/Controllers/Payments/is_proper.php'));
        $converters['is_proper']['title']       = 'Is proper: isProperModelAndAmount($model, $amount), returns true or false';
        
        return view('pay-uz::editors',compact('listeners','converters'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function blank(){
        return view('pay-uz::blank');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function settings(){
        return view('pay-uz::settings');
    }
}
