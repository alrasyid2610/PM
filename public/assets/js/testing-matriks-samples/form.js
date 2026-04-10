function renderForm(res) {
    console.log(res, " render form ", res);
    return `
        <form id="detailForm" enctype="multipart/form-data">

            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-2">

                    <h3>Testing Matriks Samples</h3>

                    <div class="btn-group">
                        <button class="btn btn-warning btn-sm btn-edit-context" title="Edit Testing Unit">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                    </div>

                </div>
                

                <div class="row mb-4 form-1">
                        <input type="hidden" name="_token" value="${window.route.csrf}">
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="id_testing_kelompok_matriks_sample" value="${res.id_testing_kelompok_matriks_sample}">

                        ${formGroup.select(
                            "kelompok",
                            "kelompok",
                            res.id_testing_kelompok_matriks_sample,
                            [],
                            {
                                mode: "ajax",
                                url: "testing-kelompok-matriks-samples/select2",
                                placeholder: "Pilih Kelompok",
                                label: res.kelompok_matriks_judul_indonesia,
                            },
                        )}

                        ${formGroup.text("kode", "Kode", res.kode, true, "col-lg-12")}
                        ${formGroup.text("judul_indonesia", "judul_indonesia", res.judul_indonesia, true, "col-lg-12")}
                        ${formGroup.text("judul_inggris", "judul_inggris", res.judul_inggris, true, "col-lg-12")}
                        ${formGroup.textarea("keterangan", "keterangan", res.keterangan)}

                </div>
            </div>
        </form>

`;
}
