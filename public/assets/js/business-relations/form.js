function renderForm(res) {
    console.log(
        "sebelum render kawasan:",
        res.id_bestate,
        res.nama_kawasan_bisnis,
    );
    console.log("sebelum render gedung:", res.id_building, res.nama_gedung);

    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="id_br" value="${res.id_br}">
    <input type="hidden" name="id_site" value="${res.id_site}">

    <!-- SECTION 1: BUSINESS RELATION -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-navy">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div class="detail-section-title">Business Relation</div>
                <div class="detail-section-sub">Data utama perusahaan klien</div>
                ${formGroup.editButton("Edit Business Relation")}
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
                    ${formGroup.text(
                        "nama_br",
                        "Nama Business Relation",
                        res.nama_br,
                        true,
                        {
                            className: "col-md-12",
                        },
                    )}
                    ${formGroup.select(
                        "entitas",
                        "Entitas",
                        res.entitas,
                        [
                            {
                                value: "Perseroan Terbatas",
                                label: "Perseroan Terbatas",
                            },
                            {
                                value: "Commanditaire Vennootschap",
                                label: "Commanditaire Vennootschap",
                            },
                            { value: "Firma", label: "Firma" },
                            { value: "Koperasi", label: "Koperasi" },
                        ],
                        { className: "col-md-4" },
                    )}
                    ${formGroup.select(
                        "kepemilikan",
                        "Kepemilikan",
                        res.kepemilikan,
                        [
                            { value: "Swasta", label: "Swasta" },
                            { value: "BUMN/BUMD", label: "BUMN/BUMD" },
                            { value: "Pemerintah", label: "Pemerintah" },
                        ],
                        { className: "col-md-4" },
                    )}
                    ${formGroup.text("npwp", "NPWP", res.npwp, false, {
                        className: "col-md-4",
                    })}
                    ${formGroup.text(
                        "sub_kategori_bisnis",
                        "Sub Kategori Bisnis",
                        res.sub_kategori_bisnis,
                        false,
                        {
                            className: "col-md-4",
                        },
                    )}
                    ${formGroup.text("website", "Website", res.website, false, {
                        className: "col-md-4",
                    })}
                    ${formGroup.select(
                        "br_is_aktif",
                        "Status",
                        res.br_is_aktif,
                        [
                            { value: 1, label: "Aktif" },
                            { value: 0, label: "Tidak Aktif" },
                        ],
                        { className: "col-md-4" },
                    )}
                    ${formGroup.textarea(
                        "npwp_alamat",
                        "Alamat NPWP",
                        res.npwp_alamat,
                        {
                            className: "col-md-12",
                        },
                    )}
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: BUSINESS RELATION SITE -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-blue">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div class="detail-section-title">Business Relation Site</div>
                <div class="detail-section-sub">Data lokasi & cabang</div>
                <div style="min-width:220px">
                    <select id="site-switcher"
                            data-id-br="${res.id_br}"
                            data-id-site="${res.id_site}"
                            data-no-disable="true"
                            class="form-select form-select-sm">
                        <option value="${res.id_site}" selected>${res.nama_lokasi}</option>
                    </select>
                </div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-2">
                    ${formGroup.text(
                        "nama_lokasi",
                        "Lokasi / Cabang",
                        res.nama_lokasi,
                        true,
                        {
                            className: "col-md-8",
                        },
                    )}
                    ${formGroup.text(
                        "npwp_cabang",
                        "NPWP Cabang",
                        res.npwp_cabang,
                        false,
                        {
                            className: "col-md-4",
                        },
                    )}
                    ${formGroup.wilayah({
                        provinsiValue: res.provinsi,
                        kotaValue: res.kota_kabupaten,
                        kecamatanValue: res.kecamatan,
                        kelurahanValue: res.kelurahan,
                        kodePos: res.kode_pos,
                    })}
                    ${formGroup.select(
                        "kawasan_bisnis",
                        "Kawasan Bisnis",
                        res.id_bestate,
                        [],
                        {
                            mode: "ajax",
                            url: "/business-estates/select2",
                            placeholder: "Pilih Kawasan Bisnis",
                            label: res.nama_kawasan_bisnis,
                            className: "col-md-4",
                            allowClear: true,
                            showAll: true,
                        },
                    )}
                    ${formGroup.select(
                        "gedung",
                        "Gedung",
                        res.id_building,
                        [],
                        {
                            mode: "ajax",
                            url: "/commercial-buildings/select2",
                            placeholder: "Pilih Gedung",
                            label: res.nama_gedung,
                            className: "col-md-4",
                            allowClear: true,
                            showAll: true,
                        },
                    )}
                    ${formGroup.select(
                        "s_is_aktif",
                        "Status",
                        res.s_is_aktif,
                        [
                            { value: 1, label: "Aktif" },
                            { value: 0, label: "Tidak Aktif" },
                        ],
                        { className: "col-md-4" },
                    )}
                    ${formGroup.textarea(
                        "alamat_lengkap",
                        "Alamat Lengkap",
                        res.alamat_lengkap,
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
