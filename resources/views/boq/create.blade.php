@extends('layouts.app')

@section('content')
<section class="section">
    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Tambah BOQ</h4>
                <p class="text-muted mb-0">Bill of Quantity untuk Work Order</p>
            </div>
            <a href="{{ url('work-orders') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
        </div>

        {{-- Work Order Selector --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Work Order <span class="text-danger">*</span>
                        </label>
                        <select id="id_wo" style="width:100%"></select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold text-muted">Judul Pekerjaan</label>
                        <p id="judulOrder" class="form-control mb-0 text-muted">—</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sections Container --}}
        <div id="boqSections"></div>

        {{-- Empty State --}}
        <div id="boqEmpty" class="card mb-4">
            <div class="card-body text-center text-muted py-5">
                <i class="fa-solid fa-layer-group fa-2x mb-3 d-block opacity-25"></i>
                <div class="fw-semibold mb-1">Belum ada section</div>
                <div class="small">Pilih Work Order lalu klik <strong>+ Tambah Section</strong> untuk memulai</div>
            </div>
        </div>

        {{-- Action Bar --}}
        <div class="d-flex justify-content-between align-items-center mt-2">
            <button type="button" id="btnAddSection" class="btn btn-outline-primary" disabled>
                <i class="fa-solid fa-plus me-1"></i> Tambah Section
            </button>
            <button type="button" id="btnSave" class="btn btn-primary" disabled>
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan BOQ
            </button>
        </div>

    </div>
</section>

{{-- ============================================================ --}}
{{-- Modal: Tambah / Edit Section                                 --}}
{{-- ============================================================ --}}
<div class="modal fade" id="modalAddSection" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSectionTitle">
                    <i class="fa-solid fa-layer-group me-2 text-primary"></i> Tambah Section
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Testing Point <span class="text-danger">*</span>
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
                    <span id="btnConfirmText">Tambah Section</span>
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
let addedPointIds  = new Set();
let selectedPoint  = null;
let editingSectionId = null;

$(document).ready(function () {

    // ── WO Select2 ────────────────────────────────────────────────────────────
    $("#id_wo").select2({
        placeholder: "Pilih Work Order",
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: "{{ route('boq.select2') }}",
            dataType: "json",
            delay: 250,
            data: p => ({ q: p.term }),
            processResults: d => ({ results: d }),
            cache: true,
        },
        language: {
            noResults: () => `<span>Tidak ditemukan. <a href="{{ route('work-orders.create') }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add</a></span>`,
        },
        escapeMarkup: m => m,
    });

    $("#id_wo").on("select2:select", function (e) {
        $("#judulOrder").text(e.params.data.judul || "—").removeClass("text-muted");
        $("#btnAddSection").prop("disabled", false);
    });

    const preselectWoId = new URLSearchParams(window.location.search).get('id_wo');
    if (preselectWoId) {
        $.get("{{ url('work-orders') }}/" + preselectWoId, function (wo) {
            if (!wo || !wo.id_wo) return;
            const opt = new Option(wo.no_wo + ' — ' + wo.judul_pekerjaan, wo.id_wo, true, true);
            Object.assign(opt, { judul: wo.judul_pekerjaan });
            $("#id_wo").append(opt).trigger('change');
            $("#judulOrder").text(wo.judul_pekerjaan || "—").removeClass("text-muted");
            $("#btnAddSection").prop("disabled", false);
        });
    }

    $("#id_wo").on("select2:clear", function () {
        $("#judulOrder").text("—").addClass("text-muted");
        $("#btnAddSection, #btnSave").prop("disabled", true);
        $("#boqSections").empty();
        $("#boqEmpty").show();
        addedPointIds.clear();
    });

    // ── Testing Point Select2 (di dalam modal) ────────────────────────────────
    $("#selectTestingPoint").select2({
        dropdownParent: $("#modalAddSection"),
        placeholder: "Ketik nama Testing Point...",
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: "{{ route('testing-points.select2') }}",
            dataType: "json",
            delay: 250,
            data: p => ({ q: p.term }),
            processResults: d => ({ results: d }),
            cache: true,
        },
    });

    $("#selectTestingPoint").on("select2:select", function (e) {
        const id = String(e.params.data.id);
        if (!editingSectionId && addedPointIds.has(id)) {
            Notify.warning("Testing Point ini sudah ditambahkan. Gunakan tombol Edit pada section tersebut.");
            $(this).val(null).trigger("change");
            selectedPoint = null;
            resetModalItems();
            return;
        }
        selectedPoint = { id: e.params.data.id, text: e.params.data.text };
        const preChecked = editingSectionId ? getCurrentItemIds(editingSectionId) : null;
        loadModalItems(e.params.data.id, preChecked);
    });

    $("#selectTestingPoint").on("select2:clear", function () {
        selectedPoint = null;
        resetModalItems();
        $("#btnConfirmSection").prop("disabled", true);
    });

    // ── Buka modal tambah ─────────────────────────────────────────────────────
    $("#btnAddSection").on("click", function () {
        editingSectionId = null;
        resetModalItems();
        $("#selectTestingPoint").val(null).trigger("change").prop("disabled", false);
        selectedPoint = null;
        new bootstrap.Modal("#modalAddSection").show();
    });

    // ── Reset saat modal ditutup ──────────────────────────────────────────────
    $("#modalAddSection").on("hidden.bs.modal", function () {
        editingSectionId = null;
        selectedPoint    = null;
        $("#selectTestingPoint").val(null).trigger("change").prop("disabled", false);
        resetModalItems();
        $("#modalSectionTitle").html('<i class="fa-solid fa-layer-group me-2 text-primary"></i> Tambah Section');
        $("#btnConfirmText").text("Tambah Section");
    });

    // ── Buka modal edit section ───────────────────────────────────────────────
    $(document).on("click", ".btn-edit-section", function () {
        const $sec  = $(this).closest(".boq-section");
        const ptId  = String($sec.data("point-id"));
        const ptTxt = $sec.find(".section-point-name").text().trim();

        editingSectionId = ptId;
        selectedPoint    = { id: ptId, text: ptTxt };

        const opt = new Option(ptTxt, ptId, true, true);
        $("#selectTestingPoint").empty().append(opt).trigger("change");
        $("#selectTestingPoint").prop("disabled", true);

        $("#modalSectionTitle").html('<i class="fa-solid fa-pen me-2 text-warning"></i> Edit Section');
        $("#btnConfirmText").text("Simpan Perubahan");

        resetModalItems();
        loadModalItems(ptId, getCurrentItemIds(ptId));
        new bootstrap.Modal("#modalAddSection").show();
    });

    // ── Pilih / Hapus semua — hanya item yang terlihat ───────────────────────
    $("#btnCheckAll").on("click", function (e) {
        e.preventDefault();
        $("#modalItemsList .modal-item-row:not(.d-none) .item-check").prop("checked", true);
        updateConfirmBtn();
    });
    $("#btnUncheckAll").on("click", function (e) {
        e.preventDefault();
        $("#modalItemsList .modal-item-row:not(.d-none) .item-check").prop("checked", false);
        updateConfirmBtn();
    });

    // ── Search filter ─────────────────────────────────────────────────────────
    $("#modalItemSearch").on("input", function () {
        const term = $(this).val().toLowerCase();
        let visible = 0;
        $("#modalItemsList .modal-item-row").each(function () {
            const item  = $(this).find(".item-check").data("item") || {};
            const text  = ((item.judul_indonesia || "") + " " + (item.judul_inggris || "")).toLowerCase();
            const match = !term || text.includes(term);
            $(this).toggleClass("d-none", !match);
            if (match) visible++;
        });
        $("#modalSearchEmpty").toggleClass("d-none", visible > 0);
    });

    // ── Konfirmasi (add / edit) ───────────────────────────────────────────────
    $("#btnConfirmSection").on("click", function () {
        const checkedItems = [];
        $("#modalItemsList .item-check:checked").each(function () {
            checkedItems.push($(this).data("item"));
        });
        if (!checkedItems.length) return;

        if (editingSectionId) {
            updateSection(editingSectionId, checkedItems);
        } else {
            addSection(selectedPoint.id, selectedPoint.text, checkedItems);
        }
        bootstrap.Modal.getInstance("#modalAddSection").hide();
    });

    // ── Hapus section ─────────────────────────────────────────────────────────
    $(document).on("click", ".btn-remove-section", function () {
        const ptId = String($(this).closest(".boq-section").data("point-id"));
        addedPointIds.delete(ptId);
        $(this).closest(".boq-section").remove();
        checkEmpty();
    });

    // ── Simpan BOQ ────────────────────────────────────────────────────────────
    $("#btnSave").on("click", function () {
        const id_wo = $("#id_wo").val();
        if (!id_wo) { Notify.warning("Pilih Work Order terlebih dahulu"); return; }

        const sections = collectSections();
        if (!sections.length) { Notify.warning("Tambahkan minimal 1 section BOQ"); return; }

        Notify.confirm("Simpan BOQ?", function () {
            $("#btnSave").prop("disabled", true)
                .html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

            $.ajax({
                url: "{{ url('boq') }}",
                method: "POST",
                contentType: "application/json",
                headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                data: JSON.stringify({ id_wo: id_wo, sections: sections }),
                success: function () {
                    Notify.success("BOQ berhasil disimpan");
                    setTimeout(() => window.location.href = "{{ url('work-orders') }}", 1000);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || "Gagal menyimpan BOQ");
                    $("#btnSave").prop("disabled", false)
                        .html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan BOQ');
                },
            });
        });
    });
});

// ── Load items di modal ────────────────────────────────────────────────────────
function loadModalItems(pointId, preCheckedIds = null) {
    resetModalItems();
    $("#modalLoading").removeClass("d-none");

    $.get("/testing-items/by-point/" + pointId, function (res) {
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

// ── Ambil item ID yang saat ini ada di section ─────────────────────────────────
function getCurrentItemIds(pointId) {
    const ids = new Set();
    $(`.boq-section[data-point-id="${pointId}"] .boq-item`).each(function () {
        ids.add(String($(this).data("item-id")));
    });
    return ids;
}

// ── Tambah section baru ────────────────────────────────────────────────────────
function addSection(pointId, pointText, items) {
    $("#boqEmpty").hide();
    const itemsHtml = items.map((item, i) => renderItem(pointId, item, i + 1)).join("");

    const html = `
        <div class="card mb-4 boq-section" data-point-id="${pointId}">
            <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <i class="fa-solid fa-layer-group" style="color:#2563eb;"></i>
                    <span class="fw-semibold section-point-name">${escHtml(pointText)}</span>
                    <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary section-item-count"
                        style="font-size:11px;">${items.length} item</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-section py-1 px-2"
                        style="font-size:12px;">
                        <i class="fa-solid fa-pen me-1"></i> Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-section py-1 px-2"
                        style="font-size:12px;">
                        <i class="fa-solid fa-trash me-1"></i> Hapus
                    </button>
                </div>
            </div>
            <div class="card-body px-3 py-3">
                <div class="section-fields">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm text-muted mb-1">Item Produk Alternatif</label>
                            <input type="text" class="form-control form-control-sm input-item-produk"
                                placeholder="opsional">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm text-muted mb-1">Qty</label>
                            <input type="text" inputmode="numeric" class="form-control form-control-sm input-qty input-num-mask input-num-int"
                                placeholder="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm text-muted mb-1">Satuan</label>
                            <input type="text" class="form-control form-control-sm input-satuan"
                                placeholder="pcs, set...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm text-muted mb-1">Harga (Rp)</label>
                            <input type="text" inputmode="numeric" class="form-control form-control-sm input-harga input-num-mask"
                                placeholder="0">
                        </div>
                        <div class="col-md-12">
                            <div class="section-total-line text-end" style="font-size:12px;color:#64748b;min-height:18px;margin-bottom:2px;"></div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                            <input type="text" class="form-control form-control-sm input-ket"
                                placeholder="opsional">
                        </div>
                    </div>
                </div>
                <div class="text-muted small fw-semibold mb-1">
                    <i class="fa-solid fa-list-check me-1"></i> Items
                </div>
                <div class="boq-items">${itemsHtml}</div>
            </div>
        </div>`;

    const $el = $(html);
    $("#boqSections").append($el);
    initNumericMask($el);
    addedPointIds.add(String(pointId));
    $("#btnSave").prop("disabled", false);
}

// ── Update items pada section yang sudah ada ───────────────────────────────────
function updateSection(pointId, items) {
    const $sec = $(`.boq-section[data-point-id="${pointId}"]`);
    $sec.find(".boq-items").html(
        items.map((item, i) => renderItem(pointId, item, i + 1)).join("")
    );
    $sec.find(".section-item-count").text(items.length + " item");
}

// ── Render satu item (display only) ───────────────────────────────────────────
function renderItem(pointId, item, num) {
    const unit  = item.kode_unit || "—";
    const nilai = item.nilai ?? "—";
    return `
        <div class="boq-item d-flex align-items-center flex-wrap gap-2"
            data-item-id="${item.id_testing_item}"
            data-point-id="${pointId}"
            data-item-produk="${escHtml(item.judul_indonesia ?? "")}">
            <span class="text-muted small fw-semibold item-num">${num}.</span>
            <span class="fw-semibold small">${escHtml(item.judul_indonesia ?? "—")}</span>
            <span class="text-muted small">/ ${escHtml(item.judul_inggris ?? "—")}</span>
            <span class="item-meta-badge">${escHtml(unit)} · ${escHtml(String(nilai))}</span>
        </div>`;
}

function checkEmpty() {
    if ($(".boq-section").length === 0) {
        $("#boqEmpty").show();
        $("#btnSave").prop("disabled", true);
    }
}

function collectSections() {
    const sections = [];
    $(".boq-section").each(function () {
        const $sec  = $(this);
        const items = [];
        $sec.find(".boq-item").each(function () {
            items.push(parseInt($(this).data("item-id")));
        });
        sections.push({
            id_testing_point:      parseInt($sec.data("point-id")),
            item_produk_alternate: $sec.find(".input-item-produk").val() || null,
            qty:                   rawNumVal($sec.find(".input-qty")[0]),
            satuan:                $sec.find(".input-satuan").val() || null,
            harga:                 rawNumVal($sec.find(".input-harga")[0]),
            keterangan:            $sec.find(".input-ket").val() || null,
            items:                 items,
        });
    });
    return sections;
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function updateSectionTotal($sec) {
    const qty   = rawNumVal($sec.find('.input-qty')[0]) || 0;
    const harga = rawNumVal($sec.find('.input-harga')[0]) || 0;
    const $line = $sec.find('.section-total-line');
    if (qty && harga) {
        $line.html(
            Number(qty).toLocaleString('en-US') + ' qty &times; Rp ' + Number(harga).toLocaleString('en-US') +
            ' = <strong style="color:#1d4ed8;">Rp ' + Number(qty * harga).toLocaleString('en-US') + '</strong>'
        );
    } else {
        $line.html('');
    }
}

$(document).on('input', '.input-qty, .input-harga', function () {
    updateSectionTotal($(this).closest('.boq-section'));
});
</script>
@endsection
