function renderParameterForm(res) {
    return `

<form class="row g-3" id="detailForm">

    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <div class="col-md-12">

        <div class="d-flex justify-content-between align-items-center mb-2">

            <h3>Testing Parameter</h3>

            <div class="btn-group">
                <button class="btn btn-warning btn-sm btn-edit-context">
                    <i class="fa-solid fa-pen"></i>
                </button>
            </div>

        </div>

        <div class="row mb-4 form-1">

            ${formGroup.select("kelompok", "Kelompok", res.kelompok, [
                { value: "Fisika", label: "Fisika" },
                { value: "Kimia Logam", label: "Kimia Logam" },
                { value: "Kimia Non Logam", label: "Kimia Non Logam" },
                { value: "Kimia Organik", label: "Kimia Organik" },
                { value: "Mikrobiologi", label: "Mikrobiologi" },
            ])}

            ${formGroup.text("kode", "Kode", res.kode, true)}

            ${formGroup.text(
                "judul_indonesia",
                "Judul Indonesia",
                res.judul_indonesia,
                true,
            )}

            ${formGroup.text("judul_inggris", "Judul Inggris", res.judul_inggris)}

            ${formGroup.text("rumus_empiris", "Rumus Empiris", res.rumus_empiris)}

            ${formGroup.text("judul_iupac", "Judul IUPAC", res.judul_iupac)}

            ${formGroup.text("referensi", "Referensi", res.referensi)}

            ${formGroup.textarea("keterangan", "Keterangan", res.keterangan)}

            ${renderAttachmentSection()}

        </div>
    </div>

</form>

`;
}

function renderAttachmentSection() {
    return `
<div class="col-md-12 mb-3">

    <label class="form-label">Attachment</label>

    <div id="attachmentPreview" class="row g-3">
    
    </div>

    <div id="attachmentUploader" class="mt-3" style="display:none">

        <input
        type="file"
        class="filepond-edit"
        name="attachments[]"
        multiple>

    </div>

</div>
`;
}
