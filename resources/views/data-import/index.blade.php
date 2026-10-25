@extends('layouts.app')

@section('page-title', 'Import Data')
@section('page-descrip', 'Import data dari file Excel — pilih jenis data, download template, isi, lalu periksa sebelum diproses')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Import Data</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 12h28l12 12v44H20V12z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M48 12v12h12" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M40 38v20M32 50l8 8 8-8" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <div class="row g-3">

        <div class="col-12">
            <x-section-card icon="fa-list-check" color="icon-navy" title="1. Pilih Data yang Mau Diimport" subtitle="Template Excel dibuat sesuai aturan data yang dipilih; pilihan tambahan (mis. Site) menyesuaikan jenis datanya">
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label required">Jenis Data</label>
                        <select id="importJenis" class="form-select">
                            <option value=""></option>
                            @foreach($jenis as $j)
                                <option value="{{ $j['key'] }}">{{ $j['label'] }}</option>
                            @endforeach
                        </select>
                        <div id="importJenisNote" class="form-text" style="display:none;"></div>
                    </div>
                    {{-- Pilihan konteks (mis. Site) dirender JS sesuai jenis data yang dipilih --}}
                    <div id="importContextWrap" class="col-md-6 col-12" style="display:none;"></div>
                    <div class="col-12">
                        <a href="#" id="btnDownloadTemplate" class="btn btn-outline-primary btn-sm disabled" tabindex="-1" aria-disabled="true">
                            <i class="fa-solid fa-download me-1"></i> Download Template
                        </a>
                    </div>
                </div>
            </x-section-card>
        </div>

        <div class="col-12">
            <x-section-card icon="fa-file-excel" color="icon-blue" title="2. Upload dan Periksa File" subtitle="File diperiksa dulu — belum ada data yang disimpan sampai Anda menekan Proses Import">
                <div class="row g-3">
                    <div class="col-md-8 col-12">
                        <input type="file" id="importFile" class="form-control" accept=".xlsx" disabled>
                    </div>
                    <div class="col-md-4 col-12">
                        <button type="button" id="btnPreview" class="btn btn-primary btn-sm" disabled>
                            <i class="fa-solid fa-magnifying-glass-chart me-1"></i> Periksa File
                        </button>
                    </div>
                </div>
            </x-section-card>
        </div>

        <div class="col-12" id="importPreviewWrap" style="display:none;">
            <x-section-card icon="fa-clipboard-check" color="icon-blue" title="3. Hasil Pemeriksaan">
                <div id="importPreviewBody"></div>
            </x-section-card>
        </div>

        <div class="col-12" id="importResultWrap" style="display:none;">
            <x-section-card icon="fa-circle-check" color="icon-navy" title="4. Hasil Import">
                <div id="importResultBody"></div>
            </x-section-card>
        </div>

    </div>
</section>
@endsection

@section('custom-script')
<script>
    window.route = {
        csrf:     "{{ csrf_token() }}",
        options:  "{{ url('data-import/options') }}",
        template: "{{ route('data-import.template') }}",
        preview:  "{{ route('data-import.preview') }}",
        process:  "{{ route('data-import.process') }}",
        result:   "{{ url('data-import/result') }}",
    };
    window.importJenis = @json($jenis->keyBy('key'));
</script>
<script src="{{ asset('assets/js/data-import/index.js') }}?v={{ @filemtime(public_path('assets/js/data-import/index.js')) }}"></script>
@endsection
