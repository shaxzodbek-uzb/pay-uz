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
            <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
                <div class="row">
                    Events editor.
                </div>
            </div>
            @foreach($file_contents as $key => $item)
                <div class="col-12">
                    <span class="text-topics h6">{{ $item['title'] }}</span>
                    <div class="wrapper">
                        <code id="{{ $key }}">{{ $item['content'] }}</code>
                    </div>
                </div>
                <div class="col-12 text-right mt-1">
                    <button data-event="{{ $key }}" class="btn btn-success save_listener_btn"><span class="fa fa-save"></span> Save</button>
                </div>
                <br>
            @endforeach
        </div>
    </div>

@stop

@section('script')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js" type="text/javascript"></script>
    <script src='http://cdnjs.cloudflare.com/ajax/libs/ace/1.1.3/ace.js'></script>

    <script type="text/javascript">
        let theme='ace/theme/monokai';
        let mode='ace/mode/scss';
        var contents = {!! json_encode($file_contents) !!};

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
                    pay_toastr.success(data.message);
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
