@extends('layouts.app')

@section('page-title', 'Poin Keterangan')
@section('page-descrip', 'Kelola master varian poin keterangan penawaran')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Poin Keterangan</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="20" cy="22" r="3" fill="white"/>
        <circle cx="20" cy="40" r="3" fill="white"/>
        <circle cx="20" cy="58" r="3" fill="white"/>
        <line x1="30" y1="22" x2="62" y2="22" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="30" y1="40" x2="62" y2="40" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="30" y1="58" x2="52" y2="58" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Poin Keterangan"
    create-route="poin-keterangan.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('poin-keterangan.data') }}",
        update:  "{{ url('poin-keterangan') }}/",
        csrf:    "{{ csrf_token() }}",
        history: "{{ url('poin-keterangan') }}/",
    }

    window.datatableHeaderLabels = {
        kategori:   'Kategori',
        template:   'Teks Poin',
        is_default: 'Paket',
        is_aktif:   'Status',
    };
</script>
<script src="{{ asset('assets/js/poin-keterangan/editor.js') }}?v={{ filemtime(public_path('assets/js/poin-keterangan/editor.js')) }}"></script>
<script src="{{ asset('assets/js/poin-keterangan/form.js') }}"></script>
<script src="{{ asset('assets/js/poin-keterangan/index.js') }}"></script>
@endsection
