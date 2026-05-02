@extends('layouts.app')

@section('page-title', 'Contracts')
@section('page-descrip', 'Kelola data kontrak pelanggan')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Contracts</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="16" y="10" width="48" height="60" rx="4" stroke="white" stroke-width="3"/>
        <line x1="26" y1="26" x2="54" y2="26" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="26" y1="36" x2="54" y2="36" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="26" y1="46" x2="42" y2="46" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <path d="M46 56l4 4 8-8" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Contracts"
    create-route="contracts.create"
    add-label="Tambah Contract"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:             "{{ route('contracts.data') }}",
        update:           "{{ url('contracts') }}/",
        history:          "{{ url('contracts') }}/",
        deleteAttachment: "{{ route('contracts.delete-attachment') }}",
        select2BR:        "{{ route('business-relations.select2') }}",
        select2Contact:   "{{ route('business-relation-contacts.select2') }}",
        select2User:      "{{ route('users.select2') }}",
        csrf:             "{{ csrf_token() }}",
    }
</script>
<script src="{{ asset('assets/js/contracts/form.js') }}"></script>
<script src="{{ asset('assets/js/contracts/index.js') }}"></script>
@endsection
