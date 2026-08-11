@extends('layouts.app')

@section('page-title', 'Entitas')
@section('page-descrip', 'Kelola data entitas badan usaha')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Entitas</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="16" y="10" width="48" height="60" rx="4" stroke="white" stroke-width="3"/>
        <line x1="26" y1="26" x2="54" y2="26" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="26" y1="36" x2="54" y2="36" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="26" y1="46" x2="42" y2="46" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Entitas"
    create-route="entitas.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('entitas.data') }}",
        update:  "{{ url('entitas') }}/",
        csrf:    "{{ csrf_token() }}",
        history: "{{ url('entitas') }}/",
    }

    window.datatableHeaderLabels = { is_aktif: 'Status' };

    window.datatableColumnRenderers = {
        is_aktif: function (data) {
            return data == 1
                ? '<span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:11px;font-weight:600;">Aktif</span>'
                : '<span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:600;">Tidak Aktif</span>';
        },
    };
</script>
<script src="{{ asset('assets/js/entitas/form.js') }}"></script>
<script src="{{ asset('assets/js/entitas/index.js') }}"></script>
@endsection
