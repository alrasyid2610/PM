// ── Shared sub-items renderer ──────────────────────────────────────────────────
function renderBoqSubItems(items) {
    if (!items || !items.length)
        return '<div class="text-muted small py-2">Tidak ada item</div>';
    return items
        .map(function (item, j) {
            return `<div class="d-flex align-items-center flex-wrap gap-2 py-2" style="border-bottom:1px solid #f1f5f9;">
            <span class="text-muted small fw-semibold">${j + 1}.</span>
            <span class="fw-semibold small">${escHtml(item.judul_indonesia ?? "—")}</span>
            <span class="text-muted small">/ ${escHtml(item.judul_inggris ?? "—")}</span>
            <span class="pm-badge pm-badge--gray" style="font-size:10px;">${escHtml(item.kode_unit || "—")} · ${escHtml(String(item.nilai ?? "—"))}</span>
        </div>`;
        })
        .join("");
}

// ── Single item view body (readonly) ───────────────────────────────────────────
function renderBoqViewBody(sec) {
    const items = sec.items ?? [];
    const totalAmount = sec.qty && sec.harga ? sec.qty * sec.harga : null;
    const harga = sec.harga ? Number(sec.harga).toLocaleString("en-US") : "—";
    return `
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <label class="form-label form-label-sm text-muted mb-1">Item Produk Alternatif</label>
                <p class="form-control form-control-sm mb-0">${escHtml(sec.item_produk_alternate ?? "—")}</p>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm text-muted mb-1">Qty</label>
                <p class="form-control form-control-sm mb-0">${escHtml(String(sec.qty ?? "—"))}</p>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm text-muted mb-1">Satuan</label>
                <p class="form-control form-control-sm mb-0">${escHtml(sec.satuan ?? "—")}</p>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm text-muted mb-1">Harga (Rp)</label>
                <p class="form-control form-control-sm mb-0">${harga}</p>
            </div>
            ${
                totalAmount !== null
                    ? `<div class="col-md-12">
                <div style="font-size:12px;color:#64748b;text-align:right;">
                    ${escHtml(String(sec.qty))} qty &times; Rp ${Number(sec.harga).toLocaleString("en-US")} =
                    <strong style="color:#1d4ed8;">Rp ${Number(totalAmount).toLocaleString("en-US")}</strong>
                </div>
            </div>`
                    : ""
            }
            <div class="col-md-12">
                <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                <p class="form-control form-control-sm mb-0">${escHtml(sec.keterangan ?? "—")}</p>
            </div>
        </div>
        <div class="text-muted small fw-semibold mb-2">
            <i class="fa-solid fa-list-check me-1"></i> Items (${items.length})
        </div>
        <div>${renderBoqSubItems(items)}</div>`;
}

// ── View mode ─────────────────────────────────────────────────────────────────
function renderBoqForm(res) {
    const sections = res.sections ?? [];
    const grandTotal = sections.reduce(
        (sum, sec) => sum + (sec.qty ?? 0) * (sec.harga ?? 0),
        0,
    );

    const woSection = formGroup.sectionCard(
        {
            icon: "fa-briefcase",
            color: "icon-blue",
            title: "Work Order",
            subtitle:
                escHtml(res.no_wo ?? "") +
                " — " +
                escHtml(res.judul_pekerjaan ?? ""),
        },
        `<div class="row g-3">
            <div class="col-md-3">
                <label class="form-label form-label-sm text-muted mb-1">No WO</label>
                <p class="form-control form-control-sm mb-0">${escHtml(res.no_wo ?? "—")}</p>
            </div>
            <div class="col-md-9">
                <label class="form-label form-label-sm text-muted mb-1">Judul Pekerjaan</label>
                <p class="form-control form-control-sm mb-0">${escHtml(res.judul_pekerjaan ?? "—")}</p>
            </div>
        </div>`,
    );

    let boqSection;
    if (sections.length === 0) {
        boqSection = `<div class="col-md-12">
            <div class="card"><div class="card-body text-center text-muted py-4">
                <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                Tidak ada data BOQ untuk Work Order ini
            </div></div>
        </div>`;
    } else {
        const accordionItems = sections
            .map(function (sec, i) {
                const items = sec.items ?? [];
                const totalAmount =
                    sec.qty && sec.harga ? sec.qty * sec.harga : null;

                const searchText = [
                    sec.point_name ?? "",
                    sec.item_produk_alternate ?? "",
                    ...items.map(
                        (it) =>
                            (it.judul_indonesia ?? "") +
                            " " +
                            (it.judul_inggris ?? ""),
                    ),
                ]
                    .join(" ")
                    .toLowerCase()
                    .replace(/"/g, " ");

                return `<div class="pm-accordion-item" data-search-text="${searchText}" data-section-idx="${i}">
                <div class="pm-accordion-header" aria-expanded="false">
                    <div class="pm-accordion-toggle">
                        <i class="fa-solid fa-chevron-right pm-accordion-chevron"></i>
                        <span class="text-muted" style="font-size:11px;flex-shrink:0;">${i + 1}.</span>
                        <span style="font-size:13px;font-weight:600;color:#1e293b;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(sec.point_name ?? "—")}</span>
                        <span class="pm-badge pm-badge--blue" style="font-size:10px;flex-shrink:0;">${items.length} item</span>
                    </div>
                    <div class="pm-accordion-meta">
                        ${sec.qty ? `<span class="text-muted" style="font-size:12px;white-space:nowrap;">${sec.qty} ${escHtml(sec.satuan || "")}</span>` : ""}
                        ${totalAmount !== null ? `<span class="pm-badge pm-badge--green">Rp ${Number(totalAmount).toLocaleString("en-US")}</span>` : ""}
                        <div class="boq-item-view-actions">
                            <button type="button" class="pm-btn-icon btn-boq-item-edit" data-section-idx="${i}" data-no-disable title="Edit item ini">
                                <i class="fa-solid fa-pen" style="font-size:10px;"></i>
                            </button>
                        </div>
                        <div class="boq-item-edit-actions">
                            <button type="button" class="pm-btn-pill pm-btn-pill--amber btn-boq-item-cancel" data-section-idx="${i}" data-no-disable style="font-size:11px;padding:3px 8px;">Batal</button>
                            <button type="button" class="pm-btn-pill pm-btn-pill--green btn-boq-item-save" data-section-idx="${i}" data-no-disable style="font-size:11px;padding:3px 8px;">
                                <i class="fa-solid fa-check" style="font-size:9px;"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
                <div class="pm-accordion-collapse" style="display:none;">
                    <div class="pm-accordion-body">${renderBoqViewBody(sec)}</div>
                </div>
            </div>`;
            })
            .join("");

        const totalLabel =
            grandTotal > 0
                ? `<span class="ms-2" style="color:#1d4ed8;font-weight:600;">· Rp ${Number(grandTotal).toLocaleString("en-US")}</span>`
                : "";

        boqSection = `<div class="col-md-12">
            <div class="pm-tab-card">
                <div class="pm-tab-header" style="margin-top: 0;">
                    <div style="font-size:13px;font-weight:600;color:#374151;">
                        ${sections.length} Item BOQ ${totalLabel}
                    </div>
                    <div class="pm-search" style="max-width:260px;">
                        <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="boqAccordionSearch" placeholder="Cari item BOQ..." data-no-disable>
                        <button type="button" id="btnClearBoqSearch" class="pm-search-clear d-none">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                </div>
                <div id="boqAccordionEmpty" class="d-none text-center text-muted py-4">
                    <i class="fa-solid fa-magnifying-glass fa-2x d-block mb-2 opacity-25"></i>
                    Tidak ada item yang cocok
                </div>
                <div class="pm-accordion" id="boqAccordion">${accordionItems}</div>
            </div>
        </div>`;
    }

    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.no_wo ?? "—"),
        subtitle: escHtml(res.judul_pekerjaan ?? "—"),
        tags:
            `<a href="/work-orders?open=${res.id_wo}" class="pm-badge pm-badge--blue" style="text-decoration:none;">
                   <i class="fa-solid fa-briefcase" style="font-size:10px;"></i>
                   ${escHtml(res.no_wo ?? "Lihat WO")}
               </a>` +
            (grandTotal > 0
                ? `<span class="pm-badge pm-badge--green">
                   <i class="fa-solid fa-tag" style="font-size:10px;"></i>
                   Rp ${Number(grandTotal).toLocaleString("en-US")}
               </span>`
                : ""),
        editText: "Edit BOQ",
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="boqDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBoqInfo">
                        <i class="fa-solid fa-circle-info me-1" style="color:#0891b2;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBoqItems">
                        <i class="fa-solid fa-layer-group me-1" style="color:#16a34a;font-size:11px;"></i>
                        BOQ
                    </button>
                </li>
            </ul>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabBoqInfo" role="tabpanel">
                    <div class="row g-3">${woSection}</div>
                </div>
                <div class="tab-pane fade" id="tabBoqItems" role="tabpanel">
                    <div class="row g-3">${boqSection}</div>
                </div>
            </div>
        </div>
    </div>

</form>`;
}

// ── Edit mode: hanya konten tab BOQ ───────────────────────────────────────────
function renderBoqEditContent() {
    return `
<div class="row g-3">
    <div class="col-md-12" id="boqSections"></div>
    <div class="col-md-12" id="boqEmpty" style="display:none;">
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="fa-solid fa-layer-group fa-2x mb-3 d-block opacity-25"></i>
                <div class="fw-semibold mb-1">Belum ada item</div>
                <div class="small">Klik <strong>+ Tambah Item</strong> untuk menambahkan</div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <button type="button" id="btnAddSection" class="btn btn-outline-primary">
            <i class="fa-solid fa-plus me-1"></i> Tambah Item
        </button>
    </div>
</div>`;
}

// ── Single editable section card ───────────────────────────────────────────────
function renderSectionCard(pointId, pointText, items) {
    const itemsHtml = items
        .map((item, i) => renderItem(pointId, item, i + 1))
        .join("");
    return `
        <div class="card mb-4 boq-section" data-point-id="${pointId}">
            <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <i class="fa-solid fa-layer-group" style="color:#2563eb;"></i>
                    <span class="fw-semibold section-point-name">${escHtml(pointText)}</span>
                    <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary section-item-count"
                        style="font-size:11px;">${items.length} item</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-section py-1 px-2" style="font-size:12px;">
                        <i class="fa-solid fa-pen me-1"></i> Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-section py-1 px-2" style="font-size:12px;">
                        <i class="fa-solid fa-trash me-1"></i> Hapus
                    </button>
                </div>
            </div>
            <div class="card-body px-3 py-3">
                <div class="section-fields">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm text-muted mb-1">Item Produk Alternatif</label>
                            <input type="text" class="form-control form-control-sm input-item-produk" placeholder="opsional">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm text-muted mb-1">Qty</label>
                            <input type="text" inputmode="numeric" class="form-control form-control-sm input-qty input-num-mask input-num-int" placeholder="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm text-muted mb-1">Satuan</label>
                            <select class="form-select form-select-sm input-satuan">
                                <option value="">— Pilih —</option>
                                <option value="PCS">PCS</option>
                                <option value="Titik">Titik</option>
                                <option value="Set">Set</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm text-muted mb-1">Harga (Rp)</label>
                            <input type="text" inputmode="numeric" class="form-control form-control-sm input-harga input-num-mask" placeholder="0">
                        </div>
                        <div class="col-md-12">
                            <div class="section-total-line text-end" style="font-size:12px;color:#64748b;min-height:18px;margin-bottom:2px;"></div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                            <input type="text" class="form-control form-control-sm input-ket" placeholder="opsional">
                        </div>
                    </div>
                </div>
                <div class="text-muted small fw-semibold mb-1">
                    <i class="fa-solid fa-list-check me-1"></i> Items
                </div>
                <div class="boq-items">${itemsHtml}</div>
            </div>
        </div>`;
}

// ── Single item row ────────────────────────────────────────────────────────────
function renderItem(pointId, item, num) {
    const unit = item.kode_unit || "—";
    const nilai = item.nilai ?? "—";
    return `
        <div class="boq-item d-flex align-items-center flex-wrap gap-2"
            data-item-id="${item.id_testing_item}"
            data-point-id="${pointId}">
            <span class="text-muted small fw-semibold item-num">${num}.</span>
            <span class="fw-semibold small">${escHtml(item.judul_indonesia ?? "—")}</span>
            <span class="text-muted small">/ ${escHtml(item.judul_inggris ?? "—")}</span>
            <span class="item-meta-badge">${escHtml(unit)} · ${escHtml(String(nilai))}</span>
        </div>`;
}
