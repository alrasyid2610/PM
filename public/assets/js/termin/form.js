function renderAssignedOutputsInner(outputs) {
    const TH = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';

    if (outputs && outputs.length) {
        const rows = outputs.map(function (item) {
            return `<tr class="assigned-output-row" data-id="${item.id_output}">
                <input type="hidden" name="selected_outputs[]" value="${item.id_output}">
                <td ${TD} style="white-space:nowrap;">
                    ${item.id_wo
                        ? `<a href="/work-orders?open=${item.id_wo}" class="fw-semibold text-decoration-none" style="color:#1a56db;font-size:12px;">${escHtml(item.no_wo || '—')}</a>`
                        : `<span style="font-size:12px;color:#64748b;">${escHtml(item.no_wo || '—')}</span>`
                    }
                </td>
                <td ${TD}>${escHtml(item.judul_output)}</td>
                <td ${TD}>
                    <input type="text" name="judul_tagihan[${item.id_output}]"
                        class="form-control form-control-sm disabled"
                        value="${escHtml(item.judul_tagihan || '')}"
                        placeholder="Sama seperti judul output" maxlength="255">
                </td>
                <td ${TD} style="text-align:right;white-space:nowrap;">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-output-termin"
                        data-id="${item.id_output}" data-no-disable style="font-size:11px;">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        }).join('');
        return `<div class="table-responsive" id="assignedOutputsTableWrap">
            <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;"><tr>
                    <th ${TH} style="min-width:110px;">No WO</th>
                    <th ${TH} style="min-width:200px;">Judul Output</th>
                    <th ${TH} style="min-width:200px;">Judul Tagihan <span style="font-size:10px;color:#94a3b8;">(opsional)</span></th>
                    <th ${TH} style="min-width:60px;"></th>
                </tr></thead>
                <tbody id="assignedOutputsTbody">${rows}</tbody>
            </table>
        </div>`;
    }

    return `<div id="noAssignedOutputMsg" class="text-center text-muted py-3">
            <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>Belum ada output yang ditagihkan
        </div>
        <table class="table table-sm mb-0" style="display:none;font-size:13px;" id="assignedOutputsTableWrap">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;"><tr>
                <th ${TH} style="min-width:110px;">No WO</th>
                <th ${TH} style="min-width:200px;">Judul Output</th>
                <th ${TH} style="min-width:200px;">Judul Tagihan <span style="font-size:10px;color:#94a3b8;">(opsional)</span></th>
                <th ${TH} style="min-width:60px;"></th>
            </tr></thead>
            <tbody id="assignedOutputsTbody"></tbody>
        </table>`;
}

function renderForm(res) {
    const statusKey = (res.status ?? 'pending').toString().toLowerCase();
    const soTag = res.id_so
        ? `<a href="/sales-orders?open=${res.id_so}" class="pm-badge pm-badge--blue" style="text-decoration:none;">
               <i class="fa-solid fa-file-contract" style="font-size:10px;"></i>
               ${escHtml(res.no_so ?? 'SO')}
           </a>`
        : '';

    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="id_so" value="${res.id_so || ''}">

    ${formGroup.actionBar({
        number: escHtml(res.nomor ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_termin,
        editText: 'Edit Termin',
        statusBadge: `<span class="detail-status-inline detail-status-${statusKey}">${escHtml(res.status ?? 'Pending')}</span>`,
        tags: soTag,
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="terminDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabTerminInfo">
                        <i class="fa-solid fa-file-invoice-dollar me-1" style="color:#7c3aed;font-size:11px;"></i>
                        Informasi Termin
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabTerminOutput">
                        <i class="fa-solid fa-file-circle-check me-1" style="color:#0f766e;font-size:11px;"></i>
                        Output Pekerjaan
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="terminTabActionsInfo" class="d-flex align-items-center gap-2">
                    <!-- Edit/Hapus di action bar atas -->
                </div>
                <div id="terminTabActionsOutput" class="d-flex align-items-center gap-2 d-none">
                    ${res.id_so
                        ? `<button type="button" id="btnAddOutputTermin" data-so-id="${res.id_so}"
                               class="pm-btn-pill pm-btn-pill--teal" data-no-disable>
                               <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                               <i class="fa-solid fa-file-circle-check" style="font-size:11px;"></i> Output
                           </button>`
                        : ''}
                </div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <!-- TAB: INFORMASI TERMIN -->
                <div class="tab-pane fade show active" id="tabTerminInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-file-invoice-dollar', color: 'icon-navy', title: 'Informasi Termin', subtitle: 'Data termin pembayaran proyek' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("nomor", "Nomor", res.nomor, true, { className: "col-md-3" })}
                                ${formGroup.text("nama", "Nama", res.nama, true, { className: "col-md-5" })}
                                ${formGroup.text("persentase", "Persentase (%)", res.persentase, true, { className: "col-md-2" })}
                                ${formGroup.select("status", "Status", res.status,
                                    [
                                        { value: "pending", label: "Pending" },
                                        { value: "proses",  label: "Proses"  },
                                        { value: "selesai", label: "Selesai" },
                                    ],
                                    { className: "col-md-2" }
                                )}
                                ${formGroup.text("nilai", "Nilai (Rp)", res.nilai, true, { className: "col-md-4" })}
                                ${formGroup.date("tanggal", "Tanggal", res.tanggal ? res.tanggal.substring(0, 10) : '', true, { className: "col-md-4" })}
                                ${formGroup.textarea("keterangan", "Keterangan", res.keterangan, { className: "col-md-4" })}
                                <div class="col-md-12">
                                    <label class="form-label form-label-sm text-muted mb-1">Sales Order</label>
                                    <p class="form-control mb-0" style="background:#f8fafc;">
                                        ${res.id_so
                                            ? `<a href="/sales-orders?open=${res.id_so}" class="text-decoration-none fw-semibold" style="color:#1a56db;">
                                                ${escHtml(res.no_so ?? 'Lihat SO')}
                                               </a>${res.judul_order ? ' <span class="text-muted small">— ' + escHtml(res.judul_order) + '</span>' : ''}`
                                            : '—'}
                                    </p>
                                </div>
                            </div>`
                        )}

                        ${formGroup.sectionCard(
                            { icon: 'fa-paperclip', color: 'icon-blue', title: 'Attachment', subtitle: 'File pendukung termin' },
                            `${renderAttachmentSection()}`
                        )}

                    </div>
                </div>

                <!-- TAB: OUTPUT PEKERJAAN -->
                <div class="tab-pane fade" id="tabTerminOutput" role="tabpanel">
                    <div class="card card-body">
                        <div id="outputTerminContent">
                            ${renderAssignedOutputsInner(res.assigned_outputs || [])}
                        </div>
                        <div id="addOutputTerminWrap" class="mt-3"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</form>
`;
}
