@extends('layouts.app')

@section('page-title', 'Sales Orders')
@section('page-descrip', 'Kelola data Sales Orders')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Sales Orders</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M28 8h4v28l-16 28h48L48 36V8h4" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M28 8h24" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <circle cx="32" cy="56" r="3" fill="white"/>
        <circle cx="44" cy="62" r="2" fill="white"/>
        <circle cx="38" cy="52" r="2" fill="white"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Sales Orders"
    create-route="sales-orders.create"
    :with-history="true"
/>
@endsection


@section('custom-script')
<script>
    window.route = {
        data:       "{{ route('sales-orders.data') }}",
        history:    "{{ url('sales-orders') }}/",
        csrf:       "{{ csrf_token() }}",
        update:     "{{ url('sales-orders') }}/",
        woProgress: "{{ url('sales-orders') }}/",
        siteSelect2: "{{ url('business-relations/sites/select2') }}",
    }
</script>
<script src="{{ asset('assets/js/sales-order/index.js') }}"></script>
<script src="{{ asset('assets/js/sales-order/form.js') }}"></script>
@endsection