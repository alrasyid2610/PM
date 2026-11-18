@extends('layouts.app')

@section('page-title', 'Master Label Budget')
@section('page-descrip', 'Kelola daftar label Budget Plan (WO, FWO, SQ WO, SQ FWO)')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Master Label Budget</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M10 40V14h26l34 34-26 26L10 40z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <circle cx="24" cy="28" r="4" stroke="white" stroke-width="3"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Master Label Budget"
    create-route="budget-label.create"
    :with-history="true"
    :search-fields="(new \App\Support\ListFilter\Sets\BudgetLabelFilters)->forView()"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('budget-label.data') }}",
        update:  "{{ url('budget-label') }}/",
        history: "{{ url('budget-label') }}/",
        csrf:    "{{ csrf_token() }}"
    }

    window.datatableHeaderLabels = {
        nama: 'Label',
        urutan: 'Urutan',
        is_aktif: 'Status',
        dipakai: 'Dipakai (plan)',
    };

    window.datatableColumnRenderers = {
        is_aktif: function (data) {
            return data === 'Aktif'
                ? '<span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:11px;font-weight:600;">Aktif</span>'
                : '<span class="badge rounded-pill" style="background:#f1f5f9;color:#475569;font-size:11px;font-weight:600;">Tidak Aktif</span>';
        },
    };
</script>
<script src="{{ asset('assets/js/budget-label/form.js') }}?v={{ @filemtime(public_path('assets/js/budget-label/form.js')) }}"></script>
<script src="{{ asset('assets/js/budget-label/index.js') }}?v={{ @filemtime(public_path('assets/js/budget-label/index.js')) }}"></script>
@endsection
