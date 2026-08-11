@extends('layouts.app')

@section('page-title', 'Kategori Bisnis')
@section('page-descrip', 'Kelola data kategori bisnis pelanggan')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Kategori Bisnis</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M14 24a4 4 0 0 1 4-4h18l8 8h18a4 4 0 0 1 4 4v24a4 4 0 0 1-4 4H18a4 4 0 0 1-4-4V24z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Kategori Bisnis"
    create-route="kategori-bisnis.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('kategori-bisnis.data') }}",
        update:  "{{ url('kategori-bisnis') }}/",
        csrf:    "{{ csrf_token() }}",
        history: "{{ url('kategori-bisnis') }}/",
    }

    window.datatableHeaderLabels = { is_aktif: 'Status' };

    window.datatableColumnRenderers = {
        is_aktif: function (data) {
            return data == 1
                ? '<span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:11px;font-weight:600;">Aktif</span>'
                : '<span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:600;">Tidak Aktif</span>';
        },
    };
</script>
<script src="{{ asset('assets/js/kategori-bisnis/form.js') }}"></script>
<script src="{{ asset('assets/js/kategori-bisnis/index.js') }}"></script>
@endsection
