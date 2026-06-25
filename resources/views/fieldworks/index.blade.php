@extends('layouts.app')

@section('page-title', 'Fieldwork')
@section('page-descrip', 'Data kegiatan pekerjaan lapangan')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Fieldwork</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="10" y="30" width="60" height="40" rx="4" stroke="white" stroke-width="3"/>
        <path d="M28 30V22a12 12 0 0 1 24 0v8" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <circle cx="40" cy="50" r="5" fill="white"/>
        <path d="M40 55v8" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List Fieldwork"
    :with-history="true"
/>

{{-- Modal: Tambah Fieldwork BOQ Section --}}
<div class="modal fade" id="modalAddFwoBoq" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-layer-group me-2 text-primary"></i> Tambah BOQ Item
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label fw-semibold">BOQ Item <span class="text-danger">*</span></label>
                    <select id="selectFwoBoq" style="width:100%"></select>
                </div>

                <div id="fwoBoqModalLoading" class="text-center text-muted py-3 d-none">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat items...
                </div>

                <div id="fwoBoqModalEmpty" class="text-center text-muted py-3 d-none">
                    <i class="fa-solid fa-inbox me-1 opacity-50"></i> Section ini tidak memiliki item
                </div>

                <div id="fwoBoqModalPreview" class="d-none">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm text-muted mb-1">
                                Qty <span id="fwoBoqMaxHint" class="fw-normal"></span>
                            </label>
                            <input type="number" id="fwoBoqQtyInput" class="form-control form-control-sm" min="1" placeholder="0">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                            <input type="text" id="fwoBoqKetInput" class="form-control form-control-sm" placeholder="opsional">
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="text-muted small fw-semibold">
                            <i class="fa-solid fa-list-check me-1"></i> Items (otomatis dari BOQ)
                        </span>
                        <button type="button" id="btnToggleModalItems"
                            class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" title="Lihat items">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div id="fwoBoqModalItemsList" style="display:none;"></div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnConfirmFwoBoq" class="btn btn-primary btn-sm" disabled>
                    <i class="fa-solid fa-check me-1"></i> Tambah Item
                </button>
            </div>
        </div>
    </div>
</div>
{{-- Modal: Bulk Tambah BOQ (direct mode) --}}
<div class="modal fade" id="modalBulkAddFwoBoq" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-clipboard-list me-2 text-success"></i> Tambah / Edit BOQ
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="bulkBoqLoading" class="text-center text-muted py-4">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data BOQ...
                </div>
                <div id="bulkBoqEmpty" class="text-center text-muted py-4 d-none">
                    <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                    Belum ada BOQ terdaftar di Work Order ini
                </div>
                <div id="bulkBoqList" class="d-none"></div>
            </div>
            <div class="modal-footer">
                <small class="text-muted me-auto">Item dengan Qty = 0 tidak akan disimpan</small>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnSaveBulkBoq" class="btn btn-success btn-sm" disabled>
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:        "{{ route('fieldworks.data') }}",
        update:      "{{ url('fieldworks') }}/",
        history:     "{{ url('fieldworks') }}/",
        csrf:        "{{ csrf_token() }}",
        woSelect2:       "{{ route('work-orders.select2') }}",
        siteSelect2:     "{{ route('business-relation-sites.select2') }}",
        picSelect2:      "{{ route('business-relation-contacts.select2') }}",
        fwoBoqByFwo:      "{{ url('fieldwork-boq/by-fwo') }}/",
        fwoBoqUpdate:     "{{ url('fieldwork-boq') }}/",
        boqSelect2ByWo:   "{{ url('boq/select2-by-wo') }}/",
        boqSectionItems:  "{{ url('boq') }}/",
        personelUpdate:   "{{ url('fieldworks') }}/",
        fwoComplete:      "{{ url('fieldworks') }}/",
        fwoAttachments:   "{{ url('fieldworks') }}/",
        userSelect2:      "{{ route('users.select2') }}",
    }
</script>

<script src="{{ asset('assets/js/fieldworks/form.js') }}"></script>
<script src="{{ asset('assets/js/fieldworks/index.js') }}"></script>
@endsection
