@extends('pay-uz::layouts.app')

@section('title')
    Invoices
@stop

@section('style')
    <style>
    </style>
@stop

@section('content')
    <div class="container-fluid pb-4">
        <!-- <div class="col-12 mb-4"> -->
        <div class="row mb-4">
            <div class="col-6">
                <span class="h5">Invoices</span>
            </div>
            <div class="col-6 text-right">
                <a href="#addInvoice"  data-toggle="modal" class="btn btn-sm btn-primary" role="button"><span class="fa fa-plus"></span> Add new</a>
            </div>
            <div class="modal fade" id="addInvoice" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form action="{{ route('payment.invoices.store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-header">
                                <h6 class="modal-title" id="exampleModalLabel">Create invoice</h6>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="recipient-model_type" class="col-form-label">
                                                <span class="text-danger">* </span>Model type:
                                            </label>
                                            <input name="model_type" type="text" class="form-control @if ($errors->has('model_type')) is-invalid @endif" id="recipient-model_type" placeholder="App\Order">
                                            @if ($errors->has('model_type'))
                                                <div class="invalid-feedback">{{ $errors->first('model_type') }}</div>
                                            @endif
                                        </div>
                                        <div class="form-group">
                                            <label for="recipient-model_id" class="col-form-label">
                                                <span class="text-danger">* </span>Model id:
                                            </label>
                                            <input name="model_id" type="text" class="form-control @if ($errors->has('model_id')) is-invalid @endif" id="recipient-model_id" placeholder="123">
                                            @if ($errors->has('model_id'))
                                                <div class="invalid-feedback">{{ $errors->first('model_id') }}</div>
                                            @endif
                                        </div>
                                        <div class="form-group">
                                            <label for="recipient-amount" class="col-form-label">
                                                <span class="text-danger">* </span>Amount:
                                            </label>
                                            <input name="amount" type="password" class="form-control @if ($errors->has('amount')) is-invalid @endif" id="recipient-amount" placeholder="120000">
                                            @if ($errors->has('amount'))
                                                <div class="invalid-feedback">{{ $errors->first('amount') }}</div>
                                            @endif
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleFormControlSelect1">
                                                <span class="text-danger">* </span>Currency:
                                            </label>
                                            <select class="form-control @if ($errors->has('currency_code')) is-invalid @endif" id="exampleFormControlSelect1" name="currency_code">
                                                <option value="{{ \Goodoneuz\PayUz\Models\Transaction::CURRENCY_CODE_UZS }}">UZS</option>
                                                <option value="{{ \Goodoneuz\PayUz\Models\Transaction::CURRENCY_CODE_USD }}">USD</option>
                                                <option value="{{ \Goodoneuz\PayUz\Models\Transaction::CURRENCY_CODE_EUR }}">EUR</option>
                                                <option value="{{ \Goodoneuz\PayUz\Models\Transaction::CURRENCY_CODE_RUB }}">RUB</option>
                                            </select>
                                            @if ($errors->has('currency_code'))
                                                <div class="invalid-feedback">{{ $errors->first('currency_code') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <a href="#" role="button" class="btn btn-sm btn-secondary btn-circle" data-dismiss="modal">Chiqish</a>
                                <button type="submit" role="button" class="btn btn-sm btn-primary btn-circle">
                                    <i class="fa fa-plus"></i> Qo'shish
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- </div> -->
        <div class="col-12 box-admin pt-3 pb-3">
            <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
                <div class="row">
                    <span class="text-topics h6">Invoices</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" id="dataTable" cellspacing="0">
                    <thead class="thead-default">
                    <tr>
                        <th>Id</th>
                        <th>Model type</th>
                        <th>Model id</th>
                        <th>Amount</th>
                        <th>State</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tfoot class="thead-default">
                        <tr>
                            <th>Id</th>
                            <th>Model type</th>
                            <th>Model id</th>
                            <th>Amount</th>
                            <th>State</th>
                            <th></th>
                        </tr>
                    </tfoot>
                    <tbody>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td>#{{ $invoice->id }}</td>
                            <td>{{ $invoice->model_type }}</td>
                            <td>{{ $invoice->model_id }}</td>
                            <td>{{ $invoice->amount }}</td>
                            <td>{{ $invoice->state }}</td>
                            <td class="text-center">
                                <a href="{{ route('payment.invoices.edit',['id'  => $invoice->id]) }}"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                                <a href="{{ route('payment.invoices.edit_status',['id'  => $invoice->id]) }}"><span class="fa @if($invoice->status == \Goodoneuz\PayUz\Models\PaymentSystem::NOT_ACTIVE) fa-lock @else fa-unlock-alt @endif " data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@stop

@section('script')
    <script type="text/javascript">
    </script>
@stop
