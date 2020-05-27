@extends('pay-uz::layouts.app')

@section('title')
    Payment systems
@stop

@section('style')
    <style>
        .wrapper {
            position: relative;
            height: 350px;
        }

        .wrapper>code {
            font-size: .9em;
            font-family: "Courier New", Courier, "Lucida Sans Typewriter", "Lucida Typewriter", monospace;
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
    </style>
@stop

@section('content')
    <div class="container-fluid pb-4">
        <!-- <div class="col-12 mb-4"> -->
        <div class="row mb-4">
            <div class="col-6">
                <span class="h5">Editors</span>
            </div>
        </div>
        <!-- </div> -->
        <div class="col-12 box-admin pt-3 pb-3">
            <ul class="nav nav-tabs" id="editorTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="listeners-tab" data-toggle="tab" href="#listeners" role="tab" aria-controls="listeners" aria-selected="true">Listeners</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="converters-tab" data-toggle="tab" href="#converters" role="tab" aria-controls="events" aria-selected="false">Converters</a>
                </li>
            </ul>
            <div class="tab-content" id="editorContent">
                <div class="tab-pane fade show active" id="listeners" role="tabpanel" aria-labelledby="listeners-tab">
                <hr>
                    @foreach($listeners as $key => $item)
                    <div class="card">
                        <div class="card-header" id="heading{{ $key }}" data-toggle="collapse" data-target="#collapse{{ $key }}" aria-expanded="false" aria-controls="collapse{{ $key }}">
                            <h5 class="mb-0">
                                <button class="btn btn-link">
                                    {{ $item['title'] }}
                                    <button data-event="{{ $key }}" class="btn btn-success save_listener_btn pull-right"><span class="fa fa-save"></span> Save</button>
                                </button>
                            </h5>
                        </div>
                        <div id="collapse{{ $key }}" class="collapse" aria-labelledby="heading{{ $key }}" data-parent="#accordion">
                            <div class="card-body">
                                <div class="wrapper">
                                    <code id="{{ $key }}">{{ $item['content'] }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    @endforeach
                </div>
                <div class="tab-pane fade" id="converters" role="tabpanel" aria-labelledby="converters-tab">
                <hr>
                    @foreach($converters as $key => $item)
                    <div class="card">
                        <div class="card-header" id="heading{{ $key }}" data-toggle="collapse" data-target="#collapse{{ $key }}" aria-expanded="false" aria-controls="collapse{{ $key }}">
                            <h5 class="mb-0">
                                <button class="btn btn-link">
                                    {{ $item['title'] }}
                                    <button data-event="{{ $key }}" class="btn btn-success save_listener_btn pull-right"><span class="fa fa-save"></span> Save</button>
                                </button>
                            </h5>
                        </div>
                        <div id="collapse{{ $key }}" class="collapse" aria-labelledby="heading{{ $key }}" data-parent="#accordion">
                            <div class="card-body">
                                <div class="wrapper">
                                    <code id="{{ $key }}">{{ $item['content'] }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    @endforeach
                </div>
            </div>
            
        </div>
    </div>

@stop

@section('script')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js" type="text/javascript"></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/ace/1.1.3/ace.js'></script>

    <script type="text/javascript">
        let theme='ace/theme/monokai';
        let mode='ace/mode/scss';
        var converters = {!! json_encode($converters) !!};
        var listeners = {!! json_encode($listeners) !!};
        var contents = Object.assign(converters, listeners);

        for (const key in contents) {
            if (contents.hasOwnProperty(key)) {
                const element = contents[key];
                let editor= ace.edit(key);
                editor.setTheme(theme);
                editor.getSession().setMode(mode);
            }
        }

        // Move to pay.js -------------
        $(".save_listener_btn").on('click',function (e) {
            e.preventDefault();
            let file_name = $(this).data('event');
            let value =  ace.edit(file_name).getValue();
            let params = {'content': value,'file_name' : file_name};
            installAjax('post','/payment/api/editable/update',params)
        });

        function installAjax(method,url,params) {
            $.ajax({
                url: url,
                type: method,
                success: function (data) {
                    if (data.responseStatus == 'success') {
                        pay_toastr.success(data.message);                        
                    }else{
                        pay_toastr.warning(data.message); 
                    }
                },
                error: function(data) {
                    pay_toastr.error(data.status + ' ' + data.statusText);
                },
                data: params,
            });
        }
        //-----------------------
    </script>
@stop
