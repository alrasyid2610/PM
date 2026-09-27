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
