function renderFwoForm(res) {
    const isCompleted = res.status === 'completed';

    const woBadge = res.id_wo
        ? `<a href="/work-orders?open=${res.id_wo}" class="pm-badge pm-badge--blue" style="text-decoration:none;">
               <i class="fa-solid fa-briefcase" style="font-size:10px;"></i>
               ${escHtml(res.wo_no_wo ?? 'Lihat WO')}
           </a>`
        : '';

    const siteBadge = res.site_name
        ? `<a href="/business-relations${res.id_site_pelanggan_pekerjaan ? '?open=' + res.id_site_pelanggan_pekerjaan : ''}" class="pm-badge" style="background:#f1f5f9;color:#475569;text-decoration:none;">
               <i class="fa-solid fa-location-dot" style="font-size:10px;"></i>
               ${escHtml(res.site_name)}
           </a>`
        : '';

    const statusClass = isCompleted ? 'detail-status-selesai' : 'detail-status-pending';
    const statusLabel = isCompleted ? 'Completed' : 'Planned';

    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.no_fwo ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_fwo,
        editText: 'Edit FWO',
        statusBadge: `<span class="detail-status-inline ${statusClass}">${statusLabel}</span>`,
        tags: woBadge + siteBadge,
        extra: !isCompleted
            ? `<button type="button" id="btnCompleteFwo" data-fwo-id="${res.id_fwo}" data-no-disable
                class="btn btn-sm btn-success" style="font-size:12px;">
                <i class="fa-solid fa-circle-check me-1"></i> Selesaikan FWO
               </button>`
            : '',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="fwoDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabFwoInfo">
                        <i class="fa-solid fa-hard-hat me-1" style="color:#d97706;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabFwoPersonel">
                        <i class="fa-solid fa-users me-1" style="color:#7c3aed;font-size:11px;"></i>
                        Personel
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabFwoBoq">
                        <i class="fa-solid fa-clipboard-list me-1" style="color:#16a34a;font-size:11px;"></i>
                        Fieldwork BOQ
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabFwoAttachment">
                        <i class="fa-solid fa-paperclip me-1" style="color:#7c3aed;font-size:11px;"></i>
                        Attachment
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="fwoTabActionsInfo" class="d-flex align-items-center gap-2">
                    <!-- Edit/Hapus di action bar atas -->
                </div>
                <div id="fwoTabActionsPersonel" class="d-flex align-items-center gap-2 d-none">
                    <!-- Edit personel via tombol Edit FWO di atas -->
                </div>
                <div id="fwoTabActionsBoq" class="d-flex align-items-center gap-2 d-none">
                    <button type="button" id="btnAddFwoBoqDirect" data-no-disable
                        class="pm-btn-pill pm-btn-pill--green">
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                        <i class="fa-solid fa-clipboard-list" style="font-size:11px;"></i> Kelola BOQ
                    </button>
                </div>
                <div id="fwoTabActionsAttachment" class="d-flex align-items-center gap-2 d-none">
                    <!-- Attachment dikelola via tombol Edit FWO di atas -->
                </div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <!-- TAB: INFORMASI FIELDWORK -->
                <div class="tab-pane fade show active" id="tabFwoInfo" role="tabpanel">
                    <div class="row g-3">
                        ${formGroup.sectionCard(
                            {
                                icon: 'fa-hard-hat',
                                color: 'icon-amber',
                                title: 'Informasi Fieldwork',
                                subtitle: 'Data dan jadwal kunjungan lapangan',
                            },
                            `<div class="row g-3 form-1">
                                <div class="mb-3 col-md-12">
                                    <label class="form-label">
                                        Work Order
                                        ${res.id_wo ? `<a href="/work-orders?open=${res.id_wo}"
                                            class="ms-2 text-decoration-none small" title="Buka halaman Work Order"
                                            style="color:var(--primary-500,#1a5fbe);">
                                            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:11px;"></i>
                                        </a>` : ''}
                                    </label>
                                    <select name="id_wo" class="form-select form-select-dynamic disabled"
                                        id="detail_id_wo"
                                        data-mode="ajax" data-url="${window.route.woSelect2}"
                                        data-allow-clear="false" data-placeholder="Pilih Work Order"
                                        data-minimum-input="0" data-show-all="false">
                                        ${res.id_wo && res.wo_no_wo
                                            ? `<option value="${res.id_wo}" selected>${escHtml(res.wo_no_wo)}</option>`
                                            : '<option value=""></option>'}
                                    </select>
                                </div>
                                ${formGroup.text('judul_pekerjaan', 'Judul Pekerjaan', res.judul_pekerjaan, true, { className: 'col-md-12' })}
                                ${formGroup.select('id_site_pelanggan_pekerjaan', 'Site Pelanggan', res.id_site_pelanggan_pekerjaan, [], {
                                    mode: 'ajax', url: window.route.siteSelect2, placeholder: 'Pilih Site',
                                    label: res.site_name, className: 'col-md-6', createUrl: '/business-relations/create',
                                })}
                                ${formGroup.select('id_pic_pelanggan_pekerjaan', 'PIC Pelanggan', res.id_pic_pelanggan_pekerjaan, [], {
                                    mode: 'ajax', url: window.route.picSelect2, placeholder: 'Pilih PIC',
                                    label: res.pic_name, className: 'col-md-6', createUrl: '/business-relation-contacts/create',
                                })}
                                ${formGroup.date('tanggal_mulai', 'Tanggal Mulai', res.tanggal_mulai ?? '', false, { className: 'col-md-4' })}
                                ${formGroup.date('tanggal_selesai', 'Tanggal Selesai', res.tanggal_selesai ?? '', false, { className: 'col-md-4' })}
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Waktu Kedatangan</label>
                                    <input type="text" name="waktu_kedatangan"
                                        class="form-control disabled fp-datetime"
                                        value="${res.waktu_kedatangan ? String(res.waktu_kedatangan).substring(0,16).replace('T',' ') : ''}"
                                        placeholder="Pilih tanggal & jam" autocomplete="off">
                                </div>
                                ${formGroup.textarea('keterangan', 'Keterangan', res.keterangan ?? '', { className: 'col-md-12' })}
                            </div>`
                        )}
                    </div>
                </div>

                <!-- TAB: PERSONEL -->
                <div class="tab-pane fade" id="tabFwoPersonel" role="tabpanel">
                    <div class="card card-body">
                        <div id="fwoPersonelContent">
                            <div class="text-center text-muted py-3">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: FIELDWORK BOQ -->
                <div class="tab-pane fade" id="tabFwoBoq" role="tabpanel">
                    <div class="card card-body">
                        <div id="fwoBoqContent">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: ATTACHMENT -->
                <div class="tab-pane fade" id="tabFwoAttachment" role="tabpanel">
                    <div class="card card-body">
                        <div id="fwoAttachmentContent">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
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

// ── View mode ──────────────────────────────────────────────────────────────────
function renderFwoBoqView(sections) {
    if (!sections || sections.length === 0) {
        return `<div class="text-center text-muted py-4">
            <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
            Belum ada Fieldwork BOQ
        </div>`;
    }

    const TH = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';

    const rows = sections.map(function (sec, i) {
        const satuan   = sec.satuan ? ' ' + escHtml(sec.satuan) : '';
        const qtyLabel = (sec.qty ?? '—') + (sec.boq_qty ? ' / ' + sec.boq_qty : '') + satuan;

        return `<tr>
            <td ${TD} style="padding:8px 12px;color:#94a3b8;text-align:center;font-size:12px;">${i + 1}</td>
            <td ${TD} style="padding:8px 12px;color:#374151;font-weight:500;">${escHtml(sec.point_name ?? '—')}</td>
            <td ${TD} style="padding:8px 12px;color:#374151;white-space:nowrap;">${qtyLabel}</td>
            <td ${TD} style="padding:8px 8px;text-align:center;width:40px;">
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-fwo-boq-delete" data-boq-id="${sec.id_boq}"
                    title="Hapus item ini" style="font-size:11px;">
                    <i class="fa-solid fa-trash"></i>
                </button>
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
                    <th ${TH} style="width:40px;">Aksi</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
}

// ── Edit mode: action bar ──────────────────────────────────────────────────────
function renderFwoBoqEditBar() {
    return `<div class="d-flex justify-content-start align-items-center mb-3">
        <button type="button" id="btnAddFwoBoqSection" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Tambah Item
        </button>
    </div>`;
}

// ── Edit mode: one section card ────────────────────────────────────────────────
function renderFwoBoqSectionEdit(sec) {
    const items       = sec.items ?? [];
    const remaining   = sec.remaining_qty ?? '';          // max yang boleh diinput FWO ini
    const unallocated = sec.unallocated_qty ?? remaining; // yang benar-benar belum dialokasikan siapapun
    const satuan      = sec.satuan ? ' ' + escHtml(sec.satuan) : '';
    const itemsHtml   = items.map(function (item, j) {
        return `<div class="d-flex align-items-center flex-wrap gap-2 py-1" style="border-bottom:1px solid #f1f5f9;">
            <span class="text-muted small fw-semibold">${j + 1}.</span>
            <span class="fw-semibold small">${escHtml(item.judul_indonesia ?? '—')}</span>
            <span class="text-muted small">/ ${escHtml(item.judul_inggris ?? '—')}</span>
            <span class="item-meta-badge">${escHtml(item.kode_unit || '—')} · ${escHtml(String(item.nilai ?? '—'))}</span>
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
                <span class="fw-semibold">${escHtml(sec.point_name ?? '—')}</span>
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
                        placeholder="opsional" value="${escHtml(sec.keterangan ?? '')}">
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="text-muted small fw-semibold">
                    <i class="fa-solid fa-list-check me-1"></i> Items (otomatis dari BOQ)
                </span>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-toggle-boq-items" style="font-size:11px;" title="Lihat items">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
            <div class="fwo-boq-items" style="display:none;">${itemsHtml || '<div class="text-muted small py-2">Tidak ada item</div>'}</div>
        </div>
    </div>`;
}

// ── Modal item row (read-only preview) ────────────────────────────────────────
function renderFwoBoqModalItem(item, j) {
    return `<div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid #f1f5f9;">
        <span class="text-muted small fw-semibold">${j + 1}.</span>
        <div>
            <span class="fw-semibold small">${escHtml(item.judul_indonesia ?? '—')}</span>
            <span class="text-muted small ms-1">/ ${escHtml(item.judul_inggris ?? '—')}</span>
        </div>
        <span class="item-meta-badge ms-auto flex-shrink-0">${escHtml(item.kode_unit || '—')} · ${escHtml(String(item.nilai ?? '—'))}</span>
    </div>`;
}

function fwoDatetimeLocal(val) {
    if (!val) return '';
    return String(val).replace(' ', 'T').substring(0, 16);
}


// ── Personel view ──────────────────────────────────────────────────────────────
function renderPersonelView(personels) {
    if (!personels || personels.length === 0) {
        return `<div class="text-center text-muted py-4">
            <i class="fa-solid fa-users fa-2x d-block mb-2 opacity-25"></i>
            Belum ada personel
        </div>`;
    }

    const roleColors = {
        'Leader':  { bg: '#fef9c3', color: '#854d0e' },
        'Driver':  { bg: '#dbeafe', color: '#1e40af' },
        'Anggota': { bg: '#f0fdf4', color: '#166534' },
    };

    const TH = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';

    const rows = personels.map(function (p, i) {
        const rc   = roleColors[p.role] || { bg: '#f1f5f9', color: '#475569' };
        const role = p.role
            ? `<span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:20px;background:${rc.bg};color:${rc.color};">${escHtml(p.role)}</span>`
            : `<span style="font-size:11px;color:#94a3b8;">—</span>`;
        return `<tr>
            <td ${TD} style="padding:8px 12px;color:#94a3b8;text-align:center;font-size:12px;">${i + 1}</td>
            <td ${TD} style="padding:8px 12px;color:#1e293b;font-weight:500;">${escHtml(p.user_name)}</td>
            <td ${TD} style="padding:8px 12px;">${role}</td>
        </tr>`;
    }).join('');

    return `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th ${TH} style="width:40px;text-align:center;">No</th>
                    <th ${TH}>Nama</th>
                    <th ${TH} style="min-width:100px;">Role</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
}

// ── Attachment constants ───────────────────────────────────────────────────────
const FWO_ATTACHMENT_TYPES = ['Berita Acara', 'Foto Lapangan', 'Laporan Teknis', 'Dokumen Lainnya'];

const FWO_ATT_ICON = {
    pdf:  { icon: 'fa-file-pdf',   bg: '#fee2e2', color: '#dc2626' },
    jpg:  { icon: 'fa-image',      bg: '#e8f0fe', color: '#1a5fbe' },
    jpeg: { icon: 'fa-image',      bg: '#e8f0fe', color: '#1a5fbe' },
    png:  { icon: 'fa-image',      bg: '#e8f0fe', color: '#1a5fbe' },
    xls:  { icon: 'fa-file-excel', bg: '#dcfce7', color: '#166534' },
    xlsx: { icon: 'fa-file-excel', bg: '#dcfce7', color: '#166534' },
    doc:  { icon: 'fa-file-word',  bg: '#dbeafe', color: '#1d4ed8' },
    docx: { icon: 'fa-file-word',  bg: '#dbeafe', color: '#1d4ed8' },
};

// ── Attachment view mode ───────────────────────────────────────────────────────
function renderFwoAttachmentView(groups) {
    if (!groups || !groups.length) {
        return `<div class="text-center text-muted py-4">
            <i class="fa-solid fa-paperclip fa-2x d-block mb-2 opacity-25"></i>
            Belum ada attachment
        </div>`;
    }

    return groups.map(function (group) {
        const files = (group.files || []).map(function (path) {
            const ext  = path.split('.').pop().toLowerCase();
            const name = path.split('/').pop();
            const ic   = FWO_ATT_ICON[ext] || { icon: 'fa-file', bg: '#f3f4f6', color: '#6b7280' };
            const url  = '/storage/' + path;
            return `<div class="att-row">
                <div class="att-icon" style="background:${ic.bg};color:${ic.color};">
                    <i class="fa-solid ${ic.icon}"></i>
                </div>
                <div class="att-info">
                    <span class="att-name" title="${escHtml(name)}">${escHtml(name)}</span>
                    <span class="att-ext">${ext.toUpperCase()}</span>
                </div>
                <div class="att-actions">
                    <a href="${url}" download class="att-btn att-btn-download" title="Download">
                        <i class="fa-solid fa-download"></i>
                    </a>
                </div>
            </div>`;
        }).join('');

        return `<div class="mb-4">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="fa-solid fa-folder-open" style="color:#7c3aed;font-size:13px;"></i>
                <span class="fw-semibold" style="font-size:13px;color:#374151;">${escHtml(group.type)}</span>
                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:10px;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;">${group.files?.length ?? 0} file</span>
            </div>
            <div class="att-list">${files || '<div class="text-muted small">Tidak ada file</div>'}</div>
        </div>`;
    }).join('<hr style="margin:8px 0;border-color:#f1f5f9;">');
}

// ── Attachment edit mode ───────────────────────────────────────────────────────
function renderFwoAttachmentEditBar() {
    return `<div class="d-flex justify-content-start mb-3">
        <button type="button" id="btnAddAttachmentGroup" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Tambah Tipe Dokumen
        </button>
    </div>`;
}

function renderFwoAttachmentGroupEdit(group, idx) {
    const typeOptions = FWO_ATTACHMENT_TYPES.map(function (t) {
        return `<option value="${escHtml(t)}" ${(group.type === t) ? 'selected' : ''}>${escHtml(t)}</option>`;
    }).join('');

    const existingFiles = (group.files || []).map(function (path) {
        const ext  = path.split('.').pop().toLowerCase();
        const name = path.split('/').pop();
        const ic   = FWO_ATT_ICON[ext] || { icon: 'fa-file', bg: '#f3f4f6', color: '#6b7280' };
        return `<div class="att-row att-existing-file" data-path="${escHtml(path)}">
            <input type="hidden" class="fwo-att-existing" value="${escHtml(path)}">
            <div class="att-icon" style="background:${ic.bg};color:${ic.color};">
                <i class="fa-solid ${ic.icon}"></i>
            </div>
            <div class="att-info">
                <span class="att-name" title="${escHtml(name)}">${escHtml(name)}</span>
                <span class="att-ext">${ext.toUpperCase()}</span>
            </div>
            <div class="att-actions">
                <button type="button" class="att-btn att-btn-delete btn-remove-att-existing" title="Hapus">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>`;
    }).join('');

    return `<div class="card mb-3 fwo-att-group" data-idx="${idx}">
        <div class="card-header d-flex justify-content-between align-items-center py-2 px-3"
            style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
            <select class="form-select form-select-sm fwo-att-type" style="width:220px;">
                ${typeOptions}
            </select>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-att-group py-1 px-2" style="font-size:12px;">
                <i class="fa-solid fa-trash me-1"></i> Hapus Grup
            </button>
        </div>
        <div class="card-body px-3 py-3">
            ${existingFiles ? `<div class="att-list mb-3">${existingFiles}</div>` : ''}
            <input type="file" class="fwo-att-filepond" multiple>
        </div>
    </div>`;
}
