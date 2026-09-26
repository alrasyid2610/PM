// SQ Work Order — halaman detail dengan tab Informasi/BOQ/BOQ Other/BOQ
// Sampling/Budget, meniru struktur halaman Work Order asli persis (menu
// sendiri, halaman sendiri, tab sama) — lihat Obsidian Modules/Sales Quotation.md.

function formatRupiahSqWo(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

function renderForm(res) {
    const sqTag = res.no_sq
        ? `<a href="/sales-quotations?open=${res.id_sq}" class="pm-badge pm-badge--blue" style="text-decoration:none;">
               <i class="fa-solid fa-file-signature" style="font-size:10px;"></i>
               ${escHtml(res.no_sq)}${res.revisi > 0 ? ' Rev.' + res.revisi : ''}
           </a>`
        : '';

    // Badge lokasi (Perusahaan · Site) — sama seperti pelangganTag di work-order/form.js
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
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.no_sq_wo ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_sq_wo,
        editText: 'Edit',
        tags: sqTag + pelangganTag,
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="sqWoDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabInfo">
                        <i class="fa-solid fa-circle-info me-1" style="color:#6366f1;font-size:11px;"></i> Informasi
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBoq" data-id-sq-wo="${res.id_sq_wo}">
                        <i class="fa-solid fa-layer-group me-1" style="color:#1a56db;font-size:11px;"></i> BOQ
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBoqOther" data-id-sq-wo="${res.id_sq_wo}">
                        <i class="fa-solid fa-file-invoice me-1" style="color:#b45309;font-size:11px;"></i> BOQ Other
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBoqSampling" data-id-sq-wo="${res.id_sq_wo}">
                        <i class="fa-solid fa-vial-virus me-1" style="color:#7c3aed;font-size:11px;"></i> BOQ Sampling
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBudget" data-id-sq-wo="${res.id_sq_wo}">
                        <i class="fa-solid fa-wallet me-1" style="color:#0f766e;font-size:11px;"></i> Budget
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="sqWoTabActionsInfo" class="d-flex align-items-center gap-2"></div>
                <div id="sqWoTabActionsBoq" class="d-none align-items-center gap-2">
                    <button type="button" id="btnRefreshSqBoq" class="pm-btn-icon" title="Refresh" data-no-disable>
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                    <button type="button" class="pm-btn-pill pm-btn-pill--blue btn-kelola-sq-boq" data-no-disable>
                        <i class="fa-solid fa-layer-group" style="font-size:10px;"></i> Kelola BOQ
                    </button>
                </div>
                <div id="sqWoTabActionsBoqOther" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill pm-btn-pill--amber btn-sq-tambahan-add" data-jenis="lainnya" data-no-disable>
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i> Tambah Item
                    </button>
                </div>
                <div id="sqWoTabActionsBoqSampling" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill pm-btn-pill--purple btn-sq-tambahan-add" data-jenis="sampling" data-no-disable>
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i> Tambah Item
                    </button>
                </div>
                <div id="sqWoTabActionsBudget" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill pm-btn-pill--teal btn-sq-wo-budget-add" data-no-disable>
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i> Tambah Budget Plan
                    </button>
                </div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabInfo" role="tabpanel">
                    <div class="row g-3">
    ${formGroup.sectionCard(
        { icon: 'fa-briefcase', color: 'icon-navy', title: 'Informasi Work Order', subtitle: 'Data pekerjaan estimasi' },
        `<div class="row g-3 form-1">
            ${formGroup.text('judul_pekerjaan', 'Judul Pekerjaan', res.judul_pekerjaan, false, { className: 'col-md-12' })}
            ${formGroup.text('hari_mulai', 'Hari Mulai (ke-)', res.hari_mulai, true, { className: 'col-md-3' })}
            ${formGroup.text('durasi_hari', 'Durasi (hari)', res.durasi_hari, false, { className: 'col-md-3' })}
            ${formGroup.select('id_site_pelanggan_pekerjaan', 'Site Pekerjaan', res.id_site_pelanggan_pekerjaan, [], {
                mode: 'ajax', url: '/business-relations/sites/select2', placeholder: 'Pilih Site',
                label: res.nama_site_pelanggan_pekerjaan, className: 'col-md-6',
            })}
            ${formGroup.select('id_pic_pelanggan_pekerjaan', 'PIC Pekerjaan', res.id_pic_pelanggan_pekerjaan, [], {
                mode: 'ajax', url: '/business-relation-contacts/select2', placeholder: 'Pilih PIC',
                label: res.nama_pic, className: 'col-md-6',
            })}
            ${formGroup.textarea('keterangan', 'Keterangan', res.keterangan, { className: 'col-md-12' })}
        </div>`,
    )}
                    </div>
                </div>
                <div class="tab-pane fade" id="tabBoq" role="tabpanel">
                    <div class="card card-body">
                        <div id="sqWoBoqSummary">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tabBoqOther" role="tabpanel">
                    <div class="card card-body">
                        <div id="sqWoBoqOtherTable">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tabBoqSampling" role="tabpanel">
                    <div class="card card-body">
                        <div id="sqWoBoqSamplingTable">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tabBudget" role="tabpanel">
                    <div class="card card-body" id="sqWoBudgetWrap" style="overflow:visible;">
                        <div id="sqWoBudgetContent">
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
