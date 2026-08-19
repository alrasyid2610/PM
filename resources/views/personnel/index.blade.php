@extends('layouts.app')

@section('page-title', 'Personnel')
@section('page-descrip', 'Kelola data personel lapangan (PIC, teknisi sampling, dsb)')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Personnel</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="30" cy="26" r="10" stroke="white" stroke-width="3"/>
        <circle cx="54" cy="30" r="8" stroke="white" stroke-width="3"/>
        <path d="M12 62c0-11.046 8.059-20 18-20s18 8.954 18 20" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <path d="M44 62c0-8.837 6.268-16 14-16s14 7.163 14 16" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Personnel"
    create-route="personnel.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:           "{{ route('personnel.data') }}",
        update:         "{{ url('personnel') }}/",
        csrf:           "{{ csrf_token() }}",
        history:        "{{ url('personnel') }}/",
        createAccount:  "{{ url('personnel') }}/",
        revokeAccount:  "{{ url('personnel') }}/",
    }

    window.datatableHeaderLabels = { is_aktif: 'Status', akses_sistem: 'Akses Sistem' };

    window.datatableColumnRenderers = {
        is_aktif: function (data) {
            return data == 1
                ? '<span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:11px;font-weight:600;">Aktif</span>'
                : '<span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:600;">Tidak Aktif</span>';
        },
    };
</script>
<script src="{{ asset('assets/js/personnel/form.js') }}"></script>
<script src="{{ asset('assets/js/personnel/index.js') }}"></script>
@endsection
