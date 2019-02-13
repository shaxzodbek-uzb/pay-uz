<?php

namespace Goodoneuz\PayUz\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Goodoneuz\PayUz\Http\Classes\Click\Click;
use Goodoneuz\PayUz\Http\Classes\Payme\Payme;
use Goodoneuz\PayUz\Models\Transaction;

class PaymentProxy extends Controller
{
    public function handle(Request $request, $payment, $type = null, $order_id = null, $state = null)
    {
        try{
            switch ($payment) {
                case Transaction::PAYME:
                    (new Payme)->run();
                    break;
                case Transaction::CLICK:
                    (new Click($request))->run();
                    break;
                default:
                    return response('Function not fount',200);
                    break;
            }
        }catch(PaymentException $e){
            $e->response();
        }

    }
    public function redirect(Request $request){
        switch ($request->type_payment){
            case Transaction::PAYME:
                $params = Payme::getRedirectParams($request);
                return view('payment.redirect_payme',compact('params'));
                break;
            case Transaction::CLICK:
                $params = Click::getRedirectParams($request);
                return view('payment.redirect_click',compact('params'));
                break;
            default:
                return redirect()->back();
        }

    }

}
