@extends('layouts.app')

@section('page-title', 'Master Output')
@section('page-descrip', 'Kelola daftar judul Output Pekerjaan dan Output Other')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Master Output</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M22 8h28l14 14v50H22V8z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M50 8v14h14" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M32 40h24M32 50h24M32 60h16" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Master Output"
    create-route="output-master.create"
    :with-history="true"
    :search-fields="(new \App\Support\ListFilter\Sets\OutputMasterFilters)->forView()"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('output-master.data') }}",
        update:  "{{ url('output-master') }}/",
        history: "{{ url('output-master') }}/",
        csrf:    "{{ csrf_token() }}"
    }

    window.datatableHeaderLabels = {
        judul: 'Judul',
        tipe: 'Tipe',
        is_lhu: 'LHU',
        is_aktif: 'Status',
        dipakai: 'Dipakai (output)',
    };

    window.datatableColumnRenderers = {
        tipe: function (data) {
            return data === 'Other'
                ? '<span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:600;">Other</span>'
                : '<span class="badge rounded-pill" style="background:#dbeafe;color:#1d4ed8;font-size:11px;font-weight:600;">Pekerjaan</span>';
        },
        is_lhu: function (data) {
            return data === 'LHU'
                ? '<span class="badge rounded-pill" style="background:#ecfeff;color:#0e7490;border:1px solid #a5f3fc;font-size:11px;font-weight:700;">LHU</span>'
                : '<span class="text-muted">—</span>';
        },
        is_aktif: function (data) {
            return data === 'Aktif'
                ? '<span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:11px;font-weight:600;">Aktif</span>'
                : '<span class="badge rounded-pill" style="background:#f1f5f9;color:#475569;font-size:11px;font-weight:600;">Tidak Aktif</span>';
        },
    };
</script>
<script src="{{ asset('assets/js/output-master/form.js') }}?v={{ @filemtime(public_path('assets/js/output-master/form.js')) }}"></script>
<script src="{{ asset('assets/js/output-master/index.js') }}?v={{ @filemtime(public_path('assets/js/output-master/index.js')) }}"></script>
@endsection
