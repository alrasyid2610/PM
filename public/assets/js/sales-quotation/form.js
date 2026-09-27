function sqStatusBadge(res) {
    if (res.deleted_at) {
        return `<span class="pm-badge" style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;">
                   <i class="fa-solid fa-trash" style="font-size:10px;"></i> Deleted
               </span>`;
    }
    const map = {
        draft:     { bg: '#f1f5f9', color: '#475569', border: '#e2e8f0', icon: 'fa-pen', label: 'Draft' },
        final:     { bg: '#eff6ff', color: '#1d4ed8', border: '#bfdbfe', icon: 'fa-lock', label: 'Final' },
        completed: { bg: '#f0fdf4', color: '#15803d', border: '#bbf7d0', icon: 'fa-circle-check', label: 'Completed' },
        cancel:    { bg: '#fff7ed', color: '#c2410c', border: '#fed7aa', icon: 'fa-ban', label: 'Cancel' },
    };
    const s = map[res.status] || map.draft;
    return `<span class="pm-badge" style="background:${s.bg};color:${s.color};border:1px solid ${s.border};">
               <i class="fa-solid ${s.icon}" style="font-size:10px;"></i> ${s.label}
           </span>`;
}

function renderForm(res) {
    const isDeleted = !!res.deleted_at;
    // Hanya Draft yang bisa diedit; Final/Completed/Cancel terkunci
    const isDraft = res.status === 'draft';
    const isFinal = res.status === 'final';

    const pelangganTagParams = res.id_pelanggan
        ? '?open=' + res.id_pelanggan + (res.id_site_pelanggan ? '&tab=tabBrsSite&site=' + res.id_site_pelanggan : '')
        : '';
    const pelangganTag = res.nama_pelanggan
        ? `<a href="/business-relations${pelangganTagParams}" class="pm-badge" style="background:#f1f5f9;color:#475569;text-decoration:none;">
               <i class="fa-solid fa-building" style="font-size:10px;"></i>
               ${escHtml(res.nama_pelanggan_display ?? res.nama_pelanggan)}
           </a>`
        : '';

    const revBadge = res.revisi > 0
        ? `<span class="pm-badge" style="background:#f5f3ff;color:#6d28d9;">Rev.${res.revisi}</span>`
        : '';

    // Link ke SO hasil Terbitkan SO (kalau sudah pernah)
    const soTerbitTag = res.id_so_terbit
        ? `<a href="/sales-orders?open=${res.id_so_terbit}" class="pm-badge pm-badge--blue" style="text-decoration:none;">
               <i class="fa-solid fa-file-invoice-dollar" style="font-size:10px;"></i>
               ${escHtml(res.no_so_terbit)}
           </a>`
        : '';

    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.no_sq ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: (isDeleted || res.status === 'completed') ? null : res.id_sq,
        editText: (isDeleted || !isDraft) ? '' : 'Edit SQ',
        statusBadge: sqStatusBadge(res),
        tags: revBadge + soTerbitTag + pelangganTag,
        moreActions: [
            {
                label: "Printout SQ (Sementara)",
                icon: "fa-solid fa-file-pdf",
                attrs: `onclick="window.open('/sales-quotations/${res.id_sq}/print','_blank')"`,
            },
            ...(!isDeleted && res.status === 'completed' ? [{
                label: "Executive Summary (Internal)",
                icon: "fa-solid fa-chart-pie",
                attrs: `onclick="window.open('/sales-quotations/${res.id_sq}/executive-summary','_blank')"`,
            }] : []),
            ...(!isDeleted && isDraft && can('sales-quotations', 'can_update') ? [{
                label: "Finalkan SQ",
                icon: "fa-solid fa-lock",
                attrs: `onclick="finalizeSq(${res.id_sq})"`,
            }] : []),
            ...(!isDeleted && (isDraft || isFinal) && can('sales-quotations', 'can_update') ? [{
                label: "Batalkan SQ",
                icon: "fa-solid fa-ban",
                attrs: `onclick="cancelSq(${res.id_sq})"`,
            }] : []),
            ...(!isDeleted && isFinal && can('sales-quotations', 'can_create') && can('sales-orders', 'can_create') ? [{
                label: "Terbitkan SO",
                icon: "fa-solid fa-right-left",
                attrs: `onclick="openConvertSqModal(${res.id_sq})"`,
            }] : []),
        ],
        extra: (!isDeleted && !isDraft)
            ? `<span style="font-size:11px;color:#64748b;display:flex;align-items:center;gap:5px;">
                   <i class="fa-solid fa-lock" style="font-size:10px;"></i>
                   SQ berstatus ${escHtml(res.status)} — terkunci, tidak bisa diedit
               </span>`
            : isDeleted
            ? `<span style="font-size:11px;color:#b91c1c;display:flex;align-items:center;gap:5px;">
                   <i class="fa-solid fa-trash" style="font-size:10px;"></i>
                   Data ini sudah dihapus pada ${new Date(res.deleted_at).toLocaleString('id-ID')}
               </span>`
            : '',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="sqDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" id="tab-info-sq-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabInfoSq">
                        <i class="fa-solid fa-circle-info me-1" style="color:#6366f1;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabSqWo" data-sq-id="${res.id_sq}">
                        <i class="fa-solid fa-briefcase me-1" style="color:#1a56db;font-size:11px;"></i>
                        Work Order
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="sqTabActionsInfo" class="d-flex align-items-center gap-2"></div>
                <div id="sqTabActionsWo" class="d-flex align-items-center gap-2 d-none">
                    <button type="button" id="btnRefreshSqWo" data-sq-id="${res.id_sq}"
                        class="pm-btn-icon" title="Refresh" data-no-disable>
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                    ${(!isDeleted && isDraft) ? `<button type="button" class="pm-btn-pill pm-btn-pill--blue btn-add-sq-wo-modal"
                        data-sq-id="${res.id_sq}" data-no-disable>
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                        <i class="fa-solid fa-briefcase" style="font-size:11px;"></i> WO
                    </button>` : ''}
                </div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabInfoSq" role="tabpanel">
                    <div class="row g-3">

    ${formGroup.sectionCard(
        { icon: 'fa-file-lines', color: 'icon-navy', title: 'Informasi Quotation', subtitle: 'Data utama sales quotation' },
        `<div class="row g-3 form-1">
            ${formGroup.date('tanggal_sq', 'Tanggal SQ', res.tanggal_sq, true, { className: 'col-md-3' })}
            ${formGroup.text('judul_order', 'Judul Order', res.judul_order, true, { className: 'col-md-9' })}
            ${formGroup.date('berlaku_sampai', 'Berlaku Sampai', res.berlaku_sampai, false, { className: 'col-md-3' })}
            ${formGroup.date('rencana_mulai', 'Rencana Mulai (acuan hari ke-1)', res.rencana_mulai, false, { className: 'col-md-3' })}
            ${formGroup.select('id_office', 'Office', res.id_office, [], {
                mode: 'ajax', url: '/office/select2', placeholder: 'Pilih Office',
                label: res.name_office || null, className: 'col-md-6', allowClear: true, createUrl: '/office/create',
            })}
        </div>`,
    )}

    ${formGroup.sectionCard(
        { icon: 'fa-building-user', color: 'icon-blue', title: 'Data Pelanggan', subtitle: 'Billing, Delivery & Payment' },
        `
        <div class="detail-party-header d-none d-md-grid">
            <div class="detail-party-label"><i class="fa-solid fa-file-invoice me-1"></i> Data Pemesan</div>
            <div class="detail-party-label"><i class="fa-solid fa-truck me-1"></i> Data Pengiriman</div>
            <div class="detail-party-label"><i class="fa-solid fa-money-bill me-1"></i> Data Pembayaran</div>
        </div>

        <div class="row g-3 form-1">
            <div class="col-12 d-md-none"><div class="detail-mobile-section-label"><i class="fa-solid fa-file-invoice me-1"></i> Data Pemesan</div></div>
            ${formGroup.select('id_pelanggan', 'Perusahaan', res.id_pelanggan, [], {
                mode: 'ajax', url: 'business-relations/select2', placeholder: 'Pilih Data',
                label: res.nama_pelanggan_display ?? res.nama_pelanggan, className: 'col-md-4',
                createUrl: '/business-relations/create', required: true,
            })}
            <div class="col-12 d-md-none"><div class="detail-mobile-section-label"><i class="fa-solid fa-truck me-1"></i> Data Pengiriman</div></div>
            ${formGroup.select('id_pelanggan_delivery', 'Perusahaan', res.id_pelanggan_delivery, [], {
                mode: 'ajax', url: 'business-relations/select2', placeholder: 'Pilih Data',
                label: res.pelanggan_delivery_display ?? res.pelanggan_delivery, className: 'col-md-4',
                createUrl: '/business-relations/create', required: true,
            })}
            <div class="col-12 d-md-none"><div class="detail-mobile-section-label"><i class="fa-solid fa-money-bill me-1"></i> Data Pembayaran</div></div>
            ${formGroup.select('id_pelanggan_payment', 'Perusahaan', res.id_pelanggan_payment, [], {
                mode: 'ajax', url: 'business-relations/select2', placeholder: 'Pilih Data',
                label: res.pelanggan_pay_display ?? res.pelanggan_pay, className: 'col-md-4',
                createUrl: '/business-relations/create', required: true,
            })}
        </div>

        <div class="row g-3 form-1">
            ${formGroup.select('id_site_pelanggan', 'Site', res.id_site_pelanggan, [], {
                mode: 'ajax', url: 'business-relations/sites/select2', placeholder: 'Pilih Data',
                label: res.nama_site_pelanggan, className: 'col-md-4', createUrl: '/business-relations/create', required: true,
            })}
            ${formGroup.select('id_site_pelanggan_delivery', 'Site', res.id_site_pelanggan_delivery, [], {
                mode: 'ajax', url: 'business-relations/sites/select2', placeholder: 'Pilih Data',
                label: res.pelanggan_site_delivery, className: 'col-md-4', createUrl: '/business-relations/create', required: true,
            })}
            ${formGroup.select('id_site_pelanggan_payment', 'Site', res.id_site_pelanggan_payment, [], {
                mode: 'ajax', url: 'business-relations/sites/select2', placeholder: 'Pilih Data',
                label: res.pelanggan_site_pay, className: 'col-md-4', createUrl: '/business-relations/create', required: true,
            })}
        </div>

        <div class="row g-3 form-1">
            <div class="mb-3 col-md-4">
                <label class="form-label required">PIC</label>
                <select name="id_pic_pelanggan" id="detail_id_pic_pelanggan" class="form-select disabled" required>
                    <option value=""></option>
                    ${res.id_pic_pelanggan ? `<option value="${res.id_pic_pelanggan}" selected>${escHtml(res.pic_pelanggan ?? '')}</option>` : ''}
                </select>
            </div>
            <div class="mb-3 col-md-4">
                <label class="form-label required">PIC</label>
                <select name="id_pic_pelanggan_delivery" id="detail_id_pic_pelanggan_delivery" class="form-select disabled" required>
                    <option value=""></option>
                    ${res.id_pic_pelanggan_delivery ? `<option value="${res.id_pic_pelanggan_delivery}" selected>${escHtml(res.pic_pelanggan_del ?? '')}</option>` : ''}
                </select>
            </div>
            <div class="mb-3 col-md-4">
                <label class="form-label required">PIC</label>
                <select name="id_pic_pelanggan_payment" id="detail_id_pic_pelanggan_payment" class="form-select disabled" required>
                    <option value=""></option>
                    ${res.id_pic_pelanggan_payment ? `<option value="${res.id_pic_pelanggan_payment}" selected>${escHtml(res.pic_pelanggan_pay ?? '')}</option>` : ''}
                </select>
            </div>
        </div>
        `,
    )}

    ${formGroup.sectionCard(
        { icon: 'fa-users', color: 'icon-green', title: 'PIC Internal', subtitle: 'Penanggung jawab dari Pramatek' },
        `<div class="row g-3 form-1">
            ${formGroup.select('pic_input', 'PIC Input', res.pic_input, [], {
                mode: 'ajax', url: 'users/select2', placeholder: 'Pilih Data', label: res.pic_input_name, className: 'col-md-4', required: true,
            })}
            ${formGroup.select('pic_marketing_internal', 'Marketing Internal', res.marketing_internal_id, [], {
                mode: 'ajax', url: 'users/select2', placeholder: 'Pilih Data', label: res.marketing_internal_name, className: 'col-md-4', required: true,
            })}
            ${formGroup.select('pic_marketing_eksternal', 'Marketing Eksternal', res.marketing_eksternal_id, [], {
                mode: 'ajax', url: 'users/select2', placeholder: 'Pilih Data', label: res.marketing_eksternal_name, className: 'col-md-4',
            })}
        </div>`,
    )}

    ${formGroup.sectionCard(
        { icon: 'fa-circle-info', color: 'icon-purple', title: 'Status & Keterangan' },
        `<div class="row g-3 form-1">
            ${formGroup.text('keterangan_status', 'Keterangan Status', res.keterangan_status, false, { className: 'col-md-12' })}
            ${formGroup.textarea('cara_pembayaran', 'Cara Pembayaran', res.cara_pembayaran, { className: 'col-md-12' })}
            ${formGroup.textarea('keterangan', 'Keterangan', res.keterangan, { className: 'col-md-12' })}
        </div>`,
    )}

                    </div>
                </div>
                <!-- TAB: WORK ORDER (Estimasi) -->
                <div class="tab-pane fade" id="tabSqWo" role="tabpanel">
                    <div class="card card-body">
                        <div class="pm-search mb-3">
                            <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="sqWoSearch" placeholder="Cari No. WO / Judul..." data-no-disable>
                            <button type="button" id="btnClearSqWoSearch" class="pm-search-clear d-none" title="Hapus"><i class="fa-solid fa-times"></i></button>
                        </div>
                        <div id="sqWoContent">
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

