@extends('pay-uz::layouts.app')

@section('title')
    {{ $payment_system->name }}
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
                <span class="h5">To'lov tizimini o'zgartirish</span>
            </div>
        </div>
        <!-- </div> -->
        <div class="col-12 box-admin pt-3 pb-3 p-4">
            <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
                <div class="row">
                    <span class="text-topics h6">{{ $payment_system->system }}</span>
                </div>
            </div>
            <form action="{{ route('payment.payment_systems.update',['payment_system'   => $payment_system->id]) }}" method="post" enctype="multipart/form-data">
                @csrf
                @method('put')
                <div class="form-group">
                    <label for="recipient-name" class="col-form-label">Name:</label>
                    <input name="name" type="text" class="form-control has-error" id="recipient-name" value="{{ $payment_system->name }}">
                </div>
                <div class="form-group">
                    <label for="recipient-system" class="col-form-label">System (<span class="text-danger">*</span>):</label>
                    <input name="system" type="text" class="form-control @if ($errors->has('system')) is-invalid @endif" id="recipient-system" placeholder="payme"  value="{{ $payment_system->system }}">
                    @if ($errors->has('system'))
                        <div class="invalid-feedback">{{ $errors->first('system') }}</div>
                    @endif
                </div>
                <div class="form-group">
                    <label for="exampleFormControlSelect1">Status:</label>
                    <select class="form-control" id="exampleFormControlSelect1" name="status">
                        <option>{{ \Goodoneuz\PayUz\Models\PaymentSystem::ACTIVE }}</option>
                        <option @if($payment_system->status == \Goodoneuz\PayUz\Models\PaymentSystem::NOT_ACTIVE) selected @endif>{{ \Goodoneuz\PayUz\Models\PaymentSystem::NOT_ACTIVE }}</option>
                    </select>
                </div>
                <div class="col-12" id="fieldsList">
                    {!! \Goodoneuz\PayUz\Services\PaymentSystemParamService::render($payment_system->system) !!}
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" id="addPaymentSystemParamBtn">
                        <span class="fa fa-plus-circle"></span> Add param
                    </button>
                </div>
                <div class="col-12 text-right">
                    <a href="{{ route('payment.payment_systems.index') }}" role="button" class="btn btn-sm btn-secondary btn-circle" data-dismiss="modal">Chiqish</a>
                    <button role="button" class="btn btn-sm btn-primary btn-circle" type="submit">
                        <i class="fa fa-save"></i> Saqlash
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('script')
    <script src="{{ config('pay-uz.pay_assets_path') }}/js/pay.js"></script>
@stop
