@extends('layouts.app')

@section('page-title', 'Kelengkapan SO & SQ')
@section('page-descrip', 'Report kelengkapan data: apakah SO/SQ sudah punya WO, FWO, Budget WO, dan Budget FWO')

@section('breadcrumb')
    <li class="breadcrumb-item">Report</li>
    <li class="breadcrumb-item active" aria-current="page">Kelengkapan SO & SQ</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="18" y="14" width="44" height="52" rx="4" stroke="white" stroke-width="3"/>
        <path d="M28 30h24M28 40h24M28 50h14" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index title="Kelengkapan SO & SQ" :with-history="false" :with-detail="false"
    :search-fields="(new \App\Support\ListFilter\Sets\KelengkapanFilters)->forView()" />
@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('report-kelengkapan.data') }}",
        csrf: "{{ csrf_token() }}",
    }

    window.datatableHeaderLabels = {
        jenis:          'Jenis',
        nomor:          'No SO/SQ',
        tanggal_buat:   'Tanggal Buat',
        pelanggan:      'Pelanggan',
        site:           'Site',
        pic_input:      'PIC Input',
        pic_order:      'PIC Order',
        ada_wo:         'Ada WO',
        ada_fwo:        'Ada FWO',
        ada_budget_wo:  'Ada Budget WO',
        ada_budget_fwo: 'Ada Budget FWO',
    };
</script>
<script src="{{ asset('assets/js/report-kelengkapan/index.js') }}?v={{ filemtime(public_path('assets/js/report-kelengkapan/index.js')) }}"></script>
@endsection
