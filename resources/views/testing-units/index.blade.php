@extends('layouts.app')

@section('page-title', 'Testing Units')
@section('page-descrip', 'Kelola data satuan pengujian laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Testing Units</li>
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
    title="List of Units"
    create-route="testing-units.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('testing-units.data') }}",
        update: "{{ url('testing-units') }}/",
        csrf: "{{ csrf_token() }}",
        history: "{{ url('testing-units') }}/",
    }
</script>

<script src="{{ asset('assets/js/testing-units/form.js') }}"></script>
<script src="{{ asset('assets/js/testing-units/index.js') }}"></script>
@endsection