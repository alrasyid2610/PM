@extends('layouts.app')
@section('page-title', 'Testing Matriks Samples')
@section('page-descrip', 'Kelola data Matriks Samples pengujian laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Testing Matriks Samples</li>
    {{-- <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('testing-units.index') }}">Testing Units</a>
    </li> --}}
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
    title="List of Testing Matriks Samples"
    create-route="testing-matriks-samples.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('testing-matriks-samples.data') }}",
        update: "{{ url('testing-matriks-samples') }}/",
        history: "{{ url('testing-matriks-samples') }}/",
        csrf: "{{ csrf_token() }}"
    }
</script>

<script src="{{ asset('assets/js/testing-matriks-samples/form.js') }}"></script>
<script src="{{ asset('assets/js/testing-matriks-samples/index.js') }}"></script>

@endsection