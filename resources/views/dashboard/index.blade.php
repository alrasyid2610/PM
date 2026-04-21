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

    {{-- STAT CARDS --}}
    <p class="section-label mb-2">Ringkasan Bisnis</p>
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon icon-navy">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Kantor Pusat</div>
                    <div class="stat-value" id="statKantorPusat">—</div>
                    <div class="stat-sub">Business Relations</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="fa-solid fa-sitemap"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Kantor Cabang</div>
                    <div class="stat-value" id="statKantorCabang">—</div>
                    <div class="stat-sub">Total Sites</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Total Sales Order</div>
                    <div class="stat-value" id="statTotalSo">—</div>
                    <div class="stat-sub">Semua status</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon icon-amber">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Work Orders</div>
                    <div class="stat-value" id="statTotalWo">—</div>
                    <div class="stat-sub">Total work order</div>
                </div>
            </div>
        </div>
    </div>

    {{-- SO STATUS --}}
    <p class="section-label mb-2">Status Sales Order</p>
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="so-card draft">
                <div class="so-status">Draft</div>
                <div class="so-value" id="soDraft">—</div>
                <div class="so-desc">Belum dikonfirmasi</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="so-card confirmed">
                <div class="so-status">Confirmed</div>
                <div class="so-value" id="soConfirmed">—</div>
                <div class="so-desc">Sudah dikonfirmasi</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="so-card on-progress">
                <div class="so-status">On Progress</div>
                <div class="so-value" id="soOnProgress">—</div>
                <div class="so-desc">Sedang dikerjakan</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="so-card done">
                <div class="so-status">Done</div>
                <div class="so-value" id="soDone">—</div>
                <div class="so-desc">Selesai</div>
            </div>
        </div>
    </div>

    {{-- CHARTS --}}
    <p class="section-label mb-2">Analitik</p>
    <div class="row g-3">
        <div class="col-md-8">
            <div class="chart-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="chart-title">Jumlah Sales Order per Bulan</div>
                        <div class="text-muted" style="font-size:12px;">
                            Tren SO sepanjang tahun
                        </div>
                    </div>
                    <select class="form-select form-select-sm w-auto" id="yearSelect">
                        <option value="{{ now()->year }}">{{ now()->year }}</option>
                        <option value="{{ now()->year - 1 }}">{{ now()->year - 1 }}</option>
                        <option value="{{ now()->year - 2 }}">{{ now()->year - 2 }}</option>
                    </select>
                </div>
                <canvas id="barChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-card">
                <div class="mb-3">
                    <div class="chart-title">Distribusi Status SO</div>
                    <div class="text-muted" style="font-size:12px;">
                        Proporsi per status
                    </div>
                </div>
                <div class="d-flex justify-content-center mb-3">
                    <canvas id="pieChart" width="160" height="160"></canvas>
                </div>
                <div id="pieLegend"></div>
            </div>
        </div>
    </div>

</section>
@endsection

@section('custom-script')
<script>
    window.route = {
        summary:    "{{ route('dashboard.summary') }}",
        soPerMonth: "{{ route('dashboard.soPerMonth') }}",
    }
</script>
<script src="{{ asset('assets/js/dashboard/index.js') }}"></script>
@endsection
