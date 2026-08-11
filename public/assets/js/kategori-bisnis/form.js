function renderForm(res) {
    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.nama ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_kategori_bisnis,
        editText: 'Edit Kategori Bisnis',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="kategoriBisnisDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabKategoriBisnisInfo">
                        <i class="fa-solid fa-tags me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="kategoriBisnisTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabKategoriBisnisInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-tags', color: 'icon-navy', title: 'Kategori Bisnis', subtitle: 'Data kategori bisnis pelanggan' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("nama", "Nama Kategori Bisnis", res.nama, true, { className: "col-md-9 col-12" })}
                                ${formGroup.select("is_aktif", "Status", res.is_aktif,
                                    [
                                        { value: 1, label: "Aktif" },
                                        { value: 0, label: "Tidak Aktif" },
                                    ],
                                    { className: "col-md-3 col-12" }
                                )}
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
