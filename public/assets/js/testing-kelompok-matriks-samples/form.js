function renderParameterForm(res) {
    console.log(res, " render form");
    return `
        <form id="detailForm" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="${window.route.csrf}">
            <input type="hidden" name="_method" value="PUT">

            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-2">

                    <h3>Kelompok Matriks Samples</h3>

                    <div class="btn-group">
                        <button class="btn btn-warning btn-sm btn-edit-context" title="Edit Testing Unit">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                    </div>

                </div>
                

                <div class="row mb-4">

                        ${formGroup.text("kode", "Kode", res.kode, true, "col-lg-12")}
                        ${formGroup.text("judul_indonesia", "judul_indonesia", res.judul_indonesia, true, "col-lg-12")}
                        ${formGroup.text("judul_inggris", "judul_inggris", res.judul_inggris, true, "col-lg-12")}
                        ${formGroup.textarea("keterangan", "keterangan", res.keterangan)}

                </div>
            </div>
        </form>

`;
}
