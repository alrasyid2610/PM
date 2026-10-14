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

    @unless($adaWidget)
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="fa-solid fa-gauge fa-2x d-block mb-2 opacity-25"></i>
                Belum ada widget dashboard untuk akun Anda.
                Hubungi admin untuk mengatur akses widget.
            </div>
        </div>
    @endunless

    {{-- Widget kartu angka (kpi) --}}
    @if(count($kpis))
    <div class="row row-cols-xl-6 row-cols-md-3 row-cols-2 g-3 mb-4">
        @foreach($kpis as $w)
            {{-- Kartu bisa diklik hanya bila tab tabel tujuannya juga diizinkan --}}
            @if(!empty($w['tab']) && in_array($w['tab'], $tableKeys))
                <x-dashboard-widget
                    :id="$w['slug']"
                    :label="$w['label']"
                    :icon="'fa-solid '.$w['icon']"
                    :icon-color="$w['color']"
                    :icon-bg="$w['bg']"
                    :data-tab="$w['tab']"
                    data-widget="{{ $w['slug'] }}"
                />
            @else
                <x-dashboard-widget
                    :id="$w['slug']"
                    :label="$w['label']"
                    :icon="'fa-solid '.$w['icon']"
                    :icon-color="$w['color']"
                    :icon-bg="$w['bg']"
                    data-widget="{{ $w['slug'] }}"
                />
            @endif
        @endforeach
    </div>
    @endif

    {{-- Widget grafik --}}
    @foreach($charts as $w)
    @if(($w['kind'] ?? '') === 'peta')
    {{-- Peta Indonesia per provinsi (Leaflet): peta di kiri, panel rincian di kanan --}}
    <div class="card mb-4" data-chart-widget="{{ $w['slug'] }}" data-chart-kind="peta">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold"><i class="fa-solid {{ $w['icon'] }} me-1" style="color:#1a56db;"></i> {{ $w['label'] }}</div>
                <button type="button" class="btn btn-sm btn-outline-secondary dw-peta-reset" data-no-disable>
                    <i class="fa-solid fa-expand me-1"></i> Reset tampilan
                </button>
            </div>
            {{-- Pilihan tampilan & filter peta --}}
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <select class="form-select form-select-sm dw-peta-metrik" style="width:150px;" title="Yang ditampilkan di peta" data-no-disable>
                    <option value="customer" selected>Jumlah customer</option>
                    <option value="site">Jumlah site</option>
                    <option value="nilai">Nilai SO (Rp)</option>
                </select>
                <select class="form-select form-select-sm dw-peta-cakupan" style="width:190px;" title="Semua customer, atau hanya yang punya SO (lokasi = Site Pemesan di SO)" data-no-disable>
                    <option value="semua" selected>Semua customer</option>
                    <option value="so">Hanya yang punya SO</option>
                </select>
                <select class="form-select form-select-sm dw-peta-tahun" style="width:150px;" title="Tahun SO" data-no-disable>
                    <option value="">Semua tahun SO</option>
                </select>
                <select class="form-select form-select-sm dw-peta-kategori" style="width:200px;" title="Kategori bisnis customer" data-no-disable>
                    <option value="">Semua kategori bisnis</option>
                </select>
                <select class="form-select form-select-sm dw-peta-mode d-none" style="width:190px;" title="Nilai SO sebelum / sesudah discount" data-no-disable>
                    <option value="bersih" selected>Bersih (setelah discount)</option>
                    <option value="kotor">Kotor (sebelum discount)</option>
                </select>
            </div>
            <div class="dw-chart-summary d-flex flex-wrap gap-3 align-items-center mb-2" style="font-size:12px;color:#64748b;"></div>
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="dw-peta-map" style="height:460px;border-radius:8px;border:1px solid #e2e8f0;background:#eaf2fb;"></div>
                </div>
                <div class="col-lg-4">
                    <div class="dw-peta-panel" style="height:460px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;padding:12px;font-size:13px;"></div>
                </div>
            </div>
            <div class="text-muted mt-2" style="font-size:10px;">
                Satu customer dengan site di beberapa provinsi dihitung di tiap provinsinya. Peta: denyherianto/indonesia-geojson-topojson-maps-with-38-provinces (CC BY 4.0), diubah (kode wilayah) · Leaflet.
            </div>
        </div>
    </div>
    @continue
    @endif
    <div class="card mb-4" data-chart-widget="{{ $w['slug'] }}" data-chart-kind="{{ $w['kind'] ?? 'bar_tahun' }}">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold"><i class="fa-solid {{ $w['icon'] }} me-1" style="color:#1a56db;"></i> {{ $w['label'] }}</div>
                <div class="d-flex gap-2">
                    @if(($w['kind'] ?? 'bar_tahun') === 'bar_tahun_nilai')
                        <select class="form-select form-select-sm dw-chart-mode" style="width:200px;" data-no-disable>
                            <option value="bersih" selected>Bersih (setelah discount)</option>
                            <option value="kotor">Kotor (sebelum discount)</option>
                            <option value="semua">Semua (kotor + bersih)</option>
                        </select>
                    @endif
                    @if(in_array($w['kind'] ?? 'bar_tahun', ['bar_tahun', 'bar_tahun_nilai']))
                        <select class="form-select form-select-sm dw-chart-year" style="width:110px;" data-no-disable></select>
                    @endif
                </div>
            </div>
            {{-- Ringkasan & legenda di luar canvas grafik supaya tidak menimpa sumbu/label --}}
            <div class="dw-chart-summary d-flex flex-wrap gap-3 align-items-center mb-2" style="font-size:12px;color:#64748b;"></div>
            <div class="dw-chart-box" style="min-height:260px;"></div>
        </div>
    </div>
    @endforeach

    {{-- Widget tabel (satu kartu, tab sesuai widget yang diizinkan) --}}
    @if(count($tables))
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-3">
                @foreach($tables as $i => $w)
                    <button class="dw-tab-btn {{ $i === 0 ? 'active' : '' }}" data-type="{{ $w['key'] }}">
                        <i class="fa-solid {{ $w['icon'] }} me-1"></i> {{ $w['label'] }}
                    </button>
                @endforeach
            </div>

            <table id="dashboard-list-table" data-datatable-auto-columns="true"
                class="table table-hover align-middle w-100">
                <thead></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    @endif

</section>
@endsection

@section('custom-script')
<style>
    /* Label angka di tengah provinsi pada Peta Customer (tidak menangkap klik) */
    .dw-peta-lbl-wrap { background: transparent; border: 0; }
    .dw-peta-lbl {
        display: inline-block; transform: translate(-50%, -50%); pointer-events: none; white-space: nowrap;
        font-size: 11px; font-weight: 700; color: #0f172a; line-height: 1;
        text-shadow: 0 0 3px #fff, 0 0 3px #fff, 0 0 2px #fff;
    }
</style>
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
@if(collect($charts)->contains(fn ($c) => ($c['kind'] ?? '') === 'peta'))
<link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.min.css') }}">
<script src="{{ asset('assets/vendor/leaflet/leaflet.min.js') }}"></script>
@endif
<script>
    window.route = {
        widget:  "{{ url('dashboard/widget') }}",
        list:    "{{ route('dashboard.list') }}",
        wo:      "{{ url('work-orders') }}",
        fwo:     "{{ url('fieldworks') }}",
        so:      "{{ url('sales-orders') }}",
        sq:      "{{ url('sales-quotations') }}",
        termin:  "{{ url('termin') }}",
    }
    window.route.petaGeo = "{{ asset('assets/vendor/indonesia-geo/indonesia-38-provinces.geojson') }}";
    window.route.customerDetail = "{{ url('business-relations') }}";
    window.dashboardFirstTab =@json($tables[0]['key'] ?? null);
</script>
<script src="{{ asset('assets/js/dashboard/index.js') }}?v={{ @filemtime(public_path('assets/js/dashboard/index.js')) }}"></script>
@endsection
