@extends('layouts.app')

@section('page-title', 'Syarat Sample')
@section('page-descrip', 'Kelola master syarat pengambilan / penerimaan sample')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Syarat Sample</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="18" y="10" width="44" height="60" rx="4" stroke="white" stroke-width="3"/>
        <path d="M28 30l5 5 9-10M28 50l5 5 9-10" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M48 32h8M48 52h8" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Syarat Sample"
    create-route="syarat-sample.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('syarat-sample.data') }}",
        update:  "{{ url('syarat-sample') }}/",
        csrf:    "{{ csrf_token() }}",
        history: "{{ url('syarat-sample') }}/",
    }

    window.datatableHeaderLabels = {
        template: 'Syarat',
        is_aktif: 'Status',
        dipakai:  'Dipakai (Testing Point)',
    };
</script>
<script src="{{ asset('assets/js/poin-keterangan/editor.js') }}?v={{ filemtime(public_path('assets/js/poin-keterangan/editor.js')) }}"></script>
<script src="{{ asset('assets/js/syarat-sample/form.js') }}?v={{ @filemtime(public_path('assets/js/syarat-sample/form.js')) }}"></script>
<script src="{{ asset('assets/js/syarat-sample/index.js') }}?v={{ @filemtime(public_path('assets/js/syarat-sample/index.js')) }}"></script>
@endsection