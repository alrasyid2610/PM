@extends('layouts.app')

@section('page-title', 'Sales Quotations')
@section('page-descrip', 'Kelola data Sales Quotation (penawaran)')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Sales Quotations</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 10h28l12 12v48a2 2 0 0 1-2 2H20a2 2 0 0 1-2-2V12a2 2 0 0 1 2-2z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M48 10v12h12" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M26 40h28M26 48h28M26 56h18" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Sales Quotations"
    create-route="sales-quotations.create"
    :with-history="true"
    :search-fields="[
        ['label' => 'No. SQ',          'value' => 'no_sq'],
        ['label' => 'Judul Order',     'value' => 'judul'],
        ['label' => 'Pelanggan',       'value' => 'pelanggan'],
        ['label' => 'Status',          'value' => 'status', 'type' => 'select', 'options' => [
            ['label' => 'All',        'value' => 'all'],
            ['label' => 'Draft',      'value' => 'draft'],
            ['label' => 'Terkirim',   'value' => 'terkirim'],
            ['label' => 'Diterima',   'value' => 'diterima'],
            ['label' => 'Ditolak',    'value' => 'ditolak'],
            ['label' => 'Cancel',     'value' => 'cancel'],
            ['label' => 'Expired',    'value' => 'expired'],
            ['label' => 'Deleted',    'value' => 'deleted'],
        ]],
    ]"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('sales-quotations.data') }}",
        history: "{{ url('sales-quotations') }}/",
        csrf:    "{{ csrf_token() }}",
        update:  "{{ url('sales-quotations') }}/",
    }
</script>
<script src="{{ asset('assets/js/sales-quotation/index.js') }}"></script>
<script src="{{ asset('assets/js/sales-quotation/form.js') }}"></script>
@endsection
