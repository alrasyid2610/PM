const INTERVAL_LABELS = {1:'Bulanan',2:'Bimulanan',3:'Triwulan',4:'Caturwulan',6:'Semester',12:'Annual'};

function periodBadge(interval, urutan) {
    if (!interval || !urutan) return '';
    const label = INTERVAL_LABELS[interval] || (interval + ' bln');
    return `<span class="pm-badge pm-badge--blue">
        <i class="fa-solid fa-calendar-days" style="font-size:9px;"></i>
        ${escHtml(label)} ke-${urutan}
    </span>`;
}

function woLockedLabel() {
    return `<span style="font-size:11px;color:#dc2626;display:flex;align-items:center;gap:5px;">
        <i class="fa-solid fa-lock" style="font-size:10px;"></i>
        WO sudah selesai, data tidak dapat diubah
    </span>`;
}

function woStatusBadge(status, deletedAt) {
    if (deletedAt) {
        return `<span class="pm-badge" style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;">
            <i class="fa-solid fa-trash" style="font-size:10px;"></i> Deleted
        </span>`;
    }
    if (status === 'completed') {
        return `<span class="pm-badge" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;">
            <i class="fa-solid fa-circle-check" style="font-size:10px;"></i> Completed
        </span>`;
    }
    return `<span class="pm-badge" style="background:#eff6ff;color:#1a56db;border:1px solid #bfdbfe;">
        <i class="fa-solid fa-spinner" style="font-size:10px;"></i> On Progress
    </span>`;
}

function renderForm(res) {
    const soTag = res.id_so
        ? `<a href="/sales-orders?open=${res.id_so}" class="pm-badge pm-badge--blue" style="text-decoration:none;">
               <i class="fa-solid fa-file-contract" style="font-size:10px;"></i>
               ${escHtml(res.no_so ?? 'SO')}
           </a>`
        : '';
    const pelangganTag = res.nama_site_pelanggan_pekerjaan
        ? `<a href="/business-relations${res.id_site_pelanggan_pekerjaan ? '?open=' + res.id_site_pelanggan_pekerjaan : ''}" class="pm-badge" style="background:#f1f5f9;color:#475569;text-decoration:none;">
               <i class="fa-solid fa-location-dot" style="font-size:10px;"></i>
               ${escHtml(res.nama_site_pelanggan_pekerjaan)}
           </a>`
        : '';

    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.no_wo ?? "—"),
        createdAt: escHtml(res.created_at ?? "—"),
        updatedAt: escHtml(res.updated_at ?? "—"),
        deleteId: (res.deleted_at || res.status === 'completed') ? null : res.id_wo,
        editText: (res.deleted_at || res.status === 'completed') ? '' : 'Edit WO',
        tags: soTag + pelangganTag,
        statusBadge: woStatusBadge(res.status, res.deleted_at),
        extra: res.deleted_at
            ? `<span style="font-size:11px;color:#b91c1c;display:flex;align-items:center;gap:5px;">
                   <i class="fa-solid fa-trash" style="font-size:10px;"></i>
                   Data ini sudah dihapus pada ${new Date(res.deleted_at).toLocaleString('id-ID')}
               </span>`
            : res.status === 'completed'
            ? `<span style="font-size:11px;color:#dc2626;display:flex;align-items:center;gap:5px;">
                   <i class="fa-solid fa-lock" style="font-size:10px;"></i>
                   WO sudah selesai, data tidak dapat diubah
               </span>`
            : `<button type="button" id="btnSelesaikanWo" data-wo-id="${res.id_wo}" data-no-disable
                class="btn btn-sm btn-success" style="font-size:12px;">
                <i class="fa-solid fa-circle-check me-1"></i> Selesaikan WO
               </button>`,
        noWrap: true,
    })}

    <!-- KPI ROW -->
    <div class="detail-kpi-section">
        <div class="pm-kpi-row" id="boqSummaryCard"></div>
    </div>

    <!-- TABS: Informasi | BOQ Progress | Fieldwork Orders | Output Pekerjaan -->
    <div class="pm-tab-card">
            <div class="pm-tab-header">
                <ul class="pm-tab-nav" id="woDetailTabs" role="tablist">
                    <li role="presentation">
                        <button class="pm-tab-btn active" id="tab-info-btn" type="button" role="tab"
                            data-bs-toggle="tab" data-bs-target="#tabInfo">
                            <i class="fa-solid fa-circle-info me-1" style="color:#6366f1;font-size:11px;"></i>
                            Informasi
                        </button>
                    </li>
                    <li role="presentation">
                        <button class="pm-tab-btn" id="tab-boq-btn" type="button" role="tab"
                            data-bs-toggle="tab" data-bs-target="#tabBoq">
                            <i class="fa-solid fa-layer-group me-1" style="color:#16a34a;font-size:11px;"></i>
                            BOQ
                        </button>
                    </li>
                    <li role="presentation">
                        <button class="pm-tab-btn" id="tab-fwo-btn" type="button" role="tab"
                            data-bs-toggle="tab" data-bs-target="#tabFwo">
                            <i class="fa-solid fa-hard-hat me-1" style="color:#1a56db;font-size:11px;"></i>
                            Fieldwork Orders
                        </button>
                    </li>
                    <li role="presentation">
                        <button class="pm-tab-btn" id="tab-output-btn" type="button" role="tab"
                            data-bs-toggle="tab" data-bs-target="#tabOutput">
                            <i class="fa-solid fa-file-circle-check me-1" style="color:#0f766e;font-size:11px;"></i>
                            Output Pekerjaan
                        </button>
                    </li>
                    <li role="presentation">
                        <button class="pm-tab-btn" id="tab-wo-budget-btn" type="button" role="tab"
                            data-bs-toggle="tab" data-bs-target="#tabWoBudget"
                            data-wo-id="${res.id_wo}">
                            <i class="fa-solid fa-wallet me-1" style="color:#0f766e;font-size:11px;"></i>
                            Budget
                        </button>
                    </li>
                    <li role="presentation">
                        <button class="pm-tab-btn" id="tab-sample-btn" type="button" role="tab"
                            data-bs-toggle="tab" data-bs-target="#tabSample"
                            data-wo-id="${res.id_wo}">
                            <i class="fa-solid fa-vial me-1" style="color:#0369a1;font-size:11px;"></i>
                            Sample
                        </button>
                    </li>
                </ul>

                <div class="pm-tab-actions">
                    <div id="woTabActionsInfo" class="d-flex align-items-center gap-2">
                    </div>
                    <div id="woTabActionsBoq" class="d-flex align-items-center gap-2 d-none">
                        <button type="button" id="btnRefreshBoqProgress" data-wo-id="${res.id_wo}"
                            class="pm-btn-icon" title="Refresh" data-no-disable>
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                        ${res.status !== 'completed' ? `
                        <button type="button" class="pm-btn-pill pm-btn-pill--green btn-add-boq-modal"
                            data-wo-id="${res.id_wo}" data-no-disable>
                            <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                            <i class="fa-solid fa-layer-group" style="font-size:11px;"></i> BOQ
                        </button>` : woLockedLabel()}
                    </div>
                    <div id="woTabActionsFwo" class="d-flex align-items-center gap-2 d-none">
                        <button type="button" id="btnRefreshFwoProgress" data-wo-id="${res.id_wo}"
                            class="pm-btn-icon" title="Refresh" data-no-disable>
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                        ${res.status !== 'completed' && can('fieldworks', 'can_create') ? `
                        <button type="button" class="pm-btn-pill pm-btn-pill--blue btn-add-fwo-modal"
                            data-wo-id="${res.id_wo}" data-no-disable>
                            <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                            <i class="fa-solid fa-hard-hat" style="font-size:11px;"></i> FWO
                        </button>` : woLockedLabel()}
                    </div>
                    <div id="woTabActionsOutput" class="d-flex align-items-center gap-2 d-none">
                        ${res.status !== 'completed' ? `
                        <button type="button" id="btnAddOutput" data-wo-id="${res.id_wo}"
                            class="pm-btn-pill pm-btn-pill--teal" data-no-disable>
                            <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                            <i class="fa-solid fa-file-circle-check" style="font-size:11px;"></i> Output
                        </button>` : woLockedLabel()}
                    </div>
                    <div id="woTabActionsBudget" class="d-none align-items-center gap-2">
                        ${res.status !== 'completed' ? `
                        <button type="button" class="pm-btn-pill pm-btn-pill--teal btn-wo-budget-add"
                            data-wo-id="${res.id_wo}" data-no-disable>
                            <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                            <i class="fa-solid fa-wallet" style="font-size:11px;"></i> Tambah Budget Plan
                        </button>` : woLockedLabel()}
                    </div>
                    <div id="woTabActionsSample" class="d-none align-items-center gap-2">
                        <button type="button" id="btnRefreshWoSample" data-wo-id="${res.id_wo}"
                            class="pm-btn-icon" title="Refresh" data-no-disable>
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                        ${res.status === 'completed' ? woLockedLabel() : ''}
                    </div>
                </div>
            </div>

            <div class="pm-tab-body">
                <div class="tab-content">
                    <!-- TAB: INFORMASI -->
                    <div class="tab-pane fade show active" id="tabInfo" role="tabpanel">
                        <div class="row g-3">

    ${formGroup.sectionCard(
        { icon: "fa-briefcase", color: "icon-navy", title: "Informasi Work Order", subtitle: "Data utama work order" },
        `<div class="row g-3 form-1">
            <div class="col-md-3">
                <label class="form-label form-label-sm text-muted mb-1">No WO</label>
                <p class="form-control mb-0">${escHtml(res.no_wo ?? "—")}</p>
            </div>
            ${formGroup.text("judul_order", "Judul Pekerjaan", res.judul_pekerjaan, true, { className: "col-md-9" })}
            <div class="mb-3 col-md-6">
                <label class="form-label">
                    Sales Order
                    ${res.id_so ? `<a href="/sales-orders?open=${res.id_so}"
                        class="ms-2 text-decoration-none small" title="Buka halaman Sales Order"
                        style="color:var(--primary-500,#1a5fbe);">
                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:11px;"></i>
                    </a>` : ''}
                </label>
                <select name="id_so" class="form-select form-select-dynamic disabled"
                    id="detail_id_so"
                    data-mode="ajax" data-url="/sales-orders/select2"
                    data-allow-clear="false" data-placeholder="Pilih Sales Order"
                    data-minimum-input="0" data-show-all="false"
                    data-create-url="/sales-orders/create">
                    ${res.id_so && res.no_so
                        ? `<option value="${res.id_so}" selected>${escHtml(res.no_so)}</option>`
                        : '<option value=""></option>'}
                </select>
            </div>
            ${formGroup.select("id_pelanggan", "Pelanggan", res.id_pelanggan_pekerjaan, [], {
                mode: "ajax", url: "/business-relations/select2",
                placeholder: "Pilih Pelanggan", label: res.nama_pelanggan_pekerjaan,
                className: "col-md-6", createUrl: "/business-relations/create",
            })}
            ${formGroup.select("id_site_pelanggan", "Site Pelanggan", res.id_site_pelanggan_pekerjaan, [], {
                mode: "ajax", url: "/business-relations/sites/select2",
                placeholder: "Pilih Site", label: res.nama_site_pelanggan_pekerjaan,
                className: "col-md-6", createUrl: "/business-relations/create",
            })}
        </div>`
    )}

    ${formGroup.sectionCard(
        { icon: "fa-calendar-days", color: "icon-blue", title: "Jadwal & Frekuensi", subtitle: "Pengulangan dan penanggung jawab" },
        `<div class="row g-3 form-1">
            <div class="col-md-3 mb-3">
                <label class="form-label">Frekuensi</label>
                <select name="interval_bulan" class="form-select disabled" id="detail_interval_bulan">
                    <option value="">— Tidak ada —</option>
                    <option value="1"  ${res.interval_bulan == 1  ? 'selected' : ''}>Bulanan</option>
                    <option value="2"  ${res.interval_bulan == 2  ? 'selected' : ''}>Bimulanan</option>
                    <option value="3"  ${res.interval_bulan == 3  ? 'selected' : ''}>Triwulan</option>
                    <option value="4"  ${res.interval_bulan == 4  ? 'selected' : ''}>Caturwulan</option>
                    <option value="6"  ${res.interval_bulan == 6  ? 'selected' : ''}>Semester</option>
                    <option value="12" ${res.interval_bulan == 12 ? 'selected' : ''}>Annual</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Urutan ke-</label>
                <input type="number" name="no_urut_period" class="form-control disabled"
                    min="1" placeholder="Auto" value="${res.no_urut_period ?? ''}">
            </div>
            ${formGroup.select("pic_pekerjaan", "PIC Pekerjaan", res.id_pic_pelanggan_pekerjaan, [], {
                mode: "ajax", url: "/users/select2",
                placeholder: "Pilih PIC", label: res.nama_pic_pelanggan_pekerjaan,
                className: "col-md-7",
            })}
            ${formGroup.date("tanggal_mulai", "Tanggal Mulai", res.tanggal_mulai ? res.tanggal_mulai.substring(0,10) : '', false, { className: "col-md-3" })}
            ${formGroup.date("tanggal_selesai", "Tanggal Selesai", res.tanggal_selesai ? res.tanggal_selesai.substring(0,10) : '', false, { className: "col-md-3" })}
        </div>`
    )}

    ${formGroup.sectionCard(
        { icon: "fa-comment-lines", color: "icon-green", title: "Keterangan", subtitle: "Catatan tambahan" },
        `<div class="row g-3 form-1">
            ${formGroup.textarea("keterangan", "Keterangan", res.keterangan, { className: "col-md-12" })}
        </div>`
    )}

                        </div>
                    </div>
                    <!-- TAB: BOQ PROGRESS -->
                    <div class="tab-pane fade" id="tabBoq" role="tabpanel">
                        <div class="card card-body">
                            <div id="boqProgressContent">
                                <div class="text-center text-muted py-4">
                                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- TAB: FIELDWORK ORDERS -->
                    <div class="tab-pane fade" id="tabFwo" role="tabpanel">
                        <div class="card card-body">
                            <div id="fwoProgressContent">
                                <div class="text-center text-muted py-4">
                                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- TAB: OUTPUT PEKERJAAN -->
                    <div class="tab-pane fade" id="tabOutput" role="tabpanel">
                        <div class="card card-body">
                            <div id="outputContent">
                                <div class="text-center text-muted py-4">
                                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- TAB: BUDGET -->
                    <div class="tab-pane fade" id="tabWoBudget" role="tabpanel">
                        <div class="card card-body" id="woBudgetWrap" style="overflow:visible;">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
                            </div>
                        </div>
                    </div>
                    <!-- TAB: SAMPLE -->
                    <div class="tab-pane fade" id="tabSample" role="tabpanel">
                        <div class="card card-body">
                            <div id="woSampleContent">
                                <div class="text-center text-muted py-4">
                                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- MODAL: TAMBAH/EDIT BUDGET PLAN -->
    <div class="modal fade" id="woBudgetPlanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
            <div class="modal-content">
                <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                    <h6 class="modal-title mb-0" id="woBudgetPlanModalLabel">
                        <i class="fa-solid fa-wallet me-2" style="color:#0f766e;"></i>
                        Tambah Budget Plan
                    </h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <input type="hidden" id="woBudgetModal-id" value="">
                    <input type="hidden" id="woBudgetModal-id-wo" value="">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="woBudgetModal-label"
                                placeholder="cth: Admin Bulan 1, Operasional Q1..." data-no-disable>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Keterangan</label>
                            <input type="text" class="form-control form-control-sm" id="woBudgetModal-keterangan"
                                placeholder="Opsional" data-no-disable>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="text" class="form-control form-control-sm fp-date" id="woBudgetModal-tgl-mulai"
                                placeholder="Pilih tanggal" autocomplete="off" data-no-disable>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="text" class="form-control form-control-sm fp-date" id="woBudgetModal-tgl-selesai"
                                placeholder="Pilih tanggal" autocomplete="off" data-no-disable>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold" style="font-size:13px;">
                            <i class="fa-solid fa-list me-1" style="color:#0f766e;"></i> Item Anggaran
                        </span>
                        <button type="button" class="pm-btn-pill pm-btn-pill--teal" id="btnWoBudgetAddRow" data-no-disable>
                            <i class="fa-solid fa-plus" style="font-size:10px;"></i> Tambah Item
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="pm-table" id="woBudgetItemsTable">
                            <thead>
                                <tr>
                                    <th style="min-width:200px;">Account</th>
                                    <th style="min-width:160px;">Nominal Budget (Rp)</th>
                                    <th style="min-width:160px;">Keterangan</th>
                                    <th style="width:100px;text-align:center;">Cash Advance</th>
                                    <th style="width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="woBudgetItemsBody"></tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-2">
                        <span class="fw-semibold" style="font-size:13px;color:#0f766e;">
                            Total: <span id="woBudgetModalTotal" style="font-size:14px;">Rp 0</span>
                        </span>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" id="woBudgetModal-btn-save" data-no-disable>
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: INPUT ACTUAL -->
    <div class="modal fade" id="woActualModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
            <div class="modal-content">
                <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                    <h6 class="modal-title mb-0" id="woActualModalLabel">
                        <i class="fa-solid fa-receipt me-2" style="color:#1d4ed8;"></i>
                        Catat Pengeluaran
                    </h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <input type="hidden" id="woActualModal-id" value="">
                    <input type="hidden" id="woActualModal-id-budget-item" value="">
                    <input type="hidden" id="woActualModal-id-wo" value="">
                    <div id="woActualModal-budget-info" class="mb-3 px-3 py-2 rounded" style="background:#f0fdf4;border:1px solid #bbf7d0;display:none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-size:12px;color:#374151;font-weight:600;" id="woActualModal-account-name">-</span>
                        </div>
                        <div class="d-flex gap-3 mt-1">
                            <span style="font-size:11px;color:#6b7280;">Anggaran: <b id="woActualModal-budget-nominal" style="color:#0f766e;">-</b></span>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-12">
                            <label class="form-label">Nominal Pengeluaran (Rp) <span class="text-danger">*</span></label>
                            <input type="text" inputmode="numeric"
                                class="form-control form-control-sm input-num-mask input-num-int"
                                id="woActualModal-nominal" placeholder="0" data-no-disable>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Keterangan</label>
                            <input type="text" class="form-control form-control-sm"
                                id="woActualModal-keterangan" placeholder="Opsional" data-no-disable>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Upload Struk / Bukti</label>
                            <input type="file" id="woActualModal-files" multiple
                                accept=".pdf,.jpg,.jpeg,.png" data-no-disable
                                class="form-control form-control-sm">
                            <div id="woActualModal-existing-files" class="mt-2"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" id="woActualModal-btn-save" data-no-disable>
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Selesaikan Plan (Upload Dokumen Realisasi) -->
    <div class="modal fade" id="woClosePlanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
            <div class="modal-content">
                <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                    <h6 class="modal-title mb-0" id="woClosePlanModalLabel">
                        <i class="fa-solid fa-circle-check me-2" style="color:#15803d;"></i>Selesaikan Plan
                    </h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <input type="hidden" id="woClosePlanModal-id">
                    <input type="hidden" id="woClosePlanModal-id-wo">
                    <div id="woClosePlanModal-surplus-info" class="alert mb-3" style="font-size:12px;display:none;">
                        <div style="font-weight:600;margin-bottom:6px;">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                            Plan ini memiliki surplus anggaran
                        </div>
                        <div>Surplus sebesar <b id="woClosePlanModal-surplus-amount" style="color:#1d4ed8;"></b> harus dikembalikan ke perusahaan.</div>
                        <div class="mt-2">
                            Silakan <a id="woClosePlanModal-print-link" href="#" target="_blank" style="color:#1d4ed8;">
                                <i class="fa-solid fa-file-invoice me-1"></i>cetak Laporan Realisasi
                            </a>, tandatangani, lalu upload di bawah.
                        </div>
                    </div>
                    <div id="woClosePlanModal-upload-section" style="display:none;">
                        <label class="form-label fw-semibold" style="font-size:12px;">
                            Upload Laporan Realisasi Anggaran <span class="text-danger">*</span>
                        </label>
                        <input type="file" id="woClosePlanModal-file" class="form-control form-control-sm"
                            accept=".pdf,.jpg,.jpeg,.png" data-no-disable>
                        <div class="form-text" style="font-size:11px;">Format: PDF, JPG, PNG. Maks 5MB.</div>
                    </div>
                    <div id="woClosePlanModal-noSurplus-info" class="text-muted" style="font-size:12px;display:none;">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Tidak ada surplus. Plan akan langsung diselesaikan.
                    </div>
                </div>
                <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                    <button type="button" class="btn btn-sm" id="woClosePlanModal-btn-save"
                        style="background:#15803d;color:#fff;border-color:#15803d;" data-no-disable>
                        <i class="fa-solid fa-circle-check me-1"></i> Selesaikan Plan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Verifikasi per Budget Plan -->
    <div class="modal fade" id="woBulkVerifyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl" style="max-width:860px;">
            <div class="modal-content">
                <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                    <h6 class="modal-title mb-0" id="woBulkVerifyModalLabel">
                        <i class="fa-solid fa-check-double me-2" style="color:#0f766e;"></i>
                        Verifikasi Pengeluaran
                    </h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-2 px-3">
                    <input type="hidden" id="woBulkVerifyModal-id-wo">
                    <div id="woBulkVerifyModal-body"></div>
                </div>
                <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" id="woBulkVerifyModal-btn-save" data-no-disable>
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Semua Verifikasi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETAIL SAMPLE -->
    <div class="modal fade" id="woSampleDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
            <div class="modal-content">
                <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                    <h6 class="modal-title mb-0" id="woSampleDetailModalLabel">
                        <i class="fa-solid fa-flask me-2" style="color:#0369a1;"></i>Detail Sample
                    </h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <input type="hidden" id="woSampleModal-id">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Jenis Sample</label>
                            <select class="form-select form-select-sm" id="woSampleModal-jenis" data-no-disable>
                                <option value="">-- Pilih --</option>
                                <option value="env">ENV — Lingkungan Hidup</option>
                                <option value="we">WE — Lingkungan Kerja</option>
                                <option value="mp">MP — Makanan & Produk</option>
                                <option value="product">Product — Produk Industri</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">No. Sample</label>
                            <input type="text" class="form-control form-control-sm" id="woSampleModal-no" placeholder="Kode/nomor sample" data-no-disable>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Tanggal Pengambilan</label>
                            <input type="text" class="form-control form-control-sm fp-date" id="woSampleModal-tanggal" placeholder="Pilih tanggal" autocomplete="off" data-no-disable>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Kondisi Sample</label>
                            <select class="form-select form-select-sm" id="woSampleModal-kondisi" data-no-disable>
                                <option value="">-- Pilih --</option>
                                <option value="baik">Baik</option>
                                <option value="rusak">Rusak</option>
                                <option value="tidak_lengkap">Tidak Lengkap</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Titik / Lokasi</label>
                            <input type="text" class="form-control form-control-sm" id="woSampleModal-titik" placeholder="Titik lokasi…" data-no-disable>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Status</label>
                            <select class="form-select form-select-sm" id="woSampleModal-status" data-no-disable>
                                <option value="belum_diambil">Belum Diambil</option>
                                <option value="diambil">Diambil</option>
                                <option value="dikirim">Dikirim ke Lab</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Keterangan</label>
                            <textarea class="form-control form-control-sm" id="woSampleModal-keterangan" rows="2" placeholder="Opsional" data-no-disable></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" id="woSampleModal-btn-save" data-no-disable>
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: BULK FILL SAMPLE -->
    <div class="modal fade" id="woSampleBulkFillModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <div class="modal-content">
                <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                    <h6 class="modal-title mb-0" id="woSampleBulkFillModalLabel">
                        <i class="fa-solid fa-wand-magic-sparkles me-2" style="color:#7c3aed;"></i>Bulk Insert
                    </h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3 px-3">
                    <input type="hidden" id="woBulkFillModal-id-boq">
                    <p class="text-muted mb-3" style="font-size:12px;">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Field yang dikosongkan <b>tidak akan diubah</b>. Hanya field yang diisi yang akan di-apply ke semua sample.
                    </p>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Jenis Sample</label>
                            <select class="form-select form-select-sm" id="woBulkFill-jenis" data-no-disable>
                                <option value="">— Tidak diubah —</option>
                                <option value="env">ENV — Lingkungan Hidup</option>
                                <option value="we">WE — Lingkungan Kerja</option>
                                <option value="mp">MP — Makanan & Produk</option>
                                <option value="product">Product — Produk Industri</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Tanggal Pengambilan</label>
                            <input type="text" class="form-control form-control-sm fp-date" id="woBulkFill-tanggal" placeholder="Pilih tanggal" autocomplete="off" data-no-disable>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Status</label>
                            <select class="form-select form-select-sm" id="woBulkFill-status" data-no-disable>
                                <option value="">— Tidak diubah —</option>
                                <option value="belum_diambil">Belum Diambil</option>
                                <option value="diambil">Diambil</option>
                                <option value="dikirim">Dikirim ke Lab</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Kondisi Sample</label>
                            <select class="form-select form-select-sm" id="woBulkFill-kondisi" data-no-disable>
                                <option value="">— Tidak diubah —</option>
                                <option value="baik">Baik</option>
                                <option value="rusak">Rusak</option>
                                <option value="tidak_lengkap">Tidak Lengkap</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" id="woSampleBulkFillModal-btn-save" data-no-disable>
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Apply ke Semua Sample
                    </button>
                </div>
            </div>
        </div>
    </div>

</form>
`;
}
