function renderForm(res) {
    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.nomor ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_testing_standard,
        editText: 'Edit',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="stdDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabStdInfo">
                        <i class="fa-solid fa-book me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="stdTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabStdInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-book', color: 'icon-navy', title: 'Testing Standards', subtitle: 'Data standar pengujian laboratorium' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("nomor", "Nomor", res.nomor, true, { className: "col-md-4" })}
                                ${formGroup.text("judul", "Judul", res.judul, true, { className: "col-md-6" })}
                                ${formGroup.select("is_aktif", "Status", res.is_aktif,
                                    [
                                        { value: 1, label: "Aktif" },
                                        { value: 0, label: "Tidak Aktif" },
                                    ],
                                    { className: "col-md-2" }
                                )}
                            </div>`
                        )}

                        ${formGroup.sectionCard(
                            { icon: 'fa-paperclip', color: 'icon-blue', title: 'Attachment', subtitle: 'File pendukung standard' },
                            `${renderAttachmentSection()}`
                        )}

                    </div>
                </div>

            </div>
        </div>
    </div>

</form>
`;
}
