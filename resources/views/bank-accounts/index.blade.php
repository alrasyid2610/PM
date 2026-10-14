@extends('layouts.app')

@section('page-title', 'Bank Account')
@section('page-descrip', 'Kelola master data rekening bank')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Bank Account</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M14 32L40 16l26 16" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <line x1="20" y1="36" x2="20" y2="56" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="34" y1="36" x2="34" y2="56" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="46" y1="36" x2="46" y2="56" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="60" y1="36" x2="60" y2="56" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="14" y1="62" x2="66" y2="62" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Bank Account"
    create-route="bank-accounts.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('bank-accounts.data') }}",
        update:  "{{ url('bank-accounts') }}/",
        csrf:    "{{ csrf_token() }}",
        history: "{{ url('bank-accounts') }}/",
    }

    window.datatableHeaderLabels = {
        bank:        'Bank',
        no_rekening: 'No. Rekening',
        atas_nama:   'Atas Nama',
        keterangan:  'Keterangan',
        is_utama:    'Utama',
    };
</script>
<script src="{{ asset('assets/js/bank-accounts/form.js') }}"></script>
<script src="{{ asset('assets/js/bank-accounts/index.js') }}"></script>
@endsection
