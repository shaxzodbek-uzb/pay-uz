@extends('pay-uz::layouts.app')

@section('title')
    Dashboard
@stop

@section('style')
    <style>

    </style>
@stop

@section('content')
    <div class="container-fluid pb-4">
        <!-- <div class="col-12 mb-4"> -->
        <div class="row mb-4">
            <div class="col-12">
                <span class="h5">Asosiy bo'lim</span>
            </div>
        </div>

        <!-- cards -->
        <div class="row">
            <div class="col-xl-3 col-sm-6 mb-3">
                <div class="card text-topics bg-white o-hidden h-100">
                    <div class="card-body">
                        <div class="card-body-icon text-secondary">
                            <i class="fa fa-fw fa-shopping-cart"></i>
                        </div>
                        <div class="mr-5">
                            Sug'urtaga <span class="badge badge-pill badge-danger">5</span> ta so'rov
                        </div>
                    </div>
                    <a href="#order_ins" class="card-footer text-topics clearfix small z-1" data-toggle="modal">
                        <span class="float-left">To'liq ko'rish</span>
                        <span class="float-right">
                    <i class="fa fa-angle-right"></i>
                  </span>
                    </a>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                <div class="card text-topics bg-white o-hidden h-100">
                    <div class="card-body">
                        <div class="card-body-icon text-secondary">
                            <i class="fa fa-fw fa-envelope"></i>
                        </div>
                        <div class="mr-5">
                            Sizda <span class="badge badge-pill badge-danger">24</span> ta yangi xabar
                        </div>
                    </div>
                    <a href="#mail" class="card-footer text-topics clearfix small z-1" data-toggle="modal">
                        <span class="float-left">To'liq ko'rish</span>
                        <span class="float-right">
                    <i class="fa fa-angle-right"></i>
                  </span>
                    </a>

                    <!-- Xabarlar -->
                    <div class="modal" id="mail" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h6 class="modal-title" id="exampleModalLabel">
                                        Xabarlar <span class="badge badge-pill badge-danger">24</span>
                                    </h6>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body py-0">
                                    <div class="row">
                                        <div class="col-12" style="height: 350px; overflow-y: scroll;">
                                            <div class="row">
                                                <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <a href="#"><b>Jamshid Eshnazarov</b></a>
                                  <span class="badge badge-pill badge-danger">2</span>
                                  <br>
                                  The oasis is a mile that way, or is that just a mirage?
                                  <br>
                                  <span class="text-secondary ft-th"><i class="fa fa-clock-o"></i> Vaqt: 22:02 | 20.10.2017</span>
                                </span>
                                                </div>
                                                <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <a href="#"><b>Sultonov Davron</b></a>
                                  <span class="badge badge-pill badge-danger">1</span>
                                  <br>
                                  Where did you get that camera?! I want one!
                                  The oasis is a mile that way, or is that just a mirage?
                                  <br>
                                  <span class="text-secondary ft-th"><i class="fa fa-clock-o"></i> Vaqt: 22:02 | 20.10.2017</span>
                                </span>
                                                </div>
                                                <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <a href="#"><b>Amonov Adiz</b></a>
                                  <span class="badge badge-pill badge-danger">1</span>
                                  <br>
                                  10 Kids Unaware of Their Halloween Costume? Where did you get that camera?! I want one!
                                  <br>
                                  <span class="text-secondary ft-th"><i class="fa fa-clock-o"></i> Vaqt: 22:02 | 20.10.2017</span>
                                </span>
                                                </div>
                                                <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <a href="#"><b>Sultonov Davron</b></a>
                                  <span class="badge badge-pill badge-danger">1</span>
                                  <br>
                                  Where did you get that camera?! I want one!
                                  The oasis is a mile that way, or is that just a mirage?
                                  <br>
                                  <span class="text-secondary ft-th"><i class="fa fa-clock-o"></i> Vaqt: 22:02 | 20.10.2017</span>
                                </span>
                                                </div>
                                                <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <a href="#"><b>Amonov Adiz</b></a>
                                  <span class="badge badge-pill badge-danger">3</span>
                                  <br>
                                  10 Kids Unaware of Their Halloween Costume? Where did you get that camera?! I want one!
                                  <br>
                                  <span class="text-secondary ft-th"><i class="fa fa-clock-o"></i> Vaqt: 22:02 | 20.10.2017</span>
                                </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-12 py-3 text-right">
                                      <a href="#" class="btn btn-sm btn-primary btn-circle">Yangi xabar yozish</a>
                                    </div> -->
                                </div>
                                <div class="modal-footer text-right">
                                    <a href="#" role="button" class="btn btn-sm btn-secondary btn-circle" data-dismiss="modal">Chiqish</a>
                                    <a href="#message" class="btn btn-sm btn-primary btn-circle" data-toggle="modal" data-dismiss="modal">Yangi xabar yozish</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Xabar yozish -->
                    <div class="modal" id="message" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h6 class="modal-title" id="exampleModalLabel">Xabar yozish</h6>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-12" style="height: 350px; overflow-y: scroll;">
                                            <form>
                                                <div class="form-group">
                                                    <label class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input">
                                                        <span class="custom-control-indicator"></span>
                                                        <span class="custom-control-description">Barcha foydalanuvchilarga yuborish</span>
                                                    </label>
                                                </div>
                                                <div class="form-group">
                                                    <label class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input">
                                                        <span class="custom-control-indicator"></span>
                                                        <span class="custom-control-description">E-mail orqali ogohlantirish</span>
                                                    </label>
                                                </div>
                                                <div class="form-group">
                                                    <label for="recipient-name" class="col-form-label">Qabul qiluvchi:</label>
                                                    <input type="text" class="form-control" id="recipient-name">
                                                </div>
                                                <div class="form-group">
                                                    <label for="message-text" class="col-form-label">Xabar matni:</label>
                                                    <textarea class="form-control" id="message-text" rows="5"></textarea>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <a href="#" role="button" class="btn btn-sm btn-secondary btn-circle" data-dismiss="modal">Chiqish</a>
                                    <a href="#" role="button" class="btn btn-sm btn-primary btn-circle">
                                        <i class="fa fa-send"></i> Yuborish
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Sugurta -->
                    <div class="modal" id="order_ins" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h6 class="modal-title" id="exampleModalLabel">
                                        Sug'urtaga <span class="badge badge-pill badge-danger">5</span> ta so'rov
                                    </h6>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body py-0">
                                    <div class="row">
                                        <div class="col-12" style="height: 350px; overflow-y: scroll;">
                                            <div class="row">
                                                <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <a href="#"><b>Jamshid Eshnazarov</b></a> -
                                  <a href="#">Страхование на случай онкологических заболеваний</a> sug'urta turi uchun so'rov yubordi
                                  <br>
                                  <span class="text-secondary ft-th">
                                    <i class="fa fa-check"></i> soni: <b>2</b>  | qiymati: <b>35$</b> - to'lov amalga oshirilgan
                                  </span>
                                  <br>
                                  <span class="text-secondary ft-th"><i class="fa fa-clock-o"></i> Vaqti: 22:02 | 20.10.2017</span>
                                  <span style="float: right;">
                                    <a href="#confirm_ins" data-toggle="modal"><i class="fa fa-check"></i> <b>Tasdiqlash</b></a>
                                  </span>
                                </span>
                                                </div>
                                                <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <a href="#"><b>Jamshid Eshnazarov</b></a> -
                                  <a href="#">Страхование на случай онкологических заболеваний</a> sug'urta turi uchun so'rov yubordi
                                  <br>
                                  <span class="text-secondary ft-th">
                                    <i class="fa fa-check"></i> soni: <b>2</b>  | qiymati: <b>35$</b> - to'lov amalga oshirilgan
                                  </span>
                                  <br>
                                  <span class="text-secondary ft-th"><i class="fa fa-clock-o"></i> Vaqti: 22:02 | 20.10.2017</span>
                                  <span style="float: right;">
                                    <a href="#confirm_ins" data-toggle="modal"><i class="fa fa-check"></i> <b>Tasdiqlash</b></a>
                                  </span>
                                </span>
                                                </div>
                                                <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <a href="#"><b>Jamshid Eshnazarov</b></a> -
                                  <a href="#">Страхование на случай онкологических заболеваний</a> sug'urta turi uchun so'rov yubordi
                                  <br>
                                  <span class="text-secondary ft-th">
                                    <i class="fa fa-check"></i> soni: <b>2</b>  | qiymati: <b>35$</b> - to'lov amalga oshirilgan
                                  </span>
                                  <br>
                                  <span class="text-secondary ft-th"><i class="fa fa-clock-o"></i> Vaqti: 22:02 | 20.10.2017</span>
                                  <span style="float: right;">
                                    <a href="#confirm_ins" data-toggle="modal"><i class="fa fa-check"></i> <b>Tasdiqlash</b></a>
                                  </span>
                                </span>
                                                </div>
                                                <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <a href="#"><b>Jamshid Eshnazarov</b></a> -
                                  <a href="#">Страхование на случай онкологических заболеваний</a> sug'urta turi uchun so'rov yubordi
                                  <br>
                                  <span class="text-secondary ft-th">
                                    <i class="fa fa-check"></i> soni: <b>2</b>  | qiymati: <b>35$</b> - to'lov amalga oshirilgan
                                  </span>
                                  <br>
                                  <span class="text-secondary ft-th"><i class="fa fa-clock-o"></i> Vaqti: 22:02 | 20.10.2017</span>
                                  <span style="float: right;">
                                    <a href="#confirm_ins" data-toggle="modal"><i class="fa fa-check"></i> <b>Tasdiqlash</b></a>
                                  </span>
                                </span>
                                                </div>
                                                <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <a href="#"><b>Jamshid Eshnazarov</b></a> -
                                  <a href="#">Страхование на случай онкологических заболеваний</a> sug'urta turi uchun so'rov yubordi
                                  <br>
                                  <span class="text-secondary ft-th">
                                    <i class="fa fa-check"></i> soni: <b>2</b>  | qiymati: <b>35$</b> - to'lov amalga oshirilgan
                                  </span>
                                  <br>
                                  <span class="text-secondary ft-th"><i class="fa fa-clock-o"></i> Vaqti: 22:02 | 20.10.2017</span>
                                  <span style="float: right;">
                                    <a href="#confirm_ins" data-toggle="modal"><i class="fa fa-check"></i> <b>Tasdiqlash</b></a>
                                  </span>
                                </span>
                                                </div>
                                                <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <a href="#"><b>Jamshid Eshnazarov</b></a> -
                                  <a href="#">Страхование на случай онкологических заболеваний</a> sug'urta turi uchun so'rov yubordi
                                  <br>
                                  <span class="text-secondary ft-th">
                                    <i class="fa fa-check"></i> soni: <b>2</b>  | qiymati: <b>35$</b> - to'lov amalga oshirilgan
                                  </span>
                                  <br>
                                  <span class="text-secondary ft-th"><i class="fa fa-clock-o"></i> Vaqti: 22:02 | 20.10.2017</span>
                                  <span style="float: right;">
                                    <a href="#confirm_ins" data-toggle="modal"><i class="fa fa-check"></i> <b>Tasdiqlash</b></a>
                                  </span>
                                </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-12 py-3 text-right">
                                      <a href="#" class="btn btn-sm btn-primary btn-circle">Yangi xabar yozish</a>
                                    </div> -->
                                </div>
                                <div class="modal-footer text-right">
                                    <a href="#" role="button" class="btn btn-sm btn-secondary btn-circle" data-dismiss="modal">Chiqish</a>
                                    <!-- <a href="#message" class="btn btn-sm btn-primary btn-circle" data-toggle="modal">Yangi xabar yozish</a> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Tasdiqlash -->
                    <div class="modal" id="confirm_ins" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-sm">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h6 class="modal-title text-topics">So'rovni tasdiqlash</h6>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-12">
                                            Siz rostdan ham ushbu sug'urta sotuvini tasdiqlaysizmi?
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <a href="#" role="button" class="btn btn-sm btn-danger btn-circle" data-dismiss="modal">Yo'q</a>
                                    <a href="#" class="btn btn-sm btn-primary btn-circle">Ha</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                <div class="card text-topics bg-white o-hidden h-100">
                    <div class="card-body">
                        <div class="card-body-icon text-secondary">
                            <i class="fa fa-fw fa-shopping-cart"></i>
                        </div>
                        <div class="mr-5">
                            Kunlik savdo <span class="badge badge-pill badge-primary">586$</span>
                        </div>
                    </div>
                    <a href="#kunlik" class="card-footer text-topics clearfix small z-1" data-toggle="modal">
                        <span class="float-left">To'liq ko'rish</span>
                        <span class="float-right">
                    <i class="fa fa-angle-right"></i>
                  </span>
                    </a>
                </div>
            </div>
            <!-- Kunlik savdo -->
            <div class="modal" id="kunlik" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel">
                                Kunlik savdo aylanmasi
                            </h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body py-0">
                            <div class="row">
                                <div class="col-12 py-3" style="background-color: #eeeeee; border-bottom: solid 1px; border-color: #dddddd;">
                                    <span class="text-topics">Hisobda: <b>6314$</b> bor</span>
                                    <span class="text-topics" style="float: right;">Bugun: <b>+68$</b></span>
                                </div>
                                <div class="col-12" style="height: 350px; overflow-y: scroll;">
                                    <div class="row">
                                        <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <span class="text-secondary ft-sc">15.09.2017 | 09:36</span>:
                                  <span class="ft-sc">
                                  <a href="#" class="text-topics">Мен ва менинг фарзандим</a>
                                  </span>
                                  <span class="ft-th"> - su'gurtasi harid qilindi </span>
                                  <span class="badge badge badge-pill badge-secondary">+23$</span>
                                </span>
                                        </div>
                                        <div class="col-12 pb-3 pt-2 cart-item" style="border-bottom: dotted 1px; border-color: #eeeeee;">
                                <span class="ft-sc text-topics">
                                  <span class="text-secondary ft-sc">15.09.2017 | 14:50</span>:
                                  <span class="ft-sc">
                                  <a href="#" class="text-topics">Страхование на случай онкологических заболеваний</a>
                                  </span>
                                  <span class="ft-th"> - su'gurtasi harid qilindi </span>
                                  <span class="badge badge badge-pill badge-secondary">+45$</span>
                                </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="col-12 py-3 text-right">
                              <a href="#" class="btn btn-sm btn-primary btn-circle">Yangi xabar yozish</a>
                            </div> -->
                        </div>
                        <div class="modal-footer text-right">
                            <a href="#" role="button" class="btn btn-sm btn-secondary btn-circle" data-dismiss="modal">Chiqish</a>
                            <!-- <a href="#message" class="btn btn-sm btn-primary btn-circle" data-toggle="modal">Yangi xabar yozish</a> -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                <div class="card text-topics bg-white o-hidden h-100">
                    <div class="card-body">
                        <div class="card-body-icon text-secondary">
                            <i class="fa fa-fw fa-comment"></i>
                        </div>
                        <div class="mr-5">
                            Forumda <span class="badge badge-pill badge-danger">63</span> ta yangi
                        </div>
                    </div>
                    <a href="#moderatsiya" class="card-footer text-topics clearfix small z-1">
                        <span class="float-left">To'liq ko'rish</span>
                        <span class="float-right">
                    <i class="fa fa-angle-right"></i>
                  </span>
                    </a>
                </div>
            </div>
        </div>
        <!-- </div> -->

        <!-- balans -->
        <div class="col-12 box-admin pt-3 pb-3 mb-3">
            <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
                <div class="row">
                    <span class="text-topics h6">Balans statistikasi</span>
                </div>
            </div>
            <div class="col-12"><canvas id="hisob" width="100%" height="25"></canvas></div>
        </div>

        <!-- users -->
        <div class="col-12 box-admin pt-3 pb-3 mb-3">
            <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
                <div class="row">
                    <span class="text-topics h6">Foydalanuvchilar tashrifi</span>
                </div>
            </div>
            <div class="col-12"><canvas id="users" width="100%" height="25"></canvas></div>
        </div>


        <!-- lenta -->
        <div class="col-12 box-admin pt-3 pb-3 mb-3">
            <!-- <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
              <div class="row">
                <span class="text-topics h6">Moderatsiya uchun</span>
              </div>
            </div> -->
            <div class="row px-3">
                <div class="col-sm-12">
                    <div class="row">
                        <div class="col-12 pb-3">
                            <span class="text-topics"><b>Balansdagi faolliklar</b></span>
                            <hr>
                            <div style="height: 250px; overflow-y: scroll;">
                                <p class="text-secondary">
                                    <a href="#" class="text-secondary ft-sc"><b>15.09.2017</b></a><b>:</b>
                                    <span class="ft-sc">
                    <a href="#" class="text-topics">Мен ва менинг фарзандим</a>
                  </span>
                                    <span class="ft-th"> - su'gurtasi harid qilindi </span>
                                    <span class="badge badge badge-pill badge-secondary">+23$</span>
                                </p>
                                <p class="text-secondary">
                                    <a href="#" class="text-secondary ft-sc"><b>15.09.2017</b></a><b>:</b>
                                    <span class="ft-sc">
                    <a href="#" class="text-topics">Страхование на случай онкологических заболеваний</a>
                  </span>
                                    <span class="ft-th"> - su'gurtasi harid qilindi </span>
                                    <span class="badge badge badge-pill badge-secondary">+45$</span>
                                </p>
                                <p class="text-secondary">
                                    <a href="#" class="text-secondary ft-sc"><b>14.09.2017</b></a><b>:</b>
                                    <span class="ft-sc">
                    <a href="#" class="text-topics">Страхование водителей от несчастных случаев</a>
                  </span>
                                    <span class="ft-th"> - su'gurtasi harid qilindi </span>
                                    <span class="badge badge badge-pill badge-secondary">+21$</span>
                                </p>
                                <p class="text-secondary">
                                    <a href="#" class="text-secondary ft-sc"><b>12.09.2017</b></a><b>:</b>
                                    <span class="ft-sc">
                    <a href="#" class="text-topics">Выезжающие за рубеж</a>
                  </span>
                                    <span class="ft-th"> - su'gurtasi harid qilindi </span>
                                    <span class="badge badge badge-pill badge-secondary">+14$</span>
                                </p>
                                <p class="text-secondary">
                                    <a href="#" class="text-secondary ft-sc"><b>15.09.2017</b></a><b>:</b>
                                    <span class="ft-sc">
                    <a href="#" class="text-topics">Мен ва менинг фарзандим</a>
                  </span>
                                    <span class="ft-th"> - su'gurtasi harid qilindi </span>
                                    <span class="badge badge badge-pill badge-secondary">+23$</span>
                                </p>
                                <p class="text-secondary">
                                    <a href="#" class="text-secondary ft-sc"><b>15.09.2017</b></a><b>:</b>
                                    <span class="ft-sc">
                    <a href="#" class="text-topics">Страхование на случай онкологических заболеваний</a>
                  </span>
                                    <span class="ft-th"> - su'gurtasi harid qilindi </span>
                                    <span class="badge badge badge-pill badge-secondary">+45$</span>
                                </p>
                                <p class="text-secondary">
                                    <a href="#" class="text-secondary ft-sc"><b>14.09.2017</b></a><b>:</b>
                                    <span class="ft-sc">
                    <a href="#" class="text-topics">Страхование водителей от несчастных случаев</a>
                  </span>
                                    <span class="ft-th"> - su'gurtasi harid qilindi </span>
                                    <span class="badge badge badge-pill badge-secondary">+21$</span>
                                </p>
                            </div>
                        </div>
                        <!-- <div class="col-12 align-items-end pt-3">
                          <p><a href="#" class="btn btn-sm btn-primary btn-circle ft-th" role="button">To'liq ko'rish</a></p>
                        </div> -->
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
@stop
