@extends('layouts.app')

@section('page-title', 'Testing Parameters')
@section('page-descrip', 'Kelola data Parameters laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Testing Parameters</li>
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
    title="List of Parameters"
    create-route="testing-parameters.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('testing-parameters.data') }}",
        update: "{{ url('testing-parameters') }}/",
        history: "{{ url('testing-parameters') }}/",
        deleteAttachment: "{{ route('testing-parameters.delete-attachment') }}",
        csrf: "{{ csrf_token() }}"
    }
</script>
<script src="{{ asset('assets/js/testing-parameters/form.js') }}"></script>
<script src="{{ asset('assets/js/testing-parameters/index.js') }}"></script>
@endsection