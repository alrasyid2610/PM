@extends('layouts.app')

@section('page-title', 'Office')
@section('page-descrip', 'Kelola data Office Pramatek')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Office</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 68V16h28l12 12v40H20z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M48 16v12h12" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <line x1="28" y1="34" x2="44" y2="34" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="28" y1="44" x2="52" y2="44" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="28" y1="54" x2="52" y2="54" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Office"
    create-route="office.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('office.data') }}",
        update:  "{{ url('office') }}/",
        csrf:    "{{ csrf_token() }}",
        history: "{{ url('office') }}/",
    }
</script>
<script src="{{ asset('assets/js/office/form.js') }}"></script>
<script src="{{ asset('assets/js/office/index.js') }}"></script>
@endsection
