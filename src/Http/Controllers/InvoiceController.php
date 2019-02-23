<?php
/**
 * Created by PhpStorm.
 * User: Azizbek Eshonaliyev
 * Date: 2/20/2019
 * Time: 8:42 PM
 */

namespace Goodoneuz\PayUz\Http\Controllers;


use App\Http\Controllers\Controller;
use Goodoneuz\PayUz\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $invoices   = Invoice::latest()->get();
        return view('pay-uz::invoices.index',compact('invoices'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'invoiceable_type'         => 'required|max:255',
            'invoiceable_id'           => 'required',
            'amount'             => 'required',
            'currency_code'      => 'required'
        ];

        $messages = [
            'invoiceable_type.required'          => "Model type can not be exist!",
            'invoiceable_id.required'            => "Model id can not be exist!",
            'amount.required'              => "Amount can not be exist!",
            'currency_code.required'       => "Currency can not be exist!",
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->route('payment.invoices.index')
                ->withErrors($validator)
                ->withInput();
        }

        $model_type = 'App\\' . Str::studly(Str::singular($request['invoiceable_type']));

        if (!is_subclass_of($model_type, 'Illuminate\Database\Eloquent\Model')) {
                return redirect()->back()->with(['warning'  => "Model type not found"]);
        }

        $model = $model_type::where('id',$request['invoiceable_id'])->first();

        if (is_null($model)){
            return redirect()->back()->with(['warning'  => "For model id Model not found"]);
        }

        \PayUz::createInvoice($model,$request['amount'],$request['currency_code']);

        return redirect()->back()->with(['success'  => "To'lov tizmi muvaffaqiyatli saqlandi."]);
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
