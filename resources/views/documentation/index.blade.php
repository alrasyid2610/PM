@extends('layouts.app')

@section('page-title', 'Dokumentasi')
@section('page-descrip', 'Panduan & dokumentasi penggunaan aplikasi per modul')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Dokumentasi</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 12h30l10 10v46H20V12z" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M50 12v10h10" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M28 38h24M28 48h24M28 58h16" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Documentation"
    create-route="documentations.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('documentations.data') }}",
        update: "{{ url('documentations') }}/",
        history: "{{ url('documentations') }}/",
        uploadImage: "{{ route('documentations.upload-image') }}",
        tagSelect2: "{{ route('documentations.tags.select2') }}",
        csrf: "{{ csrf_token() }}"
    }

    window.documentationModuleOptions = @json($moduleOptions);

    window.datatableHeaderLabels = {
        pembuat: 'Dibuat Oleh',
        is_published: 'Status',
        created_at: 'Tanggal',
    };
</script>
<script src="{{ asset('assets/vendor/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/js/documentation/form.js') }}"></script>
<script src="{{ asset('assets/js/documentation/index.js') }}"></script>
@endsection

@section('style')
<link href="{{ asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
@endsection
