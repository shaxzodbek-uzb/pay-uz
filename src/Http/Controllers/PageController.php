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
        $listeners['after_pay']['content']     = file_get_contents(base_path('/app/Http/Controllers/Payments/after_pay.php'));
        $listeners['after_pay']['title']       = "After pay listener content.";
        
        $listeners['before_pay']['content']    = file_get_contents(base_path('/app/Http/Controllers/Payments/before_pay.php'));
        $listeners['before_pay']['title']      = "Before pay listener content.";
        
        $listeners['cancel_pay']['content']    = file_get_contents(base_path('/app/Http/Controllers/Payments/cancel_pay.php'));
        $listeners['cancel_pay']['title']      = "Cancel pay listener content.";
        
        $listeners['paying']['content']        = file_get_contents(base_path('/app/Http/Controllers/Payments/paying.php'));
        $listeners['paying']['title']          = "Cancel pay listenr content.";
        
        $converters['key_model']['content']     = file_get_contents(base_path('/app/Http/Controllers/Payments/key_model.php'));
        $converters['key_model']['title']       = "Key to Model function content";
        
        $converters['model_key']['content']     = file_get_contents(base_path('/app/Http/Controllers/Payments/model_key.php'));
        $converters['model_key']['title']       = "Model to Key function content";
        
        $converters['is_proper']['content']     = file_get_contents(base_path('/app/Http/Controllers/Payments/is_proper.php'));
        $converters['is_proper']['title']       = "Is proper function content";
        
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
