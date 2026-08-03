@extends('layouts.app')
@section('page-title', 'Kalender')

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="10" y="18" width="60" height="52" rx="5" stroke="white" stroke-width="3"/>
        <path d="M10 32h60" stroke="white" stroke-width="3"/>
        <path d="M26 10v16M54 10v16" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <rect x="20" y="42" width="12" height="10" rx="2" fill="white" opacity=".7"/>
        <rect x="34" y="42" width="12" height="10" rx="2" fill="white"/>
        <rect x="48" y="42" width="12" height="10" rx="2" fill="white" opacity=".5"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <div class="card" style="border-radius:12px;box-shadow:0 1px 8px rgba(0,0,0,.07);">
        <div class="card-body p-3">

            {{-- Toolbar atas --}}
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <button id="btnPrev" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button id="btnToday" class="btn btn-sm btn-outline-primary px-3">Hari Ini</button>
                <button id="btnNext" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
                <span id="calTitle" class="fw-semibold ms-1" style="font-size:15px;color:#1e3a5f;min-width:160px;"></span>

                <div class="ms-auto d-flex gap-2">
                    <button id="btnViewMonth" class="btn btn-sm btn-primary">Bulan</button>
                    <button id="btnViewWeek"  class="btn btn-sm btn-outline-secondary">Minggu</button>
                </div>
            </div>

            {{-- Slicer --}}
            <div class="d-flex flex-wrap gap-2 mb-3">

                {{-- Tipe data --}}
                <div class="d-flex align-items-center gap-3 flex-wrap px-3 py-2 flex-grow-1"
                     style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;">
                    <span class="fw-semibold text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Tipe</span>

                    <label class="d-flex align-items-center gap-2 mb-0" style="cursor:pointer;user-select:none;">
                        <input type="checkbox" class="slicer-type" value="fwo" checked
                               style="accent-color:#3b82f6;width:15px;height:15px;cursor:pointer;">
                        <span style="width:10px;height:10px;border-radius:2px;background:#3b82f6;display:inline-block;"></span>
                        <span style="width:10px;height:10px;border-radius:2px;background:#10b981;display:inline-block;"></span>
                        FWO
                    </label>

                    <div style="width:1px;height:18px;background:#e2e8f0;"></div>

                    <label class="d-flex align-items-center gap-2 mb-0" style="cursor:pointer;user-select:none;">
                        <input type="checkbox" class="slicer-type" value="wo"
                               style="accent-color:#f59e0b;width:15px;height:15px;cursor:pointer;">
                        <span style="width:10px;height:10px;border-radius:2px;background:#f59e0b;display:inline-block;"></span>
                        <span style="width:10px;height:10px;border-radius:2px;background:#8b5cf6;display:inline-block;"></span>
                        WO
                    </label>

                    <div style="width:1px;height:18px;background:#e2e8f0;"></div>

                    <label class="d-flex align-items-center gap-2 mb-0" style="cursor:pointer;user-select:none;">
                        <input type="checkbox" class="slicer-type" value="so"
                               style="accent-color:#ec4899;width:15px;height:15px;cursor:pointer;">
                        <span style="width:10px;height:10px;border-radius:2px;background:#ec4899;display:inline-block;"></span>
                        <span style="width:10px;height:10px;border-radius:2px;background:#6b7280;display:inline-block;"></span>
                        SO
                    </label>
                </div>

                {{-- Status --}}
                <div class="d-flex align-items-center gap-3 px-3 py-2"
                     style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;">
                    <span class="fw-semibold text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Status</span>

                    <label class="d-flex align-items-center gap-2 mb-0" style="cursor:pointer;user-select:none;">
                        <input type="checkbox" class="slicer-status" value="aktif" checked
                               style="accent-color:#0891b2;width:15px;height:15px;cursor:pointer;">
                        Aktif
                    </label>

                    <label class="d-flex align-items-center gap-2 mb-0" style="cursor:pointer;user-select:none;">
                        <input type="checkbox" class="slicer-status" value="completed" checked
                               style="accent-color:#10b981;width:15px;height:15px;cursor:pointer;">
                        Selesai
                    </label>
                </div>

            </div>

            {{-- Search --}}
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <select id="searchBy" class="form-select form-select-sm" style="width:auto;min-width:160px;">
                    <option value="">-- Cari berdasarkan --</option>
                    <option value="no">Nomor (FWO/WO/SO)</option>
                    <option value="judul">Judul Pekerjaan</option>
                    <option value="pelanggan">Pelanggan</option>
                    <option value="site">Site / Lokasi</option>
                </select>
                <input type="text" id="searchQ" class="form-control form-control-sm" placeholder="Kata kunci..." style="max-width:240px;" disabled>
                <button id="btnSearch" class="btn btn-sm btn-primary" disabled>
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Cari
                </button>
                <button id="btnClearSearch" class="btn btn-sm btn-outline-secondary" style="display:none;">
                    <i class="fa-solid fa-xmark me-1"></i> Reset
                </button>
                <span id="searchBadge" style="display:none;font-size:12px;color:#0891b2;background:#e0f2fe;padding:2px 10px;border-radius:20px;"></span>
            </div>

            {{-- Calendar --}}
            <div id="calendarContainer" style="position:relative;min-height:500px;"></div>

        </div>
    </div>
</section>

{{-- Tooltip --}}
<div id="calTooltip" style="display:none;position:fixed;z-index:9999;background:#fff;
    border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;
    box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:220px;max-width:300px;font-size:13px;pointer-events:none;">
    <div id="calTooltipContent"></div>
</div>
@endsection

@section('custom-script')
<script src="{{ asset('assets/vendor/daypilot/daypilot-javascript.min.js') }}"></script>
<script src="{{ asset('assets/js/calendar/index.js') }}"></script>
@endsection
