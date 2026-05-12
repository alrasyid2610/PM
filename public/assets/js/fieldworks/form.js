function renderFwoForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <!-- ACTION BAR -->
    <div class="col-md-12">
        <div class="detail-action-bar">
            <div>
                <div class="detail-number">${escFwo(res.no_fwo ?? '—')}</div>
                <div class="detail-date">
                    Dibuat ${escFwo(res.created_at ?? '—')} &nbsp;·&nbsp; Diupdate ${escFwo(res.updated_at ?? '—')}
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                ${res.id_wo ? `<a href="/work-orders?open=${res.id_wo}" target="_blank"
                    style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;background:#e8f0fe;color:#1a56db;font-size:12px;font-weight:600;text-decoration:none;border:1px solid #c7d7f8;transition:background 0.15s;"
                    onmouseover="this.style.background='#c7d7f8'" onmouseout="this.style.background='#e8f0fe'">
                    <i class="fa-solid fa-briefcase" style="font-size:11px;"></i>
                    ${escFwo(res.wo_no_wo ?? 'Lihat WO')}
                </a>` : ''}
                ${formGroup.editButton('Edit FWO')}
            </div>
        </div>
    </div>

    ${formGroup.sectionCard(
        {
            icon: 'fa-hard-hat',
            color: 'icon-amber',
            title: 'Informasi Fieldwork',
            subtitle: escFwo(res.no_fwo ?? '') + (res.judul_pekerjaan ? ' — ' + escFwo(res.judul_pekerjaan) : ''),
            editTitle: 'Edit Fieldwork',
        },
        `<div class="row g-3 form-1">
            ${formGroup.select('id_wo', 'Work Order', res.id_wo, [], {
                mode: 'ajax',
                url: window.route.woSelect2,
                placeholder: 'Pilih Work Order',
                label: res.wo_no_wo,
                className: 'col-md-12',
            })}
            ${formGroup.text('judul_pekerjaan', 'Judul Pekerjaan', res.judul_pekerjaan, true, {
                className: 'col-md-12',
            })}
            ${formGroup.select('id_site_pelanggan_pekerjaan', 'Site Pelanggan', res.id_site_pelanggan_pekerjaan, [], {
                mode: 'ajax',
                url: window.route.siteSelect2,
                placeholder: 'Pilih Site',
                label: res.site_name,
                className: 'col-md-6',
                createUrl: '/business-relations/create',
            })}
            ${formGroup.select('id_pic_pelanggan_pekerjaan', 'PIC Pelanggan', res.id_pic_pelanggan_pekerjaan, [], {
                mode: 'ajax',
                url: window.route.picSelect2,
                placeholder: 'Pilih PIC',
                label: res.pic_name,
                className: 'col-md-6',
                createUrl: '/business-relation-contacts/create',
            })}
            ${formGroup.date('tanggal_mulai', 'Tanggal Mulai', res.tanggal_mulai ?? '', false, {
                className: 'col-md-4',
            })}
            ${formGroup.date('tanggal_selesai', 'Tanggal Selesai', res.tanggal_selesai ?? '', false, {
                className: 'col-md-4',
            })}
            <div class="col-md-4 mb-3">
                <label class="form-label">Waktu Kedatangan</label>
                <input type="datetime-local" name="waktu_kedatangan"
                    class="form-control disabled"
                    value="${fwoDatetimeLocal(res.waktu_kedatangan)}">
            </div>
            ${formGroup.textarea('keterangan', 'Keterangan', res.keterangan ?? '', {
                className: 'col-md-12',
            })}
        </div>`
    )}
</form>

<div class="row g-3 mt-1">
    ${formGroup.sectionCard(
        {
            icon: 'fa-clipboard-list',
            color: 'icon-green',
            title: 'Fieldwork BOQ',
            subtitle: 'BOQ yang dikerjakan pada kunjungan lapangan ini',
            id: 'fwoBoqSection',
            actions: `<button type="button" class="btn-fwo-boq-edit btn-action-edit ms-0">
                <i class="fa-solid fa-pen"></i> Edit BOQ
            </button>`,
        },
        `<div id="fwoBoqContent">
            <div class="text-center text-muted py-4">
                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
            </div>
        </div>`
    )}
</div>`;
}

// ── View mode ──────────────────────────────────────────────────────────────────
function renderFwoBoqView(sections) {
    if (!sections || sections.length === 0) {
        return `<div class="text-center text-muted py-4">
            <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
            Belum ada Fieldwork BOQ
        </div>`;
    }

    return sections.map(function (sec) {
        const items    = sec.items ?? [];
        const satuan   = sec.satuan ? ' ' + escFwo(sec.satuan) : '';
        const qtyLabel = (sec.qty ?? '—') + (sec.boq_qty ? ' / ' + sec.boq_qty : '') + satuan;

        const pct      = (sec.boq_qty && sec.qty) ? Math.min(100, Math.round((sec.qty / sec.boq_qty) * 100)) : 0;
        const barColor = pct >= 100 ? 'bg-success' : pct > 0 ? 'bg-primary' : 'bg-secondary';

        const priceHtml = sec.harga > 0
            ? `<div style="font-size:11px;color:#64748b;margin-top:3px;">
                <span>Rp ${Number(sec.harga).toLocaleString('en-US')}${satuan ? ' /' + satuan : ''}</span>
                <span style="margin:0 4px;">&times;</span>
                <span>${sec.qty ?? 0}${satuan}</span>
                <span style="margin:0 4px;">=</span>
                <strong style="color:#1d4ed8;">Rp ${Number((sec.qty ?? 0) * sec.harga).toLocaleString('en-US')}</strong>
               </div>`
            : '';

        const progressHtml = `
            <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                <span class="small text-muted">Progress kunjungan ini</span>
                <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;${pct >= 100 ? 'background:#198754;color:#fff;' : 'background:#dbeafe;color:#1d4ed8;'}">${pct}%</span>
            </div>
            <div class="progress" style="height:5px;">
                <div class="progress-bar ${barColor}" style="width:${pct}%;transition:width .4s;"></div>
            </div>`;

        const itemsHtml = items.map(function (item, j) {
            return `<div class="d-flex align-items-center flex-wrap gap-2 py-2" style="border-bottom:1px solid #f1f5f9;">
                <span class="text-muted small fw-semibold">${j + 1}.</span>
                <span class="fw-semibold small">${escFwo(item.judul_indonesia ?? '—')}</span>
                <span class="text-muted small">/ ${escFwo(item.judul_inggris ?? '—')}</span>
                <span class="item-meta-badge">${escFwo(item.kode_unit || '—')} · ${escFwo(String(item.nilai ?? '—'))}</span>
            </div>`;
        }).join('');

        return formGroup.sectionCard(
            {
                icon: 'fa-layer-group',
                color: 'icon-blue',
                title: escFwo(sec.point_name ?? '—'),
                subtitle: items.length + ' item · Qty: ' + qtyLabel,
            },
            `${priceHtml}
            ${progressHtml}
            <div class="text-muted small fw-semibold mb-1 mt-3">
                <i class="fa-solid fa-list-check me-1"></i> Items
            </div>
            <div>${itemsHtml || '<div class="text-muted small py-2">Tidak ada item</div>'}</div>
            ${sec.keterangan ? `<div class="mt-2 small text-muted"><i class="fa-solid fa-note-sticky me-1"></i>${escFwo(sec.keterangan)}</div>` : ''}`
        );
    }).join('');
}

// ── Edit mode: action bar ──────────────────────────────────────────────────────
function renderFwoBoqEditBar() {
    return `<div class="d-flex justify-content-between align-items-center mb-3">
        <button type="button" id="btnAddFwoBoqSection" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Tambah Section
        </button>
        <div class="d-flex gap-2">
            <button type="button" id="btnCancelFwoBoq" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-times me-1"></i> Batal
            </button>
            <button type="button" id="btnSaveFwoBoq" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-check me-1"></i> Simpan BOQ
            </button>
        </div>
    </div>`;
}

// ── Edit mode: one section card ────────────────────────────────────────────────
function renderFwoBoqSectionEdit(sec) {
    const items       = sec.items ?? [];
    const remaining   = sec.remaining_qty ?? '';          // max yang boleh diinput FWO ini
    const unallocated = sec.unallocated_qty ?? remaining; // yang benar-benar belum dialokasikan siapapun
    const satuan      = sec.satuan ? ' ' + escFwo(sec.satuan) : '';
    const itemsHtml   = items.map(function (item, j) {
        return `<div class="d-flex align-items-center flex-wrap gap-2 py-1" style="border-bottom:1px solid #f1f5f9;">
            <span class="text-muted small fw-semibold">${j + 1}.</span>
            <span class="fw-semibold small">${escFwo(item.judul_indonesia ?? '—')}</span>
            <span class="text-muted small">/ ${escFwo(item.judul_inggris ?? '—')}</span>
            <span class="item-meta-badge">${escFwo(item.kode_unit || '—')} · ${escFwo(String(item.nilai ?? '—'))}</span>
        </div>`;
    }).join('');

    return `<div class="card mb-3 fwo-boq-section"
            data-boq-id="${sec.id_boq}"
            data-point-id="${sec.id_testing_point}"
            data-remaining-qty="${remaining}">
        <div class="card-header d-flex justify-content-between align-items-center py-2 px-3"
            style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <i class="fa-solid fa-layer-group" style="color:#2563eb;"></i>
                <span class="fw-semibold">${escFwo(sec.point_name ?? '—')}</span>
                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary" style="font-size:11px;">
                    ${items.length} item
                </span>
                ${unallocated !== '' ? `<span class="badge bg-warning text-dark" style="font-size:11px;">Sisa: ${unallocated}${satuan}</span>` : ''}
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-fwo-boq py-1 px-2" style="font-size:12px;">
                <i class="fa-solid fa-trash me-1"></i> Hapus
            </button>
        </div>
        <div class="card-body px-3 py-3">
            <div class="row g-2 mb-3" style="background:#fafbfc;border:1px solid #e9ecef;border-radius:6px;padding:10px 12px;">
                <div class="col-md-4">
                    <label class="form-label form-label-sm text-muted mb-1">Qty</label>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm input-fwo-qty input-num-mask input-num-int"
                        placeholder="0"
                        value="${sec.qty ?? ''}">
                </div>
                <div class="col-md-8">
                    <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                    <input type="text" class="form-control form-control-sm input-fwo-ket"
                        placeholder="opsional" value="${escFwo(sec.keterangan ?? '')}">
                </div>
            </div>
            <div class="text-muted small fw-semibold mb-1">
                <i class="fa-solid fa-list-check me-1"></i> Items (otomatis dari BOQ)
            </div>
            <div class="fwo-boq-items">${itemsHtml || '<div class="text-muted small py-2">Tidak ada item</div>'}</div>
        </div>
    </div>`;
}

// ── Modal item row (read-only preview) ────────────────────────────────────────
function renderFwoBoqModalItem(item, j) {
    return `<div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid #f1f5f9;">
        <span class="text-muted small fw-semibold">${j + 1}.</span>
        <div>
            <span class="fw-semibold small">${escFwo(item.judul_indonesia ?? '—')}</span>
            <span class="text-muted small ms-1">/ ${escFwo(item.judul_inggris ?? '—')}</span>
        </div>
        <span class="item-meta-badge ms-auto flex-shrink-0">${escFwo(item.kode_unit || '—')} · ${escFwo(String(item.nilai ?? '—'))}</span>
    </div>`;
}

function fwoDatetimeLocal(val) {
    if (!val) return '';
    return String(val).replace(' ', 'T').substring(0, 16);
}

function escFwo(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
