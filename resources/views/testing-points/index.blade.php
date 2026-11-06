@extends('layouts.app')

@section('page-title', 'Testing Points')
@section('page-descrip', 'Kelola data Testing Points pengujian laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Testing Points</li>
    {{-- <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('testing-units.index') }}">Testing Units</a>
    </li> --}}
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M28 8h4v28l-16 28h48L48 36V8h4" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M28 8h24" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <circle cx="32" cy="56" r="3" fill="white"/>
        <circle cx="44" cy="62" r="2" fill="white"/>
        <circle cx="38" cy="52" r="2" fill="white"/>
    </svg>
@endsection

@section('content')
<x-crud-index title="List of Testing Points" create-route="testing-points.create" :with-history="true" />

{{-- Modal: Salin Testing Point --}}
<div class="modal fade" id="modalCloneTestingPoint" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title"><i class="fa-solid fa-copy me-2 text-primary"></i> Salin Testing Point</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalCloneTpBody">
                <div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...</div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnConfirmCloneTp" class="btn btn-primary btn-sm" disabled>
                    <i class="fa-solid fa-copy me-1"></i> Buat Salinan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Tambah / Ubah Paket (tab "Paket" detail Testing Point) --}}
<div class="modal fade" id="modalPaket" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="modalPaketTitle"><i class="fa-solid fa-boxes-stacked me-2" style="color:#b45309;"></i> Tambah Paket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="paketId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Paket <span class="text-danger">*</span></label>
                    <input type="text" id="paketNama" class="form-control form-control-sm" maxlength="255" placeholder="cth: parameter 20">
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold small text-muted">
                        <i class="fa-solid fa-list-check me-1"></i> Pilih item untuk paket ini:
                        <span id="paketCount" class="pm-badge pm-badge--blue ms-1" style="font-size:10px;">0 dipilih</span>
                    </span>
                    <div class="d-flex gap-3">
                        <a href="#" id="paketCheckAll" class="small text-decoration-none">Pilih Semua</a>
                        <a href="#" id="paketUncheckAll" class="small text-decoration-none text-secondary">Hapus Semua</a>
                    </div>
                </div>
                <div class="mb-2">
                    <input type="text" id="paketItemSearch" class="form-control form-control-sm" placeholder="Cari item...">
                </div>
                <div id="paketItemsList" style="max-height:340px;overflow-y:auto;">
                    <div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat item...</div>
                </div>
                <div id="paketSearchEmpty" class="text-center text-muted py-3 d-none" style="font-size:13px;">
                    <i class="fa-solid fa-magnifying-glass me-1 opacity-50"></i> Tidak ada item yang cocok
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnSavePaket" class="btn btn-primary btn-sm" disabled>
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Paket
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('style')
<style>
    #modalPaket .modal-item-row { border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 14px; margin-bottom: 6px; transition: background .1s; }
    #modalPaket .modal-item-row:hover { background: #f8fafc; }
    #modalPaket .item-meta-badge, #pointPaketWrap .item-meta-badge { font-size: 11px; padding: 2px 8px; border-radius: 20px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; white-space: nowrap; }
</style>
@endsection

@section('custom-script')
{{-- Dragula: drag-and-drop reorder baris Testing Items (lihat tableForm.js) --}}
<link href="{{ asset('assets/vendor/dragula/dragula.min.css') }}" rel="stylesheet">
<script src="{{ asset('assets/vendor/dragula/dragula.min.js') }}"></script>

{{-- Catatan: #row-template yang benar-benar dipakai di-render inline oleh
     renderForm() di testing-points/form.js (di dalam #detailContent, dimuat
     lewat AJAX). Template statis yang dulu ada di sini sudah tidak
     dipakai/basi (duplikat id="row-template" & sudah ketinggalan struktur —
     tidak ada kolom drag handle/hidden status[]) — dihapus 2026-09-09 supaya
     tidak jadi duplikat ID yang membingungkan di kemudian hari. --}}
<script>
    window.route = {
        data: "{{ route('testing-points.data') }}",
        update: "{{ url('testing-points') }}/",
        deleteAttachment: "{{ route('testing-points.delete-attachment') }}",
        history: "{{ url('testing-points') }}/",
        csrf: "{{ csrf_token() }}",

        // Paket Testing Point
        paket:            "{{ url('testing-points') }}/",

        // Clone Testing Point
        itemsByPoint:     "{{ url('testing-items/by-point') }}/",
        select2Standard:  "{{ route('testing-standards.select2') }}",
        createStandard:   "{{ route('testing-standards.create') }}",
        select2Matriks:   "{{ route('testing-matriks-samples.select2') }}",
        createMatriks:    "{{ route('testing-matriks-samples.create') }}",
        select2Parameter: "{{ route('testing-parameters.select2') }}",
        createParameter:  "{{ route('testing-parameters.create') }}",
        select2Unit:      "{{ route('testing-units.select2') }}",
        createUnit:       "{{ route('testing-units.create') }}",
    }
</script>

<script src="{{ asset('assets/js/testing-points/form.js') }}"></script>
<script src="{{ asset('assets/js/testing-points/index.js') }}"></script>
@endsection