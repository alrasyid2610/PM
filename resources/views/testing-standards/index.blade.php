@extends('layouts.app')

@section('page-title', 'Testing Standards')
@section('page-descrip', 'Kelola data Standards pengujian laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Testing Standards</li>
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
    title="List of Testing Standards"
    create-route="testing-standards.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('testing-standards.data') }}",
        update: "{{ url('testing-standards') }}/",
        deleteAttachment: "{{ route('testing-standards.delete-attachment') }}",
        history: "{{ url('testing-standards') }}/",
        csrf: "{{ csrf_token() }}"
    }
</script>

<script src="{{ asset('assets/js/testing-standards/form.js') }}"></script>
<script src="{{ asset('assets/js/testing-standards/index.js') }}"></script>
@endsection