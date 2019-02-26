@extends('pay-uz::layouts.app')

@section('title')
    To'lov tizimi yaratish.
@stop

@section('style')
    <link rel="stylesheet" href="{{ config('pay-uz.pay_assets_path') }}/css/pay.css">
    <style>

    </style>
@stop

@section('content')
    <div class="container-fluid pb-4">
        <!-- <div class="col-12 mb-4"> -->
        <div class="row mb-4">
            <div class="col-6">
                <span class="h5">Yangi to'lov tizimi qo'shish</span>
            </div>
        </div>
        <!-- </div> -->
        <div class="col-12 box-admin pt-3 pb-3 p-4">
            <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
                <div class="row">
                    <span class="text-topics h6">Yangi to'lov tizimi qo'shish</span>
                </div>
            </div>
                <form action="{{ route('payment.payment_systems.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="recipient-name" class="col-form-label">Name:</label>
                        <input name="name" type="text" class="form-control has-error" id="recipient-name" value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label for="recipient-system" class="col-form-label">Payment system (<span class="text-danger">*</span>):</label>
                        <input name="system" type="text" class="form-control @if ($errors->has('system')) is-invalid @endif" id="recipient-system" placeholder="payme"  value="{{ old('system') }}">
                        @if ($errors->has('system'))
                            <div class="invalid-feedback">{{ $errors->first('system') }}</div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="exampleFormControlSelect1">Status:</label>
                        <select class="form-control" id="exampleFormControlSelect1" name="status">
                            <option>{{ \Goodoneuz\PayUz\Models\PaymentSystem::ACTIVE }}</option>
                            <option @if(old('status') == \Goodoneuz\PayUz\Models\PaymentSystem::NOT_ACTIVE) selected @endif>{{ \Goodoneuz\PayUz\Models\PaymentSystem::NOT_ACTIVE }}</option>
                        </select>
                    </div>
                    <div class="col-12" id="fieldsList">

                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" id="addPaymentSystemParamBtn">
                            <span class="fa fa-plus-circle"></span> Add param
                        </button>
                    </div>
                    <div class="col-12 text-right">
                        <a href="{{ route('payment.payment_systems.index') }}" role="button" class="btn btn-sm btn-secondary btn-circle" data-dismiss="modal"> <span class="fa fa-close"></span> Exit</a>
                        <button role="button" class="btn btn-sm btn-primary btn-circle" type="submit">
                            <i class="fa fa-save"></i> Save
                        </button>
                    </div>
                </form>
        </div>
    </div>
@stop

@section('script')
    <script src="{{ config('pay-uz.pay_assets_path') }}/js/pay.js"></script>
@stop
