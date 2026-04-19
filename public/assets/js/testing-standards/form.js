function renderForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <!-- SECTION 1: INFORMASI STANDARD -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-navy">
                    <i class="fa-solid fa-book"></i>
                </div>
                <div class="detail-section-title">Testing Standards</div>
                <div class="detail-section-sub">Data standar pengujian laboratorium</div>
                ${formGroup.editButton("Edit Testing Standard")}
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
                    ${formGroup.text("nomor", "Nomor", res.nomor, true, {
                        className: "col-md-4",
                    })}
                    ${formGroup.text("judul", "Judul", res.judul, true, {
                        className: "col-md-6",
                    })}
                    ${formGroup.select(
                        "is_aktif",
                        "Status",
                        res.is_aktif,
                        [
                            { value: 1, label: "Aktif" },
                            { value: 0, label: "Tidak Aktif" },
                        ],
                        { className: "col-md-2" },
                    )}
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: ATTACHMENT -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-blue">
                    <i class="fa-solid fa-paperclip"></i>
                </div>
                <div class="detail-section-title">Attachment</div>
                <div class="detail-section-sub">File pendukung standard</div>
            </div>
            <div class="detail-section-body">
                ${renderAttachmentSection()}
            </div>
        </div>
    </div>

</form>
`;
}
