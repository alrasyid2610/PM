function renderForm(res) {
    return `
<form id="detailForm">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="id_br" value="${res.id_br}">
    <input type="hidden" name="id_site" value="${res.id_site}">

    ${formGroup.actionBar({
        number: escHtml(res.nama_br ?? '—'),
        createdAt: escHtml(res.s_created_at ?? '—'),
        updatedAt: escHtml(res.s_updated_at ?? '—'),
        deleteId: res.id_site,
        editText: 'Edit Business Relation',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="brDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBrInfo">
                        <i class="fa-solid fa-building me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="brTabActionsInfo" class="d-flex align-items-center gap-2">
                    <!-- Edit/Hapus di action bar atas -->
                </div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <!-- TAB: INFORMASI -->
                <div class="tab-pane fade show active" id="tabBrInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            {
                                icon: "fa-building",
                                color: "icon-navy",
                                title: "Business Relation",
                                subtitle: "Data utama perusahaan klien",
                            },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("nama_br", "Nama Business Relation", res.nama_br, true, { className: "col-md-12" })}
                                ${formGroup.select("entitas", "Entitas", res.entitas,
                                    [
                                        { value: "Perseroan Terbatas",        label: "Perseroan Terbatas" },
                                        { value: "Commanditaire Vennootschap", label: "Commanditaire Vennootschap" },
                                        { value: "Firma",                     label: "Firma" },
                                        { value: "Koperasi",                  label: "Koperasi" },
                                    ],
                                    { className: "col-md-4" }
                                )}
                                ${formGroup.select("kepemilikan", "Kepemilikan", res.kepemilikan,
                                    [
                                        { value: "Swasta",      label: "Swasta" },
                                        { value: "BUMN/BUMD",   label: "BUMN/BUMD" },
                                        { value: "Pemerintah",  label: "Pemerintah" },
                                    ],
                                    { className: "col-md-4" }
                                )}
                                ${formGroup.text("npwp", "NPWP", res.npwp, false, { className: "col-md-4" })}
                                ${formGroup.select("kategori_bisnis", "Kategori Bisnis", res.kategori_bisnis,
                                    [
                                        { value: "Manufaktur",       label: "Manufaktur" },
                                        { value: "Makanan & Minuman", label: "Makanan & Minuman" },
                                        { value: "Otomotif",         label: "Otomotif" },
                                        { value: "Industri",         label: "Industri" },
                                        { value: "Perdagangan",      label: "Perdagangan" },
                                        { value: "Jasa",             label: "Jasa" },
                                        { value: "Konstruksi",       label: "Konstruksi" },
                                    ],
                                    { className: "col-md-4" }
                                )}
                                ${formGroup.select("sub_kategori_bisnis", "Sub Kategori Bisnis", res.sub_kategori_bisnis,
                                    [
                                        { value: "Otomotif",  label: "Otomotif" },
                                        { value: "Food",      label: "Food" },
                                        { value: "Industry",  label: "Industry" },
                                    ],
                                    { className: "col-md-4" }
                                )}
                                ${formGroup.text("website", "Website", res.website, false, { className: "col-md-4" })}
                                ${formGroup.text("nomor_telepon", "Nomor Telepon", res.nomor_telepon, false, { className: "col-md-4" })}
                                ${formGroup.select("br_is_aktif", "Status", res.br_is_aktif,
                                    [
                                        { value: 1, label: "Aktif" },
                                        { value: 0, label: "Tidak Aktif" },
                                    ],
                                    { className: "col-md-4" }
                                )}
                                ${formGroup.textarea("npwp_alamat", "Alamat NPWP", res.npwp_alamat, { className: "col-md-12" })}
                            </div>`
                        )}

                        ${formGroup.sectionCard(
                            {
                                icon: "fa-location-dot",
                                color: "icon-blue",
                                title: "Business Relation Site",
                                subtitle: "Data Site",
                            },
                            `<div class="row g-3 form-2">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Pilih Site</label>
                                    <select id="site-switcher"
                                            data-id-br="${res.id_br}"
                                            data-id-site="${res.id_site}"
                                            data-no-disable="true"
                                            class="form-select">
                                        <option value="${res.id_site}" selected>${res.nama_lokasi}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 form-2">
                                ${formGroup.text("nama_lokasi", "Site", res.nama_lokasi, true, { className: "col-md-7" })}
                                ${formGroup.text("npwp_cabang", "NPWP Site", res.npwp_cabang, false, { className: "col-md-3" })}
                                ${formGroup.checkbox("is_kantor_pusat", "Kantor Pusat", res.is_kantor_pusat, { className: "col-md-2", checkLabel: "Kantor Pusat" })}
                                ${formGroup.wilayah({
                                    provinsiValue: res.provinsi,
                                    kotaValue: res.kota_kabupaten,
                                    kecamatanValue: res.kecamatan,
                                    kelurahanValue: res.kelurahan,
                                    kodePos: res.kode_pos,
                                })}
                                ${formGroup.select("kawasan_bisnis", "Kawasan Bisnis", res.id_bestate, [], {
                                    mode: "ajax", url: "/business-estates/select2",
                                    placeholder: "Pilih Kawasan Bisnis", label: res.nama_kawasan_bisnis,
                                    className: "col-md-4", allowClear: true, showAll: true,
                                    createUrl: "/business-estates/create",
                                })}
                                ${formGroup.select("gedung", "Gedung", res.id_building, [], {
                                    mode: "ajax", url: "/commercial-buildings/select2",
                                    placeholder: "Pilih Gedung", label: res.nama_gedung,
                                    className: "col-md-4", allowClear: true, showAll: true,
                                    createUrl: "/commercial-buildings/create",
                                })}
                                ${formGroup.select("s_is_aktif", "Status", res.s_is_aktif,
                                    [
                                        { value: 1, label: "Aktif" },
                                        { value: 0, label: "Tidak Aktif" },
                                    ],
                                    { className: "col-md-4" }
                                )}
                                ${formGroup.textarea("alamat_lengkap", "Alamat Lengkap", res.alamat_lengkap, { className: "col-md-12" })}
                            </div>`
                        )}

                    </div>
                </div>

            </div>
        </div>
    </div>

</form>
`;
}
