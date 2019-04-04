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

        $file_contents = [];
        $file_contents['after_pay']['content']     = file_get_contents(base_path('/app/Http/Controllers/Payments/after_pay.php'));
        $file_contents['after_pay']['title']       = "After pay listener content.";
        
        $file_contents['before_pay']['content']    = file_get_contents(base_path('/app/Http/Controllers/Payments/before_pay.php'));
        $file_contents['before_pay']['title']      = "Before pay listener content.";
        
        $file_contents['cancel_pay']['content']    = file_get_contents(base_path('/app/Http/Controllers/Payments/cancel_pay.php'));
        $file_contents['cancel_pay']['title']      = "Cancel pay listener content.";
        
        $file_contents['paying']['content']        = file_get_contents(base_path('/app/Http/Controllers/Payments/paying.php'));
        $file_contents['paying']['title']          = "Cancel pay listenr content.";

        $file_contents['is_proper']['content']     = file_get_contents(base_path('/app/Http/Controllers/Payments/is_proper.php'));
        $file_contents['is_proper']['title']       = "Is proper function content";
        
        $file_contents['key_model']['content']     = file_get_contents(base_path('/app/Http/Controllers/Payments/key_model.php'));
        $file_contents['key_model']['title']       = "Key to Model function content";
        
        $file_contents['model_key']['content']     = file_get_contents(base_path('/app/Http/Controllers/Payments/model_key.php'));
        $file_contents['model_key']['title']       = "Model to Key function content";
        
        return view('pay-uz::editors',compact('file_contents'));
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
