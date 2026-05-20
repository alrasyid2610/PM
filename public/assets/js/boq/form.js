// ── View mode ─────────────────────────────────────────────────────────────────
function renderBoqForm(res) {
    const sections = res.sections ?? [];

    const woSection = formGroup.sectionCard(
        {
            icon: 'fa-file-lines',
            color: 'icon-navy',
            title: 'BOQ',
            subtitle: escHtml(res.no_wo ?? '') + ' — ' + escHtml(res.judul_pekerjaan ?? ''),
        },
        `<div class="row g-3">
            <div class="col-md-3">
                <label class="form-label form-label-sm text-muted mb-1">No WO</label>
                <p class="form-control form-control-sm mb-0">${escHtml(res.no_wo ?? '—')}</p>
            </div>
            <div class="col-md-9">
                <label class="form-label form-label-sm text-muted mb-1">Judul Pekerjaan</label>
                <p class="form-control form-control-sm mb-0">${escHtml(res.judul_pekerjaan ?? '—')}</p>
            </div>
        </div>`
    );

    const grandTotal    = sections.reduce((sum, sec) => sum + ((sec.qty ?? 0) * (sec.harga ?? 0)), 0);

    const sectionsHtml = sections.length === 0
        ? `<div class="col-md-12">
               <div class="card"><div class="card-body text-center text-muted py-4">
                   <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                   Tidak ada data BOQ untuk Work Order ini
               </div></div>
           </div>`
        : sections.map(function (sec) {
            const items = sec.items ?? [];
            const itemsHtml = items.map(function (item, j) {
                const unit  = escHtml(item.kode_unit  || '—');
                const nilai = escHtml(String(item.nilai ?? '—'));
                return `
                    <div class="d-flex align-items-center flex-wrap gap-2 py-2"
                        style="border-bottom:1px solid #f1f5f9;">
                        <span class="text-muted small fw-semibold">${j + 1}.</span>
                        <span class="fw-semibold small">${escHtml(item.judul_indonesia ?? '—')}</span>
                        <span class="text-muted small">/ ${escHtml(item.judul_inggris ?? '—')}</span>
                        <span style="font-size:11px;padding:2px 8px;border-radius:20px;background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;white-space:nowrap;">
                            ${unit} · ${nilai}
                        </span>
                    </div>`;
            }).join('');

            const harga       = sec.harga ? Number(sec.harga).toLocaleString('en-US') : '—';
            const totalAmount = (sec.qty && sec.harga) ? sec.qty * sec.harga : null;
            const totalLine   = totalAmount !== null
                ? `<div class="col-md-12">
                    <div style="font-size:12px;color:#64748b;text-align:right;padding-top:2px;">
                        ${escHtml(String(sec.qty))} qty &times; Rp ${Number(sec.harga).toLocaleString('en-US')} =
                        <strong style="color:#1d4ed8;">Rp ${Number(totalAmount).toLocaleString('en-US')}</strong>
                    </div>
                   </div>`
                : '';

            const bodyHtml = `
                <div style="background:#fafbfc;border:1px solid #e9ecef;border-radius:6px;padding:12px 14px;margin-bottom:14px;">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm text-muted mb-1">Item Produk Alternatif</label>
                            <p class="form-control form-control-sm mb-0">${escHtml(sec.item_produk_alternate ?? '—')}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm text-muted mb-1">Qty</label>
                            <p class="form-control form-control-sm mb-0">${escHtml(String(sec.qty ?? '—'))}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm text-muted mb-1">Satuan</label>
                            <p class="form-control form-control-sm mb-0">${escHtml(sec.satuan ?? '—')}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm text-muted mb-1">Harga (Rp)</label>
                            <p class="form-control form-control-sm mb-0">${harga}</p>
                        </div>
                        ${totalLine}
                        <div class="col-md-12">
                            <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                            <p class="form-control form-control-sm mb-0">${escHtml(sec.keterangan ?? '—')}</p>
                        </div>
                    </div>
                </div>
                <div class="text-muted small fw-semibold mb-1">
                    <i class="fa-solid fa-list-check me-1"></i> Items
                </div>
                <div>${itemsHtml || '<div class="text-muted small py-2">Tidak ada item</div>'}</div>`;

            return formGroup.sectionCard(
                {
                    icon: 'fa-layer-group',
                    color: 'icon-blue',
                    title: escHtml(sec.point_name ?? '—'),
                    subtitle: items.length + ' item',
                },
                bodyHtml
            );
        }).join('');

    return `
<div class="row g-3" id="detailForm">

    ${formGroup.actionBar({
        number: escHtml(res.no_wo ?? '—'),
        subtitle: escHtml(res.judul_pekerjaan ?? '—'),
        leftExtra: `${grandTotal > 0 ? `<div style="font-size:13px;font-weight:600;color:#1d4ed8;margin-top:3px;">Total BOQ: Rp ${Number(grandTotal).toLocaleString('en-US')}</div>` : ''}
            <div class="mt-1">
                <a href="/work-orders?open=${res.id_wo}" target="_blank"
                    style="display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:20px;background:#e8f0fe;color:#1a56db;font-size:11px;font-weight:600;text-decoration:none;border:1px solid #c7d7f8;">
                    <i class="fa-solid fa-briefcase" style="font-size:10px;"></i>
                    ${escHtml(res.no_wo ?? 'Lihat WO')}
                </a>
            </div>`,
        editHtml: `<button type="button" class="btn-boq-edit btn-action-edit ms-0"><i class="fa-solid fa-pen"></i> Edit</button>`,
    })}

    ${woSection}
    ${sectionsHtml}
</div>`;
}

// ── Edit mode shell ────────────────────────────────────────────────────────────
function renderBoqEditMode(res) {
    const woCard = formGroup.sectionCard(
        {
            icon: 'fa-file-lines',
            color: 'icon-navy',
            title: 'Work Order',
            subtitle: escHtml(res.no_wo ?? '') + ' — ' + escHtml(res.judul_pekerjaan ?? ''),
        },
        `<div class="row g-3">
            <div class="col-md-3">
                <label class="form-label form-label-sm text-muted mb-1">No WO</label>
                <p class="form-control form-control-sm mb-0">${escHtml(res.no_wo ?? '—')}</p>
            </div>
            <div class="col-md-9">
                <label class="form-label form-label-sm text-muted mb-1">Judul Pekerjaan</label>
                <p class="form-control form-control-sm mb-0">${escHtml(res.judul_pekerjaan ?? '—')}</p>
            </div>
        </div>`
    );

    return `
<div class="row g-3">

    <!-- ACTION BAR -->
    <div class="col-md-12">
        <div class="detail-action-bar">
            <div>
                <div class="detail-number">${escHtml(res.no_wo ?? '—')}</div>
                <div class="detail-date">${escHtml(res.judul_pekerjaan ?? '—')}</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="btnCancelEdit" class="btn-action-edit editing">
                    <i class="fa-solid fa-times"></i> Batal
                </button>
                <button type="button" id="btnSaveBoq" class="btn-action-save">
                    <i class="fa-solid fa-check"></i> Simpan
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-12">${woCard}</div>

    <div class="col-md-12" id="boqSections"></div>

    <div class="col-md-12" id="boqEmpty" style="display:none;">
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="fa-solid fa-layer-group fa-2x mb-3 d-block opacity-25"></i>
                <div class="fw-semibold mb-1">Belum ada section</div>
                <div class="small">Klik <strong>+ Tambah Section</strong> untuk menambahkan</div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <button type="button" id="btnAddSection" class="btn btn-outline-primary">
            <i class="fa-solid fa-plus me-1"></i> Tambah Section
        </button>
    </div>

</div>`;
}

// ── Single editable section card ───────────────────────────────────────────────
function renderSectionCard(pointId, pointText, items) {
    const itemsHtml = items.map((item, i) => renderItem(pointId, item, i + 1)).join('');
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
                            <input type="text" class="form-control form-control-sm input-satuan" placeholder="pcs, set...">
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
    const unit  = item.kode_unit || '—';
    const nilai = item.nilai ?? '—';
    return `
        <div class="boq-item d-flex align-items-center flex-wrap gap-2"
            data-item-id="${item.id_testing_item}"
            data-point-id="${pointId}">
            <span class="text-muted small fw-semibold item-num">${num}.</span>
            <span class="fw-semibold small">${escHtml(item.judul_indonesia ?? '—')}</span>
            <span class="text-muted small">/ ${escHtml(item.judul_inggris ?? '—')}</span>
            <span class="item-meta-badge">${escHtml(unit)} · ${escHtml(String(nilai))}</span>
        </div>`;
}

