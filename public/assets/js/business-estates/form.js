function renderForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <!-- SECTION 1: INFORMASI KAWASAN -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-navy">
                    <i class="fa-solid fa-industry"></i>
                </div>
                <div class="detail-section-title">Business Estates</div>
                <div class="detail-section-sub">Data kawasan industri</div>
                <button class="btn btn-warning btn-sm btn-edit-context ms-2" title="Edit Business Estate">
                    <i class="fa-solid fa-pen"></i>
                </button>
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
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
                    ${formGroup.text(
                        "provinsi",
                        "Provinsi",
                        res.provinsi,
                        true,
                        {
                            className: "col-md-4 col-12",
                        },
                    )}
                    ${formGroup.text(
                        "kota_kabupaten",
                        "Kota / Kabupaten",
                        res.kota_kabupaten,
                        true,
                        {
                            className: "col-md-4 col-12",
                        },
                    )}
                    ${formGroup.text("website", "Website", res.website, false, {
                        className: "col-md-4 col-12",
                    })}
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
                <div class="detail-section-sub">Data pemilik & pengurus kawasan</div>
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
