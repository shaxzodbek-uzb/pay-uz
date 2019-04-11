<?php

namespace Goodoneuz\PayUz\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Session;
use Stripe;
use App;
use Goodoneuz\PayUz\Http\Classes\DataFormat;
use Goodoneuz\PayUz\Models\PaymentSystem;
use Goodoneuz\PayUz\Models\PaymentSystemParam;
use Goodoneuz\PayUz\Models\Transaction;
use Goodoneuz\PayUz\Services\PaymentSystemService;
use Goodoneuz\PayUz\Services\PaymentService;
use Goodoneuz\PayUz\Http\Classes\PaymentException;
  
class StripePaymentController extends Controller{

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function form($key, $amount){
        $url = Input::get('callback_url');
        if (isempty($url))
            $url = url('/');
        $config = PaymentSystemService::getPaymentSystemParamsCollect(PaymentSystem::STRIPE);
        return view('pay-uz::stripe',compact('config','url','amount', 'key'));
    }

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        Stripe\Stripe::setApiKey($config['secret_key']);
        $charge = Stripe\Charge::create ([
                "amount" => $request->amount,
                "currency" => "USD",
                "source" => $request->stripeToken,
                "description" => "Pay for service" 
        ]);   
        Session::flash('success', 'Payment successful!');
        //todo: create transaction for stripe

        header("Location: ". $request->url);
        die();
        return back();
    }

}