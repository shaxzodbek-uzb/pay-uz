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
                    <span class="text-topics h6">Editros</span>
                </div>
            </div>
            <div class="col-12">
                <div class="row">
                    <h4>
                        Start pay
                    </h4>
                </div>
                <div class="wrapper">
                    <code id="ace-start">
                        public function start(){
                            dd("Hello");
                        }
                    </code>
                </div>
            </div>
            <br>
            <div class="col-12">
                <div class="row">
                    <h4>
                        Doing pay
                    </h4>
                </div>
                <div class="wrapper">
                    <code id="ace-doing">
                        public function start(){
                            dd("Hello");
                        }
                    </code>
                </div>
            </div>
            <br>
            <div class="col-12">
                <div class="row">
                    <h4>
                        End of pay
                    </h4>
                </div>
                <div class="wrapper">
                    <code id="ace-end">
                        public function start(){
                            dd("Hello");
                        }
                    </code>
                </div>
            </div>
        </div>
    </div>

@stop

@section('script')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js" type="text/javascript"></script>
    <script src='http://cdnjs.cloudflare.com/ajax/libs/ace/1.1.3/ace.js'></script>

    <script type="text/javascript">
        let theme='ace/theme/monokai';
        let mode='ace/mode/scss';
        let editor_start    = ace.edit('ace-start');
        let editor_doing    = ace.edit('ace-doing');
        let editor_end      = ace.edit('ace-end');
        editor_start.setTheme(theme);
        editor_start.getSession().setMode(mode);
        editor_doing.setTheme(theme);
        editor_doing.getSession().setMode(mode);
        editor_end.setTheme(theme);
        editor_end.getSession().setMode(mode);
    </script>
@stop
