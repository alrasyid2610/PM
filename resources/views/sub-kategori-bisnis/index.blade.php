@extends('layouts.app')

@section('page-title', 'Sub Kategori Bisnis')
@section('page-descrip', 'Kelola data sub kategori bisnis pelanggan')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Sub Kategori Bisnis</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M14 24a4 4 0 0 1 4-4h18l8 8h18a4 4 0 0 1 4 4v24a4 4 0 0 1-4 4H18a4 4 0 0 1-4-4V24z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Sub Kategori Bisnis"
    create-route="sub-kategori-bisnis.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:               "{{ route('sub-kategori-bisnis.data') }}",
        update:             "{{ url('sub-kategori-bisnis') }}/",
        csrf:               "{{ csrf_token() }}",
        history:            "{{ url('sub-kategori-bisnis') }}/",
        select2KategoriBisnis: "{{ route('kategori-bisnis.select2') }}",
        createKategoriBisnis:  "{{ route('kategori-bisnis.create') }}",
    }

    window.datatableHeaderLabels = {
        nama_kategori_bisnis: 'Kategori Bisnis',
        is_aktif: 'Status',
    };

    window.datatableColumnRenderers = {
        is_aktif: function (data) {
            return data == 1
                ? '<span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:11px;font-weight:600;">Aktif</span>'
                : '<span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:600;">Tidak Aktif</span>';
        },
    };
</script>
<script src="{{ asset('assets/js/sub-kategori-bisnis/form.js') }}"></script>
<script src="{{ asset('assets/js/sub-kategori-bisnis/index.js') }}"></script>
@endsection
