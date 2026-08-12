@extends('layouts.app')

@section('page-title', 'Satuan')
@section('page-descrip', 'Kelola data satuan BOQ')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Satuan</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M14 30h52v20H14z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <line x1="24" y1="30" x2="24" y2="38" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="34" y1="30" x2="34" y2="38" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="44" y1="30" x2="44" y2="38" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="54" y1="30" x2="54" y2="38" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Satuan"
    create-route="satuan.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('satuan.data') }}",
        update:  "{{ url('satuan') }}/",
        csrf:    "{{ csrf_token() }}",
        history: "{{ url('satuan') }}/",
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
<script src="{{ asset('assets/js/satuan/form.js') }}"></script>
<script src="{{ asset('assets/js/satuan/index.js') }}"></script>
@endsection
