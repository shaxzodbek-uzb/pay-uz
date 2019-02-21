@if ($errors->any())
    {!! implode('<br>', $errors->all('<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Xatolik</strong> :message
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
    ')) !!}
@endif

@if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Muvaffaqiyatli</strong> {{ session()->get('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif
