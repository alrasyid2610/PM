@extends('layouts.app')

@section('page-title', 'Dashboard')
@section('page-descrip', 'Ringkasan data sistem ERP Pramatek')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="8" y="8" width="28" height="28" rx="4" stroke="white" stroke-width="3"/>
        <rect x="44" y="8" width="28" height="28" rx="4" stroke="white" stroke-width="3"/>
        <rect x="8" y="44" width="28" height="28" rx="4" stroke="white" stroke-width="3"/>
        <rect x="44" y="44" width="28" height="28" rx="4" stroke="white" stroke-width="3"/>
    </svg>
@endsection

@section('content')
<section class="section">

    <div class="row row-cols-xl-6 row-cols-md-3 row-cols-2 g-3 mb-4">
        <x-dashboard-widget
            id="wg-so-outstanding"
            label="Outstanding SO"
            icon="fa-solid fa-file-lines"
            icon-color="#6366f1"
            icon-bg="#eef2ff"
            data-tab="so"
        />
        <x-dashboard-widget
            id="wg-wo-outstanding"
            label="Outstanding WO"
            icon="fa-solid fa-briefcase"
            icon-color="#0284c7"
            icon-bg="#e0f2fe"
            data-tab="wo"
        />
        <x-dashboard-widget
            id="wg-fwo-outstanding"
            label="Outstanding FWO"
            icon="fa-solid fa-helmet-safety"
            icon-color="#0d9488"
            icon-bg="#f0fdfa"
            data-tab="fwo"
        />
        <x-dashboard-widget
            id="wg-termin-outstanding"
            label="Outstanding Termin"
            icon="fa-solid fa-receipt"
            icon-color="#7c3aed"
            icon-bg="#f5f3ff"
            data-tab="termin"
        />
        <x-dashboard-widget
            id="wg-overdue"
            label="Overdue"
            sub="lewat tenggat"
            icon="fa-solid fa-flag"
            icon-color="#dc2626"
            icon-bg="#fef2f2"
        />
        {{-- <x-dashboard-widget
            id="wg-due-7"
            label="Jatuh Tempo 7 Hari"
            sub="perlu perhatian"
            icon="fa-solid fa-clock"
            icon-color="#d97706"
            icon-bg="#fffbeb"
        /> --}}
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-3">
                <button class="dw-tab-btn active" data-type="so">
                    <i class="fa-solid fa-file-lines me-1"></i> Sales Order
                </button>
                <button class="dw-tab-btn" data-type="wo">
                    <i class="fa-solid fa-briefcase me-1"></i> Work Order
                </button>
                <button class="dw-tab-btn" data-type="fwo">
                    <i class="fa-solid fa-helmet-safety me-1"></i> Fieldwork Order
                </button>
                <button class="dw-tab-btn" data-type="termin">
                    <i class="fa-solid fa-receipt me-1"></i> Termin
                </button>
            </div>

            <table id="dashboard-list-table" data-datatable-auto-columns="true"
                class="table table-hover align-middle w-100">
                <thead></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</section>
@endsection

@section('custom-script')
<script>
    window.route = {
        summary: "{{ route('dashboard.summary') }}",
        list:    "{{ route('dashboard.list') }}",
        wo:      "{{ url('work-orders') }}",
        fwo:     "{{ url('fieldworks') }}",
        so:      "{{ url('sales-orders') }}",
        termin:  "{{ url('termin') }}",
    }
</script>
<script src="{{ asset('assets/js/dashboard/index.js') }}"></script>
@endsection
