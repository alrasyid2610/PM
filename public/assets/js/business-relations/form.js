// ─── SAMPLING POINT HELPERS ───────────────────────────────────────────────

function renderSamplingTab(jenis, idSite) {
    const labelJenis = jenis === 'env' ? 'ENV' : 'WE';
    const tabId = `sp-${jenis}`;

    return `
    <div class="card card-body" id="${tabId}-wrap" data-id-site="${idSite}" data-jenis="${jenis}">
        <div class="mb-3">
            <div class="pm-search">
                <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="${tabId}-search" placeholder="Cari kode atau nama..." data-no-disable>
                <button type="button" id="${tabId}-search-clear" class="pm-search-clear d-none" title="Hapus" data-no-disable>
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        </div>
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
                    <div id="spModal-we-fields" class="col-md-12" style="display:none;">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Gedung</label>
                                <input type="text" class="form-control form-control-sm" id="spModal-gedung" placeholder="Nama gedung" data-no-disable>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ruangan</label>
                                <input type="text" class="form-control form-control-sm" id="spModal-ruangan" placeholder="Nama ruangan" data-no-disable>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Lantai</label>
                                <input type="text" class="form-control form-control-sm" id="spModal-lantai" placeholder="cth: 2, LG" data-no-disable>
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

    // Reset search saat reload
    $(`#${tabId}-search`).val('');
    $(`#${tabId}-search-clear`).addClass('d-none');

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
                const searchVal = [r.kode, r.nama, r.keterangan].filter(Boolean).join(' ').toLowerCase();
                return `<tr data-search="${escHtml(searchVal)}">
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
        $('#brTabActionsEnv, #brTabActionsWe, #brTabActionsMp, #brTabActionsProduct').addClass('d-none').removeClass('d-flex');
        $(`#brTabActions${jenis === 'env' ? 'Env' : 'We'}`).removeClass('d-none').addClass('d-flex');
    });

    // Sembunyikan action sampling saat tab Informasi aktif
    $panel.on('shown.bs.tab', '[data-bs-target="#tabBrInfo"]', function () {
        $('#brTabActionsEnv, #brTabActionsWe, #brTabActionsMp, #brTabActionsProduct').addClass('d-none').removeClass('d-flex');
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
            gedung:     jenis === 'we' ? ($('#spModal-gedung').val().trim() || '') : '',
            ruangan:    jenis === 'we' ? ($('#spModal-ruangan').val().trim() || '') : '',
            lantai:     jenis === 'we' ? ($('#spModal-lantai').val().trim() || '') : '',
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

    // Search Sampling (ENV & WE)
    $panel.on('input', '#sp-env-search, #sp-we-search', function () {
        const q     = $(this).val().toLowerCase().trim();
        const tabId = $(this).attr('id').replace('-search', '');
        const $rows = $(`#${tabId}-table-wrap tbody tr`);
        $rows.each(function () {
            const val = $(this).data('search') || '';
            $(this).toggle(!q || val.includes(q));
        });
        $(`#${tabId}-search-clear`).toggleClass('d-none', !q);
    });

    $panel.on('click', '#sp-env-search-clear, #sp-we-search-clear', function () {
        const tabId = $(this).attr('id').replace('-search-clear', '');
        $(`#${tabId}-search`).val('').trigger('input');
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
    $('#spModal-gedung').val(isEdit ? (data.gedung ?? '') : '');
    $('#spModal-ruangan').val(isEdit ? (data.ruangan ?? '') : '');
    $('#spModal-lantai').val(isEdit ? (data.lantai ?? '') : '');
    $('#spModal-keterangan').val(isEdit ? (data.keterangan ?? '') : '');
    $('#spModal-is_aktif').val(isEdit ? data.is_aktif : '1');

    // Label wajib koordinat
    const reqMark = coordRequired ? ' <span class="text-danger">*</span>' : '';
    $('#spModal-lat-label').html('Latitude' + reqMark);
    $('#spModal-lng-label').html('Longitude' + reqMark);

    // Tampilkan field Gedung/Ruangan/Lantai hanya untuk WE
    $('#spModal-we-fields').toggle(!coordRequired);

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

// ─── MAN POWER (MP) ───────────────────────────────────────────────────────

function renderMpModal() {
    return `
<div class="modal fade" id="mpModal" tabindex="-1" aria-labelledby="mpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width:480px;">
        <div class="modal-content">
            <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                <h6 class="modal-title mb-0" id="mpModalLabel">
                    <i class="fa-solid fa-user-plus me-2" style="color:#7c3aed;"></i>
                    Tambah Man Power
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3 px-3">
                <div class="row g-2">
                    <input type="hidden" id="mpModal-id" value="" data-no-disable>
                    <input type="hidden" id="mpModal-id-site" value="" data-no-disable>
                    <div class="col-md-5">
                        <label class="form-label">No. Karyawan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="mpModal-no_karyawan"
                            placeholder="cth: EMP-001" data-no-disable>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="mpModal-nama"
                            placeholder="Nama karyawan" data-no-disable>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Status</label>
                        <select class="form-select form-select-sm" id="mpModal-is_aktif" data-no-disable>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                <button type="button" class="btn btn-sm btn-primary" id="mpModal-btn-save" data-no-disable
                    style="background:#7c3aed;border-color:#7c3aed;">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>`;
}

function renderMpList(rows) {
    if (!rows.length) {
        return `<div class="text-center text-muted py-4" style="font-size:13px;">
            <i class="fa-solid fa-users-slash me-1"></i> Belum ada Man Power terdaftar.
        </div>`;
    }

    const badgeAktif = `<span class="badge" style="background:#dcfce7;color:#166534;font-size:10px;font-weight:600;padding:2px 7px;">Aktif</span>`;
    const badgeNon   = `<span class="badge" style="background:#f1f5f9;color:#64748b;font-size:10px;font-weight:600;padding:2px 7px;">Non-Aktif</span>`;

    const rows_html = rows.map((r, i) => {
        const searchVal = [r.no_karyawan, r.nama].filter(Boolean).join(' ').toLowerCase();
        return `
        <tr data-search="${escHtml(searchVal)}">
            <td style="color:#94a3b8;text-align:center;">${i + 1}</td>
            <td class="fw-semibold">${escHtml(r.no_karyawan ?? '—')}</td>
            <td>${escHtml(r.nama)}</td>
            <td>${r.is_aktif ? badgeAktif : badgeNon}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-mp-edit"
                    data-id="${r.id_mp}" title="Edit" style="font-size:11px;" data-no-disable>
                    <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-mp-delete"
                    data-id="${r.id_mp}" data-nama="${escHtml(r.nama)}" title="Hapus" style="font-size:11px;" data-no-disable>
                    <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
                </button>
            </td>
        </tr>`;
    }).join('');

    return `<div class="table-responsive">
        <table class="pm-table">
            <thead>
                <tr>
                    <th style="width:40px;text-align:center;">No</th>
                    <th style="min-width:120px;">No. Karyawan</th>
                    <th style="min-width:200px;">Nama</th>
                    <th style="min-width:90px;">Status</th>
                    <th style="min-width:80px;">Aksi</th>
                </tr>
            </thead>
            <tbody>${rows_html}</tbody>
        </table>
    </div>`;
}

function loadMpData(idSite) {
    // Reset search saat reload
    $('#mp-search').val('');
    $('#mp-search-clear').addClass('d-none');

    $('#mp-table-wrap').html(
        `<div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>`
    );
    $.get(`/brs-mp/${idSite}/list`)
        .done(function (r) {
            $('#mp-table-wrap').html(renderMpList(r.data ?? []));
        })
        .fail(function () {
            $('#mp-table-wrap').html(
                `<div class="text-center text-danger py-3">Gagal memuat data Man Power.</div>`
            );
        });
}

function initMpTabEvents() {
    const $panel = $('#detailContent');

    // Load data saat tab diklik
    $panel.on('shown.bs.tab', '[data-bs-target="#tabBrsMp"]', function () {
        const idSite = $(this).data('id-site');
        loadMpData(idSite);
        $('#brTabActionsEnv, #brTabActionsWe, #brTabActionsProduct').addClass('d-none').removeClass('d-flex');
        $('#brTabActionsMp').removeClass('d-none').addClass('d-flex');
    });

    // Sembunyikan tombol MP saat pindah tab lain
    $panel.on('shown.bs.tab', '[data-bs-target="#tabBrInfo"]', function () {
        $('#brTabActionsMp, #brTabActionsProduct').addClass('d-none').removeClass('d-flex');
    });
    $panel.on('shown.bs.tab', '[data-bs-target^="#tabSampling"]', function () {
        $('#brTabActionsMp, #brTabActionsProduct').addClass('d-none').removeClass('d-flex');
    });

    // Tombol Tambah MP
    $panel.on('click', '.btn-mp-add', function () {
        const idSite = $(this).data('id-site');
        _openMpModal({ idSite, isEdit: false });
    });

    // Tombol Simpan modal MP
    $(document).off('click.mp', '#mpModal-btn-save').on('click.mp', '#mpModal-btn-save', function () {
        const id     = $('#mpModal-id').val();
        const idSite = $('#mpModal-id-site').val();
        const noKaryawan = $('#mpModal-no_karyawan').val().trim();
        const nama       = $('#mpModal-nama').val().trim();

        if (!noKaryawan || !nama) return Swal.fire('Perhatian', 'No. Karyawan dan Nama wajib diisi.', 'warning');

        const data = {
            _token:      window.route.csrf,
            id_site:     idSite,
            no_karyawan: $('#mpModal-no_karyawan').val().trim() || null,
            nama:        nama,
            is_aktif:    $('#mpModal-is_aktif').val(),
        };

        const isEdit = !!id;
        const url    = isEdit ? `/brs-mp/${id}` : '/brs-mp';
        if (isEdit) data._method = 'PUT';

        $('#mpModal-btn-save').prop('disabled', true);
        $.post(url, data)
            .done(function () {
                bootstrap.Modal.getInstance(document.getElementById('mpModal'))?.hide();
                loadMpData(idSite);
                Swal.fire({ icon: 'success', title: 'Tersimpan', timer: 1200, showConfirmButton: false });
            })
            .fail(function (xhr) {
                const errs = xhr.responseJSON?.errors;
                const msg  = errs ? Object.values(errs).flat().join('<br>') : (xhr.responseJSON?.message || 'Terjadi kesalahan.');
                Swal.fire('Gagal', msg, 'error');
            })
            .always(function () {
                $('#mpModal-btn-save').prop('disabled', false);
            });
    });

    // Tombol Edit baris
    $panel.on('click', '.btn-mp-edit', function () {
        const id     = $(this).data('id');
        const idSite = $('#mp-wrap').data('id-site');
        $.get(`/brs-mp/${id}`)
            .done(function (r) {
                _openMpModal({ idSite, isEdit: true, data: r });
            });
    });

    // Search MP
    $panel.on('input', '#mp-search', function () {
        const q     = $(this).val().toLowerCase().trim();
        const $rows = $('#mp-table-wrap tbody tr');
        $rows.each(function () {
            const val = $(this).data('search') || '';
            $(this).toggle(!q || val.includes(q));
        });
        $('#mp-search-clear').toggleClass('d-none', !q);
    });

    $panel.on('click', '#mp-search-clear', function () {
        $('#mp-search').val('').trigger('input');
    });

    // Tombol Hapus baris
    $panel.on('click', '.btn-mp-delete', function () {
        const id     = $(this).data('id');
        const nama   = $(this).data('nama');
        const idSite = $('#mp-wrap').data('id-site');

        Swal.fire({
            title: 'Hapus Man Power?',
            html: `<b>${nama}</b> akan dihapus.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({ url: `/brs-mp/${id}`, type: 'DELETE', data: { _token: window.route.csrf } })
                .done(function () {
                    loadMpData(idSite);
                    Swal.fire({ icon: 'success', title: 'Dihapus', timer: 1200, showConfirmButton: false });
                })
                .fail(function () { Swal.fire('Gagal', 'Tidak dapat menghapus data.', 'error'); });
        });
    });
}

function _openMpModal({ idSite, isEdit, data }) {
    $('#mpModalLabel').html(
        `<i class="fa-solid fa-user${isEdit ? '-pen' : '-plus'} me-2" style="color:#7c3aed;"></i>`
        + (isEdit ? 'Edit' : 'Tambah') + ' Man Power'
    );

    $('#mpModal-id').val(isEdit ? data.id_mp : '');
    $('#mpModal-id-site').val(idSite);
    $('#mpModal-no_karyawan').val(isEdit ? (data.no_karyawan ?? '') : '');
    $('#mpModal-nama').val(isEdit ? data.nama : '');
    $('#mpModal-is_aktif').val(isEdit ? String(data.is_aktif) : '1');

    new bootstrap.Modal(document.getElementById('mpModal')).show();
}

// ─── PRODUCT ──────────────────────────────────────────────────────────────

function renderProductModal() {
    return `
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-no-disable></button>
            </div>
            <div class="modal-body py-3 px-3">
                <input type="hidden" id="productModal-id" data-no-disable>
                <input type="hidden" id="productModal-id-br" data-no-disable>
                <div class="row g-2">
                    <div class="col-md-8">
                        <label class="form-label">Nama Product <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="productModal-nama_product" placeholder="Nama product" data-no-disable>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Seri Product</label>
                        <input type="text" class="form-control form-control-sm" id="productModal-seri_product" placeholder="Seri / tipe" data-no-disable>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control form-control-sm" id="productModal-keterangan" rows="2" style="resize:none;" placeholder="Opsional" data-no-disable></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select form-select-sm" id="productModal-is_aktif" data-no-disable>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                <button type="button" class="btn btn-sm btn-primary" id="productModal-btn-save" data-no-disable
                    style="background:#0f766e;border-color:#0f766e;">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>`;
}

function renderProductList(rows) {
    if (!rows.length) {
        return `<div class="text-center text-muted py-4">
            <i class="fa-solid fa-box-open me-1"></i> Belum ada product terdaftar.
        </div>`;
    }
    return `
    <div class="table-responsive">
        <table class="pm-table">
            <thead>
                <tr>
                    <th style="width:36px;text-align:center;">NO</th>
                    <th>NAMA PRODUCT</th>
                    <th>SERI</th>
                    <th>STATUS</th>
                    <th style="text-align:center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                ${rows.map((r, i) => {
                    const searchVal = [r.nama_product, r.seri_product].filter(Boolean).join(' ').toLowerCase();
                    return `<tr data-search="${escHtml(searchVal)}">
                        <td style="text-align:center;color:#9ca3af;font-size:12px;">${i + 1}</td>
                        <td class="fw-semibold">${escHtml(r.nama_product)}</td>
                        <td>${r.seri_product ? escHtml(r.seri_product) : '<span class="text-muted">—</span>'}</td>
                        <td>${spStatusBadge(r.is_aktif)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-product-edit"
                                data-id="${r.id_product}" title="Edit" style="font-size:11px;" data-no-disable>
                                <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-product-delete"
                                data-id="${r.id_product}" data-nama="${escHtml(r.nama_product)}" title="Hapus" style="font-size:11px;" data-no-disable>
                                <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
                            </button>
                        </td>
                    </tr>`;
                }).join('')}
            </tbody>
        </table>
    </div>`;
}

function loadProductData(idBr) {
    $('#product-search').val('');
    $('#product-search-clear').addClass('d-none');
    $('#product-table-wrap').html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...</div>');

    $.get(`/br-products/${idBr}/list`)
        .done(function (res) {
            $('#product-table-wrap').html(renderProductList(res.data || []));
        })
        .fail(function () {
            $('#product-table-wrap').html('<div class="text-center text-danger py-3">Gagal memuat data product.</div>');
        });
}

function initProductTabEvents() {
    const $panel = $('#detailContent');

    // Load saat tab dibuka
    $panel.on('shown.bs.tab', '[data-bs-target="#tabBrsProduct"]', function () {
        const idBr = $(this).data('id-br');
        loadProductData(idBr);
        $('#brTabActionsEnv, #brTabActionsWe, #brTabActionsMp, #brTabActionsProduct').addClass('d-none').removeClass('d-flex');
        $('#brTabActionsProduct').removeClass('d-none').addClass('d-flex');
    });

    // Sembunyikan action product saat tab lain aktif
    $panel.on('shown.bs.tab', '[data-bs-target="#tabBrInfo"], [data-bs-target^="#tabSampling"], [data-bs-target="#tabBrsMp"]', function () {
        $('#brTabActionsProduct').addClass('d-none').removeClass('d-flex');
    });

    // Tombol Tambah
    $panel.on('click', '.btn-product-add', function () {
        _openProductModal({ idBr: $(this).data('id-br'), isEdit: false });
    });

    // Tombol Simpan
    $(document).off('click.product', '#productModal-btn-save').on('click.product', '#productModal-btn-save', function () {
        const id    = $('#productModal-id').val();
        const idBr  = $('#productModal-id-br').val();
        const nama  = $('#productModal-nama_product').val().trim();
        const seri  = $('#productModal-seri_product').val().trim();

        if (!nama) return Swal.fire('Perhatian', 'Nama Product wajib diisi.', 'warning');

        const payload = {
            _token:        window.route.csrf,
            id_br:         idBr,
            nama_product:  nama,
            seri_product:  seri,
            keterangan:    $('#productModal-keterangan').val(),
            is_aktif:      $('#productModal-is_aktif').val(),
        };

        const isEdit = !!id;
        const url    = isEdit ? `/br-products/${id}` : '/br-products';
        if (isEdit) payload._method = 'PUT';

        $('#productModal-btn-save').prop('disabled', true);
        $.post(url, payload)
            .done(function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal')).hide();
                loadProductData(idBr);
                Swal.fire({ icon: 'success', title: 'Tersimpan', timer: 1200, showConfirmButton: false });
            })
            .fail(function (xhr) {
                const errs = xhr.responseJSON?.errors;
                const msg  = errs ? Object.values(errs).flat().join('<br>') : (xhr.responseJSON?.message || 'Terjadi kesalahan.');
                Swal.fire('Gagal', msg, 'error');
            })
            .always(function () {
                $('#productModal-btn-save').prop('disabled', false);
            });
    });

    // Tombol Edit di baris
    $panel.on('click', '.btn-product-edit', function () {
        const id = $(this).data('id');
        $.get(`/br-products/${id}`)
            .done(function (r) {
                _openProductModal({ idBr: r.id_br, isEdit: true, data: r });
            });
    });

    // Tombol Hapus di baris
    $panel.on('click', '.btn-product-delete', function () {
        const id   = $(this).data('id');
        const nama = $(this).data('nama');
        const idBr = $('#product-wrap').data('id-br');

        Swal.fire({
            title: 'Hapus Product?',
            html: `<b>${escHtml(nama)}</b> akan dihapus secara permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ef4444',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({ url: `/br-products/${id}`, method: 'DELETE', data: { _token: window.route.csrf } })
                .done(function () {
                    loadProductData(idBr);
                    Swal.fire({ icon: 'success', title: 'Dihapus', timer: 1000, showConfirmButton: false });
                })
                .fail(function (xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                });
        });
    });

    // Search
    $panel.on('input', '#product-search', function () {
        const q = $(this).val().toLowerCase().trim();
        $('#product-table-wrap tbody tr').each(function () {
            $(this).toggle(!q || ($(this).data('search') || '').includes(q));
        });
        $('#product-search-clear').toggleClass('d-none', !q);
    });

    $panel.on('click', '#product-search-clear', function () {
        $('#product-search').val('').trigger('input');
    });
}

function _openProductModal({ idBr, isEdit, data }) {
    $('#productModalLabel').html(
        `<i class="fa-solid fa-box-open me-2" style="color:#0f766e;"></i>`
        + (isEdit ? 'Edit' : 'Tambah') + ' Product'
    );

    $('#productModal-id').val(isEdit ? data.id_product : '');
    $('#productModal-id-br').val(idBr);
    $('#productModal-nama_product').val(isEdit ? data.nama_product : '');
    $('#productModal-seri_product').val(isEdit ? (data.seri_product ?? '') : '');
    $('#productModal-keterangan').val(isEdit ? (data.keterangan ?? '') : '');
    $('#productModal-is_aktif').val(isEdit ? String(data.is_aktif) : '1');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal')).show();
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
        number: escHtml(res.nama_lokasi ?? '—'),
        deleteId: res.id_site,
        editText: 'Edit Business Relation',
        noWrap: true,
        subtitle: `
            <div class="mb-1">
                <span style="font-size:13px;font-weight:600;color:#475569;">${escHtml(res.nama_br ?? '—')}</span>
            </div>
            <div class="d-flex align-items-center gap-2 detail-date" style="margin:0;">
                ${res.is_kantor_pusat
                    ? '<span class="badge" style="background:#dbeafe;color:#1d4ed8;font-size:11px;font-weight:600;padding:3px 8px;">Kantor Pusat</span>'
                    : '<span class="badge" style="background:#f1f5f9;color:#475569;font-size:11px;font-weight:600;padding:3px 8px;">Cabang</span>'}
                <span>Dibuat ${escHtml(res.s_created_at ?? '—')} &nbsp;·&nbsp; Diupdate ${escHtml(res.s_updated_at ?? '—')}</span>
            </div>
        `,
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
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBrsMp"
                        data-id-site="${res.id_site}">
                        <i class="fa-solid fa-users me-1" style="color:#7c3aed;font-size:11px;"></i>
                        Man Power
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBrsProduct"
                        data-id-br="${res.id_br}">
                        <i class="fa-solid fa-box-open me-1" style="color:#0f766e;font-size:11px;"></i>
                        Product
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
                <div id="brTabActionsMp" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill btn-mp-add"
                        data-id-site="${res.id_site}" data-no-disable
                        style="border-color:#7c3aed;color:#7c3aed;">
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                        <i class="fa-solid fa-user-plus" style="font-size:11px;"></i> Tambah
                    </button>
                </div>
                <div id="brTabActionsProduct" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill btn-product-add"
                        data-id-br="${res.id_br}" data-no-disable
                        style="border-color:#0f766e;color:#0f766e;">
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                        <i class="fa-solid fa-box-open" style="font-size:11px;"></i> Tambah
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
                                <div class="col-md-12">
                                    <label class="form-label">Koordinat Site</label>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <input type="number" step="any" name="latitude"
                                                class="form-control form-control-sm"
                                                placeholder="Latitude (cth: -6.12345678)"
                                                value="${res.latitude ?? ''}">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="number" step="any" name="longitude"
                                                class="form-control form-control-sm"
                                                placeholder="Longitude (cth: 106.12345678)"
                                                value="${res.longitude ?? ''}">
                                        </div>
                                    </div>
                                    ${(res.latitude && res.longitude) ? `
                                    <div class="mt-1">
                                        ${spCoordCell(res.latitude, res.longitude)}
                                    </div>` : ''}
                                </div>
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

                <!-- TAB: PRODUCT -->
                <div class="tab-pane fade" id="tabBrsProduct" role="tabpanel">
                    <div class="card card-body" id="product-wrap" data-id-br="${res.id_br}">
                        <div class="mb-3">
                            <div class="pm-search">
                                <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="product-search" placeholder="Cari nama atau seri product..." data-no-disable>
                                <button type="button" id="product-search-clear" class="pm-search-clear d-none" title="Hapus" data-no-disable>
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div id="product-table-wrap">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: MAN POWER -->
                <div class="tab-pane fade" id="tabBrsMp" role="tabpanel">
                    <div class="card card-body" id="mp-wrap" data-id-site="${res.id_site}">
                        <div class="mb-3">
                            <div class="pm-search">
                                <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="mp-search" placeholder="Cari no. karyawan atau nama..." data-no-disable>
                                <button type="button" id="mp-search-clear" class="pm-search-clear d-none" title="Hapus" data-no-disable>
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div id="mp-table-wrap">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    ${renderSpModal()}
    ${renderMpModal()}
    ${renderProductModal()}

</form>
`;
}
