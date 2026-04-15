function renderUnitForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-amber">
                    <i class="fa-solid fa-ruler"></i>
                </div>
                <div class="detail-section-title">Informasi Unit</div>
                <div class="detail-section-sub">Data satuan pengujian laboratorium</div>
                <button class="btn btn-warning btn-sm btn-edit-context ms-2" title="Edit Testing Unit">
                    <i class="fa-solid fa-pen"></i>
                </button>
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
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
                        true,
                        {
                            className: "col-md-4",
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

</form>
`;
}
