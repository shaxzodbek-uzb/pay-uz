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
                <span class="h5">Transactions</span>
            </div>
            <div class="col-6 text-right">
                <a href="{{ route('payment.transactions.create') }}" class="btn btn-sm btn-primary" role="button"><span class="fa fa-plus"></span> Add new</a>
            </div>
        </div>
        <!-- </div> -->
        <div class="col-12 box-admin pt-3 pb-3">
            <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
                <div class="row">
                    <span class="text-topics h6">Transactions</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" id="dataTable" cellspacing="0">
                    <thead class="thead-default">
                    <tr>
                        <th>Transaction code</th>
                        <th>System transaction id</th>
                        <th>Payment system</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Condition</th>
                        <th>Created at</th>
                    </tr>
                    </thead>
                    <tfoot class="thead-default">
                    <tr>
                        <th>Transaction code</th>
                        <th>System transaction id</th>
                        <th>Payment system</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Condition</th>
                        <th>Created at</th>
                    </tr>
                    </tfoot>
                    <tbody>
                    @foreach($transactions as $transaction)
                        <tr>
                            <td>#{{ $transaction->id }}</td>
                            <td>{{ $transaction->system_transaction_id }}</td>
                            <td>{{ $transaction->payment_system }}</td>
                            <td>{{ $transaction->amount }}</td>
                            <td>{{ $transaction->currency_code }} </td>
                            <td>{{ $transaction->state }} </td>
                            <td>{{ $transaction->created_at }} </td>
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
