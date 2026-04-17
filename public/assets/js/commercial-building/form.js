function renderUnitForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <!-- SECTION 1: INFORMASI GEDUNG -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-blue">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div class="detail-section-title">Commercial Building</div>
                <div class="detail-section-sub">Data gedung komersial</div>
                <button class="btn btn-warning btn-sm btn-edit-context ms-2" title="Edit Commercial Building">
                    <i class="fa-solid fa-pen"></i>
                </button>
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
                    ${formGroup.text("nama", "Nama Gedung", res.nama, true, {
                        className: "col-md-6 col-12",
                    })}
                    ${formGroup.text("website", "Website", res.website, false, {
                        className: "col-md-4 col-12",
                    })}
                    ${formGroup.select(
                        "is_aktif",
                        "Status",
                        res.is_aktif,
                        [
                            { value: 1, label: "Aktif" },
                            { value: 0, label: "Tidak Aktif" },
                        ],
                        { className: "col-md-2 col-12" },
                    )}
                    ${formGroup.textarea("alamat", "Alamat", res.alamat, {
                        className: "col-md-12",
                    })}
                    ${formGroup.text(
                        "provinsi",
                        "Provinsi",
                        res.provinsi,
                        false,
                        {
                            className: "col-md-4 col-12",
                        },
                    )}
                    ${formGroup.text(
                        "kota_kabupaten",
                        "Kota / Kabupaten",
                        res.kota_kabupaten,
                        false,
                        {
                            className: "col-md-4 col-12",
                        },
                    )}
                    ${formGroup.text(
                        "kode_pos",
                        "Kode Pos",
                        res.kode_pos,
                        false,
                        {
                            className: "col-md-4 col-12",
                        },
                    )}
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: PENANGGUNG JAWAB -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-green">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="detail-section-title">Penanggung Jawab</div>
                <div class="detail-section-sub">Data pemilik & pengurus gedung</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
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
                </div>
            </div>
        </div>
    </div>

</form>
`;
}
