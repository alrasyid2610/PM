function renderForm(res) {
    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.nama ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_sub_kategori_bisnis,
        editText: 'Edit Sub Kategori Bisnis',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="subKategoriBisnisDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabSubKategoriBisnisInfo">
                        <i class="fa-solid fa-tag me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="subKategoriBisnisTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabSubKategoriBisnisInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-tag', color: 'icon-navy', title: 'Sub Kategori Bisnis', subtitle: 'Data sub kategori bisnis pelanggan' },
                            `<div class="row g-3 form-1">
                                ${formGroup.select("id_kategori_bisnis", "Kategori Bisnis", res.id_kategori_bisnis, [], {
                                    mode: "ajax", url: "/kategori-bisnis/select2",
                                    placeholder: "Pilih Kategori Bisnis", label: res.nama_kategori_bisnis,
                                    className: "col-md-6", createUrl: "/kategori-bisnis/create",
                                })}
                                ${formGroup.text("nama", "Nama Sub Kategori Bisnis", res.nama, true, { className: "col-md-6" })}
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
