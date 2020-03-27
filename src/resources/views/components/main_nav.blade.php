@php($current_route=Illuminate\Support\Facades\Route::currentRouteName())
<div class="navbar navbar-expand-md navbar-dark bg-dark fixed-top" id="mainNav">
    <a class="navbar-brand" href="/admin">Pay-uz</a>
    <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav navbar-sidenav" id="exampleAccordion">
            <li class="nav-item @if($current_route == 'payment.dashboard') active @endif" data-toggle="tooltip" data-placement="right" title="Asosiy bo'lim">
                <a class="nav-link" href="{{ route('payment.dashboard') }}">
                    <i class="fa fa-fw fa-dashboard"></i>
                    <span class="nav-link-text">
                Asosiy bo'lim</span>
                </a>
            </li>
            <li class="nav-item @if($current_route == 'payment.projects.index') active @endif" data-toggle="tooltip" data-placement="right" title="Payment Systems">
                <a class="nav-link" href="{{ route('payment.projects.index') }}">
                    <i class="fa fa-fw fa-list"></i>
                    <span class="nav-link-text">
                Loyihalar</span>
                </a>
            </li>
            <li class="nav-item @if($current_route == 'payment.payment_systems.index') active @endif" data-toggle="tooltip" data-placement="right" title="Payment Systems">
                <a class="nav-link" href="{{ route('payment.payment_systems.index') }}">
                    <i class="fa fa-fw fa-list"></i>
                    <span class="nav-link-text">
                To'lov tizimlari</span>
                </a>
            </li>
            <li class="nav-item @if($current_route == 'payment.transactions.index') active @endif" data-toggle="tooltip" data-placement="right" title="Transactions">
                <a class="nav-link" href="{{ route('payment.transactions.index') }}">
                    <i class="fa fa-fw fa-exchange"></i>
                    <span class="nav-link-text">
                Transactions</span>
                </a>
            </li>
            <li class="nav-item @if($current_route == 'payment.settings.index') active @endif" data-toggle="tooltip" data-placement="right" title="Settings">
                <a class="nav-link" href="{{ route('payment.settings') }}">
                    <i class="fa fa-fw fa-cog"></i>
                    <span class="nav-link-text">
                 Settings
                </span>
                </a>
            </li>
            <li class="nav-item @if($current_route == 'payment.editors.index') active @endif" data-toggle="tooltip" data-placement="right" title="Editors">
                <a class="nav-link" href="{{ route('payment.editors') }}">
                    <i class="fa fa-fw fa-edit"></i>
                    <span class="nav-link-text">
                 Editors
                </span>
                </a>
            </li>
            <li class="nav-item" data-toggle="tooltip" data-placement="right" title="Logout">
                <a class="nav-link" href="#">
                    <i class="fa fa-fw fa-times-circle"></i>
                    <span class="nav-link-text">
                Logout</span>
                </a>
            </li>
        </ul>
        <ul class="navbar-nav sidenav-toggler" style="font-size: 1rem !important;">
            <li class="nav-item">
                <a class="nav-link text-center" id="sidenavToggler">
                    <i class="fa fa-fw fa-angle-left"></i>
                </a>
            </li>
        </ul>
    </div>
</div>
<!-- End MainNav -->
