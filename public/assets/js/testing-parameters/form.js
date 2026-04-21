function renderParameterForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <!-- SECTION 1: INFORMASI PARAMETER -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-amber">
                    <i class="fa-solid fa-flask"></i>
                </div>
                <div class="detail-section-title">Informasi Parameter</div>
                <div class="detail-section-sub">Data parameter pengujian laboratorium</div>
                ${formGroup.editButton("Edit Parameter")}
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
                    ${formGroup.select(
                        "kelompok",
                        "Kelompok",
                        res.kelompok,
                        [
                            { value: "Fisika", label: "Fisika" },
                            { value: "Kimia Logam", label: "Kimia Logam" },
                            {
                                value: "Kimia Non Logam",
                                label: "Kimia Non Logam",
                            },
                            { value: "Kimia Organik", label: "Kimia Organik" },
                            { value: "Mikrobiologi", label: "Mikrobiologi" },
                        ],
                        { className: "col-md-4" },
                    )}
                    ${formGroup.text("kode", "Kode", res.kode, true, {
                        className: "col-md-4",
                    })}
                    ${formGroup.text(
                        "judul_indonesia",
                        "Judul Indonesia",
                        res.judul_indonesia,
                        true,
                        {
                            className: "col-md-4",
                        },
                    )}
                    ${formGroup.text(
                        "judul_inggris",
                        "Judul Inggris",
                        res.judul_inggris,
                        false,
                        {
                            className: "col-md-4",
                        },
                    )}
                    ${formGroup.text(
                        "rumus_empiris",
                        "Rumus Empiris",
                        res.rumus_empiris,
                        false,
                        {
                            className: "col-md-4",
                        },
                    )}
                    ${formGroup.text(
                        "judul_iupac",
                        "Judul IUPAC",
                        res.judul_iupac,
                        false,
                        {
                            className: "col-md-4",
                        },
                    )}
                    ${formGroup.text(
                        "referensi",
                        "Referensi",
                        res.referensi,
                        false,
                        {
                            className: "col-md-12",
                        },
                    )}
                    ${formGroup.textarea(
                        "keterangan",
                        "Keterangan",
                        res.keterangan,
                        {
                            className: "col-md-12",
                        },
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
                <div class="detail-section-sub">File pendukung parameter</div>
            </div>
            <div class="detail-section-body">
                ${renderAttachmentSection()}
            </div>
        </div>
    </div>

</form>
`;
}
