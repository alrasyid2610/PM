function renderParameterForm(res) {
    console.log(res, " render form");
    return `
<form class="row g-3" id="detailForm" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.sectionCard(
        { icon: 'fa-layer-group', color: 'icon-teal', title: 'Kelompok Matriks Samples', subtitle: 'Data kelompok matriks sampel', editTitle: 'Edit Kelompok Matriks' },
        `<div class="row g-3 form-1">
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
                </div>`
    )}

</form>
`;
}
