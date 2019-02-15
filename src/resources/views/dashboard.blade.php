@extends('pay-uz::layouts.app')

@section('content')
    <div id="page-wrapper" >
        <div id="page-inner">
            <div class="row">
                <div class="col-md-12">
                    <h2>BASIC UI ELEMENTS</h2>
                </div>
            </div>
            <!-- /. ROW  -->
            <hr />
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <h5>Input Examples Set</h5>
                    <div class="input-group">
                        <span class="input-group-addon">@</span>
                        <input type="text" class="form-control" placeholder="Username" />
                    </div>
                    <br />
                    <div class="input-group">
                        <input type="text" class="form-control" />
                        <span class="input-group-addon">.00</span>
                    </div>
                    <br />
                    <div class="input-group">
                        <span class="input-group-addon">$</span>
                        <input type="text" class="form-control" />
                        <span class="input-group-addon">.00</span>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <h5>Buttons Samples</h5>
                    <a href="#" class="btn btn-default">default</a>
                    <a href="#" class="btn btn-primary">primary</a>
                    <a href="#" class="btn btn-danger">danger</a>
                    <a href="#" class="btn btn-success">success</a>
                    <a href="#" class="btn btn-info">info</a>
                    <a href="#" class="btn btn-warning">warning</a>
                    <br>
                    <br>
                    <h5>Progressbar Samples</h5>
                    <div class="progress progress-striped">
                        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 40%">
                            <span class="sr-only">40% Complete (success)</span>
                        </div>
                    </div>
                    <div class="progress progress-striped active">
                        <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%">
                            <span class="sr-only">20% Complete</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /. ROW  -->
            <hr>
            <div class="row">
                <div class="col-lg-4 col-md-4">
                    <div class="form-group">
                        <label>Text Input Example</label>
                        <input class="form-control" />
                        <p class="help-block">Help text here.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <label>Click to see blank page</label>
                    <a href="blank.html" target="_blank" class="btn btn-danger btn-lg btn-block">BLANK PAGE</a>
                </div>
                <div class="col-lg-4 col-md-4">
                    For More Examples Please visit official bootstrap website <a href="http://getbootstrap.com" target="_blank">getbootstrap.com</a>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-lg-6 col-md-6">
                    <h5>Table  Sample One</h5>
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Username</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>1</td>
                            <td>Mark</td>
                            <td>Otto</td>
                            <td>@mdo</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Jacob</td>
                            <td>Thornton</td>
                            <td>@fat</td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Mark</td>
                            <td>Otto</td>
                            <td>@mdo</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Larry</td>
                            <td>the Bird</td>
                            <td>@twitter</td>
                        </tr>
                        </tbody>
                    </table>

                </div>
                <div class="col-lg-6 col-md-6">
                    <h5>Table  Sample Two</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Username</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr class="success">
                                <td>1</td>
                                <td>Mark</td>
                                <td>Otto</td>
                                <td>@mdo</td>
                            </tr>
                            <tr class="info">
                                <td>2</td>
                                <td>Jacob</td>
                                <td>Thornton</td>
                                <td>@fat</td>
                            </tr>
                            <tr class="warning">
                                <td>3</td>
                                <td>Larry</td>
                                <td>the Bird</td>
                                <td>@twitter</td>
                            </tr>
                            <tr class="danger">
                                <td>4</td>
                                <td>John</td>
                                <td>Smith</td>
                                <td>@jsmith</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /. ROW  -->
            <hr>
            <div class="row">
                <div class="col-lg-4 col-md-4">
                    <h5>Panel Sample</h5>
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            Default Panel
                        </div>
                        <div class="panel-body">
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum tincidunt est vitae ultrices accumsan. Aliquam ornare lacus adipiscing, posuere lectus et, fringilla augue.</p>
                        </div>
                        <div class="panel-footer">
                            Panel Footer
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h5>Accordion Sample</h5>
                    <div class="panel-group" id="accordion">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" class="collapsed">Collapsible Group Item #1</a>
                                </h4>
                            </div>
                            <div id="collapseOne" class="panel-collapse collapse" style="height: 0px;">
                                <div class="panel-body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt.
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo">Collapsible Group Item #2</a>
                                </h4>
                            </div>
                            <div id="collapseTwo" class="panel-collapse in" style="height: auto;">
                                <div class="panel-body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt.

                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseThree" class="collapsed">Collapsible Group Item #3</a>
                                </h4>
                            </div>
                            <div id="collapseThree" class="panel-collapse collapse">
                                <div class="panel-body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h5>Tabs Sample</h5>
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#home" data-toggle="tab">Home</a>
                        </li>
                        <li class=""><a href="#profile" data-toggle="tab">Profile</a>
                        </li>
                        <li class=""><a href="#messages" data-toggle="tab">Messages</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade active in" id="home">
                            <h4>Home Tab</h4>
                            <p>
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit eserunt mollit anim id est laborum.
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit eserunt mollit anim id est laborum.
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit eserunt mollit anim id est laborum.
                            </p>
                        </div>
                        <div class="tab-pane fade" id="profile">
                            <h4>Profile Tab</h4>
                            <p>
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit eserunt mollit anim id est laborum.
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit eserunt mollit anim id est laborum.
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit eserunt mollit anim id est laborum.
                            </p>
                        </div>
                        <div class="tab-pane fade" id="messages">
                            <h4>Messages Tab</h4>
                            <p>
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit eserunt mollit anim id est laborum.
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit eserunt mollit anim id est laborum.
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit eserunt mollit anim id est laborum.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /. ROW  -->
            <hr />
            <div class="row">
                <div class="col-lg-12 col-md-12">
                    <h5>Information</h5>
                    This is a type of bare admin that means you can customize your own admin using this admin structured  template . For More Examples of bootstrap elements or components please visit official bootstrap website <a href="http://getbootstrap.com" target="_blank">getbootstrap.com</a>
                    . And if you want full template please download <a href="http://www.binarytheme.com/bootstrap-free-admin-dashboard-template/" target="_blank">FREE BCORE ADMIN </a>&nbsp;,&nbsp;  <a href="http://www.binarytheme.com/free-bootstrap-admin-template-siminta/" target="_blank">FREE SIMINTA ADMIN</a> and <a href="http://binarycart.com/" target="_blank">FREE BINARY ADMIN</a>.
                </div>
            </div>
            <!-- /. ROW  -->
        </div>
    </div>
    <!-- /. PAGE WRAPPER  -->
    </div>
@stop