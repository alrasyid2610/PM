let page;

// ── Tab switch ────────────────────────────────────────────────────────────────
$(document).on('shown.bs.tab', '#pointDetailTabs button[data-bs-toggle="tab"]', function (e) {
    const target = $(e.target).data('bs-target');
    $('#pointTabActionsInfo, #pointTabActionsItems').addClass('d-none');
    if (target === '#tabPointInfo')  $('#pointTabActionsInfo').removeClass('d-none');
    if (target === '#tabPointItems') $('#pointTabActionsItems').removeClass('d-none');
});

// ── Salin Testing Point ──────────────────────────────────────────────────────
// Full-control clone: field Informasi & baris Testing Items di-pre-fill dari
// sumber, semua bebas diedit/hapus/tambah sebelum "Buat Salinan". Tabel
// item-nya dibangun manual (bukan pakai class DynamicTable) — pola identik
// dengan testing-points/create.blade.php, supaya tidak berbagi state/listener
// dengan DynamicTable instance milik tab "Testing Items" di halaman detail
// (singleton #dynamicTableActionMenu-nya bisa salah sasaran kalau dipakai
// bersamaan dari 2 instance/dua tabel berbeda).
let currentCloneSourceId = null;

function cloneTpRowHtml(item) {
    item = item || {};
    const checked = item.status == 1 ? 'checked' : '';
    return `
        <tr>
            <td class="text-center" style="cursor:grab;">
                <button type="button" class="drag-handle-btn" tabindex="-1" title="Geser untuk urutkan">
                    <i class="fa-solid fa-grip-vertical"></i>
                </button>
            </td>
            <td class="row-number"></td>
            <td><input type="text" name="judul_indonesia[]" class="form-control form-control-sm" value="${escHtml(item.judul_indonesia || '')}"></td>
            <td><input type="text" name="judul_inggris[]" class="form-control form-control-sm" value="${escHtml(item.judul_inggris || '')}"></td>
            <td><select name="parameter[]" class="form-control form-control-sm clone-parameter-select"></select></td>
            <td><select name="unit[]" class="form-control form-control-sm clone-unit-select"></select></td>
            <td><input type="text" name="nilai[]" class="form-control form-control-sm" value="${escHtml(item.nilai || '')}"></td>
            <td><input type="text" name="item_keterangan[]" class="form-control form-control-sm" value="${escHtml(item.keterangan || '')}"></td>
            <td class="text-center">
                <input type="hidden" name="status[]" value="${item.status == 1 ? '1' : '0'}" class="clone-status-hidden">
                <input type="checkbox" class="clone-status-checkbox" value="1" ${checked}>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm btn-clone-row-remove">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>`;
}

function initCloneRowSelect2($row, item) {
    item = item || {};
    $row.find('.clone-parameter-select').select2({
        width: '100%',
        placeholder: 'Pilih Parameter...',
        allowClear: true,
        dropdownParent: $('#modalCloneTestingPoint'),
        ajax: {
            url: window.route.select2Parameter,
            dataType: 'json',
            delay: 250,
            data: (params) => ({ q: params.term }),
            processResults: (data) => ({ results: data }),
            cache: true,
        },
        language: { noResults: () => `<span>Tidak ditemukan. <a href="${window.route.createParameter}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>` },
        escapeMarkup: (m) => m,
    });
    if (item.parameter) {
        const text = [item.kode_parameter, item.judul_indonesia_parameter].filter(Boolean).join(' - ');
        $row.find('.clone-parameter-select').append(new Option(text, item.parameter, true, true)).trigger('change');
    }

    $row.find('.clone-unit-select').select2({
        width: '100%',
        placeholder: 'Pilih Unit...',
        allowClear: true,
        dropdownParent: $('#modalCloneTestingPoint'),
        ajax: {
            url: window.route.select2Unit,
            dataType: 'json',
            delay: 250,
            data: (params) => ({ q: params.term }),
            processResults: (data) => ({ results: data }),
            cache: true,
        },
        language: { noResults: () => `<span>Tidak ditemukan. <a href="${window.route.createUnit}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>` },
        escapeMarkup: (m) => m,
    });
    if (item.unit) {
        const text = [item.kode_unit, item.judul_indonesia_unit].filter(Boolean).join(' - ');
        $row.find('.clone-unit-select').append(new Option(text, item.unit, true, true)).trigger('change');
    }
}

function updateCloneRowNumbers() {
    $('#cloneItemsTable tbody tr').each(function (i) { $(this).find('.row-number').text(i + 1); });
}

function renderCloneTpModal(source, items) {
    const rowsHtml = items.length ? items.map(cloneTpRowHtml).join('') : cloneTpRowHtml();

    $('#modalCloneTpBody').html(`
        <div class="row g-3">
            <div class="col-md-6 col-12">
                <label class="form-label required">Testing Standard</label>
                <select id="cloneIdStandard" class="form-select" required></select>
            </div>
            <div class="col-md-6 col-12">
                <label class="form-label required">Testing Matriks Sample</label>
                <select id="cloneIdMatriks" class="form-select" required></select>
            </div>
            <div class="col-md-6 col-12">
                <label class="form-label required">Nama</label>
                <input type="text" id="cloneNama" class="form-control" required value="${escHtml(source.nama || '')}">
            </div>
            <div class="col-md-4 col-12">
                <label class="form-label">Nomor Halaman</label>
                <input type="text" id="cloneNomorHalaman" class="form-control" value="${escHtml(source.nomor_halaman || '')}">
            </div>
            <div class="col-md-2 col-12">
                <label class="form-label required">Status</label>
                <select id="cloneIsAktif" class="form-select" required>
                    <option value="1" ${source.is_aktif == 1 ? 'selected' : ''}>Aktif</option>
                    <option value="0" ${source.is_aktif == 0 ? 'selected' : ''}>Tidak Aktif</option>
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Deskripsi</label>
                <textarea id="cloneDeskripsi" class="form-control" rows="2">${escHtml(source.deskripsi || '')}</textarea>
            </div>
            <div class="col-md-12">
                <label class="form-label">Keterangan</label>
                <textarea id="cloneKeterangan" class="form-control" rows="2">${escHtml(source.keterangan || '')}</textarea>
            </div>
        </div>

        <hr class="my-3">

        <h6 class="mb-2"><i class="fa-solid fa-table-list me-1 text-success"></i> Testing Items</h6>
        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
            <div class="pm-search">
                <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="cloneItemsSearch" placeholder="Cari judul, nilai, keterangan...">
                <button type="button" id="cloneItemsSearchClear" class="pm-search-clear d-none" title="Hapus">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="btnCloneAddRow">
                <i class="fa-solid fa-plus me-1"></i> Tambah Baris
            </button>
        </div>
        <div class="dynamic-table-wrapper">
            <div class="table-responsive">
                <table id="cloneItemsTable" class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:32px"></th>
                            <th style="white-space:nowrap;width:40px">No</th>
                            <th style="min-width:220px">Judul Indonesia</th>
                            <th style="min-width:220px">Judul Inggris</th>
                            <th style="min-width:150px">Parameter</th>
                            <th style="min-width:130px">Unit</th>
                            <th style="min-width:110px">Nilai</th>
                            <th style="min-width:130px">Keterangan</th>
                            <th style="white-space:nowrap;width:60px">Status</th>
                            <th style="white-space:nowrap;width:50px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>
        </div>
    `);

    $('#cloneIdStandard').select2({
        width: '100%', placeholder: 'Pilih Testing Standard...', allowClear: true,
        dropdownParent: $('#modalCloneTestingPoint'),
        ajax: { url: window.route.select2Standard, dataType: 'json', delay: 250, data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true },
        language: { noResults: () => `<span>Tidak ditemukan. <a href="${window.route.createStandard}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>` },
        escapeMarkup: (m) => m,
    });
    if (source.id_testing_standard) {
        $('#cloneIdStandard').append(new Option(source.standard_judul || source.standard_nomor || ('#' + source.id_testing_standard), source.id_testing_standard, true, true)).trigger('change');
    }

    $('#cloneIdMatriks').select2({
        width: '100%', placeholder: 'Pilih Matriks Sample...', allowClear: true,
        dropdownParent: $('#modalCloneTestingPoint'),
        ajax: { url: window.route.select2Matriks, dataType: 'json', delay: 250, data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true },
        language: { noResults: () => `<span>Tidak ditemukan. <a href="${window.route.createMatriks}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>` },
        escapeMarkup: (m) => m,
    });
    if (source.id_testing_matriks_sample) {
        $('#cloneIdMatriks').append(new Option(source.matrik_sample_judul_indonesia || ('#' + source.id_testing_matriks_sample), source.id_testing_matriks_sample, true, true)).trigger('change');
    }

    $('#cloneIsAktif').select2({ width: '100%', dropdownParent: $('#modalCloneTestingPoint') });

    $('#cloneItemsTable tbody tr').each(function (i) {
        initCloneRowSelect2($(this), items[i] || {});
    });
    updateCloneRowNumbers();

    if (typeof dragula !== 'undefined') {
        if (window._cloneTpDrake) { window._cloneTpDrake.destroy(); }
        window._cloneTpDrake = dragula([document.querySelector('#cloneItemsTable tbody')], {
            moves: function (el, source, handle) { return $(handle).closest('.drag-handle-btn').length > 0; },
        }).on('drop', function () {
            updateCloneRowNumbers();
            if (window.Notify) Notify.toast('Urutan baris diperbarui');
        });
    }

    $('#btnConfirmCloneTp').prop('disabled', false);
}

$(document).on('click', '.btn-clone-testing-point', function () {
    currentCloneSourceId = $(this).data('id');
    $('#btnConfirmCloneTp').prop('disabled', true);
    $('#modalCloneTpBody').html('<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...</div>');
    new bootstrap.Modal($('#modalCloneTestingPoint')[0]).show();

    $.when(
        $.get(window.route.update + currentCloneSourceId),
        $.get(window.route.itemsByPoint + currentCloneSourceId)
    ).done(function (sourceRes, itemsRes) {
        renderCloneTpModal(sourceRes[0], (itemsRes[0] && itemsRes[0].data) || []);
    }).fail(function () {
        $('#modalCloneTpBody').html('<div class="alert alert-danger mb-0">Gagal memuat data Testing Point.</div>');
    });
});

$(document).on('click', '#btnCloneAddRow', function () {
    const $row = $(cloneTpRowHtml());
    $('#cloneItemsTable tbody').append($row);
    initCloneRowSelect2($row, {});
    updateCloneRowNumbers();
});

$(document).on('click', '.btn-clone-row-remove', function () {
    if ($('#cloneItemsTable tbody tr').length <= 1) {
        Notify.warning('Minimal harus ada 1 baris.!');
        return;
    }
    $(this).closest('tr').remove();
    updateCloneRowNumbers();
});

$(document).on('change', '.clone-status-checkbox', function () {
    $(this).closest('td').find('.clone-status-hidden').val(this.checked ? '1' : '0');
});

// Search client-side di modal clone — sama seperti tab Testing Items di
// halaman edit/create, karena Testing Point sumber bisa punya puluhan item.
function cloneRowSearchText(row) {
    const $row = $(row);
    const parts = [$row.find('.row-number').text()];
    $row.find('input[type="text"]').each(function () { parts.push($(this).val()); });
    $row.find('select').each(function () { parts.push($(this).find('option:selected').text()); });
    return parts.filter(Boolean).join(' ').toLowerCase();
}

$(document).on('input', '#cloneItemsSearch', function () {
    const q = $(this).val().toLowerCase().trim();
    $('#cloneItemsTable tbody tr').each(function () {
        $(this).toggle(!q || cloneRowSearchText(this).includes(q));
    });
    $('#cloneItemsSearchClear').toggleClass('d-none', !q);
});

$(document).on('click', '#cloneItemsSearchClear', function () {
    $('#cloneItemsSearch').val('').trigger('input');
});

$(document).on('click', '#btnConfirmCloneTp', function () {
    if (!currentCloneSourceId) return;

    const nama = $('#cloneNama').val().trim();
    const idStandard = $('#cloneIdStandard').val();
    const idMatriks = $('#cloneIdMatriks').val();
    if (!nama || !idStandard || !idMatriks) {
        Notify.error('Testing Standard, Matriks Sample, dan Nama wajib diisi.');
        return;
    }

    const payload = {
        _token: window.route.csrf,
        id_testing_standard: idStandard,
        id_testing_matriks_sample: idMatriks,
        nama: nama,
        nomor_halaman: $('#cloneNomorHalaman').val(),
        is_aktif: $('#cloneIsAktif').val(),
        deskripsi: $('#cloneDeskripsi').val(),
        keterangan: $('#cloneKeterangan').val(),
        judul_indonesia: [],
        judul_inggris: [],
        parameter: [],
        unit: [],
        nilai: [],
        item_keterangan: [],
        status: [],
    };

    $('#cloneItemsTable tbody tr').each(function () {
        const $row = $(this);
        payload.judul_indonesia.push($row.find('[name="judul_indonesia[]"]').val());
        payload.judul_inggris.push($row.find('[name="judul_inggris[]"]').val());
        payload.parameter.push($row.find('[name="parameter[]"]').val());
        payload.unit.push($row.find('[name="unit[]"]').val());
        payload.nilai.push($row.find('[name="nilai[]"]').val());
        payload.item_keterangan.push($row.find('[name="item_keterangan[]"]').val());
        payload.status.push($row.find('.clone-status-hidden').val());
    });

    const $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

    $.ajax({
        url: window.route.update + currentCloneSourceId + '/duplicate',
        method: 'POST',
        data: payload,
        success: function (res) {
            Notify.success(res.message || 'Testing Point berhasil disalin');
            setTimeout(function () {
                window.location.href = window.location.pathname + '?open=' + res.id;
            }, 800);
        },
        error: function (xhr) {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-copy me-1"></i> Buat Salinan');
            if (xhr.status === 422) {
                const errors = xhr.responseJSON?.errors;
                const msg = errors ? Object.values(errors).map((e) => e[0]).join('<br>') : xhr.responseJSON?.message;
                Notify.error(msg || 'Validasi gagal');
            } else {
                Notify.error(xhr.responseJSON?.message || 'Terjadi kesalahan server');
            }
        },
    });
});

$('#modalCloneTestingPoint').on('hidden.bs.modal', function () {
    currentCloneSourceId = null;
    if (window._cloneTpDrake) { window._cloneTpDrake.destroy(); window._cloneTpDrake = null; }
    $('#modalCloneTpBody').html('<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...</div>');
});

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_testing_point",
        renderForm: renderForm,
        initSelect: function () {},
        initDynamicTable: true,
        useAttachment: true,
        afterLoad: function (res) {
            if (can('testing-points', 'can_create')) {
                $('#pointTabActionsInfo').html(`
                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-clone-testing-point" data-id="${res.id_testing_point}">
                        <i class="fa-solid fa-copy me-1"></i> Salin Testing Point ini
                    </button>
                `);
            } else {
                $('#pointTabActionsInfo').html('');
            }
        },
        historyConfig: {
            masterLabel: "Testing Point",
            linesLabel: "Testing Items",
            linesDisplayFields: ["nomor", "judul_indonesia", "judul_inggris", "nilai"],
        },
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus Testing Point?', function () {
            $.ajax({
                url: window.route.update + id,
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' },
                success: function (res) {
                    Notify.success(res.message || 'Data berhasil dihapus');
                    setTimeout(function () { window.location.href = window.location.pathname; }, 1000);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                },
            });
        });
    });
});
