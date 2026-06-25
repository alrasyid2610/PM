@extends('layouts.app')

@section('page-title', 'Termin')
@section('page-descrip', 'Kelola data termin pembayaran proyek')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Termin</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="12" y="10" width="56" height="60" rx="4" stroke="white" stroke-width="3"/>
        <path d="M24 28h32M24 40h32M24 52h20" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <path d="M52 48l6 6-6 6" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Termin"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:             "{{ route('termin.data') }}",
        show:             "{{ url('termin') }}/",
        update:           "{{ url('termin') }}/",
        deleteAttachment: "{{ route('termin.delete-attachment') }}",
        history:          "{{ url('termin') }}/",
        outputsBySo:      "{{ url('termin/outputs-by-so') }}/",
        csrf:             "{{ csrf_token() }}"
    }
</script>

<script src="{{ asset('assets/js/termin/form.js') }}"></script>
<script src="{{ asset('assets/js/termin/index.js') }}"></script>
@endsection
