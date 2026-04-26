@extends('layouts.app')

@section('page-title', 'Grup Menu')
@section('page-descrip', 'Kelola grup menu dan hak akses per departemen')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Grup Menu</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="10" y="20" width="60" height="10" rx="3" stroke="white" stroke-width="3"/>
        <rect x="10" y="38" width="60" height="10" rx="3" stroke="white" stroke-width="3"/>
        <rect x="10" y="56" width="40" height="10" rx="3" stroke="white" stroke-width="3"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Grup Menu"
    create-route="menu-groups.create"
    add-label="Tambah Grup"
    :with-history="false"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('menu-groups.data') }}",
        update:  "{{ url('menu-groups') }}/",
        history: "{{ url('menu-groups') }}/",
        csrf:    "{{ csrf_token() }}",
    }
</script>
<script src="{{ asset('assets/js/menu-groups/form.js') }}"></script>
<script src="{{ asset('assets/js/menu-groups/index.js') }}"></script>
@endsection

