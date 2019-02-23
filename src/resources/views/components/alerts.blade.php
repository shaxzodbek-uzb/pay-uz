@if ($errors->any())
    {!! implode('', $errors->all('<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><span class="fa fa-warning"></span> Xatolik</strong> :message
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
    ')) !!}
@endif

@if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong><span class="fa fa-check"></span> Muvaffaqiyatli</strong> {{ session()->get('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session()->has('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong><span class="fa fa-warning"></span> Xatolik</strong> {{ session()->get('warning') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif
