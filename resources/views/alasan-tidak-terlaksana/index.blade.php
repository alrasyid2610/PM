@extends('layouts.app')

@section('page-title', 'Alasan Tidak Terlaksana')
@section('page-descrip', 'Kelola master alasan titik sample FWO tidak dapat diambil')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Alasan Tidak Terlaksana</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="40" cy="40" r="24" stroke="white" stroke-width="3"/>
        <line x1="23" y1="57" x2="57" y2="23" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Alasan Tidak Terlaksana"
    create-route="alasan-tidak-terlaksana.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('alasan-tidak-terlaksana.data') }}",
        update:  "{{ url('alasan-tidak-terlaksana') }}/",
        csrf:    "{{ csrf_token() }}",
        history: "{{ url('alasan-tidak-terlaksana') }}/",
    }

    window.datatableHeaderLabels = {
        nama:             'Alasan',
        wajib_keterangan: 'Keterangan',
        is_aktif:         'Status',
    };
</script>
<script src="{{ asset('assets/js/alasan-tidak-terlaksana/form.js') }}"></script>
<script src="{{ asset('assets/js/alasan-tidak-terlaksana/index.js') }}"></script>
@endsection
