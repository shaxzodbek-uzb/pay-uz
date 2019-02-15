<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <a class="navbar-brand" href="#">Pay-uz</a>
        </div>
        <ul class="nav navbar-nav">
            <li><a href="#">Dashboard</a></li>
        </ul>
    </div>
</nav>
<!-- /. NAV TOP  -->
<nav class="navbar-default navbar-side" role="navigation">
    <div class="sidebar-collapse">
        <ul class="nav" id="main-menu">
            <li>
                <a href="{{ route('payment.dashboard') }}" ><i class="fa fa-desktop"></i>   Dashboard </a>
            </li>
            <li>
                <a href="{{ route('payment.editors') }}" ><i class="fa fa-edit"></i>    Editors </a>
            </li>
            <li>
                <a href="{{ route('payment.blank') }}" ><i class="fa fa-circle"></i>    Blank </a>
            </li>
        </ul>
    </div>

</nav>
<!-- /. NAV SIDE  -->
