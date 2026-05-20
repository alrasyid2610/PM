function renderForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.nama ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_bestate,
        editText: 'Edit Business Estate',
    })}

    <!-- SECTION 1: INFORMASI KAWASAN -->
    ${formGroup.sectionCard(
        { icon: 'fa-industry', color: 'icon-navy', title: 'Business Estates', subtitle: 'Data kawasan industri' },
        `<div class="row g-3 form-1">
                    ${formGroup.text("nama", "Nama Kawasan", res.nama, true, {
                        className: "col-md-6 col-12",
                    })}
                    ${formGroup.text("kode", "Kode", res.kode, true, {
                        className: "col-md-3 col-12",
                    })}
                    ${formGroup.select(
                        "is_aktif",
                        "Status",
                        res.is_aktif,
                        [
                            { value: 1, label: "Aktif" },
                            { value: 0, label: "Tidak Aktif" },
                        ],
                        { className: "col-md-3 col-12" },
                    )}
                    ${formGroup.textarea("alamat", "Alamat", res.alamat, {
                        className: "col-md-12",
                    })}
                    <div class="col-md-4 col-12 mb-3">
                        <label class="form-label">Provinsi</label>
                        <select name="provinsi" class="form-select wilayah-provinsi disabled" data-value="${res.provinsi ?? ''}">
                            ${res.provinsi ? `<option value="${res.provinsi}" selected>${res.provinsi}</option>` : '<option value="">-- Pilih --</option>'}
                        </select>
                    </div>
                    <div class="col-md-4 col-12 mb-3">
                        <label class="form-label">Kota / Kabupaten</label>
                        <select name="kota_kabupaten" class="form-select wilayah-kota disabled" data-value="${res.kota_kabupaten ?? ''}">
                            ${res.kota_kabupaten ? `<option value="${res.kota_kabupaten}" selected>${res.kota_kabupaten}</option>` : '<option value="">-- Pilih --</option>'}
                        </select>
                    </div>
                    ${formGroup.text("website", "Website", res.website, false, {
                        className: "col-md-4 col-12",
                    })}
                </div>`
    )}

    <!-- SECTION 2: PENANGGUNG JAWAB -->
    ${formGroup.sectionCard(
        { icon: 'fa-users', color: 'icon-green', title: 'Penanggung Jawab', subtitle: 'Data pemilik & pengurus kawasan' },
        `<div class="row g-3 form-2">
                    ${formGroup.text("pemilik", "Pemilik", res.pemilik, false, {
                        className: "col-md-6 col-12",
                    })}
                    ${formGroup.text(
                        "pengurus",
                        "Pengurus",
                        res.pengurus,
                        false,
                        {
                            className: "col-md-6 col-12",
                        },
                    )}
                </div>`
    )}

</form>
`;
}
