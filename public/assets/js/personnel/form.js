function renderForm(res) {
    const aksesBody = res.id_user
        ? `<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
               <div>
                   <span class="pm-badge pm-badge--blue"><i class="fa-solid fa-circle-check" style="font-size:10px;"></i> Punya Akses Login</span>
                   <div class="text-muted small mt-1">${escHtml(res.akun_email ?? '—')} ${res.akun_aktif == 0 ? '<span class="text-danger">(nonaktif)</span>' : ''}</div>
               </div>
               <button type="button" class="btn btn-sm btn-outline-danger btn-revoke-account" data-id="${res.id_personnel}" data-no-disable>
                   <i class="fa-solid fa-user-slash me-1"></i> Cabut Akses
               </button>
           </div>`
        : `<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
               <div class="text-muted small">Personnel ini belum punya akun login — tidak bisa akses sistem.</div>
               <button type="button" class="btn btn-sm btn-primary btn-create-account" data-id="${res.id_personnel}" data-no-disable>
                   <i class="fa-solid fa-user-plus me-1"></i> Buatkan Akun Login
               </button>
           </div>`;

    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.nama ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_personnel,
        editText: 'Edit Personnel',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="personnelDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabPersonnelInfo">
                        <i class="fa-solid fa-id-card me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="personnelTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabPersonnelInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-id-card', color: 'icon-navy', title: 'Data Personnel', subtitle: 'PIC, teknisi sampling, dsb' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("nama", "Nama", res.nama, true, { className: "col-md-6 col-12" })}
                                ${formGroup.text("no_hp", "No. HP", res.no_hp, false, { className: "col-md-3 col-12" })}
                                ${formGroup.select("is_aktif", "Status", res.is_aktif,
                                    [
                                        { value: 1, label: "Aktif" },
                                        { value: 0, label: "Tidak Aktif" },
                                    ],
                                    { className: "col-md-3 col-12" }
                                )}
                                ${formGroup.textarea("keterangan", "Keterangan", res.keterangan, { className: "col-12" })}
                            </div>`
                        )}

                        <div class="col-12">
                            <div class="pm-tab-card">
                                <div class="pm-tab-header" style="margin-top:0;">
                                    <div style="font-size:13px;font-weight:600;color:#374151;">
                                        <i class="fa-solid fa-key me-1" style="color:#b45309;"></i> Akses Sistem
                                    </div>
                                </div>
                                <div class="pm-tab-body" id="personnelAksesSistem">${aksesBody}</div>
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
