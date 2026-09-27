@extends('layouts.app')

@section('page-title', 'SQ Fieldworks')
@section('page-descrip', 'Kelola Fieldwork estimasi Sales Quotation')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">SQ Fieldworks</li>
@endsection

@section('style')
<style>
    /* SQ berstatus Final/Completed/Cancel — sembunyikan semua aksi ubah */
    .sq-locked .btn-kelola-sq-fwo-boq, .sq-locked .btn-sq-fwo-budget-add,
    .sq-locked .btn-edit-sq-fwo-budget, .sq-locked .btn-remove-sq-fwo-budget { display: none !important; }
</style>
@endsection

@section('content')
<x-crud-index
    title="List of SQ Fieldworks"
    :with-history="true"
    :search-fields="[
        ['label' => 'No. FWO',          'value' => 'no_sq_fwo'],
        ['label' => 'No. WO',           'value' => 'no_sq_wo'],
        ['label' => 'No. SQ',           'value' => 'no_sq'],
        ['label' => 'Judul Pekerjaan',  'value' => 'judul'],
    ]"
/>

{{-- Modal iframe: Create SQ Fieldwork (pola sama dengan "+ WO" di SQ) --}}
<div class="modal fade" id="modalCreateSqFwo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="height:90vh;">
            <div class="modal-header py-2 px-3" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <span class="fw-semibold" style="font-size:14px;">
                    <i class="fa-solid fa-helmet-safety me-2" style="color:#1a56db;"></i>
                    Tambah SQ Fieldwork
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    onclick="document.getElementById('iframeCreateSqFwo').src=''"></button>
            </div>
            <div class="modal-body p-0" style="overflow:hidden;position:relative;">
                <div id="loaderCreateSqFwo" class="iframe-loading-overlay">
                    <i class="fa-solid fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
                    <span>Memuat form...</span>
                </div>
                <iframe id="iframeCreateSqFwo" src="" frameborder="0"
                    style="width:100%;height:100%;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Bulk Tambah/Edit BOQ FWO — replikasi #modalBulkAddFwoBoq di fieldworks/index.blade.php --}}
<div class="modal fade" id="modalBulkAddSqFwoBoq" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-clipboard-list me-2 text-success"></i> Tambah / Edit BOQ
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="sqFwoBulkBoqLoading" class="text-center text-muted py-4">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data BOQ...
                </div>
                <div id="sqFwoBulkBoqEmpty" class="text-center text-muted py-4 d-none">
                    <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                    Belum ada BOQ terdaftar di Work Order ini
                </div>
                <div id="sqFwoBulkBoqList" class="d-none"></div>
            </div>
            <div class="modal-footer">
                <small class="text-muted me-auto">Item dengan Qty = 0 tidak akan disimpan</small>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnSaveSqFwoBulkBoq" class="btn btn-success btn-sm" disabled>
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Tambah/Edit Budget Plan FWO — replikasi #sqWoBudgetPlanModal --}}
<div class="modal fade" id="sqFwoBudgetPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content">
            <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                <h6 class="modal-title mb-0" id="sqFwoBudgetPlanModalLabel">
                    <i class="fa-solid fa-wallet me-2" style="color:#0f766e;"></i>Tambah Budget Plan
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3 px-3">
                <input type="hidden" id="sqFwoBudgetModal-id">
                <input type="hidden" id="sqFwoBudgetModal-id-sq-fwo">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="sqFwoBudgetModal-label"
                            placeholder="cth: Hari 1, Operasional Minggu 1..." data-no-disable>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Keterangan</label>
                        <input type="text" class="form-control form-control-sm" id="sqFwoBudgetModal-keterangan"
                            placeholder="Opsional" data-no-disable>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hari Mulai (ke-)</label>
                        <input type="number" min="1" class="form-control form-control-sm" id="sqFwoBudgetModal-hari-mulai" data-no-disable>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hari Selesai (ke-)</label>
                        <input type="number" min="1" class="form-control form-control-sm" id="sqFwoBudgetModal-hari-selesai" data-no-disable>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold" style="font-size:13px;">
                        <i class="fa-solid fa-list me-1" style="color:#0f766e;"></i> Item Anggaran
                    </span>
                    <button type="button" class="pm-btn-pill pm-btn-pill--teal" id="btnSqFwoBudgetAddRow" data-no-disable>
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i> Tambah Item
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="pm-table" id="sqFwoBudgetItemsTable">
                        <thead>
                            <tr>
                                <th style="min-width:200px;">Account</th>
                                <th style="min-width:160px;">Nominal Budget (Rp)</th>
                                <th style="min-width:160px;">Keterangan</th>
                                <th style="width:100px;text-align:center;">Cash Advance</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="sqFwoBudgetItemsBody"></tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-2">
                    <span class="fw-semibold" style="font-size:13px;color:#0f766e;">
                        Total: <span id="sqFwoBudgetModalTotal" style="font-size:14px;">Rp 0</span>
                    </span>
                </div>
            </div>
            <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                <button type="button" class="btn btn-sm btn-primary" id="sqFwoBudgetModal-btn-save" data-no-disable>
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
        data: "{{ route('sq-fieldworks.data') }}",
        history: "{{ url('sq-fieldworks') }}/",
        csrf: "{{ csrf_token() }}",
        update: "{{ url('sq-fieldworks') }}/",
    }
    window.sqFwoRoute = {
        boqByFwo: "{{ url('sq-fwo-boq/by-fwo') }}/",
        boqSelectByWo: "{{ url('sq-fwo-boq/select-by-wo') }}/",
        boqSectionItems: "{{ url('sq-fwo-boq/section-items') }}/",
        boqUpdate: "{{ url('sq-fwo-boq') }}/",
        budgetList: "{{ url('sq-fwo-budgets') }}/",
        budgetStore: "{{ route('sq-fwo-budgets.store') }}",
        budgetShow: "{{ url('sq-fwo-budgets') }}/",
        budgetUpdate: "{{ url('sq-fwo-budgets') }}/",
        budgetDelete: "{{ url('sq-fwo-budgets') }}/",
        select2Account: "{{ url('budget-accounts/select2') }}",
        csrf: "{{ csrf_token() }}",
    }
</script>
<script src="{{ asset('assets/js/sq-fieldwork/index.js') }}?v={{ @filemtime(public_path('assets/js/sq-fieldwork/index.js')) }}"></script>
<script src="{{ asset('assets/js/sq-fieldwork/form.js') }}?v={{ @filemtime(public_path('assets/js/sq-fieldwork/form.js')) }}"></script>
@endsection
