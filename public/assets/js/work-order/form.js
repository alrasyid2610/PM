const INTERVAL_LABELS = {1:'Bulanan',2:'Bimulanan',3:'Triwulan',4:'Caturwulan',6:'Semester',12:'Annual'};

function periodBadge(interval, urutan) {
    if (!interval || !urutan) return '';
    const label = INTERVAL_LABELS[interval] || (interval + ' bln');
    return `<span class="pm-badge pm-badge--blue">
        <i class="fa-solid fa-calendar-days" style="font-size:9px;"></i>
        ${escHtml(label)} ke-${urutan}
    </span>`;
}

function renderForm(res) {
    const soTag = res.id_so
        ? `<a href="/sales-orders?open=${res.id_so}" class="pm-badge pm-badge--blue" style="text-decoration:none;">
               <i class="fa-solid fa-file-contract" style="font-size:10px;"></i>
               ${escHtml(res.no_so ?? 'SO')}
           </a>`
        : '';
    const pelangganTag = res.nama_pelanggan_pekerjaan
        ? `<span class="pm-badge" style="background:#f1f5f9;color:#475569;">
               <i class="fa-solid fa-building" style="font-size:10px;"></i>
               ${escHtml(res.nama_pelanggan_pekerjaan)}
           </span>`
        : '';

    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.no_wo ?? "—"),
        createdAt: escHtml(res.created_at ?? "—"),
        updatedAt: escHtml(res.updated_at ?? "—"),
        deleteId: res.id_wo,
        editText: "Edit WO",
        tags: soTag + pelangganTag,
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
                            BOQ Progress
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
                </ul>

                <div class="pm-tab-actions">
                    <div id="woTabActionsInfo" class="d-flex align-items-center gap-2">
                        <!-- Edit/Hapus ada di action bar atas -->
                    </div>
                    <div id="woTabActionsBoq" class="d-flex align-items-center gap-2 d-none">
                        <button type="button" id="btnRefreshBoqProgress" data-wo-id="${res.id_wo}"
                            class="pm-btn-icon" title="Refresh" data-no-disable>
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                        <button type="button" class="pm-btn-pill pm-btn-pill--green btn-add-boq-modal"
                            data-wo-id="${res.id_wo}" data-no-disable>
                            <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                            <i class="fa-solid fa-layer-group" style="font-size:11px;"></i> BOQ
                        </button>
                    </div>
                    <div id="woTabActionsFwo" class="d-flex align-items-center gap-2 d-none">
                        <button type="button" id="btnRefreshFwoProgress" data-wo-id="${res.id_wo}"
                            class="pm-btn-icon" title="Refresh" data-no-disable>
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                        <button type="button" class="pm-btn-pill pm-btn-pill--blue btn-add-fwo-modal"
                            data-wo-id="${res.id_wo}" data-no-disable>
                            <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                            <i class="fa-solid fa-hard-hat" style="font-size:11px;"></i> FWO
                        </button>
                    </div>
                    <div id="woTabActionsOutput" class="d-flex align-items-center gap-2 d-none">
                        <button type="button" id="btnAddOutput" data-wo-id="${res.id_wo}"
                            class="pm-btn-pill pm-btn-pill--teal" data-no-disable>
                            <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                            <i class="fa-solid fa-file-circle-check" style="font-size:11px;"></i> Output
                        </button>
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
                mode: "ajax", url: "/business-relation-contacts/select2",
                placeholder: "Pilih PIC", label: res.nama_pic_pelanggan_pekerjaan,
                className: "col-md-7", createUrl: "/business-relation-contacts/create",
            })}
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
                </div>
            </div>
        </div>

</form>
`;
}
