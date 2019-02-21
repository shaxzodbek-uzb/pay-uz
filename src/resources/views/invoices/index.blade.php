@extends('pay-uz::layouts.app')

@section('title')
    Transactions
@stop

@section('style')
    <style>

    </style>
@stop

@section('content')
    <div class="container-fluid pb-4">
        <!-- <div class="col-12 mb-4"> -->
        <div class="row mb-4">
            <div class="col-6">
                <span class="h5">Foydalanuvchilar</span>
            </div>
            <div class="col-6 text-right">
                <a href="#add_user" class="btn btn-sm btn-primary" role="button" data-toggle="modal"><span class="fa fa-plus"></span> Yangi qo'shish</a>
            </div>
        </div>
        <!-- Qoshish -->
        <div class="modal fade" id="add_user" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title" id="exampleModalLabel">Yangi foydalanuvchi qo'shish</h6>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-danger ft-sc" role="alert">
                                    Ma'lumotlarni to'ldirishda xatolik yuz berdi:<br>
                                    * E-mail ko'rsatilmadi
                                </div>
                                <form>
                                    <div class="form-group">
                                        <label for="recipient-name" class="col-form-label">Ismi:</label>
                                        <input type="text" class="form-control" id="recipient-name">
                                    </div>
                                    <div class="form-group">
                                        <label for="recipient-name" class="col-form-label">Familyasi:</label>
                                        <input type="text" class="form-control" id="recipient-name">
                                    </div>
                                    <div class="form-group">
                                        <label for="recipient-name" class="col-form-label">Otasining ismi:</label>
                                        <input type="text" class="form-control" id="recipient-name">
                                    </div>
                                    <div class="form-group">
                                        <label for="exampleFormControlInput1">
                                            <span class="text-danger">* </span>E-mail:
                                        </label>
                                        <input type="email" class="form-control" id="exampleFormControlInput1" placeholder="name@example.com">
                                    </div>
                                    <div class="form-group">
                                        <label for="recipient-name" class="col-form-label">
                                            <span class="text-danger">* </span>Telefon raqami:
                                        </label>
                                        <input type="text" class="form-control" id="recipient-name" placeholder="+(998xx) xxx-xx-xx">
                                    </div>
                                    <div class="form-group">
                                        <label for="exampleFormControlSelect1">Viloyat:</label>
                                        <select class="form-control" id="exampleFormControlSelect1">
                                            <option>Toshkent</option>
                                            <option>Andijon</option>
                                            <option>Samarqand</option>
                                            <option>Navoiy</option>
                                            <option>Buxoro</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="exampleFormControlSelect1">Tuman/Shahar:</label>
                                        <select class="form-control" id="exampleFormControlSelect1">
                                            <option>Toshkent shahri</option>
                                            <option>Qatshi</option>
                                            <option>Uchquduq</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="recipient-name" class="col-form-label">Tug'ilgan vaqti:</label>
                                        <input type="text" class="form-control" id="recipient-name" placeholder="dd.mm.yyyy">
                                    </div>
                                    <div class="form-group">
                                        <label for="exampleFormControlSelect1">Darajasi:</label>
                                        <select class="form-control" id="exampleFormControlSelect1">
                                            <option>Admin</option>
                                            <option>Moderator</option>
                                            <option>Oddiy foydalanuvchi</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="recipient-name" class="col-form-label">
                                            <span class="text-danger">* </span>Parol:
                                        </label>
                                        <input type="password" class="form-control" id="recipient-name">
                                    </div>
                                    <div class="form-group">
                                        <label for="recipient-name" class="col-form-label">
                                            <span class="text-danger">* </span>Parolni tasdiqlang:
                                        </label>
                                        <input type="password" class="form-control" id="recipient-name">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" role="button" class="btn btn-sm btn-secondary btn-circle" data-dismiss="modal">Chiqish</a>
                        <a href="#" role="button" class="btn btn-sm btn-primary btn-circle">
                            <i class="fa fa-plus"></i> Qo'shish
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 box-admin pt-3 pb-3 mb-3">
            <!-- <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
              <div class="row">
                <span class="text-topics h6">Umumiy ma'lumot</span>
              </div>
            </div> -->
            <div class="row px-3">
                <div class="col-sm-4">
                    <div class="row">
                        <div class="col-12 text-topics">
                            <span class="text-topics"><b>Foydalanuvchilar</b></span>
                            <hr>
                            <p>
                                <span class="fa fa-users"></span> Ro'yhatdan o'tgan foydalanuvhcilar soni: <span class="badge badge-pill badge-primary">985</span>
                            </p>
                            <p class="text-danger">
                                <span class="fa fa-user-times"></span> Bloklangan foydalanuvchilar soni: <span class="badge badge-pill badge-danger">63</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="row">
                        <div class="col-12 text-topics">
                            <span class="text-topics"><b>Mehmonlar</b></span>
                            <hr>
                            <p>
                                <span class="fa fa-user-circle-o"></span> Jami mehmonlar soni: <span class="badge badge-pill badge-primary">16378</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="row">
                        <div class="col-12 text-topics">
                            <span class="text-topics"><b>Bugungi statistika</b></span>
                            <hr>
                            <p>
                                <span class="fa fa-users"></span> Tashrif buyurgan foydalanuvchilar: <span class="badge badge-pill badge-primary">91</span>
                            </p>
                            <p>
                                <span class="fa fa-user-circle-o"></span> Tashrif buyurgan mehmonlar: <span class="badge badge-pill badge-primary">148</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>

        <!-- balans -->
        <div class="col-12 box-admin pt-3 pb-3 mb-3">
            <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
                <div class="row">
                    <span class="text-topics h6">Balans statistikasi</span>
                </div>
            </div>
            <div class="col-12"><canvas id="hisob" width="100%" height="25"></canvas></div>
        </div>

        <!-- </div> -->
        <div class="col-12 box-admin pt-3 pb-3">
            <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
                <div class="row">
                    <span class="text-topics h6">Foydalanuvchilar jadvali</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" id="dataTable" cellspacing="0">
                    <thead class="thead-default">
                    <tr>
                        <th>Ism</th>
                        <th>Familya</th>
                        <th>Officer</th>
                        <th>Age</th>
                        <th>Start date</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tfoot class="thead-default">
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Office</th>
                        <th>Age</th>
                        <th>Start date</th>
                        <th></th>
                    </tr>
                    </tfoot>
                    <tbody>
                    <tr>
                        <td>Tiger Nixon</td>
                        <td>System Architect</td>
                        <td>Edinburgh</td>
                        <td>61</td>
                        <td>2011/04/25</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Garrett Winters</td>
                        <td>Accountant</td>
                        <td>Tokyo</td>
                        <td>63</td>
                        <td>2011/07/25</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr class="table-danger">
                        <td>Ashton Cox</td>
                        <td>Junior Technical Author</td>
                        <td>San Francisco</td>
                        <td>66</td>
                        <td>2009/01/12</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-lock" data-toggle="tooltip" data-placement="top" title="Ochish"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Cedric Kelly</td>
                        <td>Senior Javascript Developer</td>
                        <td>Edinburgh</td>
                        <td>22</td>
                        <td>2012/03/29</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Airi Satou</td>
                        <td>Accountant</td>
                        <td>Tokyo</td>
                        <td>33</td>
                        <td>2008/11/28</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Brielle Williamson</td>
                        <td>Integration Specialist</td>
                        <td>New York</td>
                        <td>61</td>
                        <td>2012/12/02</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Herrod Chandler</td>
                        <td>Sales Assistant</td>
                        <td>San Francisco</td>
                        <td>59</td>
                        <td>2012/08/06</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Rhona Davidson</td>
                        <td>Integration Specialist</td>
                        <td>Tokyo</td>
                        <td>55</td>
                        <td>2010/10/14</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Colleen Hurst</td>
                        <td>Javascript Developer</td>
                        <td>San Francisco</td>
                        <td>39</td>
                        <td>2009/09/15</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Sonya Frost</td>
                        <td>Software Engineer</td>
                        <td>Edinburgh</td>
                        <td>23</td>
                        <td>2008/12/13</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Jena Gaines</td>
                        <td>Office Manager</td>
                        <td>London</td>
                        <td>30</td>
                        <td>2008/12/19</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Quinn Flynn</td>
                        <td>Support Lead</td>
                        <td>Edinburgh</td>
                        <td>22</td>
                        <td>2013/03/03</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Charde Marshall</td>
                        <td>Regional Director</td>
                        <td>San Francisco</td>
                        <td>36</td>
                        <td>2008/10/16</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Haley Kennedy</td>
                        <td>Senior Marketing Designer</td>
                        <td>London</td>
                        <td>43</td>
                        <td>2012/12/18</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Tatyana Fitzpatrick</td>
                        <td>Regional Director</td>
                        <td>London</td>
                        <td>19</td>
                        <td>2010/03/17</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Michael Silva</td>
                        <td>Marketing Designer</td>
                        <td>London</td>
                        <td>66</td>
                        <td>2012/11/27</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Paul Byrd</td>
                        <td>Chief Financial Officer (CFO)</td>
                        <td>New York</td>
                        <td>64</td>
                        <td>2010/06/09</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Gloria Little</td>
                        <td>Systems Administrator</td>
                        <td>New York</td>
                        <td>59</td>
                        <td>2009/04/10</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Bradley Greer</td>
                        <td>Software Engineer</td>
                        <td>London</td>
                        <td>41</td>
                        <td>2012/10/13</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Dai Rios</td>
                        <td>Personnel Lead</td>
                        <td>Edinburgh</td>
                        <td>35</td>
                        <td>2012/09/26</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Jenette Caldwell</td>
                        <td>Development Lead</td>
                        <td>New York</td>
                        <td>30</td>
                        <td>2011/09/03</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Yuri Berry</td>
                        <td>Chief Marketing Officer (CMO)</td>
                        <td>New York</td>
                        <td>40</td>
                        <td>2009/06/25</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Caesar Vance</td>
                        <td>Pre-Sales Support</td>
                        <td>New York</td>
                        <td>21</td>
                        <td>2011/12/12</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Doris Wilder</td>
                        <td>Sales Assistant</td>
                        <td>Sidney</td>
                        <td>23</td>
                        <td>2010/09/20</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Angelica Ramos</td>
                        <td>Chief Executive Officer (CEO)</td>
                        <td>London</td>
                        <td>47</td>
                        <td>2009/10/09</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Gavin Joyce</td>
                        <td>Developer</td>
                        <td>Edinburgh</td>
                        <td>42</td>
                        <td>2010/12/22</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Jennifer Chang</td>
                        <td>Regional Director</td>
                        <td>Singapore</td>
                        <td>28</td>
                        <td>2010/11/14</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Brenden Wagner</td>
                        <td>Software Engineer</td>
                        <td>San Francisco</td>
                        <td>28</td>
                        <td>2011/06/07</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Fiona Green</td>
                        <td>Chief Operating Officer (COO)</td>
                        <td>San Francisco</td>
                        <td>48</td>
                        <td>2010/03/11</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Shou Itou</td>
                        <td>Regional Marketing</td>
                        <td>Tokyo</td>
                        <td>20</td>
                        <td>2011/08/14</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Michelle House</td>
                        <td>Integration Specialist</td>
                        <td>Sidney</td>
                        <td>37</td>
                        <td>2011/06/02</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Suki Burks</td>
                        <td>Developer</td>
                        <td>London</td>
                        <td>53</td>
                        <td>2009/10/22</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Prescott Bartlett</td>
                        <td>Technical Author</td>
                        <td>London</td>
                        <td>27</td>
                        <td>2011/05/07</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Gavin Cortez</td>
                        <td>Team Leader</td>
                        <td>San Francisco</td>
                        <td>22</td>
                        <td>2008/10/26</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Martena Mccray</td>
                        <td>Post-Sales support</td>
                        <td>Edinburgh</td>
                        <td>46</td>
                        <td>2011/03/09</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Unity Butler</td>
                        <td>Marketing Designer</td>
                        <td>San Francisco</td>
                        <td>47</td>
                        <td>2009/12/09</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Howard Hatfield</td>
                        <td>Office Manager</td>
                        <td>San Francisco</td>
                        <td>51</td>
                        <td>2008/12/16</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Hope Fuentes</td>
                        <td>Secretary</td>
                        <td>San Francisco</td>
                        <td>41</td>
                        <td>2010/02/12</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Vivian Harrell</td>
                        <td>Financial Controller</td>
                        <td>San Francisco</td>
                        <td>62</td>
                        <td>2009/02/14</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Timothy Mooney</td>
                        <td>Office Manager</td>
                        <td>London</td>
                        <td>37</td>
                        <td>2008/12/11</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Jackson Bradshaw</td>
                        <td>Director</td>
                        <td>New York</td>
                        <td>65</td>
                        <td>2008/09/26</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Olivia Liang</td>
                        <td>Support Engineer</td>
                        <td>Singapore</td>
                        <td>64</td>
                        <td>2011/02/03</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Bruno Nash</td>
                        <td>Software Engineer</td>
                        <td>London</td>
                        <td>38</td>
                        <td>2011/05/03</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Sakura Yamamoto</td>
                        <td>Support Engineer</td>
                        <td>Tokyo</td>
                        <td>37</td>
                        <td>2009/08/19</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Thor Walton</td>
                        <td>Developer</td>
                        <td>New York</td>
                        <td>61</td>
                        <td>2013/08/11</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Finn Camacho</td>
                        <td>Support Engineer</td>
                        <td>San Francisco</td>
                        <td>47</td>
                        <td>2009/07/07</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Serge Baldwin</td>
                        <td>Data Coordinator</td>
                        <td>Singapore</td>
                        <td>64</td>
                        <td>2012/04/09</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Zenaida Frank</td>
                        <td>Software Engineer</td>
                        <td>New York</td>
                        <td>63</td>
                        <td>2010/01/04</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Zorita Serrano</td>
                        <td>Software Engineer</td>
                        <td>San Francisco</td>
                        <td>56</td>
                        <td>2012/06/01</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Jennifer Acosta</td>
                        <td>Junior Javascript Developer</td>
                        <td>Edinburgh</td>
                        <td>43</td>
                        <td>2013/02/01</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Cara Stevens</td>
                        <td>Sales Assistant</td>
                        <td>New York</td>
                        <td>46</td>
                        <td>2011/12/06</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Hermione Butler</td>
                        <td>Regional Director</td>
                        <td>London</td>
                        <td>47</td>
                        <td>2011/03/21</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Lael Greer</td>
                        <td>Systems Administrator</td>
                        <td>London</td>
                        <td>21</td>
                        <td>2009/02/27</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Jonas Alexander</td>
                        <td>Developer</td>
                        <td>San Francisco</td>
                        <td>30</td>
                        <td>2010/07/14</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Shad Decker</td>
                        <td>Regional Director</td>
                        <td>Edinburgh</td>
                        <td>51</td>
                        <td>2008/11/13</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Michael Bruce</td>
                        <td>Javascript Developer</td>
                        <td>Singapore</td>
                        <td>29</td>
                        <td>2011/06/27</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    <tr>
                        <td>Donna Snider</td>
                        <td>Customer Support</td>
                        <td>New York</td>
                        <td>27</td>
                        <td>2011/01/25</td>
                        <td class="text-center">
                            <a href="#"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                            <a href="#"><span class="fa fa-unlock-alt" data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('script')
    <script type="text/javascript">
        // Hourly users
        let ctx = document.getElementById("soatlik");
        let myLineChart2 = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ["00:00", "01:00", "02:00", "03:00", "04:00", "05:00", "06:00", "07:00", "08:00", "09:00", "10:00", "11:00", "12:00", "13:00", "14:00", "15:00", "16:00", "17:00", "18:00", "19:00", "20:00", "21:00", "22:00", "23:00"],
                datasets: [{
                    label: "Userlar",
                    lineTension: 0.5,
                    backgroundColor: "rgba(2,117,216,0.2)",
                    borderColor: "rgba(2,117,216,1)",
                    pointRadius: 5,
                    pointBackgroundColor: "rgba(2,117,216,1)",
                    pointBorderColor: "rgba(255,255,255,0.8)",
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: "rgba(2,117,216,1)",
                    pointHitRadius: 20,
                    pointBorderWidth: 1,
                    data: [1500, 1693, 1523, 1782, 1900, 1364, 1874,1400, 1893, 1623, 2082, 1500, 1764, 1374],
                },
                    {
                        label: "Mehmonlar",
                        lineTension: 0.5,
                        backgroundColor: "rgba(255,30,5,0.2)",
                        borderColor: "rgba(255,30,5,1)",
                        pointRadius: 5,
                        pointBackgroundColor: "rgba(255,30,5,1)",
                        pointBorderColor: "rgba(255,255,255,0.8)",
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: "rgba(255,30,5,1)",
                        pointHitRadius: 20,
                        pointBorderWidth: 1,
                        data: [1400, 1893, 1623, 2082, 1500, 1764, 1374,1500, 1693, 1523, 1782, 1900, 1364, 1874],
                    }],
            },
            options: {
                scales: {
                    xAxes: [{
                        time: {
                            unit: 'date'
                        },
                        gridLines: {
                            display: true
                        },
                        ticks: {
                            maxTicksLimit: 12
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            min: 0,
                            max: 3000,
                            maxTicksLimit: 10
                        },
                        gridLines: {
                            color: "rgba(0, 0, 0, .125)",
                        }
                    }],
                },
                legend: {
                    display: true
                }
            }
        });
    </script>
@stop
