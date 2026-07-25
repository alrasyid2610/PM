// ─── SAMPLING POINT HELPERS ───────────────────────────────────────────────

function renderSamplingTab(jenis, idSite) {
    const labelJenis = jenis === 'env' ? 'ENV' : 'WE';
    const tabId = `sp-${jenis}`;

    return `
    <div class="card card-body" id="${tabId}-wrap" data-id-site="${idSite}" data-jenis="${jenis}">
        <div id="${tabId}-table-wrap">
            <div class="text-center text-muted py-4">
                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...
            </div>
        </div>
    </div>`;
}

function renderSpModal() {
    return `
<div class="modal fade" id="spModal" tabindex="-1" aria-labelledby="spModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content">
            <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                <h6 class="modal-title mb-0" id="spModalLabel">
                    <i class="fa-solid fa-plus me-2" style="color:#1e40af;"></i>
                    Tambah Sampling Point
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3 px-3">
                <div class="row g-2">
                    <input type="hidden" id="spModal-id" value="" data-no-disable>
                    <input type="hidden" id="spModal-jenis" value="" data-no-disable>
                    <input type="hidden" id="spModal-id-site" value="" data-no-disable>
                    <input type="hidden" id="spModal-coord-required" value="" data-no-disable>
                    <div class="col-md-4">
                        <label class="form-label">Kode <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="spModal-kode" placeholder="cth: A, SP-001" data-no-disable>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="spModal-nama" placeholder="Nama titik sampling" data-no-disable>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select form-select-sm" id="spModal-is_aktif" data-no-disable>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end" id="spModal-has-coord-wrap">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="spModal-has-coord" data-no-disable>
                            <label class="form-check-label" style="font-size:12px;" for="spModal-has-coord">Ada Koordinat</label>
                        </div>
                    </div>
                    <div id="spModal-coord-wrap" class="col-md-12" style="display:none;">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" id="spModal-lat-label">Latitude</label>
                                <input type="number" step="any" class="form-control form-control-sm" id="spModal-latitude" placeholder="-6.12345678" data-no-disable>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" id="spModal-lng-label">Longitude</label>
                                <input type="number" step="any" class="form-control form-control-sm" id="spModal-longitude" placeholder="106.12345678" data-no-disable>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control form-control-sm" id="spModal-keterangan" rows="2" style="resize:none;" data-no-disable></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                <button type="button" class="btn btn-sm btn-primary" id="spModal-btn-save" data-no-disable>
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>`;
}

function spStatusBadge(isAktif) {
    return isAktif
        ? `<span class="pm-badge pm-badge--completed" style="font-size:10px;">Aktif</span>`
        : `<span class="pm-badge pm-badge--cancelled" style="font-size:10px;">Non-aktif</span>`;
}

function spCoordCell(lat, lng) {
    if (!lat || !lng) return '<span class="text-muted">—</span>';
    const url = `https://www.google.com/maps?q=${lat},${lng}`;
    return `<a href="${url}" target="_blank" title="Buka di Google Maps" style="font-size:11px;white-space:nowrap;">
        <i class="fa-solid fa-map-location-dot me-1" style="color:#dc2626;"></i>${parseFloat(lat).toFixed(5)}, ${parseFloat(lng).toFixed(5)}
    </a>`;
}

function loadSamplingData(jenis, idSite) {
    const tabId    = `sp-${jenis}`;
    const $wrap    = $(`#${tabId}-table-wrap`);

    $wrap.html(`<div class="text-center text-muted py-4" id="${tabId}-loading">
        <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...
    </div>`);

    $.get(`/brs-sampling-points/${idSite}/data`, { jenis })
        .done(function (res) {
            const rows      = res.data || [];
            const labelJenis = jenis === 'env' ? 'ENV' : 'WE';

            if (rows.length === 0) {
                $wrap.html(`<div class="text-center text-muted py-4" style="font-size:13px;">
                    <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                    Belum ada data Sampling Point ${labelJenis}
                </div>`);
                return;
            }

            const rowsHtml = rows.map(function (r, idx) {
                return `<tr>
                    <td style="color:#94a3b8;text-align:center;">${idx + 1}</td>
                    <td class="fw-semibold">${escHtml(r.kode)}</td>
                    <td>${escHtml(r.nama)}${r.keterangan ? `<br><small class="text-muted">${escHtml(r.keterangan)}</small>` : ''}</td>
                    <td>${spCoordCell(r.latitude, r.longitude)}</td>
                    <td>${spStatusBadge(r.is_aktif)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-sp-edit"
                            data-id="${r.id_sp}" data-jenis="${jenis}" title="Edit" style="font-size:11px;" data-no-disable>
                            <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-sp-delete"
                            data-id="${r.id_sp}" data-nama="${escHtml(r.nama)}" data-jenis="${jenis}" title="Hapus" style="font-size:11px;" data-no-disable>
                            <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');

            $wrap.html(`<div class="table-responsive">
                <table class="pm-table">
                    <thead>
                        <tr>
                            <th style="width:40px;text-align:center;">No</th>
                            <th style="min-width:80px;">Kode</th>
                            <th style="min-width:200px;">Nama</th>
                            <th style="min-width:160px;">Koordinat</th>
                            <th style="min-width:90px;">Status</th>
                            <th style="min-width:80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>`);
        })
        .fail(function () {
            $wrap.html('<div class="text-center text-danger py-3">Gagal memuat data.</div>');
        });
}

function initSamplingTabEvents() {
    const $panel = $('#detailContent');

    // Load data saat tab diklik + tampilkan tombol Tambah yang sesuai
    $panel.on('shown.bs.tab', '[data-bs-target^="#tabSampling"]', function () {
        const jenis  = $(this).data('jenis');
        const idSite = $(this).data('id-site');
        loadSamplingData(jenis, idSite);

        // Tampilkan action button yang sesuai
        $('#brTabActionsEnv, #brTabActionsWe').addClass('d-none').removeClass('d-flex');
        $(`#brTabActions${jenis === 'env' ? 'Env' : 'We'}`).removeClass('d-none').addClass('d-flex');
    });

    // Sembunyikan action sampling saat tab Informasi aktif
    $panel.on('shown.bs.tab', '[data-bs-target="#tabBrInfo"]', function () {
        $('#brTabActionsEnv, #brTabActionsWe').addClass('d-none').removeClass('d-flex');
    });

    // Tombol Tambah (di tab-actions area)
    $panel.on('click', '.btn-sp-add', function () {
        const jenis        = $(this).data('jenis');
        const idSite       = $(this).data('id-site');
        const coordRequired = jenis === 'env';

        _openSpModal({ jenis, idSite, coordRequired, isEdit: false });
    });

    // Toggle koordinat di modal (WE)
    $(document).off('change.sp', '#spModal-has-coord').on('change.sp', '#spModal-has-coord', function () {
        $('#spModal-coord-wrap').toggle(this.checked);
    });

    // Tombol Simpan di modal
    $(document).off('click.sp', '#spModal-btn-save').on('click.sp', '#spModal-btn-save', function () {
        const jenis         = $('#spModal-jenis').val();
        const idSite        = $('#spModal-id-site').val();
        const coordRequired = $('#spModal-coord-required').val() === 'true';
        const id            = $('#spModal-id').val();
        const hasCoord      = coordRequired || $('#spModal-has-coord').is(':checked');

        const kode = $('#spModal-kode').val().trim();
        const nama = $('#spModal-nama').val().trim();
        const lat  = $('#spModal-latitude').val().trim();
        const lng  = $('#spModal-longitude').val().trim();

        if (!kode || !nama) return Swal.fire('Perhatian', 'Kode dan Nama wajib diisi.', 'warning');
        if (coordRequired && (!lat || !lng)) return Swal.fire('Perhatian', 'Koordinat wajib diisi untuk Sampling ENV.', 'warning');

        const data = {
            _token:     window.route.csrf,
            id_site:    idSite,
            jenis:      jenis,
            kode:       kode,
            nama:       nama,
            latitude:   hasCoord ? lat : '',
            longitude:  hasCoord ? lng : '',
            keterangan: $('#spModal-keterangan').val(),
            is_aktif:   $('#spModal-is_aktif').val(),
        };

        const isEdit = !!id;
        const url    = isEdit ? `/brs-sampling-points/${id}` : '/brs-sampling-points';
        if (isEdit) data._method = 'PUT';

        $('#spModal-btn-save').prop('disabled', true);
        $.post(url, data)
            .done(function () {
                bootstrap.Modal.getInstance(document.getElementById('spModal'))?.hide();
                loadSamplingData(jenis, idSite);
                Swal.fire({ icon: 'success', title: 'Tersimpan', timer: 1200, showConfirmButton: false });
            })
            .fail(function (xhr) {
                const errs = xhr.responseJSON?.errors;
                const msg  = errs ? Object.values(errs).flat().join('<br>') : (xhr.responseJSON?.message || 'Terjadi kesalahan.');
                Swal.fire('Gagal', msg, 'error');
            })
            .always(function () {
                $('#spModal-btn-save').prop('disabled', false);
            });
    });

    // Tombol Edit di baris tabel
    $panel.on('click', '.btn-sp-edit', function () {
        const id    = $(this).data('id');
        const jenis = $(this).data('jenis');
        const idSite = $(`#sp-${jenis}-wrap`).data('id-site');

        $.get(`/brs-sampling-points/${id}`)
            .done(function (r) {
                const coordRequired = r.jenis === 'env';
                _openSpModal({ jenis: r.jenis, idSite, coordRequired, isEdit: true, data: r });
            });
    });

    // Tombol Hapus di baris tabel
    $panel.on('click', '.btn-sp-delete', function () {
        const id     = $(this).data('id');
        const nama   = $(this).data('nama');
        const jenis  = $(this).data('jenis');
        const idSite = $(`#sp-${jenis}-wrap`).data('id-site');

        Swal.fire({
            title: 'Hapus Sampling Point?',
            html: `<b>${nama}</b> akan dihapus.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({ url: `/brs-sampling-points/${id}`, type: 'DELETE', data: { _token: window.route.csrf } })
                .done(function () {
                    loadSamplingData(jenis, idSite);
                    Swal.fire({ icon: 'success', title: 'Dihapus', timer: 1200, showConfirmButton: false });
                })
                .fail(function () { Swal.fire('Gagal', 'Tidak dapat menghapus data.', 'error'); });
        });
    });
}

function _openSpModal({ jenis, idSite, coordRequired, isEdit, data }) {
    const labelJenis = jenis === 'env' ? 'ENV' : 'WE';
    const iconClass  = jenis === 'env' ? 'fa-wind' : 'fa-helmet-safety';
    const iconColor  = jenis === 'env' ? '#0e7490' : '#b45309';

    // Set judul modal
    $('#spModalLabel').html(
        `<i class="fa-solid ${iconClass} me-2" style="color:${iconColor};"></i>`
        + (isEdit ? `Edit` : `Tambah`) + ` Sampling Point ${labelJenis}`
    );

    // Reset & isi fields
    $('#spModal-id').val(isEdit ? data.id_sp : '');
    $('#spModal-jenis').val(jenis);
    $('#spModal-id-site').val(idSite);
    $('#spModal-coord-required').val(coordRequired ? 'true' : 'false');
    $('#spModal-kode').val(isEdit ? data.kode : '');
    $('#spModal-nama').val(isEdit ? data.nama : '');
    $('#spModal-latitude').val(isEdit ? (data.latitude ?? '') : '');
    $('#spModal-longitude').val(isEdit ? (data.longitude ?? '') : '');
    $('#spModal-keterangan').val(isEdit ? (data.keterangan ?? '') : '');
    $('#spModal-is_aktif').val(isEdit ? data.is_aktif : '1');

    // Label wajib koordinat
    const reqMark = coordRequired ? ' <span class="text-danger">*</span>' : '';
    $('#spModal-lat-label').html('Latitude' + reqMark);
    $('#spModal-lng-label').html('Longitude' + reqMark);

    // Tampilkan/sembunyikan checkbox "Ada Koordinat" (hanya WE)
    if (coordRequired) {
        $('#spModal-has-coord-wrap').hide();
        $('#spModal-has-coord').prop('checked', true);
        $('#spModal-coord-wrap').show();
    } else {
        $('#spModal-has-coord-wrap').show();
        const hasCoord = isEdit ? !!(data.latitude || data.longitude) : false;
        $('#spModal-has-coord').prop('checked', hasCoord);
        $('#spModal-coord-wrap').toggle(hasCoord);
    }

    new bootstrap.Modal(document.getElementById('spModal')).show();
}

// ─── MAIN FORM ────────────────────────────────────────────────────────────

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
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabSamplingEnv"
                        data-id-site="${res.id_site}" data-jenis="env">
                        <i class="fa-solid fa-wind me-1" style="color:#0e7490;font-size:11px;"></i>
                        Sampling ENV
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabSamplingWe"
                        data-id-site="${res.id_site}" data-jenis="we">
                        <i class="fa-solid fa-helmet-safety me-1" style="color:#b45309;font-size:11px;"></i>
                        Sampling WE
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="brTabActionsInfo" class="d-flex align-items-center gap-2">
                    <!-- Edit/Hapus di action bar atas -->
                </div>
                <div id="brTabActionsEnv" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill pm-btn-pill--teal btn-sp-add"
                        data-jenis="env" data-id-site="${res.id_site}" data-no-disable>
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                        <i class="fa-solid fa-wind" style="font-size:11px;"></i> Tambah
                    </button>
                </div>
                <div id="brTabActionsWe" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill pm-btn-pill--amber btn-sp-add"
                        data-jenis="we" data-id-site="${res.id_site}" data-no-disable>
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                        <i class="fa-solid fa-helmet-safety" style="font-size:11px;"></i> Tambah
                    </button>
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
                                ${formGroup.text("nama_jalan", "Nama Jalan", res.nama_jalan, false, { className: "col-md-12" })}
                                ${formGroup.textarea("alamat_lengkap", "Alamat Lengkap", res.alamat_lengkap, { className: "col-md-12" })}
                                ${formGroup.textarea("keterangan_alamat", "Keterangan Alamat", res.keterangan_alamat, { className: "col-md-12" })}
                            </div>`
                        )}

                    </div>
                </div>

                <!-- TAB: SAMPLING ENV -->
                <div class="tab-pane fade" id="tabSamplingEnv" role="tabpanel">
                    ${renderSamplingTab('env', res.id_site)}
                </div>

                <!-- TAB: SAMPLING WE -->
                <div class="tab-pane fade" id="tabSamplingWe" role="tabpanel">
                    ${renderSamplingTab('we', res.id_site)}
                </div>

            </div>
        </div>
    </div>

    ${renderSpModal()}

</form>
`;
}
