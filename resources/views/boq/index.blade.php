@extends('layouts.app')

@section('page-title', 'BOQ')
@section('page-descrip', 'Data Bill of Quantity')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">BOQ</li>
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
<x-crud-index
    title="List BOQ"
    create-route="boq.create"
    :with-history="true"
/>

{{-- Modal: Tambah / Edit Section (digunakan saat edit inline) --}}
<div class="modal fade" id="modalAddSection" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSectionTitle">
                    <i class="fa-solid fa-layer-group me-2 text-primary"></i> Tambah Item
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Item <span class="text-danger">*</span>
                    </label>
                    <select id="selectTestingPoint" style="width:100%"></select>
                </div>

                <div id="modalLoading" class="text-center text-muted py-3 d-none">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat item...
                </div>

                <div id="modalEmpty" class="text-center text-muted py-3 d-none">
                    <i class="fa-solid fa-inbox me-1 opacity-50"></i> Tidak ada item pada Testing Point ini
                </div>

                <div id="modalItemsWrap" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small text-muted">
                            <i class="fa-solid fa-list-check me-1"></i> Pilih item yang akan dimasukkan:
                        </span>
                        <div class="d-flex gap-3">
                            <a href="#" id="btnCheckAll" class="small text-decoration-none">Pilih Semua</a>
                            <a href="#" id="btnUncheckAll" class="small text-decoration-none text-secondary">Hapus Semua</a>
                        </div>
                    </div>
                    <div class="mb-2">
                        <input type="text" id="modalItemSearch" class="form-control form-control-sm"
                            placeholder="Cari item...">
                    </div>
                    <div id="modalItemsList"></div>
                    <div id="modalSearchEmpty" class="text-center text-muted py-3 d-none" style="font-size:13px;">
                        <i class="fa-solid fa-magnifying-glass me-1 opacity-50"></i> Tidak ada item yang cocok
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnConfirmSection" class="btn btn-primary btn-sm" disabled>
                    <i class="fa-solid fa-check me-1"></i>
                    <span id="btnConfirmText">Tambah Item</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('custom-script')
<style>
    .boq-section .card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .section-fields {
        background: #fafbfc;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 12px 14px;
        margin-bottom: 14px;
    }
    .boq-item {
        padding: 7px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .boq-item:last-child { border-bottom: none; }
    .item-meta-badge {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 20px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
        white-space: nowrap;
    }
    .modal-item-row {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
        margin-bottom: 8px;
        transition: background 0.1s;
    }
    .modal-item-row:hover { background: #f8fafc; }
</style>

<script>
    window.route = {
        data:    "{{ route('boq.data') }}",
        update:  "{{ url('boq') }}/",
        history: "{{ url('boq') }}/",
        csrf:    "{{ csrf_token() }}",
        testingPointSelect2: "{{ route('testing-points.select2') }}",
        testingItemsByPoint: "{{ url('testing-items/by-point') }}/",
    }
</script>

<script src="{{ asset('assets/js/boq/form.js') }}"></script>
<script src="{{ asset('assets/js/boq/index.js') }}"></script>
<script>
function loadModalItems(pointId, preCheckedIds = null) {
    resetModalItems();
    $("#modalLoading").removeClass("d-none");

    $.get(window.route.testingItemsByPoint + pointId, function (res) {
        $("#modalLoading").addClass("d-none");
        const items = res.data ?? [];

        if (!items.length) {
            $("#modalEmpty").removeClass("d-none");
            return;
        }

        let html = "";
        items.forEach(function (item) {
            const unit     = item.kode_unit || "—";
            const nilai    = item.nilai ?? "—";
            const checked  = preCheckedIds ? preCheckedIds.has(String(item.id_testing_item)) : true;
            const safeItem = JSON.stringify(item).replace(/'/g, "&#39;");
            html += `
                <div class="modal-item-row d-flex align-items-center gap-3">
                    <input type="checkbox" class="form-check-input item-check flex-shrink-0 mt-0"
                        id="mitem_${item.id_testing_item}" ${checked ? "checked" : ""}
                        data-item='${safeItem}'>
                    <label class="d-flex justify-content-between align-items-center w-100 gap-2"
                        for="mitem_${item.id_testing_item}" style="cursor:pointer; margin:0;">
                        <div>
                            <span class="fw-semibold">${escHtml(item.judul_indonesia ?? "—")}</span>
                            <span class="text-muted ms-1 small">/ ${escHtml(item.judul_inggris ?? "—")}</span>
                        </div>
                        <span class="item-meta-badge flex-shrink-0">${escHtml(unit)} · ${escHtml(String(nilai))}</span>
                    </label>
                </div>`;
        });

        $("#modalItemsList").html(html);
        $("#modalItemsWrap").removeClass("d-none");
        $("#modalItemsList").on("change", ".item-check", updateConfirmBtn);
        updateConfirmBtn();
    }).fail(function () {
        $("#modalLoading").addClass("d-none");
        $("#modalEmpty").removeClass("d-none");
    });
}

function resetModalItems() {
    $("#modalLoading, #modalEmpty, #modalItemsWrap, #modalSearchEmpty").addClass("d-none");
    $("#modalItemsList").empty();
    $("#modalItemSearch").val("");
    $("#btnConfirmSection").prop("disabled", true);
}

function updateConfirmBtn() {
    $("#btnConfirmSection").prop("disabled",
        $("#modalItemsList .item-check:checked").length === 0
    );
}
</script>
@endsection
