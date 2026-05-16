function renderForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <!-- ACTION BAR -->
    <div class="col-md-12">
        <div class="detail-action-bar">
            <div>
                <div class="detail-number">${escWo(res.no_wo ?? '—')}</div>
                <div class="detail-date">
                    Dibuat ${escWo(res.created_at ?? '—')} &nbsp;·&nbsp; Diupdate ${escWo(res.updated_at ?? '—')}
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                ${formGroup.editButton('Edit WO')}
            </div>
        </div>
    </div>

    <!-- SUMMARY CARD -->
    <div class="col-md-12">
        <div id="boqSummaryCard"></div>
    </div>

    <!-- SECTION 1: INFORMASI WORK ORDER -->
    ${formGroup.sectionCard(
        {
            icon: 'fa-briefcase',
            color: 'icon-navy',
            title: 'Informasi Work Order',
            subtitle: 'Data utama work order',
        },
        `<div class="row g-3 form-1">
            <div class="col-md-3">
                <label class="form-label form-label-sm text-muted mb-1">No WO</label>
                <p class="form-control mb-0">${escWo(res.no_wo ?? '—')}</p>
            </div>
            ${formGroup.text('judul_order', 'Judul Pekerjaan', res.judul_pekerjaan, true, { className: 'col-md-9' })}
            ${formGroup.select('id_so', 'Sales Order', res.id_so, [], {
                mode: 'ajax',
                url: '/sales-orders/select2',
                placeholder: 'Pilih Sales Order',
                label: res.no_so,
                className: 'col-md-6',
                createUrl: '/sales-orders/create',
            })}
            ${formGroup.select('id_pelanggan', 'Pelanggan', res.id_pelanggan_pekerjaan, [], {
                mode: 'ajax',
                url: '/business-relations/select2',
                placeholder: 'Pilih Pelanggan',
                label: res.nama_pelanggan_pekerjaan,
                className: 'col-md-6',
                createUrl: '/business-relations/create',
            })}
            ${formGroup.select('id_site_pelanggan', 'Site Pelanggan', res.id_site_pelanggan_pekerjaan, [], {
                mode: 'ajax',
                url: '/business-relations/sites/select2',
                placeholder: 'Pilih Site',
                label: res.nama_site_pelanggan_pekerjaan,
                className: 'col-md-6',
                createUrl: '/business-relations/create',
            })}
            ${formGroup.select('pic_pekerjaan', 'PIC Pekerjaan', res.id_pic_pelanggan_pekerjaan, [], {
                mode: 'ajax',
                url: '/business-relation-contacts/select2',
                placeholder: 'Pilih PIC',
                label: res.nama_pic_pelanggan_pekerjaan,
                className: 'col-md-6',
                createUrl: '/business-relation-contacts/create',
            })}
            ${formGroup.textarea('keterangan', 'Keterangan', res.keterangan, { className: 'col-md-12' })}
        </div>`
    )}

    <!-- SECTION 3: BOQ PROGRESS -->
    ${formGroup.sectionCard(
        {
            icon: 'fa-layer-group',
            color: 'icon-green',
            title: 'BOQ Progress',
            subtitle: 'Progress eksekusi BOQ & Fieldwork Orders',
            id: 'boq-section',
            actions: `<div class="d-flex align-items-center gap-2">
                <button type="button" id="btnRefreshBoqProgress" data-wo-id="${res.id_wo}"
                    class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:12px;" title="Refresh"
                    data-no-disable>
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
                <button type="button" id="btnToggleBoqView"
                    class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:12px;" title="Tampilan Tabel"
                    data-no-disable>
                    <i class="fa-solid fa-table-list"></i>
                </button>
                <a href="/boq/create?id_wo=${res.id_wo}" target="_blank"
                    style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;background:#dcfce7;color:#166534;font-size:12px;font-weight:600;text-decoration:none;border:1px solid #bbf7d0;transition:background 0.15s;"
                    onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='#dcfce7'">
                    <i class="fa-solid fa-plus" style="font-size:10px;"></i><i class="fa-solid fa-layer-group ms-1" style="font-size:11px;"></i> BOQ
                </a>
                <a href="/fieldworks/create?id_wo=${res.id_wo}" target="_blank"
                    style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;background:#e8f0fe;color:#1a56db;font-size:12px;font-weight:600;text-decoration:none;border:1px solid #c7d7f8;transition:background 0.15s;"
                    onmouseover="this.style.background='#c7d7f8'" onmouseout="this.style.background='#e8f0fe'">
                    <i class="fa-solid fa-plus" style="font-size:10px;"></i><i class="fa-solid fa-hard-hat ms-1" style="font-size:11px;"></i> FWO
                </a>
            </div>`,
        },
        `<div id="boqProgressContent">
            <div class="text-center text-muted py-4">
                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
            </div>
        </div>`
    )}

</form>
`;
}

function escWo(str) {
    return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
