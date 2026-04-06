function renderForm(res) {
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
        <input type="hidden" name="id_contact" value="${res.id_contact}">
        
        <div class="row mb-4 form-1">
            <input type="hidden" name="_method" value="PUT">

            ${formGroup.select(
                "id_br",
                "Business Relation Site",
                res.id_br,
                [],
                {
                    mode: "ajax",
                    url: "business-relations/sites/select2",
                    placeholder: "Pilih Kelompok",
                    label: res.nama_lokasi,
                },
            )}
        
            ${formGroup.text("nama_pic", "Nama PIC", res.nama_pic, true, {
                className: "col-12",
            })}

            ${formGroup.text(
                "nomor_telepon_pic",
                "No. Telp PIC",
                res.nomor_telepon_pic,
                true,
                {
                    className: "col-md-3 col-12",
                },
            )}

            ${formGroup.text("email_pic", "Email PIC", res.email_pic, true, {
                className: "col-md-3 col-12",
            })}

            ${formGroup.text("lokasi_pic", "Lokasi PIC", res.lokasi_pic, true, {
                className: "col-md-3 col-12",
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
                    className: "col-12 col-md-3",
                },
            )}
        </div>

    </div>
</form>

`;
}
