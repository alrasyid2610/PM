@extends('layouts.app')

@section('page-title', 'Commercial Buildings')
@section('page-descrip', 'Kelola data Commercial Buildings')

@section('breadcrumb')
    {{-- <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('testing-units.index') }}">Testing Units</a>
    </li> --}}
    <li class="breadcrumb-item active" aria-current="page">Commercial Buildings</li>
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
    title="List of Commercial Buildings"
    create-route="commercial-buildings.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('commercial-buildings.data') }}",
        update: "{{ url('commercial-buildings') }}/",
        csrf: "{{ csrf_token() }}",
        history: "{{ url('commercial-buildings') }}/",
    }
</script>

<script src="{{ asset('assets/js/commercial-building/form.js') }}"></script>
<script src="{{ asset('assets/js/commercial-building/index.js') }}"></script>
@endsection