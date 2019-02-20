<!-- MainNav -->
<div class="navbar navbar-expand-md navbar-dark bg-dark fixed-top" id="mainNav">
    <a class="navbar-brand" href="/admin">Pay-uz</a>
    <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav navbar-sidenav" id="exampleAccordion">
            <li class="nav-item active" data-toggle="tooltip" data-placement="right" title="Asosiy bo'lim">
                <a class="nav-link" href="/admin">
                    <i class="fa fa-fw fa-dashboard"></i>
                    <span class="nav-link-text">
                Asosiy bo'lim</span>
                </a>
            </li>
            <li class="nav-item" data-toggle="tooltip" data-placement="right" title="Payment Systems">
                <a class="nav-link" href="">
                    <i class="fa fa-fw fa-list"></i>
                    <span class="nav-link-text">
                Payment Systems</span>
                </a>
            </li>
            <li class="nav-item" data-toggle="tooltip" data-placement="right" title="Transactions">
                <a class="nav-link" href="{{ route('payment.transactions.index') }}">
                    <i class="fa fa-fw fa-exchange"></i>
                    <span class="nav-link-text">
                Transactions</span>
                </a>
            </li>
            <li class="nav-item" data-toggle="tooltip" data-placement="right" title="Invoices">
                <a class="nav-link" href="/">
                    <i class="fa fa-fw fa-building"></i>
                    <span class="nav-link-text">
                 Invoices
                </span>
                </a>
            </li>
            <li class="nav-item" data-toggle="tooltip" data-placement="right" title="Settings">
                <a class="nav-link" href="/">
                    <i class="fa fa-fw fa-cog"></i>
                    <span class="nav-link-text">
                 Settings
                </span>
                </a>
            </li>
            <li class="nav-item" data-toggle="tooltip" data-placement="right" title="Logout">
                <a class="nav-link" href="">
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
