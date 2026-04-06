function renderForm(res) {
    return `

<form class="row g-3" id="detailForm">

    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <div class="col-md-12">

        <div class="d-flex justify-content-between align-items-center mb-2">

            <h3>Testing Unit</h3>

            <div class="btn-group">
                <button class="btn btn-warning btn-sm btn-edit-context" title="Edit Testing Unit">
                    <i class="fa-solid fa-pen"></i>
                </button>
            </div>

        </div>

        <div class="row mb-4">

            ${formGroup.text("nomor", "Nomor", res.nomor, true)}
            ${formGroup.text("judul", "Judul", res.judul, true)}
            ${formGroup.select("is_aktif", "Aktif", res.is_aktif, [
                { value: 1, label: "Aktif" },
                { value: 0, label: "Tidak Aktif" },
            ])};
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
