@extends('layouts.app')

@section('page-title', 'Export Data Points')
@section('page-descrip', 'Unduh 1 file Excel berisi seluruh data Kelompok Matriks, Matriks Sample, Testing Standard, Testing Points, dan Testing Item')

@section('breadcrumb')
    <li class="breadcrumb-item">Report</li>
    <li class="breadcrumb-item active" aria-current="page">Export Data Points</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 12h28l12 12v44H20V12z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M48 12v12h12" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M40 48v-14M33 41l7-7 7 7" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M32 52h16" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <div class="row g-3">
        <div class="col-12">
            <x-section-card icon="fa-file-excel" color="icon-navy" title="Export Data Points"
                subtitle="1 baris Excel = 1 Testing Item, kolom Kelompok/Matriks/Standard/Testing Point ikut digabung di tiap baris">
                <p class="mb-2">
                    File berisi seluruh <strong>Kelompok Matriks Sample</strong>, <strong>Matriks Sample</strong>,
                    <strong>Testing Standard</strong>, <strong>Testing Points</strong>, dan <strong>Testing Item</strong>
                    yang masih aktif &amp; belum dihapus. Testing Point ikut semua status approval
                    (Draft / Menunggu Approval / Disetujui) — laporan ini untuk referensi data lengkap,
                    bukan daftar yang boleh dipakai di BOQ.
                </p>
                <p class="mb-3 text-muted" style="font-size:13px;">
                    Total <strong>{{ number_format($total, 0, ',', '.') }}</strong> baris Testing Item akan diekspor.
                </p>
                <a href="{{ route('export-data-points.export') }}" class="btn btn-primary">
                    <i class="fa-solid fa-download me-1"></i> Export Excel
                </a>
            </x-section-card>
        </div>
    </div>
</section>
@endsection
