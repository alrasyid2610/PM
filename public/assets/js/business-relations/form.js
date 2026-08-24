// ─── TAB ACTIONS HELPER ────────────────────────────────────────────────────

function hideAllBrTabActions() {
    $('#brTabActionsEnv, #brTabActionsWe, #brTabActionsMp, #brTabActionsProduct, #brTabActionsContact, #brTabActionsSiteInfo')
        .addClass('d-none').removeClass('d-flex');
}

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
                            <div class="col-md-12">
                                <small class="text-muted" id="spModal-dms-preview"></small>
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

// Konversi 1 nilai desimal derajat → string DMS (Degrees Minutes Seconds).
// isLat menentukan arah mata angin: N/S untuk latitude, E/W untuk longitude.
function decimalToDms(deg, isLat) {
    const d = parseFloat(deg);
    if (isNaN(d)) return '';
    const abs       = Math.abs(d);
    const degrees   = Math.floor(abs);
    const minutesF  = (abs - degrees) * 60;
    const minutes   = Math.floor(minutesF);
    const seconds   = ((minutesF - minutes) * 60).toFixed(1);
    const dir       = isLat ? (d >= 0 ? 'N' : 'S') : (d >= 0 ? 'E' : 'W');
    return `${degrees}°${minutes}'${seconds}"${dir}`;
}

function coordToDms(lat, lng) {
    if (lat === '' || lat === null || lat === undefined || lng === '' || lng === null || lng === undefined) return '';
    return `${decimalToDms(lat, true)} ${decimalToDms(lng, false)}`;
}

// Versi spCoordCell + baris DMS di bawahnya — khusus tabel Sampling Point.
function spCoordCellWithDms(lat, lng) {
    if (!lat || !lng) return '<span class="text-muted">—</span>';
    return `${spCoordCell(lat, lng)}
        <div class="text-muted" style="font-size:10px;white-space:nowrap;">${coordToDms(lat, lng)}</div>`;
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
                    <td>${spCoordCellWithDms(r.latitude, r.longitude)}</td>
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
        const $bar = $(`#brTabActions${jenis === 'env' ? 'Env' : 'We'}`);
        $bar.find('.btn-sp-add').attr('data-id-site', idSite);
        $bar.removeClass('d-none').addClass('d-flex');
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

    // Paste koordinat gabungan dari Google Maps (cth: "-6.1957, 106.9190")
    // ke field Latitude → otomatis kesplit ke Latitude + Longitude
    $(document).off('paste.sp-coord', '#spModal-latitude, #spModal-longitude')
        .on('paste.sp-coord', '#spModal-latitude, #spModal-longitude', function (e) {
            const text = (e.originalEvent || e).clipboardData.getData('text');
            const match = text.match(/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/);
            if (!match) return; // bukan format "lat, lng" — biarkan paste normal jalan

            e.preventDefault();
            $('#spModal-latitude').val(match[1]);
            $('#spModal-longitude').val(match[2]);
            if (!$('#spModal-has-coord-wrap').is(':hidden')) {
                $('#spModal-has-coord').prop('checked', true).trigger('change');
            }
            $('#spModal-latitude').trigger('input');
        });

    // Preview DMS live saat Latitude/Longitude diketik/diubah
    $(document).off('input.sp-dms', '#spModal-latitude, #spModal-longitude')
        .on('input.sp-dms', '#spModal-latitude, #spModal-longitude', function () {
            const lat = $('#spModal-latitude').val();
            const lng = $('#spModal-longitude').val();
            $('#spModal-dms-preview').text(coordToDms(lat, lng));
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
    $('#spModal-dms-preview').text(isEdit ? coordToDms(data.latitude, data.longitude) : '');
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
        $('#brTabActionsMp .btn-mp-add').attr('data-id-site', idSite);
        $('#brTabActionsMp').removeClass('d-none').addClass('d-flex');
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

// ─── CONTACT (PIC) ────────────────────────────────────────────────────────

function renderContactModal() {
    return `
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width:480px;">
        <div class="modal-content">
            <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                <h6 class="modal-title mb-0" id="contactModalLabel">
                    <i class="fa-solid fa-address-book me-2" style="color:#db2777;"></i>
                    Tambah Contact
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3 px-3">
                <div class="row g-2">
                    <input type="hidden" id="contactModal-id" value="" data-no-disable>
                    <input type="hidden" id="contactModal-id-br" value="" data-no-disable>
                    <div class="col-md-8">
                        <label class="form-label">Nama PIC <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="contactModal-nama_pic"
                            placeholder="Nama kontak" data-no-disable>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jabatan</label>
                        <input type="text" class="form-control form-control-sm" id="contactModal-jabatan"
                            placeholder="cth: HRD, Manager" data-no-disable>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. Telepon <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="contactModal-nomor_telepon_pic"
                            placeholder="cth: 081234567890" data-no-disable>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control form-control-sm" id="contactModal-email_pic"
                            placeholder="Opsional" data-no-disable>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Site</label>
                        <select id="contactModal-id_site" class="form-select form-select-sm" data-no-disable
                            style="width:100%;"></select>
                        <small class="text-muted">Kosongkan untuk kontak umum (berlaku di semua site)</small>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keterangan Lokasi</label>
                        <input type="text" class="form-control form-control-sm" id="contactModal-lokasi_pic"
                            placeholder="cth: Lantai 2, dekat lobby (opsional)" data-no-disable>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select form-select-sm" id="contactModal-is_aktif" data-no-disable>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-no-disable>Batal</button>
                <button type="button" class="btn btn-sm btn-primary" id="contactModal-btn-save" data-no-disable
                    style="background:#db2777;border-color:#db2777;">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>`;
}

function renderContactList(rows) {
    if (!rows.length) {
        return `<div class="text-center text-muted py-4" style="font-size:13px;">
            <i class="fa-solid fa-address-book me-1"></i> Belum ada Contact terdaftar.
        </div>`;
    }

    const badgeAktif = `<span class="badge" style="background:#dcfce7;color:#166534;font-size:10px;font-weight:600;padding:2px 7px;">Aktif</span>`;
    const badgeNon   = `<span class="badge" style="background:#f1f5f9;color:#64748b;font-size:10px;font-weight:600;padding:2px 7px;">Non-Aktif</span>`;
    const badgeUmum  = `<span class="badge" style="background:#fce7f3;color:#9d174d;font-size:10px;font-weight:600;padding:2px 7px;">Umum</span>`;

    const rows_html = rows.map((r, i) => {
        const searchVal = [r.nama_pic, r.jabatan, r.nomor_telepon_pic, r.email_pic, r.nama_lokasi].filter(Boolean).join(' ').toLowerCase();
        const isUmum = r.id_site === null;
        return `
        <tr data-search="${escHtml(searchVal)}">
            <td style="color:#94a3b8;text-align:center;">${i + 1}</td>
            <td class="fw-semibold">${escHtml(r.nama_pic)}</td>
            <td>${r.jabatan ? escHtml(r.jabatan) : '<span class="text-muted">—</span>'}</td>
            <td>${isUmum ? badgeUmum : escHtml(r.nama_lokasi ?? '—')}</td>
            <td>${escHtml(r.nomor_telepon_pic ?? '—')}</td>
            <td>${r.email_pic ? escHtml(r.email_pic) : '<span class="text-muted">—</span>'}</td>
            <td>${r.lokasi_pic ? escHtml(r.lokasi_pic) : '<span class="text-muted">—</span>'}</td>
            <td>${r.is_aktif ? badgeAktif : badgeNon}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-contact-edit"
                    data-id="${r.id_contact}" title="Edit" style="font-size:11px;" data-no-disable>
                    <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-contact-delete"
                    data-id="${r.id_contact}" data-nama="${escHtml(r.nama_pic)}" title="Hapus" style="font-size:11px;" data-no-disable>
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
                    <th style="min-width:160px;">Nama PIC</th>
                    <th style="min-width:130px;">Jabatan</th>
                    <th style="min-width:140px;">Site</th>
                    <th style="min-width:130px;">No. Telepon</th>
                    <th style="min-width:160px;">Email</th>
                    <th style="min-width:160px;">Lokasi</th>
                    <th style="min-width:90px;">Status</th>
                    <th style="min-width:80px;">Aksi</th>
                </tr>
            </thead>
            <tbody>${rows_html}</tbody>
        </table>
    </div>`;
}

function loadContactData(idBr) {
    // Reset search saat reload
    $('#contact-search').val('');
    $('#contact-search-clear').addClass('d-none');

    $('#contact-table-wrap').html(
        `<div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>`
    );
    $.get(`/business-relation-contacts/by-br/${idBr}`)
        .done(function (r) {
            $('#contact-table-wrap').html(renderContactList(r.data ?? []));
        })
        .fail(function () {
            $('#contact-table-wrap').html(
                `<div class="text-center text-danger py-3">Gagal memuat data Contact.</div>`
            );
        });
}

function initContactTabEvents() {
    const $panel = $('#detailContent');

    // Load data saat tab diklik
    $panel.on('shown.bs.tab', '[data-bs-target="#tabBrsContact"]', function () {
        const idBr = $(this).data('id-br');
        loadContactData(idBr);
        $('#brTabActionsContact').removeClass('d-none').addClass('d-flex');
    });

    // Tombol Tambah Contact
    $panel.on('click', '.btn-contact-add', function () {
        const idBr = $(this).data('id-br');
        _openContactModal({ idBr, isEdit: false });
    });

    // Tombol Simpan modal Contact
    $(document).off('click.contact', '#contactModal-btn-save').on('click.contact', '#contactModal-btn-save', function () {
        const id     = $('#contactModal-id').val();
        const idBr   = $('#contactModal-id-br').val();
        const idSite = $('#contactModal-id_site').val();
        const namaPic = $('#contactModal-nama_pic').val().trim();
        const noTelp  = $('#contactModal-nomor_telepon_pic').val().trim();

        if (!namaPic) return Swal.fire('Perhatian', 'Nama PIC wajib diisi.', 'warning');
        if (!noTelp)  return Swal.fire('Perhatian', 'No. Telepon wajib diisi.', 'warning');

        const data = {
            _token:             window.route.csrf,
            id_br:              idBr,
            id_site:            idSite || '',
            nama_pic:           namaPic,
            jabatan:            $('#contactModal-jabatan').val().trim() || null,
            nomor_telepon_pic:  noTelp,
            email_pic:          $('#contactModal-email_pic').val().trim() || null,
            lokasi_pic:         $('#contactModal-lokasi_pic').val().trim() || null,
            is_aktif:           $('#contactModal-is_aktif').val(),
        };

        const isEdit = !!id;
        const url    = isEdit ? `/business-relation-contacts/${id}` : '/business-relation-contacts/store';
        if (isEdit) data._method = 'PUT';

        $('#contactModal-btn-save').prop('disabled', true);
        $.post(url, data)
            .done(function () {
                bootstrap.Modal.getInstance(document.getElementById('contactModal'))?.hide();
                loadContactData(idBr);
                Swal.fire({ icon: 'success', title: 'Tersimpan', timer: 1200, showConfirmButton: false });
            })
            .fail(function (xhr) {
                const errs = xhr.responseJSON?.errors;
                const msg  = errs ? Object.values(errs).flat().join('<br>') : (xhr.responseJSON?.message || 'Terjadi kesalahan.');
                Swal.fire('Gagal', msg, 'error');
            })
            .always(function () {
                $('#contactModal-btn-save').prop('disabled', false);
            });
    });

    // Tombol Edit baris
    $panel.on('click', '.btn-contact-edit', function () {
        const id   = $(this).data('id');
        const idBr = $('#contact-wrap').data('id-br');
        $.get(`/business-relation-contacts/${id}`)
            .done(function (r) {
                _openContactModal({ idBr, isEdit: true, data: r });
            });
    });

    // Search Contact
    $panel.on('input', '#contact-search', function () {
        const q     = $(this).val().toLowerCase().trim();
        const $rows = $('#contact-table-wrap tbody tr');
        $rows.each(function () {
            const val = $(this).data('search') || '';
            $(this).toggle(!q || val.includes(q));
        });
        $('#contact-search-clear').toggleClass('d-none', !q);
    });

    $panel.on('click', '#contact-search-clear', function () {
        $('#contact-search').val('').trigger('input');
    });

    // Tombol Hapus baris
    $panel.on('click', '.btn-contact-delete', function () {
        const id   = $(this).data('id');
        const nama = $(this).data('nama');
        const idBr = $('#contact-wrap').data('id-br');

        Swal.fire({
            title: 'Hapus Contact?',
            html: `<b>${escHtml(nama)}</b> akan dihapus.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({ url: `/business-relation-contacts/${id}`, type: 'DELETE', data: { _token: window.route.csrf } })
                .done(function () {
                    loadContactData(idBr);
                    Swal.fire({ icon: 'success', title: 'Dihapus', timer: 1200, showConfirmButton: false });
                })
                .fail(function () { Swal.fire('Gagal', 'Tidak dapat menghapus data.', 'error'); });
        });
    });
}

function _openContactModal({ idBr, isEdit, data }) {
    $('#contactModalLabel').html(
        `<i class="fa-solid fa-address-book me-2" style="color:#db2777;"></i>`
        + (isEdit ? 'Edit' : 'Tambah') + ' Contact'
    );

    $('#contactModal-id').val(isEdit ? data.id_contact : '');
    $('#contactModal-id-br').val(idBr);
    $('#contactModal-nama_pic').val(isEdit ? (data.nama_pic ?? '') : '');
    $('#contactModal-jabatan').val(isEdit ? (data.jabatan ?? '') : '');
    $('#contactModal-nomor_telepon_pic').val(isEdit ? (data.nomor_telepon_pic ?? '') : '');
    $('#contactModal-email_pic').val(isEdit ? (data.email_pic ?? '') : '');
    $('#contactModal-lokasi_pic').val(isEdit ? (data.lokasi_pic ?? '') : '');
    $('#contactModal-is_aktif').val(isEdit ? String(data.is_aktif) : '1');

    const $site = $('#contactModal-id_site');
    if ($site.hasClass('select2-hidden-accessible')) $site.select2('destroy');
    $site.empty();
    if (isEdit && data.id_site) {
        $site.append(new Option(data.nama_lokasi ?? `Site #${data.id_site}`, data.id_site, true, true));
    }
    $site.select2({
        width: '100%',
        placeholder: 'Umum (semua site)',
        allowClear: true,
        dropdownParent: $('#contactModal'),
        ajax: {
            url: `/business-relations/${idBr}/sites`,
            delay: 200,
            dataType: 'json',
            data: (p) => ({ q: p.term ?? '' }),
            processResults: (d) => ({ results: d }),
        },
    });

    new bootstrap.Modal(document.getElementById('contactModal')).show();
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
        $('#brTabActionsProduct').removeClass('d-none').addClass('d-flex');
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

// ─── SITE (picker + workspace per-site) ────────────────────────────────────

function renderSiteTab(idBr) {
    return `
    <div id="site-picker-wrap" data-id-br="${idBr}">
        <div class="mb-3">
            <div class="pm-search">
                <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="site-picker-search" placeholder="Cari nama site atau kota..." data-no-disable>
                <button type="button" id="site-picker-search-clear" class="pm-search-clear d-none" title="Hapus" data-no-disable>
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        </div>
        <div id="site-picker-list">
            <div class="text-center text-muted py-4">
                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...
            </div>
        </div>
    </div>
    <div id="site-workspace-wrap" class="d-none">
        <div id="site-workspace-content"></div>
    </div>`;
}

function renderSitePickerRows(sites) {
    if (!sites.length) {
        return `<div class="text-center text-muted py-4" style="font-size:13px;">
            <i class="fa-solid fa-location-dot me-1"></i> Belum ada Site terdaftar untuk Business Relation ini.
        </div>`;
    }

    const badgeAktif = `<span class="badge" style="background:#dcfce7;color:#166534;font-size:10px;font-weight:600;padding:2px 7px;">Aktif</span>`;
    const badgeNon   = `<span class="badge" style="background:#f1f5f9;color:#64748b;font-size:10px;font-weight:600;padding:2px 7px;">Non-Aktif</span>`;
    const badgePusat = `<span class="badge ms-1" style="background:#dbeafe;color:#1d4ed8;font-size:10px;font-weight:600;padding:2px 7px;">Kantor Pusat</span>`;

    const rows = sites.map(function (s) {
        const alamatSingkat = [s.kota_kabupaten, s.provinsi].filter(Boolean).join(', ');
        const searchVal = [s.nama_lokasi, s.kota_kabupaten, s.provinsi].filter(Boolean).join(' ').toLowerCase();
        return `
        <div class="pm-site-card btn-site-pick" data-id-site="${s.id}" data-search="${escHtml(searchVal)}">
            <div>
                <div class="fw-semibold" style="font-size:13px;">
                    ${escHtml(s.nama_lokasi)}${s.is_kantor_pusat ? badgePusat : ''}
                    ${s.is_aktif ? badgeAktif : badgeNon}
                </div>
                <div class="text-muted" style="font-size:12px;">
                    <i class="fa-solid fa-location-dot me-1"></i>${alamatSingkat ? escHtml(alamatSingkat) : '—'}
                </div>
            </div>
            <i class="fa-solid fa-chevron-right text-muted"></i>
        </div>`;
    }).join('');

    return `<div id="site-picker-rows">${rows}</div>`;
}

function loadSitePickerList(idBr) {
    $('#site-picker-search').val('');
    $('#site-picker-search-clear').addClass('d-none');
    $('#site-picker-list').html(`<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...</div>`);

    $.get(`/business-relations/${idBr}/sites`)
        .done(function (sites) {
            $('#site-picker-list').html(renderSitePickerRows(sites || []));
        })
        .fail(function () {
            $('#site-picker-list').html('<div class="text-center text-danger py-3">Gagal memuat data Site.</div>');
        });
}

function renderSiteInfoTab(site) {
    return `
    <div class="card card-body" id="site-info-wrap" data-id-site="${site.id_site}">
        <div class="row g-3">
            ${formGroup.text("si_nama_lokasi", "Site", site.nama_lokasi, true, { className: "col-md-7" })}
            ${formGroup.text("si_npwp_cabang", "NPWP Site", site.npwp_cabang, false, { className: "col-md-3" })}
            ${formGroup.checkbox("si_is_kantor_pusat", "Kantor Pusat", site.is_kantor_pusat, { className: "col-md-2", checkLabel: "Kantor Pusat" })}
            ${formGroup.wilayah({
                provinsiValue: site.provinsi,
                kotaValue: site.kota_kabupaten,
                kecamatanValue: site.kecamatan,
                kelurahanValue: site.kelurahan,
                kodePos: site.kode_pos,
            })}
            ${formGroup.select("si_kawasan_bisnis", "Kawasan Bisnis", site.id_bestate, [], {
                mode: "ajax", url: "/business-estates/select2",
                placeholder: "Pilih Kawasan Bisnis", label: site.nama_kawasan_bisnis,
                className: "col-md-4", allowClear: true, showAll: true,
                createUrl: "/business-estates/create",
            })}
            ${formGroup.select("si_gedung", "Gedung", site.id_building, [], {
                mode: "ajax", url: "/commercial-buildings/select2",
                placeholder: "Pilih Gedung", label: site.nama_gedung,
                className: "col-md-4", allowClear: true, showAll: true,
                createUrl: "/commercial-buildings/create",
            })}
            ${formGroup.select("si_is_aktif", "Status", site.s_is_aktif,
                [
                    { value: 1, label: "Aktif" },
                    { value: 0, label: "Tidak Aktif" },
                ],
                { className: "col-md-4" }
            )}
            ${formGroup.text("si_nama_jalan", "Nama Jalan", site.nama_jalan, false, { className: "col-md-12" })}
            ${formGroup.textarea("si_alamat_lengkap", "Alamat Lengkap", site.alamat_lengkap, { className: "col-md-12" })}
            ${formGroup.textarea("si_keterangan_alamat", "Keterangan Alamat", site.keterangan_alamat, { className: "col-md-12" })}
            <div class="col-md-12">
                <label class="form-label">Koordinat Site</label>
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="number" step="any" name="si_latitude"
                            class="form-control form-control-sm"
                            placeholder="Latitude (cth: -6.12345678)"
                            value="${site.latitude ?? ''}">
                    </div>
                    <div class="col-md-6">
                        <input type="number" step="any" name="si_longitude"
                            class="form-control form-control-sm"
                            placeholder="Longitude (cth: 106.12345678)"
                            value="${site.longitude ?? ''}">
                    </div>
                </div>
                ${(site.latitude && site.longitude) ? `
                <div class="mt-1">
                    ${spCoordCell(site.latitude, site.longitude)}
                </div>` : ''}
            </div>
        </div>
    </div>`;
}

function renderSiteWorkspace(site) {
    return `
    <div class="pm-subtab-card">
        <div class="pm-tab-header" style="background:#f8fafc;">
            <div class="pm-site-subtab-bar">
                <button type="button" class="pm-site-back-pill" id="btn-back-to-site-list" data-no-disable>
                    <i class="fa-solid fa-arrow-left"></i> Daftar Site
                </button>
                <span class="pm-site-subtab-divider"></span>
                <ul class="pm-tab-nav" id="siteSubTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabSiteInfo">
                        <i class="fa-solid fa-location-dot me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi Site
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabSamplingEnv"
                        data-id-site="${site.id_site}" data-jenis="env">
                        <i class="fa-solid fa-wind me-1" style="color:#0e7490;font-size:11px;"></i>
                        Sampling ENV
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabSamplingWe"
                        data-id-site="${site.id_site}" data-jenis="we">
                        <i class="fa-solid fa-helmet-safety me-1" style="color:#b45309;font-size:11px;"></i>
                        Sampling WE
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBrsMp"
                        data-id-site="${site.id_site}">
                        <i class="fa-solid fa-users me-1" style="color:#7c3aed;font-size:11px;"></i>
                        Man Power
                    </button>
                </li>
                </ul>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabSiteInfo" role="tabpanel">
                    ${renderSiteInfoTab(site)}
                </div>
                <div class="tab-pane fade" id="tabSamplingEnv" role="tabpanel">
                    ${renderSamplingTab('env', site.id_site)}
                </div>
                <div class="tab-pane fade" id="tabSamplingWe" role="tabpanel">
                    ${renderSamplingTab('we', site.id_site)}
                </div>
                <div class="tab-pane fade" id="tabBrsMp" role="tabpanel">
                    <div class="card card-body" id="mp-wrap" data-id-site="${site.id_site}">
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
    </div>`;
}

function openSiteWorkspace(idSite) {
    $('#site-workspace-content').html(`<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data Site...</div>`);
    $('#site-picker-wrap').addClass('d-none');
    $('#site-workspace-wrap').removeClass('d-none');

    $.get(`/business-relations/sites/${idSite}/detail`)
        .done(function (site) {
            $('#site-workspace-content').html(renderSiteWorkspace(site));

            // Field Informasi Site dikelola toggle Edit sendiri, bukan ikut form BR
            $('#site-info-wrap').find('input, select, textarea')
                .attr('data-no-disable', 'true')
                .prop('disabled', true);

            initDynamicSelect('#site-info-wrap');
            WilayahEngine.init('#site-info-wrap');

            // Reset tombol Edit/Simpan/Batal Site ke kondisi awal (view mode)
            $('#btn-edit-site-info').removeClass('d-none');
            $('#btn-save-site-info, #btn-cancel-site-info').addClass('d-none').removeClass('d-inline-flex');

            new bootstrap.Tab(document.querySelector('#siteSubTabs [data-bs-target="#tabSiteInfo"]')).show();
        })
        .fail(function () {
            $('#site-workspace-content').html('<div class="text-center text-danger py-3">Gagal memuat data Site.</div>');
        });
}

function backToSitePickerList() {
    $('#site-workspace-wrap').addClass('d-none');
    $('#site-picker-wrap').removeClass('d-none');
    $('#site-workspace-content').empty();
    hideAllBrTabActions();
}

function initSiteTabEvents() {
    const $panel = $('#detailContent');

    $panel.on('shown.bs.tab', '[data-bs-target="#tabBrsSite"]', function () {
        const idBr = $(this).data('id-br');
        $('#site-workspace-wrap').addClass('d-none');
        $('#site-picker-wrap').removeClass('d-none');
        loadSitePickerList(idBr);
    });

    $panel.on('click', '.btn-site-pick', function () {
        openSiteWorkspace($(this).data('id-site'));
    });

    $panel.on('click', '#btn-back-to-site-list', function () {
        backToSitePickerList();
    });

    // Search Site picker
    $panel.on('input', '#site-picker-search', function () {
        const q = $(this).val().toLowerCase().trim();
        $('#site-picker-rows .btn-site-pick').each(function () {
            $(this).toggle(!q || ($(this).data('search') || '').includes(q));
        });
        $('#site-picker-search-clear').toggleClass('d-none', !q);
    });
    $panel.on('click', '#site-picker-search-clear', function () {
        $('#site-picker-search').val('').trigger('input');
    });

    // Tampilkan action bar Informasi Site
    $panel.on('shown.bs.tab', '[data-bs-target="#tabSiteInfo"]', function () {
        $('#brTabActionsSiteInfo').removeClass('d-none').addClass('d-flex');
    });

    // Toggle Edit Site
    $panel.on('click', '#btn-edit-site-info', function () {
        $('#site-info-wrap').find('input, select, textarea').prop('disabled', false);
        $(this).addClass('d-none');
        $('#btn-save-site-info, #btn-cancel-site-info').removeClass('d-none').addClass('d-inline-flex');
    });

    $panel.on('click', '#btn-cancel-site-info', function () {
        const idSite = $('#site-info-wrap').data('id-site');
        openSiteWorkspace(idSite);
    });

    $panel.on('click', '#btn-save-site-info', function () {
        const idSite = $('#site-info-wrap').data('id-site');
        const $wrap  = $('#site-info-wrap');

        const payload = {
            _token:             window.route.csrf,
            _method:            'PUT',
            nama_lokasi:        $wrap.find('[name="si_nama_lokasi"]').val().trim(),
            npwp_cabang:        $wrap.find('[name="si_npwp_cabang"]').val().trim(),
            is_kantor_pusat:    $wrap.find('[name="si_is_kantor_pusat"]').is(':checked') ? 1 : 0,
            provinsi:           $wrap.find('[name="provinsi"]').val(),
            kota_kabupaten:     $wrap.find('[name="kota_kabupaten"]').val(),
            kecamatan:          $wrap.find('[name="kecamatan"]').val(),
            kelurahan:          $wrap.find('[name="kelurahan"]').val(),
            kode_pos:           $wrap.find('[name="kode_pos"]').val(),
            kawasan_bisnis:     $wrap.find('[name="si_kawasan_bisnis"]').val() || null,
            gedung:             $wrap.find('[name="si_gedung"]').val() || null,
            is_aktif:           $wrap.find('[name="si_is_aktif"]').val(),
            nama_jalan:         $wrap.find('[name="si_nama_jalan"]').val(),
            alamat_lengkap:     $wrap.find('[name="si_alamat_lengkap"]').val(),
            keterangan_alamat:  $wrap.find('[name="si_keterangan_alamat"]').val(),
            latitude:           $wrap.find('[name="si_latitude"]').val() || null,
            longitude:          $wrap.find('[name="si_longitude"]').val() || null,
        };

        if (!payload.nama_lokasi) return Swal.fire('Perhatian', 'Nama Site wajib diisi.', 'warning');

        $('#btn-save-site-info').prop('disabled', true);
        $.post(`/business-relation-sites/${idSite}`, payload)
            .done(function () {
                Swal.fire({ icon: 'success', title: 'Tersimpan', timer: 1200, showConfirmButton: false });
                openSiteWorkspace(idSite);
            })
            .fail(function (xhr) {
                const errs = xhr.responseJSON?.errors;
                const msg  = errs ? Object.values(errs).flat().join('<br>') : (xhr.responseJSON?.message || 'Terjadi kesalahan.');
                Swal.fire('Gagal', msg, 'error');
            })
            .always(function () {
                $('#btn-save-site-info').prop('disabled', false);
            });
    });

    // Hapus Site
    $panel.on('click', '#btn-delete-site-info', function () {
        const idSite = $('#site-info-wrap').data('id-site');
        const idBr   = $('#site-picker-wrap').data('id-br');

        Swal.fire({
            title: 'Hapus Site?',
            html: 'Site ini beserta data Sampling Point dan Man Power di dalamnya tidak akan tampil lagi.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({ url: `/business-relations/sites/${idSite}`, type: 'DELETE', data: { _token: window.route.csrf } })
                .done(function () {
                    Swal.fire({ icon: 'success', title: 'Dihapus', timer: 1200, showConfirmButton: false });
                    backToSitePickerList();
                    loadSitePickerList(idBr);
                })
                .fail(function () { Swal.fire('Gagal', 'Tidak dapat menghapus data.', 'error'); });
        });
    });
}

// ─── MAIN FORM ────────────────────────────────────────────────────────────

function renderForm(res) {
    return `
<form id="detailForm">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="id_br" value="${res.id_br}">

    ${formGroup.actionBar({
        number: escHtml(res.nama_br ?? '—'),
        editText: 'Edit Business Relation',
        noWrap: true,
        subtitle: `
            <div class="d-flex align-items-center gap-2 detail-date" style="margin:0;">
                <span class="badge rounded-pill" style="background:#e0f2fe;color:#0284c7;font-size:11px;font-weight:600;">
                    <i class="fa-solid fa-location-dot me-1" style="font-size:10px;"></i>${res.jumlah_site ?? 0} Site
                </span>
                <span>Dibuat ${escHtml(res.br_created_at ?? '—')} &nbsp;·&nbsp; Diupdate ${escHtml(res.br_updated_at ?? '—')}</span>
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
                        data-bs-toggle="tab" data-bs-target="#tabBrsContact"
                        data-id-br="${res.id_br}">
                        <i class="fa-solid fa-address-book me-1" style="color:#db2777;font-size:11px;"></i>
                        Contact
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
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBrsSite"
                        data-id-br="${res.id_br}">
                        <i class="fa-solid fa-map-location-dot me-1" style="color:#0369a1;font-size:11px;"></i>
                        Site
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="brTabActionsInfo" class="d-flex align-items-center gap-2">
                    <!-- Edit di action bar atas -->
                </div>
                <div id="brTabActionsContact" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill btn-contact-add"
                        data-id-br="${res.id_br}" data-no-disable
                        style="border-color:#db2777;color:#db2777;">
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                        <i class="fa-solid fa-address-book" style="font-size:11px;"></i> Tambah
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
                <div id="brTabActionsSiteInfo" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill" id="btn-edit-site-info" data-no-disable
                        style="border-color:#1a3a6e;color:#1a3a6e;">
                        <i class="fa-solid fa-pen" style="font-size:11px;"></i> Edit Site
                    </button>
                    <button type="button" class="pm-btn-pill d-none" id="btn-save-site-info" data-no-disable
                        style="border-color:#166534;color:#166534;">
                        <i class="fa-solid fa-check" style="font-size:11px;"></i> Simpan
                    </button>
                    <button type="button" class="pm-btn-pill d-none" id="btn-cancel-site-info" data-no-disable
                        style="border-color:#64748b;color:#64748b;">
                        <i class="fa-solid fa-xmark" style="font-size:11px;"></i> Batal
                    </button>
                    <button type="button" class="pm-btn-pill" id="btn-delete-site-info" data-no-disable
                        style="border-color:#dc2626;color:#dc2626;">
                        <i class="fa-solid fa-trash" style="font-size:11px;"></i> Hapus Site
                    </button>
                </div>
                <div id="brTabActionsEnv" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill pm-btn-pill--teal btn-sp-add"
                        data-jenis="env" data-no-disable>
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                        <i class="fa-solid fa-wind" style="font-size:11px;"></i> Tambah
                    </button>
                </div>
                <div id="brTabActionsWe" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill pm-btn-pill--amber btn-sp-add"
                        data-jenis="we" data-no-disable>
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                        <i class="fa-solid fa-helmet-safety" style="font-size:11px;"></i> Tambah
                    </button>
                </div>
                <div id="brTabActionsMp" class="d-none align-items-center gap-2">
                    <button type="button" class="pm-btn-pill btn-mp-add" data-no-disable
                        style="border-color:#7c3aed;color:#7c3aed;">
                        <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                        <i class="fa-solid fa-user-plus" style="font-size:11px;"></i> Tambah
                    </button>
                </div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <!-- TAB: INFORMASI (BR) -->
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
                                ${formGroup.select("id_entitas", "Entitas", res.id_entitas, [], {
                                    mode: "ajax", url: "/entitas/select2",
                                    placeholder: "Pilih Entitas", label: res.nama_entitas,
                                    className: "col-md-4", createUrl: "/entitas/create",
                                })}
                                ${formGroup.select("id_kepemilikan", "Kepemilikan", res.id_kepemilikan, [], {
                                    mode: "ajax", url: "/kepemilikan/select2",
                                    placeholder: "Pilih Kepemilikan", label: res.nama_kepemilikan,
                                    className: "col-md-4", createUrl: "/kepemilikan/create",
                                })}
                                ${formGroup.text("npwp", "NPWP", res.npwp, false, { className: "col-md-4" })}
                                ${formGroup.select("id_kategori_bisnis", "Kategori Bisnis", res.id_kategori_bisnis, [], {
                                    mode: "ajax", url: "/kategori-bisnis/select2",
                                    placeholder: "Pilih Kategori Bisnis", label: res.nama_kategori_bisnis,
                                    className: "col-md-4", createUrl: "/kategori-bisnis/create",
                                })}
                                ${formGroup.select("id_sub_kategori_bisnis", "Sub Kategori Bisnis", res.id_sub_kategori_bisnis, [], {
                                    mode: "ajax", url: "/sub-kategori-bisnis/select2",
                                    placeholder: "Pilih Sub Kategori Bisnis", label: res.nama_sub_kategori_bisnis,
                                    className: "col-md-4", createUrl: "/sub-kategori-bisnis/create",
                                })}
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
                    </div>
                </div>

                <!-- TAB: CONTACT (BR) -->
                <div class="tab-pane fade" id="tabBrsContact" role="tabpanel">
                    <div class="card card-body" id="contact-wrap" data-id-br="${res.id_br}">
                        <div class="mb-3">
                            <div class="pm-search">
                                <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="contact-search" placeholder="Cari nama, no. telepon, atau site..." data-no-disable>
                                <button type="button" id="contact-search-clear" class="pm-search-clear d-none" title="Hapus" data-no-disable>
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div id="contact-table-wrap">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: PRODUCT (BR) -->
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

                <!-- TAB: SITE (picker → workspace per-site) -->
                <div class="tab-pane fade" id="tabBrsSite" role="tabpanel">
                    ${renderSiteTab(res.id_br)}
                </div>

            </div>
        </div>
    </div>

    ${renderSpModal()}
    ${renderMpModal()}
    ${renderContactModal()}
    ${renderProductModal()}

</form>
`;
}
