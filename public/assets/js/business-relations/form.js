function renderForm(res) {
    console.log(
        "sebelum render kawasan:",
        res.id_bestate,
        res.nama_kawasan_bisnis,
    );
    console.log("sebelum render gedung:", res.id_building, res.nama_gedung);

    return `

<form class="row g-3" id="detailForm">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h3>Business Relations</h3>
            <div class="btn-group">
                <button class="btn btn-warning btn-sm btn-edit-context" title="Edit Testing Unit">
                    <i class="fa-solid fa-pen"></i>
                </button>
            </div>
        </div>
        
        <div class="row mb-4 form-1">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="_token" value="${window.route.csrf}">
            <input type="hidden" name="id_br" value="${res.id_br}">
            <input type="hidden" name="id_site" value="${res.id_site}">


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
                [{ value: "Perseroan Terbatas", label: "Perseroan Terbatas" }],
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
                {
                    className: "col-md-4",
                },
            )}

            ${formGroup.text("npwp", "NPWP", res.npwp, true, {
                className: "col-lg-4",
            })}

            ${formGroup.text(
                "sub_kategori_bisnis",
                "Sub Kategori Bisnis",
                res.sub_kategori_bisnis,
                true,
                {
                    className: "col-md-4",
                },
            )}

            ${formGroup.text("website", "Website", res.website, true, {
                className: "col-md-4",
            })}

            ${formGroup.select(
                "br_is_aktif",
                "Aktif",
                res.br_is_aktif,
                [
                    { value: 1, label: "Aktif" },
                    { value: 0, label: "Tidak Aktif" },
                ],
                {
                    className: "col-md-4",
                },
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

        <div class="row mb-4 form-2">
            <h3>Business Relation Site</h3>
            <input type="hidden" name="_method" value="PUT">

            ${formGroup.text(
                "nama_lokasi",
                "Lokasi / Cabang",
                res.nama_lokasi,
                true,
                {
                    className: "col-md-12",
                },
            )}


            ${formGroup.text(
                "npwp_cabang",
                "NPWP Cabang",
                res.npwp_cabang,
                true,
                {
                    className: "col-lg-12",
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
                    label: res.nama_kawasan_bisnis, // ← pakai nama_kawasan_bisnis untuk label
                    className: "col-md-4",
                    allowClear: true,
                    showAll: true,
                },
            )}

            ${formGroup.select("gedung", "Gedung", res.id_building, [], {
                mode: "ajax",
                url: "/commercial-buildings/select2",
                placeholder: "Pilih Gedung",
                label: res.nama_gedung, // ← pakai nama_gedung untuk label
                className: "col-md-4",
                allowClear: true,
                showAll: true,
            })}

            ${formGroup.select(
                "s_is_aktif",
                "Aktif",
                res.s_is_aktif,
                [
                    { value: 1, label: "Aktif" },
                    { value: 0, label: "Tidak Aktif" },
                ],
                {
                    className: "col-md-4",
                },
            )}

            ${formGroup.textarea(
                "alamat_lengkap",
                "alamat Lengkap",
                res.alamat_lengkap,
                {
                    className: "col-md-12",
                },
            )}

        </div>

    </div>
</form>

`;
}
