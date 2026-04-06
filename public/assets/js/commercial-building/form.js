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

            ${formGroup.text("nama", "Nama Gedung", res.nama, true, {
                className: "col-md-6 col-12",
            })}

            ${formGroup.text("website", "Website", res.website, true, {
                className: "col-md-6 col-12",
            })}

            ${formGroup.textarea("alamat", "Alamat", res.alamat)}

            ${formGroup.text("provinsi", "provinsi", res.provinsi, true, {
                className: "col-md-4 col-12",
            })}

            ${formGroup.text(
                "kota_kabupaten",
                "Kota / Kabupaten",
                res.kota_kabupaten,
                true,
                {
                    className: "col-md-4 col-12",
                },
            )}

            ${formGroup.text("kode_pos", "Kode Pos", res.kode_pos, true, {
                className: "col-md-4 col-12",
            })}

            ${formGroup.text("pemilik", "Pemilik", res.pemilik, true, {
                className: "col-md-5 col-12",
            })}

            ${formGroup.text("pengurus", "pengurus", res.pengurus, true, {
                className: "col-md-5 col-12",
            })}

            ${formGroup.select(
                "is_aktif",
                "Aktif",
                res.is_aktif,
                [
                    { value: 1, label: "Aktif" },
                    { value: 0, label: "Tidak Aktif" },
                ],
                {
                    className: "col-12 col-md-2",
                },
            )};

        </div>

    </div>
</form>

`;
}
