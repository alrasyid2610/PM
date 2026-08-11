@extends('layouts.app')

@section('page-title', 'Kepemilikan')
@section('page-descrip', 'Kelola data kepemilikan badan usaha')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Kepemilikan</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M40 10 14 26v6h52v-6L40 10z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <line x1="18" y1="36" x2="18" y2="60" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="32" y1="36" x2="32" y2="60" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="48" y1="36" x2="48" y2="60" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="62" y1="36" x2="62" y2="60" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="14" y1="66" x2="66" y2="66" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Kepemilikan"
    create-route="kepemilikan.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('kepemilikan.data') }}",
        update:  "{{ url('kepemilikan') }}/",
        csrf:    "{{ csrf_token() }}",
        history: "{{ url('kepemilikan') }}/",
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
<script src="{{ asset('assets/js/kepemilikan/form.js') }}"></script>
<script src="{{ asset('assets/js/kepemilikan/index.js') }}"></script>
@endsection
