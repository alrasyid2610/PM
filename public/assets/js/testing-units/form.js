function renderUnitForm(res) {
    return `

<form class="row g-3" id="detailForm">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h3>Testing Unit</h3>
            <div class="btn-group">
                <button class="btn btn-warning btn-sm btn-edit-context" title="Edit Testing Unit">
                    <i class="fa-solid fa-pen"></i>
                </button>
            </div>
        </div>
        <input type="hidden" name="_token" value="${window.route.csrf}">
        <input type="hidden" name="_method" value="PUT">

        <div class="row mb-4 form-1">

            ${formGroup.text("kode", "Kode", res.kode, true)}

            ${formGroup.text(
                "judul_indonesia",
                "Judul Indonesia",
                res.judul_indonesia,
                true,
            )}

            ${formGroup.text(
                "judul_inggris",
                "Judul Inggris",
                res.judul_inggris,
                true,
            )}

            ${formGroup.textarea("keterangan", "Keterangan", res.keterangan)}
        </div>

    </div>
</form>

`;
}
