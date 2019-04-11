<?php

namespace Goodoneuz\PayUz\Http\Controllers;
   

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Session;

use Stripe;


   

class StripePaymentController extends Controller

{

    /**

     * success response method.

     *

     * @return \Illuminate\Http\Response

     */

    public function stripe()

    {

        return view('stripe');

    }

  

    /**

     * success response method.

     *

     * @return \Illuminate\Http\Response

     */

    public function stripePost(Request $request)

    {

        Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        $charge = Stripe\Charge::create ([

                "amount" => 1000 * 100,

                "currency" => "RUB",

                "source" => $request->stripeToken,

                "description" => "Test payment from itsolutionstuff.com." 

        ]);
            dd($charge);
  

        Session::flash('success', 'Payment successful!');

          

        return back();

    }

}