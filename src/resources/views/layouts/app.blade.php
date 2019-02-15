<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="authors" content="Shaxzodbek,Azizbek">
    <title>Pay-uz System | @yield('title')</title>
    <!-- BOOTSTRAP STYLES-->
    <link href="{{ config('pay-uz.pay_assets_path') }}/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="{{ config('pay-uz.pay_assets_path') }}/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="{{ config('pay-uz.pay_assets_path') }}/css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    @yield('style')
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body>
<div id="wrapper">

    @include('pay-uz::components.header')

    @yield('content')
</div>
    @include('pay-uz::components.footer')
<!-- /. WRAPPER  -->
<!-- SCRIPTS -AT THE BOTOM TO REDUCE THE LOAD TIME-->
<!-- JQUERY SCRIPTS -->
<script src="{{ config('pay-uz.pay_assets_path') }}/js/jquery-1.10.2.js"></script>
<!-- BOOTSTRAP SCRIPTS -->
<script src="{{ config('pay-uz.pay_assets_path') }}/js/bootstrap.min.js"></script>
<!-- CUSTOM SCRIPTS -->
<script src="{{ config('pay-uz.pay_assets_path') }}/js/custom.js"></script>
@yield('script')
</body>
</html>
