@if(isset($params))
    @foreach($params as $param)
        <div class="row">
            <div class="col-3">
                <div class="form-group">
                    <label for="recipient-{{ $param->label }}" class="col-form-label">Label name:</label>
                    <input name="params[old-{{$param->id}}][label]" type="text" class="form-control" id="recipient-{{ $param->label }}"  value="{{ $param->label }}">
                </div>
            </div>
            <div class="col-3">
                <div class="form-group">
                    <label for="recipient-{{ $param->name }}" class="col-form-label">Key:</label>
                    <input name="params[old-{{$param->id}}][name]" type="text" class="form-control" id="recipient-{{ $param->name }}"  value="{{ $param->name }}">
                </div>
            </div>
            <div class="col-3">
                <div class="form-group">
                    <label for="recipient-{{ $param->value }}" class="col-form-label">Value:</label>
                    <input name="params[old-{{$param->id}}][value]" type="text" class="form-control" id="recipient-{{ $param->value }}"  value="{{ $param->value }}">
                </div>
            </div>
            <div class="col-3">
                <div class="form-group">
                    <a href="{{ route('payment.payment_systems.delete_param',['param_id' => $param->id]) }}" class="btn btn-outline-danger remove_payment_system_btn_sb delete_param_btn_sb text-danger"> <span class="fa fa-trash"></span> Delete</a>
                </div>
            </div>
        </div>
    @endforeach
@endif
