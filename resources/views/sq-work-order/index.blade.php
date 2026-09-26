@extends('layouts.app')

@section('page-title', 'SQ Work Orders')
@section('page-descrip', 'Kelola Work Order estimasi Sales Quotation')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">SQ Work Orders</li>
@endsection

@section('style')
<style>
    .col-resize-handle {
        position: absolute; top: 0; right: 0; width: 6px; height: 100%;
        cursor: col-resize; user-select: none; z-index: 2;
    }
    .col-resize-handle:hover, .col-resize-handle.resizing { background: rgba(29, 78, 216, 0.35); }
    /* Kelola BOQ — sama persis style /boq */
    .sq-boq-manage-section .card-header { background: #fff; border-bottom: 1px solid #e2e8f0; }
    .btn-sq-boq-manage-toggle { color: #64748b; font-size: 12px; width: 14px; text-align: center; cursor: pointer; transition: transform .2s; }
    .btn-sq-boq-manage-toggle.rotated { transform: rotate(90deg); }
    .modal-item-row { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 8px; transition: background 0.1s; }
    .modal-item-row:hover { background: #f8fafc; }
    .boq-items-toggle:hover .text-muted { color: #475569 !important; }
    .boq-items-chevron.collapsed { transform: rotate(180deg); }
    .item-meta-badge { font-size: 11px; padding: 2px 8px; border-radius: 20px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; white-space: nowrap; }
</style>
@endsection

@section('content')
<x-crud-index
    title="List of SQ Work Orders"
    :with-history="true"
    :search-fields="[
        ['label' => 'No. WO',           'value' => 'no_sq_wo'],
        ['label' => 'No. SQ',           'value' => 'no_sq'],
        ['label' => 'Judul Pekerjaan',  'value' => 'judul'],
        ['label' => 'Pelanggan',        'value' => 'pelanggan'],
    ]"
/>

<!-- MODAL: Kelola BOQ — replikasi penuh halaman /boq (Tambah BOQ) -->
<div class="modal fade" id="sqBoqManageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content" style="height:90vh;">
            <div class="modal-header py-2 px-3" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <span class="fw-semibold" style="font-size:14px;">
                    <i class="fa-solid fa-layer-group me-2" style="color:#2563eb;"></i>
                    Tambah BOQ
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow-y:auto;">
                <div id="sqBoqManageBanner" class="d-flex align-items-center gap-3 flex-wrap"
                    style="position:sticky;top:-1rem;z-index:100;background:#fff;border-bottom:2px solid #e2e8f0;padding:10px 16px;margin:-1rem -1rem 16px;box-shadow:0 2px 10px rgba(0,0,0,.08);font-size:13px;"></div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Work Order</label>
                                <p id="sqBoqManageWoLabel" class="form-control mb-0 text-muted">—</p>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold text-muted">Judul Pekerjaan</label>
                                <p id="sqBoqManageJudul" class="form-control mb-0 text-muted">—</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="sqBoqManageSections"></div>

                <div id="sqBoqManageEmpty" class="card mb-4 d-none">
                    <div class="card-body text-center text-muted py-5">
                        <i class="fa-solid fa-layer-group fa-2x mb-3 d-block opacity-25"></i>
                        <div class="fw-semibold mb-1">Belum ada item</div>
                        <div class="small">Klik <strong>+ Tambah Item</strong> untuk memulai</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center" style="border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-outline-primary" id="btnSqBoqManageAdd">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Item
                </button>
                <button type="button" class="btn btn-primary" id="btnSqBoqManageSave" disabled>
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan BOQ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Tambah/Edit Item BOQ (Testing Point + checklist Testing Item) -->
<div class="modal fade" id="sqBoqSectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                <h6 class="modal-title mb-0" id="sqBoqSectionModalLabel">
                    <i class="fa-solid fa-layer-group me-2" style="color:#1a56db;"></i>Tambah Item BOQ
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3 px-3">
                <input type="hidden" id="sqBoqSectionModal-id">
                <input type="hidden" id="sqBoqSectionModal-id-sq-wo">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Item <span class="text-danger">*</span></label>
                    <select id="sqBoqSectionModal-point" style="width:100%"></select>
                </div>
                <div id="sqBoqSectionModal-items-wrap" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small text-muted">
                            <i class="fa-solid fa-list-check me-1"></i> Pilih item yang akan dimasukkan:
                        </span>
                        <div class="d-flex gap-3">
                            <a href="#" id="sqBoqSectionModal-check-all" class="small text-decoration-none">Pilih Semua</a>
                            <a href="#" id="sqBoqSectionModal-uncheck-all" class="small text-decoration-none text-secondary">Hapus Semua</a>
                        </div>
                    </div>
                    <div class="mb-2">
                        <input type="text" id="sqBoqSectionModal-search" class="form-control form-control-sm" placeholder="Cari item...">
                    </div>
                    <div id="sqBoqSectionModal-items" style="max-height:320px;overflow-y:auto;"></div>
                    <div id="sqBoqSectionModal-search-empty" class="text-center text-muted py-3 d-none" style="font-size:13px;">
                        <i class="fa-solid fa-magnifying-glass me-1 opacity-50"></i> Tidak ada item yang cocok
                    </div>
                </div>
                <div id="sqBoqSectionModal-empty" class="text-center text-muted py-3">
                    <span class="fst-italic small">Pilih Testing Point dulu.</span>
                </div>
            </div>
            <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                <button type="button" class="btn btn-sm btn-primary" id="sqBoqSectionModal-btn-save" disabled data-no-disable>
                    <i class="fa-solid fa-check me-1"></i> <span id="sqBoqSectionModal-btn-save-text">Tambah Item</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Tambah/Edit BOQ Other/Sampling -->
<div class="modal fade" id="sqBoqTambahanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content">
            <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                <h6 class="modal-title mb-0" id="sqBoqTambahanModalLabel">
                    <i class="fa-solid fa-file-invoice me-2" style="color:#b45309;"></i>Tambah Item
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3 px-3">
                <input type="hidden" id="sqBoqTambahanModal-id">
                <input type="hidden" id="sqBoqTambahanModal-jenis">
                <input type="hidden" id="sqBoqTambahanModal-id-sq-wo">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:12px;">Nama Item <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="sqBoqTambahanModal-nama"
                            placeholder="cth: Penyusunan Dokumen" data-no-disable>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12px;">Qty <span class="text-danger">*</span></label>
                        <input type="text" inputmode="numeric" class="form-control form-control-sm input-num-mask input-num-int" id="sqBoqTambahanModal-qty" placeholder="0" data-no-disable>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12px;">Satuan</label>
                        <select class="form-select form-select-sm" id="sqBoqTambahanModal-satuan" data-no-disable></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:12px;">Harga (Rp) <span class="text-danger">*</span></label>
                        <input type="text" inputmode="numeric" class="form-control form-control-sm input-num-mask" id="sqBoqTambahanModal-harga" placeholder="0" data-no-disable>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:12px;">Keterangan</label>
                        <textarea class="form-control form-control-sm" id="sqBoqTambahanModal-keterangan" rows="2" placeholder="Opsional" data-no-disable></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                <button type="button" class="btn btn-sm btn-primary" id="sqBoqTambahanModal-btn-save" data-no-disable>
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Tambah/Edit Budget Plan -->
<div class="modal fade" id="sqWoBudgetPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content">
            <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                <h6 class="modal-title mb-0" id="sqWoBudgetPlanModalLabel">
                    <i class="fa-solid fa-wallet me-2" style="color:#0f766e;"></i>Tambah Budget Plan
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3 px-3">
                <input type="hidden" id="sqWoBudgetModal-id">
                <input type="hidden" id="sqWoBudgetModal-id-sq-wo">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="sqWoBudgetModal-label"
                            placeholder="cth: Hari 1, Operasional Minggu 1..." data-no-disable>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Keterangan</label>
                        <input type="text" class="form-control form-control-sm" id="sqWoBudgetModal-keterangan"
                            placeholder="Opsional" data-no-disable>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hari Mulai (ke-)</label>
                        <input type="number" min="1" class="form-control form-control-sm" id="sqWoBudgetModal-hari-mulai" data-no-disable>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hari Selesai (ke-)</label>
                        <input type="number" min="1" class="form-control form-control-sm" id="sqWoBudgetModal-hari-selesai" data-no-disable>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold" style="font-size:13px;">
                        <i class="fa-solid fa-list me-1" style="color:#0f766e;"></i> Item Anggaran
                    </span>
                    <button type="button" class="pm-btn-pill pm-btn-pill--teal" id="btnSqWoBudgetAddRow" data-no-disable>
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i> Tambah Item
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="pm-table" id="sqWoBudgetItemsTable">
                        <thead>
                            <tr>
                                <th style="min-width:200px;">Account</th>
                                <th style="min-width:160px;">Nominal Budget (Rp)</th>
                                <th style="min-width:160px;">Keterangan</th>
                                <th style="width:100px;text-align:center;">Cash Advance</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="sqWoBudgetItemsBody"></tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-2">
                    <span class="fw-semibold" style="font-size:13px;color:#0f766e;">
                        Total: <span id="sqWoBudgetModalTotal" style="font-size:14px;">Rp 0</span>
                    </span>
                </div>
            </div>
            <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                <button type="button" class="btn btn-sm btn-primary" id="sqWoBudgetModal-btn-save" data-no-disable>
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
        data:    "{{ route('sq-work-orders.data') }}",
        history: "{{ url('sq-work-orders') }}/",
        csrf:    "{{ csrf_token() }}",
        update:  "{{ url('sq-work-orders') }}/",
    }
    window.sqWoRoute = {
        boqShow: "{{ url('sq-boq') }}/",
        boqSave: "{{ url('sq-boq') }}/",
        tambahanList:   "{{ url('sq-boq-tambahan') }}/",
        tambahanStore:  "{{ route('sq-boq-tambahan.store') }}",
        tambahanUpdate: "{{ url('sq-boq-tambahan') }}/",
        tambahanDelete: "{{ url('sq-boq-tambahan') }}/",
        budgetList:   "{{ url('sq-wo-budgets') }}/",
        budgetStore:  "{{ route('sq-wo-budgets.store') }}",
        budgetShow:   "{{ url('sq-wo-budgets') }}/",
        budgetUpdate: "{{ url('sq-wo-budgets') }}/",
        budgetDelete: "{{ url('sq-wo-budgets') }}/",
        select2Site: "{{ url('business-relations/sites/select2') }}",
        select2Contact: "{{ route('business-relation-contacts.select2') }}",
        select2Satuan: "{{ route('satuan.select2') }}",
        select2TestingPoint: "{{ route('testing-points.select2') }}",
        select2Account: "{{ url('budget-accounts/select2') }}",
        itemsByPoint: "{{ url('testing-items/by-point') }}/",
        csrf: "{{ csrf_token() }}",
    }
</script>
<script src="{{ asset('assets/js/sq-work-order/index.js') }}"></script>
<script src="{{ asset('assets/js/sq-work-order/form.js') }}"></script>
@endsection
