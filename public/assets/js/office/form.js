function renderForm(res) {
    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.name ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_office,
        editText: 'Edit Office',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="officeDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabOfficeInfo">
                        <i class="fa-solid fa-building me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="officeTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabOfficeInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-building', color: 'icon-navy', title: 'Office', subtitle: 'Data kantor Pramatek' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("name", "Nama Office", res.name, true, { className: "col-md-6 col-12" })}
                                ${formGroup.text("alamat", "Alamat", res.alamat, false, { className: "col-md-6 col-12" })}
                            </div>`
                        )}

                    </div>
                </div>

            </div>
        </div>
    </div>

</form>
`;
}
