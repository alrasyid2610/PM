@extends('layouts.app')

@section('page-title', 'User Management')
@section('page-descrip', 'Kelola pengguna dan hak akses sistem')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">User Management</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="40" cy="28" r="14" stroke="white" stroke-width="3"/>
        <path d="M12 68c0-15.464 12.536-28 28-28s28 12.536 28 28" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Users"
    create-route="users.create"
    add-label="Tambah User"
    :with-history="false"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('users.data') }}",
        update:  "{{ url('users') }}/",
        history: "{{ url('users') }}/",
        csrf:    "{{ csrf_token() }}",
    }
</script>
<script src="{{ asset('assets/js/users/form.js') }}"></script>
<script src="{{ asset('assets/js/users/index.js') }}"></script>
@endsection

