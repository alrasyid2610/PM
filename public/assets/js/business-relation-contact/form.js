function renderForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="id_contact" value="${res.id_contact}">

    ${formGroup.sectionCard(
        { icon: 'fa-address-card', color: 'icon-green', title: 'Business Relation Contacts', subtitle: 'Data kontak PIC pelanggan', editTitle: 'Edit Contact' },
        `<div class="row g-3 form-1">
                    ${formGroup.select(
                        "id_br",
                        "Business Relation",
                        res.id_br,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relations/select2",
                            placeholder: "Pilih Business Relation",
                            label: res.nama_br,
                            className: "col-md-12",
                            createUrl: "/business-relations/create",
                        },
                    )}
                    ${formGroup.text(
                        "nama_pic",
                        "Nama PIC",
                        res.nama_pic,
                        true,
                        {
                            className: "col-md-12",
                        },
                    )}
                    ${formGroup.text(
                        "nomor_telepon_pic",
                        "No. Telp PIC",
                        res.nomor_telepon_pic,
                        true,
                        {
                            className: "col-md-3",
                        },
                    )}
                    ${formGroup.text(
                        "email_pic",
                        "Email PIC",
                        res.email_pic,
                        true,
                        {
                            className: "col-md-3",
                        },
                    )}
                    ${formGroup.text(
                        "lokasi_pic",
                        "Lokasi PIC",
                        res.lokasi_pic,
                        true,
                        {
                            className: "col-md-3",
                        },
                    )}
                    ${formGroup.select(
                        "is_aktif",
                        "Status",
                        res.is_aktif,
                        [
                            { value: 1, label: "Aktif" },
                            { value: 0, label: "Tidak Aktif" },
                        ],
                        { className: "col-md-3" },
                    )}
                </div>`
    )}

</form>
`;
}
