@extends('pay-uz::layouts.app')

@section('title')
    Payment systems
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
                <span class="h5">Payment systems</span>
            </div>
            <div class="col-6 text-right">
                <a href="{{ route('payment.payment_systems.create') }}" class="btn btn-sm btn-primary" role="button"><span class="fa fa-plus"></span> Add new</a>
            </div>
        </div>
        <!-- </div> -->
        <div class="col-12 box-admin pt-3 pb-3">
            <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
                <div class="row">
                    <span class="text-topics h6">Payment systems</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" id="dataTable" cellspacing="0">
                    <thead class="thead-default">
                    <tr>
                        <th>Name</th>
                        <th>System</th>
                        <th>Created at</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tfoot class="thead-default">
                    <tr>
                        <th>Name</th>
                        <th>System</th>
                        <th>Created at</th>
                        <th></th>
                    </tr>
                    </tfoot>
                    <tbody>
                    @foreach($payment_systems as $payment_system)
                        <tr class="@if($payment_system->status == \Goodoneuz\PayUz\Models\PaymentSystem::NOT_ACTIVE) table-danger @endif">
                            <td>{{ $payment_system->name }}</td>
                            <td>{{ $payment_system->system }}</td>
                            <td>{{ $payment_system->created_at }}</td>
                            <td class="text-center">
                                <a href="#" data-system_id="{{ $payment_system->id }}" class="deleteBtn"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                                <a href="{{ route('payment.payment_systems.edit',['payment_system'  => $payment_system->id]) }}"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                                <a href="{{ route('payment.payment_systems.edit_status',['payment_system'  => $payment_system->id]) }}"><span class="fa @if($payment_system->status == \Goodoneuz\PayUz\Models\PaymentSystem::NOT_ACTIVE) fa-lock @else fa-unlock-alt @endif " data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete modal -->
    <div class="modal fade" id="modalDeletePaymentSystem" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="deleteSystemForm" method="post" action="">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to remove it?
                        <input id="deleteSystemId" type="hidden" name="payment_system_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('script')
    <script type="text/javascript">
        $('.deleteBtn').on('click', function () {
            $("#deleteSystemForm").attr('action','/payment/payment_systems/'+$(this).data('system_id'));
            $("#modalDeletePaymentSystem").modal('show');
        });
    </script>
@stop
