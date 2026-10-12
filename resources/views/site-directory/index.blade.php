@extends('layouts.app')

@section('page-title', 'Site')
@section('page-descrip', 'Daftar semua Site (BRS) lintas Perusahaan — klik baris untuk membuka workspace Business Relation')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Site</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M40 68s20-18 20-32a20 20 0 10-40 0c0 14 20 32 20 32z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <circle cx="40" cy="36" r="7" stroke="white" stroke-width="3"/>
    </svg>
@endsection

@section('content')
<x-crud-index title="List of Site" :with-history="false" :with-detail="false" />
@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('site-directory.data') }}",
        csrf: "{{ csrf_token() }}",
    }

    window.datatableHeaderLabels = {
        nama_lokasi:    'Nama Site',
        nama_br:        'Perusahaan',
        kota_kabupaten: 'Kota/Kabupaten',
        lokasi_gedung:  'Lokasi',
        tipe:           'Tipe',
        status:         'Status',
    };
</script>
<script src="{{ asset('assets/js/site-directory/index.js') }}?v={{ filemtime(public_path('assets/js/site-directory/index.js')) }}"></script>
@endsection
