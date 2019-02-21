@extends('pay-uz::layouts.app')

@section('title')
    To'lov tizimi yaratish.
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
                        <label for="recipient-login" class="col-form-label">Login:</label>
                        <input name="login" type="text" class="form-control" id="recipient-login"  value="{{ old('login') }}">
                    </div>
                    <div class="form-group">
                        <label for="recipient-password" class="col-form-label">Password:</label>
                        <input name="password" type="text" class="form-control" id="recipient-password" value="{{ old('password') }}">
                    </div>
                    <div class="form-group">
                        <label for="recipient-system" class="col-form-label">System (<span class="text-danger">*</span>):</label>
                        <input name="system" type="text" class="form-control @if ($errors->has('system')) is-invalid @endif" id="recipient-system" placeholder="payme"  value="{{ old('system') }}">
                        @if ($errors->has('system'))
                            <div class="invalid-feedback">{{ $errors->first('system') }}</div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="recipient-merchant_id" class="col-form-label">Merchant ID:</label>
                        <input name="merchant_id" type="text" class="form-control" id="recipient-merchant_id"  value="{{ old('merchant_id') }}">
                    </div>
                    <div class="form-group">
                        <label for="recipient-service_id" class="col-form-label">Service ID:</label>
                        <input name="service_id" type="text" class="form-control" id="recipient-service_id"  value="{{ old('service_id') }}">
                    </div>
                    <div class="form-group">
                        <label for="recipient-secret_key" class="col-form-label">Secret Key:</label>
                        <input name="secret_key" type="text" class="form-control" id="recipient-secret_key"  value="{{ old('secret_key') }}">
                    </div>
                    <div class="form-group">
                        <label for="recipient-merchant_user_id" class="col-form-label">Merchant User ID:</label>
                        <input name="merchant_user_id" type="text" class="form-control" id="recipient-merchant_user_id"  value="{{ old('merchant_user_id') }}">
                    </div>
                    <div class="form-group">
                        <label for="recipient-end_point_url" class="col-form-label">endPointUrl:</label>
                        <input name="end_point_url" type="text" class="form-control" id="recipient-end_point_url"  value="{{ old('end_point_url') }}">
                    </div>
                    <div class="form-group">
                        <label for="exampleFormControlSelect1">Status:</label>
                        <select class="form-control" id="exampleFormControlSelect1" name="status">
                            <option>{{ \Goodoneuz\PayUz\Models\PaymentSystem::ACTIVE }}</option>
                            <option @if(old('status') == \Goodoneuz\PayUz\Models\PaymentSystem::NOT_ACTIVE) selected @endif>{{ \Goodoneuz\PayUz\Models\PaymentSystem::NOT_ACTIVE }}</option>
                        </select>
                    </div>
                    <div class="col-12 text-right">
                        <a href="{{ route('payment.payment_systems.index') }}" role="button" class="btn btn-sm btn-secondary btn-circle" data-dismiss="modal">Chiqish</a>
                        <button role="button" class="btn btn-sm btn-primary btn-circle" type="submit">
                            <i class="fa fa-plus"></i> Qo'shish
                        </button>
                    </div>
                </form>
        </div>
    </div>
@stop

@section('script')

@stop
