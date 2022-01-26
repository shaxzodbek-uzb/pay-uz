<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="authors" content="Shaxzodbek,Azizbek">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Pay-uz | @yield('title')</title>
    <!-- Bootstrap css -->
    <link rel="stylesheet" href="{{ config('pay-uz.pay_assets_path') }}/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ config('pay-uz.pay_assets_path') }}/css/style.css">
    <!-- Qo`shimcha css -->
    <link rel="stylesheet" href="{{ config('pay-uz.pay_assets_path') }}/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ config('pay-uz.pay_assets_path') }}/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="{{ config('pay-uz.pay_assets_path') }}/css/sb-admin.css">
    @yield('style')
    <link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>

<body style="background-color: #eeeeee;">
    <!-- For Pay toastr -->
    <div id="pay-toastr-box" style="position:fixed; top:10; right:0;z-index:100000"></div>
    <!-- End -->
    <!-- TopPanel -->
    <!-- End TopPanel -->

    @include('pay-uz::components.main_nav')
    <!-- Content -->
    <!-- Sugurtalar -->
    <div class="content-wrapper" style="padding-top: 80px;">
        <div class="container-fluid">
            @include('pay-uz::components.alerts')
        </div>
        @yield('content')
    </div>
    <!-- End Content -->

    <!-- Sozlovchi JavaScript -->
    <!-- jQuery birinchi, keyin Popper js, keyin Bootstrap js -->
    <script src="{{ config('pay-uz.pay_assets_path') }}/js/jquery-3.2.1.min.js"></script>
    <script src="{{ config('pay-uz.pay_assets_path') }}/js/popper.min.js"></script>
    <script src="{{ config('pay-uz.pay_assets_path') }}/js/bootstrap.min.js"></script>
    <!-- Qo`shimcha js -->
    <script src="{{ config('pay-uz.pay_assets_path') }}/js/Chart.min.js"></script>
    <script src="{{ config('pay-uz.pay_assets_path') }}/js/jquery.dataTables.js"></script>
    <script src="{{ config('pay-uz.pay_assets_path') }}/js/dataTables.bootstrap4.js"></script>
    <script src="{{ config('pay-uz.pay_assets_path') }}/js/jquery.easing.min.js"></script>
    <script src="{{ config('pay-uz.pay_assets_path') }}/js/sb-admin.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });

        $(function() {
            $('[data-toggle="tooltip"]').tooltip()
        });
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    <<script>
        let pay_toastr = {
        alert_box:$("#pay-toastr-box"),

        success(message,options = []){
        this.show("Successfully",message,'alert-success','fa fa-check');
        },

        error(message,options = []){
        this.show("Error",message,'alert-danger','fa fa-bug');
        },

        warning(message,options = []){
        this.show("Warning",message,'alert-warning','fa fa-warning');
        },
        show:function(title,message,class_name,icon,options = []) {
        if (this.alert_box)
        this.alert_box.append(
        `
        <div class="alert ${class_name} alert-dismissible pay-auto-close-alert fade show" role="alert">
            <strong> <span class="${icon}"></span> ${title} </strong> ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        `
        )
        }
        }
        </script>
        @yield('script')
</body>