<?php
/**
 * Created by PhpStorm.
 * User: Azizbek Eshonaliyev
 * Date: 2/20/2019
 * Time: 8:41 PM
 */

namespace Goodoneuz\PayUz\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Goodoneuz\PayUz\Models\PaymentSystem;
use Goodoneuz\PayUz\Models\PaymentSystemParam;
use Goodoneuz\PayUz\Services\PaymentSystemService;

class PaymentSystemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $payment_systems = PaymentSystem::latest()->get();
        return view('pay-uz::payment_systems.index',compact('payment_systems'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('pay-uz::payment_systems.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'system'         => 'required|unique:payment_systems|max:255',
        ];

        $messages = [
            'system.required'          => "Payment system cannot be exist!",
            'system.unique'          => "The payment system has already been taken!",
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->route('payment.payment_systems.create')
                ->withErrors($validator)
                ->withInput();
        }

        PaymentSystemService::createPaymentSystem($request);

        return redirect()->route('payment.payment_systems.index')->with(['success'  => "Payment system successfully saved."]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * @param PaymentSystem $payment_system
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(PaymentSystem $payment_system)
    {
        return view('pay-uz::payment_systems.edit',compact('payment_system'));
    }


    /**
     * @param Request $request
     * @param PaymentSystem $payment_system
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, PaymentSystem $payment_system)
    {
        $rules = [
            'system'         => 'required|max:255|unique:payment_systems'. ',system,' . $payment_system->id,
        ];

        $messages = [
            'system.required'          => "Payment system cannot be exist!",
            'system.unique'          => "The payment system has already been taken!",
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->route('payment.payment_systems.edit',['payment_system' => $payment_system->id])
                ->withErrors($validator)
                ->withInput();
        }

        PaymentSystemService::updatePaymentSystem($request,$payment_system);

        return redirect()->route('payment.payment_systems.edit',['payment_system' => $payment_system->id])->with(['success'  => "Payment system successfully saved."]);
    }

    /**
     * @param PaymentSystem $payment_system
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(PaymentSystem $payment_system)
    {
        $payment_system->delete();
        return redirect()->back()->with(['success'  => "To'lov tizimi o'chirildi"]);
    }

    public function editStatus(PaymentSystem $paymentSystem){
        $paymentSystem->status = ($paymentSystem->status == PaymentSystem::NOT_ACTIVE) ? PaymentSystem::ACTIVE : PaymentSystem::NOT_ACTIVE;
        $paymentSystem->update();
        return  redirect()->back()->with(['success' => "Status o'xgartirildi"]);
    }

    public function deleteParam($param_id){
        $param = PaymentSystemParam::find($param_id);
        if ($param){
            $param->delete();
            return redirect()->back()->with(['success'  => 'Param deleted!']);
        }
        return redirect()->back()->with(['warning'  => 'Param not found!']);
    }
}
