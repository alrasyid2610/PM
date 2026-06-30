function renderForm(res) {
    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="id_contact" value="${res.id_contact}">

    ${formGroup.actionBar({
        number: escHtml(res.nama_pic ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_contact,
        editText: 'Edit Contact',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="contactDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabContactInfo">
                        <i class="fa-solid fa-address-card me-1" style="color:#16a34a;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="contactTabActionsInfo" class="d-flex align-items-center gap-2">
                    <!-- Edit/Hapus di action bar atas -->
                </div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <!-- TAB: INFORMASI -->
                <div class="tab-pane fade show active" id="tabContactInfo" role="tabpanel">
                    <div class="row g-3">
                        ${formGroup.sectionCard(
                            { icon: 'fa-address-card', color: 'icon-green', title: 'Business Relation Contact', subtitle: 'Data kontak PIC pelanggan' },
                            `<div class="row g-3 form-1">
                                ${formGroup.select("id_br", "Business Relation", res.id_br, [], {
                                    mode: "ajax", url: "business-relations/select2",
                                    placeholder: "Pilih Business Relation", label: res.nama_br,
                                    className: "col-md-12", createUrl: "/business-relations/create",
                                })}
                                ${formGroup.text("nama_pic", "Nama PIC", res.nama_pic, true, { className: "col-md-12" })}
                                <div class="col-md-3 mb-3">
                                    <label class="form-label form-label-sm text-muted mb-1 required">No. Telp PIC</label>
                                    <input type="text" name="nomor_telepon_pic" class="form-control disabled numeric-only"
                                        inputmode="numeric" value="${escHtml(res.nomor_telepon_pic ?? '')}" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label form-label-sm text-muted mb-1">Email PIC</label>
                                    <input type="email" name="email_pic" class="form-control disabled"
                                        value="${escHtml(res.email_pic ?? '')}">
                                </div>
                                ${formGroup.text("lokasi_pic", "Lokasi PIC", res.lokasi_pic, true, { className: "col-md-3" })}
                                ${formGroup.select("is_aktif", "Status", res.is_aktif,
                                    [
                                        { value: 1, label: "Aktif" },
                                        { value: 0, label: "Tidak Aktif" },
                                    ],
                                    { className: "col-md-3" }
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
