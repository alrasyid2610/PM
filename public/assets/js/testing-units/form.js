function renderUnitForm(res) {
    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.kode ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_testing_unit,
        deleteClass: 'btn-delete-unit',
        editText: 'Edit Testing Unit',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="unitDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabUnitInfo">
                        <i class="fa-solid fa-ruler me-1" style="color:#d97706;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="unitTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabUnitInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-ruler', color: 'icon-amber', title: 'Informasi Unit', subtitle: 'Data satuan pengujian laboratorium' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("kode", "Kode", res.kode, true, { className: "col-md-4" })}
                                ${formGroup.text("judul_indonesia", "Judul Indonesia", res.judul_indonesia, true, { className: "col-md-4" })}
                                ${formGroup.text("judul_inggris", "Judul Inggris", res.judul_inggris, true, { className: "col-md-4" })}
                                ${formGroup.textarea("keterangan", "Keterangan", res.keterangan, { className: "col-md-12" })}
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
