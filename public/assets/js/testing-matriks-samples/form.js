function renderForm(res) {
    console.log(res, " render form ", res);
    return `
<form class="row g-3" id="detailForm" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="id_testing_kelompok_matriks_sample" value="${res.id_testing_kelompok_matriks_sample}">

    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-teal">
                    <i class="fa-solid fa-vials"></i>
                </div>
                <div class="detail-section-title">Testing Matriks Samples</div>
                <div class="detail-section-sub">Data matriks sampel pengujian</div>
                ${formGroup.editButton("Edit Matriks Sample")}
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
                    ${formGroup.select(
                        "kelompok",
                        "Kelompok",
                        res.id_testing_kelompok_matriks_sample,
                        [],
                        {
                            mode: "ajax",
                            url: "testing-kelompok-matriks-samples/select2",
                            placeholder: "Pilih Kelompok",
                            label: res.kelompok_matriks_judul_indonesia,
                            className: "col-md-12",
                            createUrl: "/testing-kelompok-matriks-samples/create",
                        },
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
