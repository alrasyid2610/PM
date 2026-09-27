// SQ Fieldwork — halaman detail dengan tab Informasi/BOQ/Budget, meniru
// struktur halaman SQ Work Order persis (menu sendiri, halaman sendiri, tab
// sama) — lihat Obsidian Modules/Sales Quotation.md.

function renderForm(res) {
    // Hanya SQ berstatus Draft yang bisa diubah (lihat SqLock di backend)
    const locked = res.sq_status && res.sq_status !== 'draft';

    const sqTag = res.no_sq
        ? `<a href="/sales-quotations?open=${res.id_sq}" class="pm-badge pm-badge--blue" style="text-decoration:none;">
               <i class="fa-solid fa-file-signature" style="font-size:10px;"></i>
               ${escHtml(res.no_sq)}${res.revisi > 0 ? ' Rev.' + res.revisi : ''}
           </a>`
        : '';

    const woTag = res.no_sq_wo
        ? `<a href="/sq-work-orders?open=${res.id_sq_wo}" class="pm-badge" style="background:#f1f5f9;color:#475569;text-decoration:none;">
               <i class="fa-solid fa-briefcase" style="font-size:10px;"></i>
               ${escHtml(res.no_sq_wo)}
           </a>`
        : '';

    const pelangganTagParams = res.id_br_pekerjaan
        ? '?open=' + res.id_br_pekerjaan + (res.id_site_pelanggan_pekerjaan ? '&tab=tabBrsSite&site=' + res.id_site_pelanggan_pekerjaan : '')
        : '';
    const pelangganLabel = brSiteLabel(res.nama_pelanggan_display, res.nama_pelanggan_pekerjaan, res.nama_site_pelanggan_pekerjaan);
    const pelangganTag = pelangganLabel
        ? `<a href="/business-relations${pelangganTagParams}" class="pm-badge" style="background:#f1f5f9;color:#475569;text-decoration:none;">
               <i class="fa-solid fa-location-dot" style="font-size:10px;"></i>
               ${escHtml(pelangganLabel)}
           </a>`
        : '';

    return `
<form id="detailForm" class="${locked ? 'sq-locked' : ''}">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.no_sq_fwo ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: locked ? null : res.id_sq_fwo,
        editText: locked ? '' : 'Edit',
        tags: sqTag + woTag + pelangganTag,
        extra: locked
            ? `<span style="font-size:11px;color:#64748b;display:flex;align-items:center;gap:5px;"><i class="fa-solid fa-lock" style="font-size:10px;"></i> SQ berstatus ${escHtml(res.sq_status)} — terkunci</span>`
            : '',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="sqFwoDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabFwoInfo">
                        <i class="fa-solid fa-circle-info me-1" style="color:#6366f1;font-size:11px;"></i> Informasi
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabFwoBoq" data-id-sq-fwo="${res.id_sq_fwo}">
                        <i class="fa-solid fa-layer-group me-1" style="color:#1a56db;font-size:11px;"></i> BOQ
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabFwoBudget" data-id-sq-fwo="${res.id_sq_fwo}">
                        <i class="fa-solid fa-wallet me-1" style="color:#0f766e;font-size:11px;"></i> Budget
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="sqFwoTabActionsInfo" class="d-flex align-items-center gap-2"></div>
                <div id="sqFwoTabActionsBoq" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill pm-btn-pill--blue btn-kelola-sq-fwo-boq" data-no-disable>
                        <i class="fa-solid fa-layer-group" style="font-size:10px;"></i> Kelola BOQ
                    </button>
                </div>
                <div id="sqFwoTabActionsBudget" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill pm-btn-pill--teal btn-sq-fwo-budget-add" data-no-disable>
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i> Tambah Budget Plan
                    </button>
                </div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabFwoInfo" role="tabpanel">
                    <div class="row g-3">
    ${formGroup.sectionCard(
        { icon: 'fa-helmet-safety', color: 'icon-navy', title: 'Informasi Fieldwork', subtitle: 'Data pekerjaan lapangan estimasi' },
        `<div class="row g-3 form-1">
            ${formGroup.text('judul_pekerjaan', 'Judul Pekerjaan', res.judul_pekerjaan, false, { className: 'col-md-12' })}
            ${formGroup.text('hari_ke', 'Hari Ke-', res.hari_ke, true, { className: 'col-md-3' })}
            ${formGroup.text('durasi_hari', 'Durasi (hari)', res.durasi_hari, false, { className: 'col-md-3' })}
            <div class="col-md-6" style="align-self:end;">
                <span style="font-size:11px;color:#94a3b8;">
                    Jadwal WO induk: Hari ke-${res.wo_hari_mulai ?? '?'}${res.wo_durasi_hari ? ' (' + res.wo_durasi_hari + ' hari)' : ''}
                </span>
            </div>
            ${formGroup.select('id_site_pelanggan_pekerjaan', 'Site Pekerjaan', res.id_site_pelanggan_pekerjaan, [], {
                mode: 'ajax', url: '/business-relations/sites/select2', placeholder: 'Pilih Site',
                label: res.nama_site_pelanggan_pekerjaan, className: 'col-md-6', required: true,
            })}
            ${formGroup.select('id_pic_pelanggan_pekerjaan', 'PIC Pekerjaan', res.id_pic_pelanggan_pekerjaan, [], {
                mode: 'ajax', url: '/business-relation-contacts/select2', placeholder: 'Pilih PIC',
                label: res.nama_pic, className: 'col-md-6', required: true,
            })}
            ${formGroup.textarea('keterangan', 'Keterangan', res.keterangan, { className: 'col-md-12' })}
        </div>`,
    )}
                    </div>
                </div>
                <div class="tab-pane fade" id="tabFwoBoq" role="tabpanel">
                    <div class="card card-body">
                        <div id="sqFwoBoqSummary">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-hourglass-half me-1"></i> Tab BOQ menyusul (Fase 3 langkah berikutnya).
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tabFwoBudget" role="tabpanel">
                    <div class="card card-body">
                        <div id="sqFwoBudgetContent">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-hourglass-half me-1"></i> Tab Budget menyusul (Fase 3 langkah berikutnya).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
`;
}

function formatRupiahSqFwo(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

// ── Tab BOQ — view mode (read-only), meniru persis renderFwoBoqView() di
// fieldworks/form.js. Edit selalu lewat modal "Kelola BOQ" (bulk), bukan
// inline — pola sama FWO asli (fwoTabActionsBoq cuma punya 1 tombol).
function renderSqFwoBoqView(sections, isLocked) {
    if (!sections || sections.length === 0) {
        return `<div class="text-center text-muted py-4">
            <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
            Belum ada Fieldwork BOQ
        </div>`;
    }

    const TH = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';

    const rows = sections.map(function (sec, i) {
        const satuan = sec.satuan ? ' ' + escHtml(sec.satuan) : '';
        const qtyLabel = (sec.qty ?? '—') + (sec.boq_qty ? ' / ' + sec.boq_qty : '') + satuan;

        return `<tr>
            <td ${TD} style="color:#94a3b8;text-align:center;font-size:12px;">${i + 1}</td>
            <td ${TD} style="color:#374151;font-weight:500;">${escHtml(sec.point_name ?? '—')}</td>
            <td ${TD} style="color:#374151;white-space:nowrap;">${qtyLabel}</td>
            <td ${TD} style="text-align:center;width:40px;">
                ${isLocked ? '' : `<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-sq-fwo-boq-delete" data-boq-id="${sec.id_sq_boq}"
                    title="Hapus item ini" style="font-size:11px;">
                    <i class="fa-solid fa-trash"></i>
                </button>`}
            </td>
        </tr>`;
    }).join('');

    return `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th ${TH} style="width:40px;text-align:center;">No</th>
                    <th ${TH}>Item BOQ</th>
                    <th ${TH} style="min-width:120px;">Qty</th>
                    ${isLocked ? '' : `<th ${TH} style="width:40px;">Aksi</th>`}
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
}

// ── Modal Bulk BOQ — daftar semua BOQ WO induk + qty/keterangan, meniru
// persis renderBulkBoqList() di fieldworks/index.js.
function renderSqFwoBulkBoqList(boqItems, currentSections) {
    const TD = 'style="padding:8px 10px;vertical-align:middle;"';
    const TH = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';

    const added = boqItems.filter(function (item) {
        return (currentSections || []).some(function (s) { return String(s.id_sq_boq) === String(item.id); });
    });
    const notAdded = boqItems.filter(function (item) {
        return !(currentSections || []).some(function (s) { return String(s.id_sq_boq) === String(item.id); });
    });

    function buildRow(item, num) {
        const existing = (currentSections || []).find(function (s) { return String(s.id_sq_boq) === String(item.id); });
        const existingQty = existing ? (existing.qty ?? '') : '';
        const existingKet = existing ? (existing.keterangan ?? '') : '';
        const satuan = item.satuan ? ' ' + escHtml(item.satuan) : '';
        const sisaColor = (item.remaining_qty > 0) ? '#1d4ed8' : '#dc2626';

        return `<tr>
            <td ${TD} style="color:#94a3b8;text-align:center;font-size:12px;">${num}</td>
            <td ${TD} style="color:#1e293b;font-weight:500;">
                ${escHtml(item.text ?? '—')}
                <button type="button" class="btn-sq-fwo-bulk-eye"
                    data-boq-id="${item.id}" title="Lihat detail items"
                    style="background:none;border:none;padding:0 0 0 4px;cursor:pointer;color:#94a3b8;font-size:12px;vertical-align:middle;line-height:1;">
                    <i class="fa-solid fa-eye"></i>
                </button>
                <div class="sq-fwo-bulk-boq-items-detail mt-1" style="display:none;"></div>
            </td>
            <td ${TD} style="color:#475569;white-space:nowrap;">${item.qty_boq ?? '—'}${satuan}</td>
            <td ${TD} style="white-space:nowrap;">
                <span style="color:${sisaColor};font-weight:600;">${item.remaining_qty ?? '—'}${satuan}</span>
            </td>
            <td ${TD} style="width:110px;">
                <input type="number" class="form-control form-control-sm sq-fwo-bulk-boq-qty"
                    data-boq-id="${item.id}"
                    data-max="${item.remaining_qty ?? ''}"
                    min="0" placeholder="0" value="${escHtml(String(existingQty))}">
            </td>
            <td ${TD}>
                <input type="text" class="form-control form-control-sm sq-fwo-bulk-boq-ket"
                    placeholder="opsional" value="${escHtml(existingKet)}">
            </td>
        </tr>`;
    }

    const addedRows = added.map(function (item, i) { return buildRow(item, i + 1); }).join('');
    const notAddedRows = notAdded.map(function (item, i) { return buildRow(item, added.length + i + 1); }).join('');
    const dividerRow = (added.length && notAdded.length)
        ? `<tr><td colspan="6" style="padding:4px 0;">
            <hr style="margin:4px 12px;border-color:#e2e8f0;">
            <span style="display:block;text-align:center;color:#94a3b8;font-size:11px;font-weight:600;letter-spacing:.4px;margin-bottom:4px;">+ Tambahkan item lainnya</span>
        </td></tr>`
        : '';

    return `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th ${TH} style="width:40px;text-align:center;">#</th>
                    <th ${TH}>Item BOQ</th>
                    <th ${TH}>Qty Kontrak</th>
                    <th ${TH}>Sisa</th>
                    <th ${TH} style="width:110px;">Qty FWO</th>
                    <th ${TH}>Keterangan</th>
                </tr>
            </thead>
            <tbody>${addedRows}${dividerRow}${notAddedRows}</tbody>
        </table>
    </div>`;
}

// ── Tab Budget — kartu Plan + Item, meniru persis renderSqWoBudgetList().
function renderSqFwoBudgetList(plans) {
    if (!plans.length) {
        return `<div class="text-center text-muted py-4">
            <i class="fa-solid fa-wallet fa-2x d-block mb-2 opacity-25"></i> Belum ada Budget Plan.
        </div>`;
    }

    return plans.map(function (p) {
        const itemRows = p.items.map(function (item, idx) {
            const caBadge = item.is_cash_advance
                ? `<span class="badge ms-1" style="background:#eff6ff;color:#1d4ed8;font-size:10px;font-weight:500;border:1px solid #bfdbfe;">CA</span>` : '';
            const categoryCell = item.nama_category
                ? `<span style="font-size:11px;color:#6b7280;background:#f1f5f9;padding:1px 6px;border-radius:4px;">${escHtml(item.nama_category)}</span>`
                : `<span class="text-muted" style="font-size:11px;">—</span>`;
            return `<tr>
                <td style="width:36px;text-align:center;color:#94a3b8;font-size:11px;">${idx + 1}</td>
                <td>${categoryCell}</td>
                <td><span class="fw-semibold">${escHtml(item.nama_account)}</span>${caBadge}</td>
                <td style="color:#1d4ed8;font-weight:600;">${formatRupiahSqFwo(item.nominal_budget)}</td>
                <td>${escHtml(item.keterangan || '—')}</td>
            </tr>`;
        }).join('');

        return `<div class="mb-3 border rounded" data-id-sq-budget="${p.id_sq_budget}">
            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                style="background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:calc(0.375rem - 1px) calc(0.375rem - 1px) 0 0;">
                <div class="d-flex align-items-center flex-wrap gap-1">
                    <span class="fw-bold" style="font-size:13px;">${escHtml(p.label)}</span>
                    ${(p.hari_mulai || p.hari_selesai) ? `<span class="text-muted ms-1" style="font-size:11px;">
                        <i class="fa-regular fa-calendar me-1"></i>Hari ke-${p.hari_mulai ?? '?'}${p.hari_selesai ? ' – ke-' + p.hari_selesai : ''}
                    </span>` : ''}
                    ${p.keterangan ? `<span class="text-muted ms-1" style="font-size:11px;">· ${escHtml(p.keterangan)}</span>` : ''}
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span style="font-size:12px;">Total: <b style="color:#1d4ed8;">${formatRupiahSqFwo(p.total_budget)}</b></span>
                    <button type="button" class="pm-btn-icon btn-edit-sq-fwo-budget" title="Edit" data-no-disable>
                        <i class="fa-solid fa-pen" style="font-size:11px;"></i>
                    </button>
                    <button type="button" class="pm-btn-icon btn-remove-sq-fwo-budget" title="Hapus" data-no-disable style="color:#dc2626;">
                        <i class="fa-solid fa-trash" style="font-size:11px;"></i>
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead style="background:#fff;"><tr>
                        <th style="width:36px;"></th><th>Kategori</th><th>Account</th><th>Nominal</th><th>Keterangan</th>
                    </tr></thead>
                    <tbody>${itemRows}</tbody>
                </table>
            </div>
        </div>`;
    }).join('');
}

function buildSqFwoBudgetItemRow(item) {
    return `<tr class="sq-fwo-budget-item-row">
        <td>
            <input type="hidden" class="bi-id" value="${item ? item.id_sq_budget_item : ''}">
            <select class="form-select form-select-sm bi-account" data-no-disable style="min-width:160px;">
                ${item ? `<option value="${item.id_account}" selected>${escHtml(item.nama_account)}</option>` : '<option value="">Pilih Account</option>'}
            </select>
        </td>
        <td>
            <input type="text" inputmode="numeric" class="form-control form-control-sm input-num-mask input-num-int bi-nominal"
                value="${item ? Number(item.nominal_budget).toLocaleString('en-US') : ''}" placeholder="0" data-no-disable>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm bi-keterangan" value="${item ? escHtml(item.keterangan || '') : ''}" placeholder="Opsional" data-no-disable>
        </td>
        <td class="text-center">
            <div class="form-check d-flex justify-content-center mb-0">
                <input type="checkbox" class="form-check-input bi-ca" data-no-disable ${item && item.is_cash_advance ? 'checked' : ''} title="Cash Advance" style="width:18px;height:18px;cursor:pointer;">
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-sq-fwo-budget-remove-row" data-no-disable><i class="fa-solid fa-trash"></i></button>
        </td>
    </tr>`;
}
