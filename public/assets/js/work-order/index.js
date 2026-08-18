const MONTH_SHORT_WO = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
function fmtDate(str) {
    if (!str) return '—';
    var d = new Date(str);
    if (isNaN(d)) return str;
    return d.getDate() + '-' + MONTH_SHORT_WO[d.getMonth()] + '-' + d.getFullYear();
}

let page;
let currentBoqData = null;
let currentBoqWoId = null;
let currentWoData = null;
let sourceFwoId = null;
let sourceWoId = null;
let copyFwoPersonelIdx = 0;
let currentOutputWoId = null;
let outputDataMap = {};
let outputFilePond = null;

window.addEventListener("storage", function (e) {
    if (e.key === "fwo_created" && e.newValue) {
        try {
            var data = JSON.parse(e.newValue);
            var target = data.id_wo || currentBoqWoId;
            var fwoModal = bootstrap.Modal.getInstance(
                document.getElementById("modalCreateFwo"),
            );
            if (fwoModal) {
                fwoModal.hide();
                document.getElementById("iframeCreateFwo").src = "";
            }
            if (target && String(target) === String(currentBoqWoId)) {
                loadBoqProgress(target);
            }
        } catch (_) {}
    }

    if (e.key === "boq_created" && e.newValue) {
        try {
            var data = JSON.parse(e.newValue);
            var target = data.id_wo || currentBoqWoId;
            var modal = bootstrap.Modal.getInstance(
                document.getElementById("modalCreateBoq"),
            );
            if (modal) {
                modal.hide();
                document.getElementById("iframeCreateBoq").src = "";
            }
            if (target && String(target) === String(currentBoqWoId)) {
                loadBoqProgress(target);
            }
        } catch (_) {}
    }

    if (e.key === "boq_updated" && e.newValue) {
        try {
            var updData = JSON.parse(e.newValue);
            var updTarget = updData.id_wo || currentBoqWoId;
            if (updTarget && String(updTarget) === String(currentBoqWoId)) {
                loadBoqProgress(updTarget);
            }
        } catch (_) {}
    }
});

// ── Tab switch: show/hide action buttons ──────────────────────────────────────
$(document).on(
    "shown.bs.tab",
    '#woDetailTabs button[data-bs-toggle="tab"]',
    function (e) {
        const target = $(e.target).data("bs-target");
        $("#woTabActionsInfo, #woTabActionsBoq, #woTabActionsFwo, #woTabActionsOutput, #woTabActionsBudget, #woTabActionsSample, #woTabActionsBoqOther, #woTabActionsBoqSampling, #woTabActionsOutputOther").addClass("d-none").removeClass("d-flex");
        if (target === "#tabInfo")   $("#woTabActionsInfo").removeClass("d-none");
        if (target === "#tabBoq")    $("#woTabActionsBoq").removeClass("d-none");
        if (target === "#tabFwo")    $("#woTabActionsFwo").removeClass("d-none");
        if (target === "#tabOutput") $("#woTabActionsOutput").removeClass("d-none");
        if (target === "#tabWoBudget") {
            $("#woTabActionsBudget").removeClass("d-none").addClass("d-flex");
            const idWo = $(e.target).data("wo-id");
            loadWoBudgetData(idWo);
        }
        if (target === "#tabSample") {
            $("#woTabActionsSample").removeClass("d-none").addClass("d-flex");
            const idWo = $(e.target).data("wo-id");
            loadWoSampleData(idWo);
        }
        if (target === "#tabBoqOther") {
            $("#woTabActionsBoqOther").removeClass("d-none").addClass("d-flex");
            const idWo = $(e.target).data("wo-id");
            loadWoBoqTambahanData("other", idWo);
        }
        if (target === "#tabBoqSampling") {
            $("#woTabActionsBoqSampling").removeClass("d-none").addClass("d-flex");
            const idWo = $(e.target).data("wo-id");
            loadWoBoqTambahanData("sampling", idWo);
        }
        if (target === "#tabOutputOther") {
            $("#woTabActionsOutputOther").removeClass("d-none").addClass("d-flex");
            const idWo = $(e.target).data("wo-id");
            loadOutputOtherData(idWo);
        }
    },
);

$(document).on("click", "#btnRefreshWoSample", function () {
    const idWo = $(this).data("wo-id");
    loadWoSampleData(idWo);
});

// ── BOQ TAMBAHAN (BOQ Other / BOQ Sampling) ─────────────────────────────────────

const WO_BOQ_TAMBAHAN_ROUTE = { other: 'wo-boq-other', sampling: 'wo-boq-sampling' };
const WO_BOQ_TAMBAHAN_LABEL = { other: 'BOQ Other', sampling: 'BOQ Sampling' };
const WO_BOQ_TAMBAHAN_ICON  = { other: 'fa-file-invoice', sampling: 'fa-vial-virus' };

function loadWoBoqTambahanData(jenis, idWo) {
    const $wrap = $(jenis === 'other' ? '#woBoqOtherContent' : '#woBoqSamplingContent');
    $wrap.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');

    $.get(`/${WO_BOQ_TAMBAHAN_ROUTE[jenis]}/${idWo}/list`)
        .done(function (res) {
            const rows     = res.data || [];
            const isLocked = res.wo_status === 'completed';
            $wrap.html(renderWoBoqTambahanList(jenis, rows, res.total || 0, isLocked));
        })
        .fail(function () {
            $wrap.html('<div class="text-center text-danger py-4">Gagal memuat data.</div>');
        });
}

function renderWoBoqTambahanList(jenis, rows, total, isLocked) {
    if (!rows.length) {
        return `<div class="text-center text-muted py-4">
            <i class="fa-solid ${WO_BOQ_TAMBAHAN_ICON[jenis]} fa-2x d-block mb-2 opacity-25"></i>
            Belum ada item ${WO_BOQ_TAMBAHAN_LABEL[jenis]}
        </div>`;
    }

    const rowsHtml = rows.map(function (r, i) {
        const subtotal = (r.qty || 0) * (r.harga || 0);
        return `<tr data-id="${r.id_boq_tambahan}">
            <td style="font-size:12px;color:#94a3b8;width:36px;">${i + 1}</td>
            <td style="font-size:12px;">${escHtml(r.nama_item)}</td>
            <td style="font-size:12px;text-align:right;white-space:nowrap;">${r.qty ?? 0} ${escHtml(r.satuan || '')}</td>
            <td style="font-size:12px;text-align:right;white-space:nowrap;">Rp ${Number(r.harga || 0).toLocaleString('id-ID')}</td>
            <td style="font-size:12px;text-align:right;white-space:nowrap;font-weight:600;">Rp ${Number(subtotal).toLocaleString('id-ID')}</td>
            <td style="font-size:12px;">${escHtml(r.keterangan || '—')}</td>
            <td class="text-center" style="width:72px;white-space:nowrap;">
                ${!isLocked ? `
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-wo-boq-tambahan-edit"
                    data-id="${r.id_boq_tambahan}" data-jenis="${jenis}" title="Edit" style="font-size:11px;">
                    <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-wo-boq-tambahan-delete"
                    data-id="${r.id_boq_tambahan}" data-jenis="${jenis}" data-nama="${escHtml(r.nama_item)}"
                    title="Hapus" style="font-size:11px;">
                    <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
                </button>` : ''}
            </td>
        </tr>`;
    }).join('');

    return `<div class="table-responsive">
        <table class="pm-table">
            <thead>
                <tr>
                    <th style="width:36px;">No</th>
                    <th>Nama Item</th>
                    <th style="width:110px;text-align:right;">Qty</th>
                    <th style="width:130px;text-align:right;">Harga</th>
                    <th style="width:140px;text-align:right;">Subtotal</th>
                    <th>Keterangan</th>
                    <th style="width:72px;"></th>
                </tr>
            </thead>
            <tbody>${rowsHtml}</tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="font-size:12px;font-weight:600;text-align:right;">Total</td>
                    <td style="font-size:12px;font-weight:700;text-align:right;color:#1d4ed8;">Rp ${Number(total).toLocaleString('id-ID')}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>`;
}

function initWoBoqTambahanSatuanSelect2(idVal, labelVal) {
    const $sel = $('#boqTambahanModal-satuan');
    if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
    $sel.empty();
    if (idVal && labelVal) {
        $sel.append(new Option(labelVal, idVal, true, true));
    }
    $sel.select2({
        width: '100%',
        placeholder: '— Pilih —',
        allowClear: true,
        minimumInputLength: 0,
        dropdownParent: $('#boqTambahanModal'),
        ajax: {
            url: '/satuan/select2',
            delay: 200,
            dataType: 'json',
            data: (p) => ({ q: p.term ?? '' }),
            processResults: (d) => ({ results: d }),
        },
        language: {
            noResults: () => `<span>Tidak ditemukan. <a href="/satuan/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`,
        },
        escapeMarkup: (m) => m,
    });
}

$(document).on('click', '.btn-boq-tambahan-add', function () {
    const jenis  = $(this).data('jenis');
    const idWo   = $(this).data('wo-id');
    const $modal = $('#boqTambahanModal');

    $modal.find('#boqTambahanModalLabel').html(`<i class="fa-solid ${WO_BOQ_TAMBAHAN_ICON[jenis]} me-2" style="color:#b45309;"></i>Tambah Item ${WO_BOQ_TAMBAHAN_LABEL[jenis]}`);
    $modal.find('#boqTambahanModal-id').val('');
    $modal.find('#boqTambahanModal-jenis').val(jenis);
    $modal.find('#boqTambahanModal-id-wo').val(idWo);
    $modal.find('#boqTambahanModal-nama').val('');
    $modal.find('#boqTambahanModal-qty').val('');
    $modal.find('#boqTambahanModal-harga').val('');
    $modal.find('#boqTambahanModal-keterangan').val('');
    initWoBoqTambahanSatuanSelect2();

    new bootstrap.Modal($modal[0]).show();
    initNumericMask($modal[0]);
});

$(document).on('click', '.btn-wo-boq-tambahan-edit', function (e) {
    e.stopPropagation();
    const id     = $(this).data('id');
    const jenis  = $(this).data('jenis');
    const $modal = $('#boqTambahanModal');

    $modal.find('#boqTambahanModalLabel').html(`<i class="fa-solid ${WO_BOQ_TAMBAHAN_ICON[jenis]} me-2" style="color:#b45309;"></i>Edit Item ${WO_BOQ_TAMBAHAN_LABEL[jenis]}`);
    $modal.find('#boqTambahanModal-id').val(id);
    $modal.find('#boqTambahanModal-jenis').val(jenis);

    $.get(`/${WO_BOQ_TAMBAHAN_ROUTE[jenis]}/${id}`)
        .done(function (r) {
            $modal.find('#boqTambahanModal-id-wo').val(r.id_wo);
            $modal.find('#boqTambahanModal-nama').val(r.nama_item || '');
            const qtyEl = $modal.find('#boqTambahanModal-qty')[0];
            if (qtyEl && qtyEl._cleave) qtyEl._cleave.setRawValue(r.qty || ''); else $modal.find('#boqTambahanModal-qty').val(r.qty || '');
            const hargaEl = $modal.find('#boqTambahanModal-harga')[0];
            if (hargaEl && hargaEl._cleave) hargaEl._cleave.setRawValue(r.harga || ''); else $modal.find('#boqTambahanModal-harga').val(r.harga || '');
            $modal.find('#boqTambahanModal-keterangan').val(r.keterangan || '');
            initWoBoqTambahanSatuanSelect2(r.id_satuan, r.satuan);
        })
        .always(function () {
            new bootstrap.Modal($modal[0]).show();
            initNumericMask($modal[0]);
        });
});

$(document).on('click', '#boqTambahanModal-btn-save', function () {
    const $modal = $('#boqTambahanModal');
    const id     = $modal.find('#boqTambahanModal-id').val();
    const jenis  = $modal.find('#boqTambahanModal-jenis').val();
    const idWo   = $modal.find('#boqTambahanModal-id-wo').val();
    const $btn   = $(this);

    const payload = {
        _token:      window.route.csrf,
        id_wo:       idWo,
        nama_item:   $modal.find('#boqTambahanModal-nama').val().trim(),
        qty:         rawNumVal($modal.find('#boqTambahanModal-qty')[0]),
        id_satuan:   $modal.find('#boqTambahanModal-satuan').val() || null,
        harga:       rawNumVal($modal.find('#boqTambahanModal-harga')[0]),
        keterangan:  $modal.find('#boqTambahanModal-keterangan').val().trim() || null,
    };

    if (!payload.nama_item) { Notify.warning('Nama item wajib diisi.'); return; }
    if (!payload.qty)       { Notify.warning('Qty wajib diisi.'); return; }

    const isEdit = !!id;
    const url    = isEdit ? `/${WO_BOQ_TAMBAHAN_ROUTE[jenis]}/${id}` : `/${WO_BOQ_TAMBAHAN_ROUTE[jenis]}`;
    if (isEdit) payload._method = 'PUT';

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');
    $.post(url, payload)
        .done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance($modal[0])?.hide();
                Notify.success('Item berhasil disimpan.');
                loadWoBoqTambahanData(jenis, idWo);
            } else {
                Notify.error(res.message || 'Gagal menyimpan item.');
            }
        })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan item.');
        })
        .always(function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan'); });
});

$(document).on('click', '.btn-wo-boq-tambahan-delete', function () {
    const id    = $(this).data('id');
    const jenis = $(this).data('jenis');
    const nama  = $(this).data('nama');
    const idWo  = $(this).closest('.card-body').attr('id') === 'woBoqOtherContent'
        ? $('#woDetailTabs button[data-bs-target="#tabBoqOther"]').data('wo-id')
        : $('#woDetailTabs button[data-bs-target="#tabBoqSampling"]').data('wo-id');

    Swal.fire({
        title: 'Hapus Item?',
        html: `Item <strong>${escHtml(nama)}</strong> akan dihapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        reverseButtons: true,
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: `/${WO_BOQ_TAMBAHAN_ROUTE[jenis]}/${id}`,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.route.csrf },
            success: function () {
                Notify.success('Item berhasil dihapus.');
                loadWoBoqTambahanData(jenis, idWo);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal menghapus item.');
            },
        });
    });
});

// ── OUTPUT OTHER (level WO, berdiri sendiri) ────────────────────────────────────

let outputOtherFilePond = null;
let woSampleFilePond = null;

const OUTPUT_OTHER_STATUS_LABEL = { belum_siap: 'Belum Siap', siap: 'Siap', terkirim: 'Terkirim' };
const OUTPUT_OTHER_STATUS_COLOR = {
    belum_siap: { bg: '#fee2e2', color: '#dc2626' },
    siap:       { bg: '#dbeafe', color: '#1d4ed8' },
    terkirim:   { bg: '#dcfce7', color: '#15803d' },
};

function loadOutputOtherData(idWo) {
    const $wrap = $('#woOutputOtherContent');
    $wrap.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');

    $.get(`/wo-output-other/${idWo}/list`)
        .done(function (res) {
            const rows     = res.data || [];
            const isLocked = res.wo_status === 'completed';
            $wrap.html(renderOutputOtherList(rows, res.total || 0, isLocked));
        })
        .fail(function () {
            $wrap.html('<div class="text-center text-danger py-4">Gagal memuat data.</div>');
        });
}

function renderOutputOtherList(rows, total, isLocked) {
    if (!rows.length) {
        return `<div class="text-center text-muted py-4">
            <i class="fa-solid fa-file-circle-plus fa-2x d-block mb-2 opacity-25"></i>
            Belum ada item Output Other
        </div>`;
    }

    const rowsHtml = rows.map(function (r, i) {
        const subtotal = (r.qty || 0) * (r.harga || 0);
        const st = OUTPUT_OTHER_STATUS_COLOR[r.status] || OUTPUT_OTHER_STATUS_COLOR.belum_siap;
        const periode = r.tanggal_mulai || r.tanggal_selesai
            ? `${r.tanggal_mulai ? r.tanggal_mulai.substring(0,10) : '—'} s/d ${r.tanggal_selesai ? r.tanggal_selesai.substring(0,10) : '—'}`
            : '—';
        const files = r.attachments || [];
        const filesHtml = files.length
            ? files.map(f => `<a href="/storage/${f}" target="_blank" class="d-block" style="font-size:11px;"><i class="fa-solid fa-paperclip me-1"></i>${escHtml(f.split('/').pop())}</a>`).join('')
            : '<span class="text-muted" style="font-size:11px;">—</span>';
        const drive = r.link_drive
            ? `<a href="${escHtml(r.link_drive)}" target="_blank" style="font-size:11px;color:#1a56db;"><i class="fa-brands fa-google-drive me-1"></i>Drive</a>`
            : '';

        return `<tr data-id="${r.id_output_tambahan}">
            <td style="font-size:12px;color:#94a3b8;width:36px;">${i + 1}</td>
            <td style="font-size:12px;">${escHtml(r.nama_item)}</td>
            <td style="font-size:12px;text-align:right;white-space:nowrap;">${r.qty ?? 0} ${escHtml(r.satuan || '')}</td>
            <td style="font-size:12px;text-align:right;white-space:nowrap;">Rp ${Number(r.harga || 0).toLocaleString('id-ID')}</td>
            <td style="font-size:12px;text-align:right;white-space:nowrap;font-weight:600;">Rp ${Number(subtotal).toLocaleString('id-ID')}</td>
            <td style="text-align:center;white-space:nowrap;">
                <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:${st.bg};color:${st.color};">${OUTPUT_OTHER_STATUS_LABEL[r.status] || r.status}</span>
            </td>
            <td style="font-size:11px;white-space:nowrap;color:#64748b;">${periode}</td>
            <td style="font-size:11px;">${filesHtml}${drive}</td>
            <td class="text-center" style="width:72px;white-space:nowrap;">
                ${!isLocked ? `
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-output-other-edit"
                    data-id="${r.id_output_tambahan}" title="Edit" style="font-size:11px;">
                    <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-output-other-delete"
                    data-id="${r.id_output_tambahan}" data-nama="${escHtml(r.nama_item)}"
                    title="Hapus" style="font-size:11px;">
                    <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
                </button>` : ''}
            </td>
        </tr>`;
    }).join('');

    return `<div class="table-responsive">
        <table class="pm-table">
            <thead>
                <tr>
                    <th style="width:36px;">No</th>
                    <th>Nama Item</th>
                    <th style="width:100px;text-align:right;">Qty</th>
                    <th style="width:120px;text-align:right;">Harga</th>
                    <th style="width:130px;text-align:right;">Subtotal</th>
                    <th style="width:90px;text-align:center;">Status</th>
                    <th style="width:170px;">Periode</th>
                    <th style="width:140px;">Lampiran</th>
                    <th style="width:72px;"></th>
                </tr>
            </thead>
            <tbody>${rowsHtml}</tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="font-size:12px;font-weight:600;text-align:right;">Total</td>
                    <td style="font-size:12px;font-weight:700;text-align:right;color:#1d4ed8;">Rp ${Number(total).toLocaleString('id-ID')}</td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>
    </div>`;
}

function initOutputOtherSatuanSelect2(idVal, labelVal) {
    const $sel = $('#outputOtherModal-satuan');
    if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
    $sel.empty();
    if (idVal && labelVal) {
        $sel.append(new Option(labelVal, idVal, true, true));
    }
    $sel.select2({
        width: '100%',
        placeholder: '— Pilih —',
        allowClear: true,
        minimumInputLength: 0,
        dropdownParent: $('#outputOtherModal'),
        ajax: {
            url: '/satuan/select2',
            delay: 200,
            dataType: 'json',
            data: (p) => ({ q: p.term ?? '' }),
            processResults: (d) => ({ results: d }),
        },
        language: {
            noResults: () => `<span>Tidak ditemukan. <a href="/satuan/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`,
        },
        escapeMarkup: (m) => m,
    });
}

$(document).on('click', '#btnAddOutputOther', function () {
    const idWo   = $(this).data('wo-id');
    const $modal = $('#outputOtherModal');

    $modal.find('#outputOtherModalLabel').html('<i class="fa-solid fa-file-circle-plus me-2" style="color:#0f766e;"></i>Tambah Output Other');
    $modal.find('#outputOtherModal-id').val('');
    $modal.find('#outputOtherModal-id-wo').val(idWo);
    $modal.find('#outputOtherModal-nama').val('');
    $modal.find('#outputOtherModal-qty').val('');
    $modal.find('#outputOtherModal-harga').val('');
    $modal.find('#outputOtherModal-status').val('belum_siap');
    $modal.find('#outputOtherModal-tgl-mulai').val('');
    $modal.find('#outputOtherModal-tgl-selesai').val('');
    $modal.find('#outputOtherModal-link-drive').val('');
    $modal.find('#outputOtherModal-keterangan').val('');
    $modal.find('#outputOtherModal-existing-files').empty();
    initOutputOtherSatuanSelect2();

    if (outputOtherFilePond) { outputOtherFilePond.destroy(); outputOtherFilePond = null; }
    outputOtherFilePond = createFileUploader('#outputOtherModal-attachments');

    new bootstrap.Modal($modal[0]).show();
    initNumericMask($modal[0]);
    initFpDate($modal[0]);
});

$(document).on('click', '.btn-output-other-edit', function (e) {
    e.stopPropagation();
    const id     = $(this).data('id');
    const $modal = $('#outputOtherModal');

    $modal.find('#outputOtherModalLabel').html('<i class="fa-solid fa-pen me-2" style="color:#0f766e;"></i>Edit Output Other');
    $modal.find('#outputOtherModal-id').val(id);

    $.get(`/wo-output-other/${id}`)
        .done(function (r) {
            $modal.find('#outputOtherModal-id-wo').val(r.id_wo);
            $modal.find('#outputOtherModal-nama').val(r.nama_item || '');
            const qtyEl = $modal.find('#outputOtherModal-qty')[0];
            if (qtyEl && qtyEl._cleave) qtyEl._cleave.setRawValue(r.qty || ''); else $modal.find('#outputOtherModal-qty').val(r.qty || '');
            const hargaEl = $modal.find('#outputOtherModal-harga')[0];
            if (hargaEl && hargaEl._cleave) hargaEl._cleave.setRawValue(r.harga || ''); else $modal.find('#outputOtherModal-harga').val(r.harga || '');
            $modal.find('#outputOtherModal-status').val(r.status || 'belum_siap');
            $modal.find('#outputOtherModal-tgl-mulai').val(r.tanggal_mulai ? r.tanggal_mulai.substring(0,10) : '');
            $modal.find('#outputOtherModal-tgl-selesai').val(r.tanggal_selesai ? r.tanggal_selesai.substring(0,10) : '');
            $modal.find('#outputOtherModal-link-drive').val(r.link_drive || '');
            $modal.find('#outputOtherModal-keterangan').val(r.keterangan || '');
            initOutputOtherSatuanSelect2(r.id_satuan, r.satuan);

            const files = r.attachments || [];
            const filesHtml = files.length
                ? files.map(f => `<div class="d-flex align-items-center gap-2 mb-1" style="font-size:12px;">
                        <i class="fa-solid fa-paperclip"></i>
                        <a href="/storage/${f}" target="_blank">${escHtml(f.split('/').pop())}</a>
                        <a href="#" class="btn-remove-existing-output-file" style="font-size:11px;color:#dc2626;cursor:pointer;">Hapus</a>
                        <input type="hidden" class="existing-output-file-path" value="${escHtml(f)}">
                    </div>`).join('')
                : '';
            $modal.find('#outputOtherModal-existing-files').html(filesHtml);
        })
        .always(function () {
            if (outputOtherFilePond) { outputOtherFilePond.destroy(); outputOtherFilePond = null; }
            outputOtherFilePond = createFileUploader('#outputOtherModal-attachments');
            new bootstrap.Modal($modal[0]).show();
            initNumericMask($modal[0]);
            initFpDate($modal[0]);
        });
});

$(document).on('click', '.btn-remove-existing-output-file', function (e) {
    e.preventDefault();
    $(this).closest('div').remove();
});

$('#outputOtherModal').on('hidden.bs.modal', function () {
    if (outputOtherFilePond) { outputOtherFilePond.destroy(); outputOtherFilePond = null; }
});

$(document).on('click', '#outputOtherModal-btn-save', function () {
    const $modal = $('#outputOtherModal');
    const id     = $modal.find('#outputOtherModal-id').val();
    const idWo   = $modal.find('#outputOtherModal-id-wo').val();
    const $btn   = $(this);

    const namaItem = $modal.find('#outputOtherModal-nama').val().trim();
    const qty      = rawNumVal($modal.find('#outputOtherModal-qty')[0]);
    if (!namaItem) { Notify.warning('Nama item wajib diisi.'); return; }
    if (!qty)      { Notify.warning('Qty wajib diisi.'); return; }

    const fd = new FormData();
    fd.append('_token', window.route.csrf);
    fd.append('id_wo', idWo);
    fd.append('nama_item', namaItem);
    fd.append('qty', qty);
    fd.append('id_satuan', $modal.find('#outputOtherModal-satuan').val() || '');
    fd.append('harga', rawNumVal($modal.find('#outputOtherModal-harga')[0]) || 0);
    fd.append('status', $modal.find('#outputOtherModal-status').val());
    fd.append('tanggal_mulai', $modal.find('#outputOtherModal-tgl-mulai').val() || '');
    fd.append('tanggal_selesai', $modal.find('#outputOtherModal-tgl-selesai').val() || '');
    fd.append('link_drive', $modal.find('#outputOtherModal-link-drive').val().trim());
    fd.append('keterangan', $modal.find('#outputOtherModal-keterangan').val().trim());

    $modal.find('.existing-output-file-path').each(function () {
        fd.append('existing_attachments[]', $(this).val());
    });
    if (outputOtherFilePond) {
        outputOtherFilePond.getFiles().forEach(function (f) {
            fd.append('attachments[]', f.file);
        });
    }

    const isEdit = !!id;
    const url    = isEdit ? `/wo-output-other/${id}` : `/wo-output-other`;

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');
    $.ajax({
        url: url,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
    })
        .done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance($modal[0])?.hide();
                Notify.success('Item berhasil disimpan.');
                loadOutputOtherData(idWo);
            } else {
                Notify.error(res.message || 'Gagal menyimpan item.');
            }
        })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan item.');
        })
        .always(function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan'); });
});

$(document).on('click', '.btn-output-other-delete', function () {
    const id   = $(this).data('id');
    const nama = $(this).data('nama');
    const idWo = $('#woDetailTabs button[data-bs-target="#tabOutputOther"]').data('wo-id');

    Swal.fire({
        title: 'Hapus Item?',
        html: `Item <strong>${escHtml(nama)}</strong> akan dihapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        reverseButtons: true,
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: `/wo-output-other/${id}`,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.route.csrf },
            success: function () {
                Notify.success('Item berhasil dihapus.');
                loadOutputOtherData(idWo);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal menghapus item.');
            },
        });
    });
});

// ── SAMPLE (langsung di level WO, tanpa FWO) ───────────────────────────────────

const JENIS_LABEL_WO_SAMPLE = { env: 'ENV', we: 'WE', mp: 'MP', product: 'Product' };
const SAMPLE_STATUS_LABEL_WO = { belum_diambil: 'Belum Diambil', diambil: 'Diambil', dikirim: 'Dikirim' };
const SAMPLE_STATUS_COLOR_WO = { belum_diambil: '#64748b', diambil: '#0369a1', dikirim: '#15803d' };
const KONDISI_LABEL_WO = { baik: 'Baik', rusak: 'Rusak', tidak_lengkap: 'Tidak Lengkap' };
const KONDISI_COLOR_WO = { baik: '#15803d', rusak: '#dc2626', tidak_lengkap: '#b45309' };

function loadWoSampleData(idWo) {
    const $wrap = $('#woSampleContent');
    $wrap.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');

    $.get(`/wo-samples/by-wo/${idWo}`)
        .done(function (res) {
            const boqs     = res.data || [];
            const isLocked = res.wo_status === 'completed';
            const idSite   = res.id_site || null;

            if (!boqs.length) {
                $wrap.html('<div class="text-center text-muted py-4">Tidak ada BOQ pada WO ini.</div>');
                return;
            }

            window._woSamplingPoints = [];
            const renderAll = function () {
                $wrap.html(renderWoSampleList(boqs, isLocked));
                initWoSampleStatusSelect2($wrap[0]);
                initWoSampleTitikSelect2($wrap[0]);
                if (!isLocked) initWoSampleModalTitikSelect2();
            };

            if (idSite) {
                $.get(`/wo-samples/sampling-points/${idSite}`)
                    .done(function (data) { window._woSamplingPoints = data || []; })
                    .always(renderAll);
            } else {
                renderAll();
            }
        })
        .fail(function () {
            $wrap.html('<div class="text-center text-danger py-4">Gagal memuat data sample.</div>');
        });
}

function _woSpOptions(usedLocations, currentVal) {
    return [{ id: '', text: '' }].concat(
        (window._woSamplingPoints || []).map(function (sp) {
            const isUsed = usedLocations && usedLocations.includes(sp.text) && sp.text !== currentVal;
            return { id: sp.text, text: sp.text, _used: isUsed };
        })
    );
}

function initWoSampleStatusSelect2(container) {
    $(container).find('.wo-sample-inline-status').each(function () {
        const $sel = $(this);
        if ($sel.data('select2')) $sel.select2('destroy');
        $sel.select2({
            width: 'resolve',
            minimumResultsForSearch: Infinity,
            dropdownParent: $sel.closest('td'),
        });
    });
}

function initWoSampleTitikSelect2(container) {
    $(container).find('table').each(function () {
        const $table = $(this);
        const usedLocations = [];
        $table.find('.wo-sample-inline-titik').each(function () {
            const val = $(this).data('current') || '';
            if (val) usedLocations.push(val);
        });

        $table.find('.wo-sample-inline-titik').each(function () {
            const $sel    = $(this);
            const current = $sel.data('current') || '';
            if ($sel.data('select2')) $sel.select2('destroy');
            $sel.select2({
                width: 'resolve',
                placeholder: 'Pilih titik…',
                allowClear: true,
                dropdownParent: $sel.closest('td'),
                data: _woSpOptions(usedLocations, current),
                templateResult: function (opt) {
                    if (!opt.id) return opt.text;
                    if (opt._used) {
                        return $('<span class="sp-opt-used" style="display:flex;align-items:center;gap:6px;color:#16a34a;pointer-events:none;cursor:not-allowed;">'
                            + '<i class="fa-solid fa-circle-check" style="font-size:11px;flex-shrink:0;"></i>'
                            + '<span>' + $('<span>').text(opt.text).html() + '</span>'
                            + '</span>');
                    }
                    return $('<span>').text(opt.text);
                },
            })
            .on('select2:selecting', function (e) {
                if (e.params && e.params.args && e.params.args.data && e.params.args.data._used) {
                    e.preventDefault();
                }
            })
            .val(current).trigger('change.select2');
        });
    });
}

function initWoSampleModalTitikSelect2() {
    const $sel = $('#woSampleModal-titik');
    if ($sel.data('select2')) $sel.select2('destroy');
    $sel.select2({
        width: '100%',
        placeholder: 'Pilih titik lokasi…',
        allowClear: true,
        dropdownParent: $('#woSampleDetailModal'),
        data: _woSpOptions(),
    });
}

function renderWoSampleList(boqs, isLocked) {
    return boqs.map(function (boq) {
        const total    = (boq.samples || []).length;
        const diambil  = (boq.samples || []).filter(s => s.status === 'diambil' || s.status === 'dikirim').length;
        const dikirim  = (boq.samples || []).filter(s => s.status === 'dikirim').length;
        const pct      = total > 0 ? Math.round((diambil / total) * 100) : 0;
        const barColor = pct === 100 ? '#15803d' : '#0369a1';
        const sisa     = boq.sisa ?? 0;

        const slotRows = total === 0
            ? `<tr><td colspan="9" class="text-center text-muted py-3" style="font-size:12px;font-style:italic;">Belum ada sample — gunakan tombol "Tambah Sample" di atas</td></tr>`
            : (boq.samples || []).map(function (s) {
                const statusVal = s.status || 'belum_diambil';
                const jenisTag  = s.jenis_sample
                    ? `<span style="font-size:10px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:4px;padding:1px 6px;">${JENIS_LABEL_WO_SAMPLE[s.jenis_sample] || s.jenis_sample}</span>`
                    : `<span style="font-size:10px;color:#94a3b8;font-style:italic;">–</span>`;
                const noSample = s.no_sample
                    ? `<span style="font-size:11px;font-weight:600;">${escHtml(s.no_sample)}</span>`
                    : `<span class="text-muted" style="font-size:11px;font-style:italic;">–</span>`;

                const attCountWo = (s.attachments || []).length;
                const attBadgeWo = attCountWo > 0
                    ? `<span class="btn-wo-sample-edit" data-id="${s.id_lab_sample}"
                            title="${attCountWo} lampiran" style="font-size:11px;color:#0369a1;cursor:pointer;white-space:nowrap;">
                            <i class="fa-solid fa-paperclip"></i> ${attCountWo}
                       </span>`
                    : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

                const titikVal  = s.titik_lokasi || '';
                const titikCell = !isLocked
                    ? `<select class="form-select form-select-sm wo-sample-inline-titik"
                            data-id="${s.id_lab_sample}"
                            data-current="${escHtml(titikVal)}"
                            style="font-size:11px;width:260px;"></select>`
                    : (titikVal ? escHtml(titikVal) : '<span style="color:#94a3b8;font-style:italic;">–</span>');

                const tglCellWo = s.tanggal_pengambilan
                    ? `<span style="font-size:11px;white-space:nowrap;">${fmtDate(s.tanggal_pengambilan)}</span>`
                    : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

                const kondisiCellWo = s.kondisi_sample
                    ? `<span style="font-size:11px;font-weight:600;color:${KONDISI_COLOR_WO[s.kondisi_sample]||'#64748b'};">${KONDISI_LABEL_WO[s.kondisi_sample]||s.kondisi_sample}</span>`
                    : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

                const keteranganCellWo = s.keterangan
                    ? `<span style="font-size:11px;" title="${escHtml(s.keterangan)}">${escHtml(s.keterangan)}</span>`
                    : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

                return `
                <tr data-id-sample="${s.id_lab_sample}" data-boq-name="${escHtml(boq.nama_boq)}">
                    <td style="font-size:12px;color:#64748b;width:36px;">${s.no_urut}</td>
                    <td style="font-size:12px;white-space:nowrap;">${jenisTag} ${noSample}</td>
                    <td style="font-size:12px;">${titikCell}</td>
                    <td style="width:100px;">${tglCellWo}</td>
                    <td style="width:100px;">${kondisiCellWo}</td>
                    <td style="font-size:12px;width:150px;">
                        ${!isLocked
                            ? `<select class="form-select form-select-sm wo-sample-inline-status"
                                    data-id="${s.id_lab_sample}"
                                    style="font-size:11px;width:140px;">
                                    <option value="belum_diambil"${statusVal==='belum_diambil'?' selected':''}>Belum Diambil</option>
                                    <option value="diambil"${statusVal==='diambil'?' selected':''}>Diambil</option>
                                    <option value="dikirim"${statusVal==='dikirim'?' selected':''}>Dikirim ke Lab</option>
                               </select>`
                            : `<span style="font-size:11px;font-weight:600;color:${SAMPLE_STATUS_COLOR_WO[statusVal]||'#64748b'};">${SAMPLE_STATUS_LABEL_WO[statusVal]||statusVal}</span>`
                        }
                    </td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${keteranganCellWo}</td>
                    <td style="width:80px;text-align:center;">${attBadgeWo}</td>
                    <td class="text-center" style="width:72px;white-space:nowrap;">
                        ${!isLocked ? `
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-wo-sample-edit"
                            data-id="${s.id_lab_sample}" title="Edit" style="font-size:11px;">
                            <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-wo-sample-delete"
                            data-id="${s.id_lab_sample}" data-no="${escHtml(s.no_sample || 'Sample #' + s.no_urut)}"
                            title="Hapus" style="font-size:11px;">
                            <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
                        </button>` : ''}
                    </td>
                </tr>`;
            }).join('');

        const addBtn = !isLocked ? `
            <div class="d-flex gap-2">
                ${total > 0 ? `
                <button type="button" class="btn btn-sm py-0 px-2 btn-wo-sample-bulk-fill"
                    data-id-boq="${boq.id_boq}"
                    style="font-size:11px;border:1px solid #7c3aed;color:#7c3aed;background:#f5f3ff;">
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Bulk Insert
                </button>` : ''}
                <div class="dropdown">
                    <button type="button" class="btn btn-sm dropdown-toggle py-0 px-2"
                        data-bs-toggle="dropdown"
                        style="font-size:11px;border:1px solid #0369a1;color:#0369a1;background:#eff6ff;">
                        <i class="fa-solid fa-plus me-1"></i>Tambah Sample
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="font-size:12px;min-width:200px;">
                        <li>
                            <button type="button" class="dropdown-item btn-wo-sample-generate"
                                data-id-boq="${boq.id_boq}" data-sisa="${sisa}">
                                <i class="fa-solid fa-wand-magic-sparkles me-2 text-primary"></i>
                                Buat Otomatis
                                ${sisa > 0 ? `<span class="badge bg-primary ms-1">${sisa}</span>` : ''}
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item btn-wo-sample-add-one"
                                data-id-boq="${boq.id_boq}">
                                <i class="fa-solid fa-plus me-2 text-success"></i>
                                Tambah 1 Sample
                            </button>
                        </li>
                    </ul>
                </div>
            </div>` : '';

        const collapseId = `woSampleCollapse_${boq.id_boq}`;
        return `
        <div class="mb-3 border rounded" style="background:#fff;">
            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                style="background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:calc(0.375rem - 1px) calc(0.375rem - 1px) 0 0;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span style="font-size:13px;font-weight:600;color:#1e293b;">${escHtml(boq.nama_boq)}</span>
                    <span class="text-muted wo-sample-progress-text" style="font-size:11px;">Target: ${boq.qty || 0} &nbsp;·&nbsp; ${diambil}/${total} diambil &nbsp;·&nbsp; ${dikirim} dikirim</span>
                    ${sisa > 0 && !isLocked ? `<span style="font-size:11px;color:#b45309;background:#fef3c7;border:1px solid #fde68a;border-radius:4px;padding:1px 6px;">${sisa} sisa qty BOQ</span>` : ''}
                </div>
                <div class="d-flex align-items-center gap-2">
                    ${addBtn}
                    <button type="button" class="btn btn-sm btn-plan-icon btn-wo-sample-collapse"
                        data-target="#${collapseId}" title="Sembunyikan / Tampilkan">
                        <i class="fa-solid fa-chevron-up"></i>
                    </button>
                </div>
            </div>
            <div id="${collapseId}">
                <div style="padding:4px 12px 0;">
                    <div class="progress" style="height:3px;border-radius:0;">
                        <div class="progress-bar" style="width:${pct}%;background:${barColor};"></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="pm-table">
                        <thead>
                            <tr>
                                <th style="width:36px;">#</th>
                                <th style="width:1%;white-space:nowrap;">Jenis / No. Sample</th>
                                <th style="width:220px;">Titik Lokasi</th>
                                <th style="width:100px;">Tgl Pengambilan</th>
                                <th style="width:100px;">Kondisi</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th style="width:80px;">Lampiran</th>
                                <th style="width:48px;"></th>
                            </tr>
                        </thead>
                        <tbody>${slotRows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
    }).join('');
}

$(document).on('click', '.btn-wo-sample-collapse', function () {
    const target = $(this).data('target');
    const $icon  = $(this).find('i');
    $(target).slideToggle(150);
    $icon.toggleClass('fa-chevron-up fa-chevron-down');
});

$(document).on('click', '.btn-wo-sample-generate', function () {
    const idBoq = $(this).data('id-boq');
    const sisa  = parseInt($(this).data('sisa') ?? 0);
    const idWo  = currentWoData.id_wo;

    if (sisa <= 0) {
        Swal.fire({ icon: 'warning', title: 'Sisa Qty Habis', text: 'Qty BOQ sudah terpenuhi.', confirmButtonText: 'OK' });
        return;
    }

    $.post(`/wo-samples/by-wo/${idWo}/boq/${idBoq}/generate`, { _token: window.route.csrf })
        .done(function (res) {
            if (res.success) {
                Notify.success('Sample berhasil dibuat otomatis.');
                loadWoSampleData(idWo);
            } else {
                Notify.error(res.message || 'Gagal membuat sample.');
            }
        })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal membuat sample.');
        });
});

$(document).on('click', '.btn-wo-sample-add-one', function () {
    const idBoq = $(this).data('id-boq');
    const idWo  = currentWoData.id_wo;

    $.post(`/wo-samples/by-wo/${idWo}/boq/${idBoq}/add-one`, { _token: window.route.csrf })
        .done(function (res) {
            if (res.success) {
                loadWoSampleData(idWo);
            } else {
                Swal.fire({ icon: 'warning', title: 'Tidak Dapat Ditambahkan', text: res.message || 'Gagal menambah sample.', confirmButtonText: 'OK' });
            }
        })
        .fail(function (xhr) {
            Swal.fire({ icon: 'warning', title: 'Tidak Dapat Ditambahkan', text: xhr.responseJSON?.message || 'Gagal menambah sample.', confirmButtonText: 'OK' });
        });
});

$(document).on('change', '.wo-sample-inline-titik', function () {
    const $sel = $(this);
    const id   = $sel.data('id');
    const val  = $sel.val() || null;

    // Update data-current lalu reinit semua titik dalam tabel yang sama
    $sel.data('current', val || '');
    const $table = $sel.closest('table');
    initWoSampleTitikSelect2($table.closest('div')[0]);

    $.post(`/wo-samples/${id}/field`, { _token: window.route.csrf, field: 'titik_lokasi', value: val })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan titik lokasi.');
        });
});

$(document).on('change', '.wo-sample-inline-status', function () {
    const id   = $(this).data('id');
    const val  = $(this).val();
    const idWo = currentWoData.id_wo;
    $.post(`/wo-samples/${id}/field`, { _token: window.route.csrf, field: 'status', value: val })
        .done(function () { loadWoSampleData(idWo); })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan status.');
        });
});

$(document).on('click', '.btn-wo-sample-bulk-fill', function (e) {
    e.stopPropagation();
    const idBoq  = $(this).data('id-boq');
    const $modal = $('#woSampleBulkFillModal');
    $modal.find('#woBulkFillModal-id-boq').val(idBoq);
    $modal.find('#woBulkFill-jenis, #woBulkFill-status, #woBulkFill-kondisi').val('');
    $modal.find('#woBulkFill-tanggal').val('');
    new bootstrap.Modal($modal[0]).show();
    initFpDate('#woSampleBulkFillModal');
});

$(document).on('click', '#woSampleBulkFillModal-btn-save', function () {
    const idBoq = $('#woBulkFillModal-id-boq').val();
    const idWo  = currentWoData.id_wo;
    const $btn  = $(this);

    const payload = {
        _token:              window.route.csrf,
        jenis_sample:        $('#woBulkFill-jenis').val() || null,
        tanggal_pengambilan: $('#woBulkFill-tanggal').val() || null,
        status:              $('#woBulkFill-status').val() || null,
        kondisi_sample:      $('#woBulkFill-kondisi').val() || null,
    };

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');
    $.post(`/wo-samples/by-wo/${idWo}/boq/${idBoq}/bulk-fill`, payload)
        .done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('woSampleBulkFillModal'))?.hide();
                Notify.success('Semua sample berhasil diperbarui.');
                loadWoSampleData(idWo);
            } else {
                Notify.error(res.message || 'Gagal memperbarui sample.');
            }
        })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal memperbarui sample.');
        })
        .always(function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-wand-magic-sparkles me-1"></i> Apply ke Semua Sample'); });
});

$(document).on('click', '.btn-wo-sample-edit', function (e) {
    e.stopPropagation();
    const idSample = $(this).data('id');
    const $tr      = $(this).closest('tr');
    const boqName  = $tr.data('boq-name') || '';

    const $modal = $('#woSampleDetailModal');
    $modal.find('#woSampleDetailModalLabel').html(`<i class="fa-solid fa-flask me-2" style="color:#0369a1;"></i>Detail Sample – ${escHtml(boqName)}`);
    $modal.find('#woSampleModal-id').val(idSample);
    $modal.find('#woSampleModal-jenis').val('');
    $modal.find('#woSampleModal-no').val('');
    $modal.find('#woSampleModal-tanggal').val('');
    const $titikSelWo = $modal.find('#woSampleModal-titik');
    $titikSelWo.data('_pendingVal', '');
    if ($titikSelWo.data('select2')) $titikSelWo.val(null).trigger('change');
    $modal.find('#woSampleModal-kondisi').val('');
    $modal.find('#woSampleModal-status').val('belum_diambil');
    $modal.find('#woSampleModal-keterangan').val('');
    $modal.find('#woSampleModal-existing-files').empty();
    if (woSampleFilePond) { woSampleFilePond.destroy(); woSampleFilePond = null; }

    $.get(`/wo-samples/${idSample}`)
        .done(function (res) {
            const s = res.data || {};
            $modal.find('#woSampleModal-jenis').val(s.jenis_sample || '');
            $modal.find('#woSampleModal-no').val(s.no_sample || '');
            $modal.find('#woSampleModal-tanggal').val(s.tanggal_pengambilan || '');
            $modal.find('#woSampleModal-titik').data('_pendingVal', s.titik_lokasi || '');
            $modal.find('#woSampleModal-kondisi').val(s.kondisi_sample || '');
            $modal.find('#woSampleModal-status').val(s.status || 'belum_diambil');
            $modal.find('#woSampleModal-keterangan').val(s.keterangan || '');

            const files = s.attachments || [];
            const filesHtml = files.length
                ? files.map(f => `<div class="d-flex align-items-center gap-2 mb-1" style="font-size:12px;">
                        <i class="fa-solid fa-paperclip"></i>
                        <a href="/storage/${f}" target="_blank">${escHtml(f.split('/').pop())}</a>
                        <a href="#" class="btn-remove-existing-wo-sample-file" style="font-size:11px;color:#dc2626;cursor:pointer;">Hapus</a>
                        <input type="hidden" class="existing-wo-sample-file-path" value="${escHtml(f)}">
                    </div>`).join('')
                : '';
            $modal.find('#woSampleModal-existing-files').html(filesHtml);
        })
        .always(function () {
            if (woSampleFilePond) { woSampleFilePond.destroy(); woSampleFilePond = null; }
            woSampleFilePond = createFileUploader('#woSampleModal-attachments');

            // Init Select2 untuk field enum
            const s2OptsWo = { width: '100%', dropdownParent: $modal, allowClear: true };
            ['#woSampleModal-jenis', '#woSampleModal-kondisi', '#woSampleModal-status'].forEach(function (sel) {
                const $el = $modal.find(sel);
                if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
                $el.select2({ ...s2OptsWo, placeholder: $el.find('option:first').text() || '-- Pilih --' });
                $el.trigger('change');
            });

            // Re-init Select2 titik lokasi (supaya options ter-refresh & value bisa di-set)
            initWoSampleModalTitikSelect2();
            const curTitikWo = $modal.find('#woSampleModal-titik').data('_pendingVal');
            if (curTitikWo !== undefined) {
                $modal.find('#woSampleModal-titik').val(curTitikWo || null).trigger('change');
                $modal.find('#woSampleModal-titik').removeData('_pendingVal');
            }

            new bootstrap.Modal($modal[0]).show();
            initFpDate('#woSampleDetailModal');
        });
});

$(document).on('click', '#woSampleModal-btn-save', function () {
    const idSample = $('#woSampleDetailModal #woSampleModal-id').val();
    if (!idSample) return;

    const $btn  = $(this);
    const idWo  = currentWoData.id_wo;

    const fd = new FormData();
    fd.append('_token', window.route.csrf);
    fd.append('jenis_sample', $('#woSampleModal-jenis').val() || '');
    fd.append('tanggal_pengambilan', $('#woSampleModal-tanggal').val() || '');
    fd.append('titik_lokasi', $('#woSampleModal-titik').val() || '');
    fd.append('kondisi_sample', $('#woSampleModal-kondisi').val() || '');
    fd.append('status', $('#woSampleModal-status').val());
    fd.append('keterangan', $('#woSampleModal-keterangan').val().trim());

    $('#woSampleModal-existing-files .existing-wo-sample-file-path').each(function () {
        fd.append('existing_attachments[]', $(this).val());
    });
    if (woSampleFilePond) {
        woSampleFilePond.getFiles().forEach(function (f) {
            fd.append('attachments[]', f.file);
        });
    }

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');
    $.ajax({
        url: `/wo-samples/${idSample}`,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
    })
        .done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('woSampleDetailModal'))?.hide();
                Notify.success('Sample berhasil disimpan.');
                loadWoSampleData(idWo);
            }
        })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan sample.');
        })
        .always(function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan'); });
});

$(document).on('click', '.btn-remove-existing-wo-sample-file', function (e) {
    e.preventDefault();
    $(this).closest('div').remove();
});

$('#woSampleDetailModal').on('hidden.bs.modal', function () {
    if (woSampleFilePond) { woSampleFilePond.destroy(); woSampleFilePond = null; }
});

$(document).on('click', '.btn-wo-sample-delete', function (e) {
    e.stopPropagation();
    const id   = $(this).data('id');
    const no   = $(this).data('no');
    const idWo = currentWoData.id_wo;

    Swal.fire({
        title: 'Hapus Sample?',
        html: `Sample <strong>${escHtml(no)}</strong> akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        reverseButtons: true,
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: `/wo-samples/${id}`,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.route.csrf },
            success: function () {
                Notify.success('Sample berhasil dihapus.');
                loadWoSampleData(idWo);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal menghapus sample.');
            },
        });
    });
});

$(document).on("click", ".btn-add-fwo-modal", function () {
    var woId = $(this).data("wo-id");
    document.getElementById("iframeCreateFwo").src =
        "/fieldworks/create?id_wo=" + woId + "&embed=1";
    new bootstrap.Modal(document.getElementById("modalCreateFwo")).show();
});

$(document).on("click", ".btn-add-boq-modal", function () {
    var woId = $(this).data("wo-id");
    document.getElementById("iframeCreateBoq").src =
        "/boq/create?id_wo=" + woId + "&embed=1";
    new bootstrap.Modal(document.getElementById("modalCreateBoq")).show();
});

// ── BOQ Summary Card ───────────────────────────────────────────────────────────
function renderBoqSummary(data) {
    const totalBoqItems = (data.sections || []).length;
    const totalFwo      = data.total_fwo ?? 0;
    const fwoCompleted  = data.fwo_completed ?? 0;
    const outBelumSiap  = data.output_belum_siap ?? 0;
    const outSiap       = data.output_siap ?? 0;
    const totalOutput   = outBelumSiap + outSiap;

    const fwoColor = fwoCompleted >= totalFwo && totalFwo > 0 ? "#16a34a"
                   : fwoCompleted > 0 ? "#d97706" : "#94a3b8";

    const kpiCard = (icon, iconBg, label, value, sub = '', tabTarget = '') => `
        <div class="pm-kpi-card${tabTarget ? ' kpi-tab-link' : ''}" ${tabTarget ? `data-tab-target="${tabTarget}" style="cursor:pointer;"` : ''}>
            <div class="pm-kpi-icon" style="background:${iconBg};">
                <i class="fa-solid ${icon}"></i>
            </div>
            <div>
                <div class="pm-kpi-label">${label}</div>
                <div class="pm-kpi-value">${value}</div>
                ${sub ? `<div class="pm-kpi-sub">${sub}</div>` : ''}
            </div>
        </div>`;

    const outputCard = totalOutput === 0 ? kpiCard("fa-file-circle-check", "#64748b", "Output", "—", "Belum ada output", "#tabOutput") :
        `<div class="pm-kpi-card kpi-tab-link" data-tab-target="#tabOutput" style="flex-direction:column;align-items:flex-start;gap:8px;min-width:220px;cursor:pointer;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div class="pm-kpi-icon" style="background:#0f766e;flex-shrink:0;">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>
                <div>
                    <div class="pm-kpi-label">Output Pekerjaan</div>
                    <div class="pm-kpi-value">${totalOutput} <span style="font-size:12px;font-weight:400;color:#94a3b8;">dokumen</span></div>
                </div>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">
                    <i class="fa-solid fa-clock me-1" style="font-size:9px;"></i>${outBelumSiap} Belum Siap
                </span>
                <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;">
                    <i class="fa-solid fa-check me-1" style="font-size:9px;"></i>${outSiap} Siap
                </span>
            </div>
        </div>`;

    $("#boqSummaryCard").html(
        kpiCard("fa-layer-group", "#0891b2", "Total BOQ", totalBoqItems + " item", '', "#tabBoq") +
        `<div class="pm-kpi-card kpi-tab-link" data-tab-target="#tabFwo" style="cursor:pointer;">
            <div class="pm-kpi-icon" style="background:${fwoColor};">
                <i class="fa-solid fa-hard-hat"></i>
            </div>
            <div>
                <div class="pm-kpi-label">Total FWO</div>
                <div class="pm-kpi-value" style="color:${fwoColor};">${fwoCompleted}<span style="font-size:12px;font-weight:500;color:#94a3b8;">/${totalFwo}</span></div>
                <div class="pm-kpi-sub">FWO selesai</div>
            </div>
        </div>` +
        outputCard
    );
}

// Load FWO

// ── Load & render BOQ progress ─────────────────────────────────────────────────
function loadBoqProgress(id_wo) {
    currentBoqWoId = id_wo;
    $("#boqProgressContent").html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>',
    );

    $.get("/work-orders/" + id_wo + "/boq-progress", function (data) {
        currentBoqData = data;
        renderBoqSummary(data);
        renderBoqView(data, id_wo);
    }).fail(function () {
        $("#boqProgressContent").html(
            '<div class="text-center text-danger py-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data</div>',
        );
    });
}

function renderBoqView(data, id_wo) {
    $("#boqProgressContent").html(renderBoqProgressTable(data, id_wo));
    $("#fwoProgressContent").html(renderFwoProgressTable(data, id_wo));
}

function renderBoqProgressTable(data, id_wo) {
    const hasBoq = data.sections && data.sections.length > 0;

    if (!hasBoq) {
        return (
            '<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
            "Belum ada data BOQ untuk Work Order ini</div>"
        );
    }

    const pctBadgeStyle =
        data.progress_pct >= 100
            ? "background:#198754;color:#fff;"
            : data.progress_pct > 0
              ? "background:#dbeafe;color:#1d4ed8;"
              : "background:#e9ecef;color:#495057;";

    const summaryHtml = `
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">BOQ Progress</span>
                    ${
                        data.total_boq_amount > 0
                            ? `<span style="font-size:11px;background:#eff6ff;color:#1d4ed8;padding:2px 8px;border-radius:20px;font-weight:600;">
                            <i class="fa-solid fa-tag me-1" style="font-size:10px;"></i>Rp ${Number(data.total_boq_amount).toLocaleString("en-US")}
                           </span>`
                            : ""
                    }
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">${data.total_fwo_qty} / ${data.total_boq_qty} qty</span>
                    <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;${pctBadgeStyle}">${data.progress_pct}%</span>
                </div>
            </div>
            <div class="progress" style="height:7px;">
                <div class="progress-bar ${data.progress_pct >= 100 ? "bg-success" : data.progress_pct > 0 ? "bg-primary" : "bg-secondary"}"
                    style="width:${data.progress_pct}%;transition:width .4s;"></div>
            </div>
        </div>`;

    const TH =
        'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';
    const TDsub =
        'style="padding:5px 12px;vertical-align:middle;background:#f8fafc;"';

    const allWoFwos = data.fwos || [];

    const rows = data.sections
        .map(function (sec, idx) {
            const satuan = sec.satuan ? escHtml(sec.satuan) : "—";
            const sisa = sec.boq_qty - sec.fwo_qty;
            const pctColor =
                sec.progress_pct >= 100
                    ? "#198754"
                    : sec.progress_pct > 0
                      ? "#1d4ed8"
                      : "#6c757d";
            const pctBg =
                sec.progress_pct >= 100
                    ? "#d1fae5"
                    : sec.progress_pct > 0
                      ? "#dbeafe"
                      : "#e9ecef";

            // Hanya FWO yang terhubung ke BOQ ini via fieldwork_boq
            const boqFwos = sec.fwos || [];
            const hasFwos = boqFwos.length > 0;

            const chevron = hasFwos
                ? `<i class="fa-solid fa-chevron-right boq-chevron" style="font-size:10px;color:#94a3b8;transition:transform .2s;margin-right:4px;"></i>`
                : '';

            const boqRow = `<tr class="boq-data-row${hasFwos ? " boq-expandable" : ""}" data-boq-id="${sec.id_boq}"
            style="cursor:${hasFwos ? "pointer" : "default"};">
            <td ${TD} style="width:40px;text-align:center;color:#94a3b8;">${idx + 1}</td>
            <td ${TD}>${chevron}<a href="/boq?open=${id_wo}" class="text-decoration-none fw-semibold" style="color:#1a56db;">${escHtml(sec.point_name)}</a></td>
            <td ${TD} style="color:#64748b;">${satuan}</td>
            <td ${TD} style="text-align:right;font-weight:600;">${sec.boq_qty}</td>
            <td ${TD} style="text-align:right;color:#7c3aed;font-weight:600;">${sec.fwo_qty}</td>
            <td ${TD} style="text-align:right;font-weight:600;color:${sisa > 0 ? "#dc2626" : "#16a34a"};">${sisa}</td>
        </tr>`;

            let fwoRows = "";
            return boqRow;
        })
        .join("");

    const searchBar = `<div class="mb-2 d-flex align-items-center gap-2">
        <div class="input-group input-group-sm" style="max-width:280px;">
            <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0;">
                <i class="fa-solid fa-magnifying-glass text-muted" style="font-size:11px;"></i>
            </span>
            <input type="text" id="boqSearchInput" class="form-control" placeholder="Cari item BOQ..."
                style="border-color:#e2e8f0;font-size:12px;" data-no-disable>
            <button type="button" id="btnClearBoqSearch" class="btn btn-outline-secondary d-none"
                style="border-color:#e2e8f0;font-size:11px;" title="Hapus pencarian">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <span id="boqSearchCount" class="text-muted" style="font-size:11px;"></span>
    </div>`;

    return (
        summaryHtml +
        searchBar +
        `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;min-width:700px;white-space:nowrap;">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th ${TH} style="width:40px;">No</th>
                    <th ${TH} style="min-width:200px;">Item BOQ</th>
                    <th ${TH} style="min-width:80px;">Satuan</th>
                    <th ${TH} style="min-width:80px;text-align:right;">BOQ Qty</th>
                    <th ${TH} style="min-width:80px;text-align:right;">FWO Qty</th>
                    <th ${TH} style="min-width:70px;text-align:right;">Sisa</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`
    );
}

function renderBoqProgressContent(data, id_wo) {
    const hasBoq = data.sections && data.sections.length > 0;

    if (!hasBoq) {
        return (
            '<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
            "Belum ada data BOQ untuk Work Order ini</div>"
        );
    }

    const pctBadgeStyle =
        data.progress_pct >= 100
            ? "background:#198754;color:#fff;"
            : data.progress_pct > 0
              ? "background:#dbeafe;color:#1d4ed8;"
              : "background:#e9ecef;color:#495057;";

    const summaryHtml = `
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">BOQ Progress</span>
                    ${
                        data.total_boq_amount > 0
                            ? `<span style="font-size:11px;background:#eff6ff;color:#1d4ed8;padding:2px 8px;border-radius:20px;font-weight:600;">
                            <i class="fa-solid fa-tag me-1" style="font-size:10px;"></i>Rp ${Number(data.total_boq_amount).toLocaleString("en-US")}
                           </span>`
                            : ""
                    }
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">${data.total_fwo_qty} / ${data.total_boq_qty} qty</span>
                    <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;${pctBadgeStyle}">${data.progress_pct}%</span>
                </div>
            </div>
            <div class="progress" style="height:7px;">
                <div class="progress-bar ${data.progress_pct >= 100 ? "bg-success" : data.progress_pct > 0 ? "bg-primary" : "bg-secondary"}"
                    style="width:${data.progress_pct}%;transition:width .4s;"></div>
            </div>
        </div>`;

    // Header baris
    const headerRow = `
        <div style="display:flex;padding:7px 14px;background:#f8fafc;border-bottom:2px solid #e2e8f0;gap:0;">
            <div style="flex:1;min-width:0;padding-right:12px;display:flex;align-items:center;gap:6px;">
                <i class="fa-solid fa-layer-group" style="color:#16a34a;font-size:11px;"></i>
                <span style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">BOQ Item</span>
            </div>
            <div style="width:1px;background:#e2e8f0;flex-shrink:0;"></div>
            <div style="flex:1;min-width:0;padding-left:12px;display:flex;align-items:center;gap:6px;">
                <i class="fa-solid fa-hard-hat" style="color:#7c3aed;font-size:11px;"></i>
                <span style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Fieldwork Orders</span>
            </div>
        </div>`;

    // Tiap BOQ = 1 baris dengan FWO di sebelah kanan
    const rowsHtml = data.sections
        .map(function (sec) {
            const satuan = sec.satuan ? escHtml(sec.satuan) : "";
            const secColor =
                sec.progress_pct >= 100
                    ? "#198754"
                    : sec.progress_pct > 0
                      ? "#1d4ed8"
                      : "#94a3b8";
            const secBg =
                sec.progress_pct >= 100
                    ? "#dcfce7"
                    : sec.progress_pct > 0
                      ? "#dbeafe"
                      : "#f1f5f9";
            const doneIcon =
                sec.progress_pct >= 100
                    ? '<i class="fa-solid fa-circle-check" style="color:#198754;font-size:12px;margin-left:6px;flex-shrink:0;"></i>'
                    : "";
            const priceHtml =
                sec.harga > 0
                    ? `<div style="font-size:11px;color:#64748b;margin-top:2px;">
                Rp ${Number(sec.harga).toLocaleString("en-US")}${satuan ? " / " + satuan : ""}
                <span style="margin:0 3px;color:#cbd5e1;">×</span>
                ${sec.boq_qty}${satuan ? " " + satuan : ""}
                <span style="margin:0 3px;color:#cbd5e1;">=</span>
                <strong style="color:#1d4ed8;">Rp ${Number(sec.total_amount).toLocaleString("en-US")}</strong>
               </div>`
                    : "";

            // FWO kolom kanan
            const boqFwos = sec.fwos || [];
            const fwoColHtml =
                boqFwos.length > 0
                    ? boqFwos
                          .map(function (fwo) {
                              const fwoPct =
                                  sec.boq_qty > 0
                                      ? Math.round(
                                            (fwo.qty / sec.boq_qty) * 100,
                                        )
                                      : 0;
                              const fPctColor =
                                  fwoPct >= 100
                                      ? "#198754"
                                      : fwoPct > 0
                                        ? "#7c3aed"
                                        : "#94a3b8";
                              const fPctBg =
                                  fwoPct >= 100
                                      ? "#d1fae5"
                                      : fwoPct > 0
                                        ? "#ede9fe"
                                        : "#f1f5f9";
                              return `<div style="display:flex;align-items:center;gap:8px;padding:4px 0;border-bottom:1px solid #f8fafc;">
                    <i class="fa-solid fa-hard-hat" style="color:#7c3aed;font-size:10px;flex-shrink:0;"></i>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;">
                            <a href="/fieldworks?open=${fwo.id_fwo}"
                                class="text-decoration-none fw-semibold" style="font-size:12px;color:#7c3aed;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(fwo.no_fwo)}</a>
                            <div style="display:flex;align-items:center;gap:5px;flex-shrink:0;">
                                <span style="font-size:11px;font-weight:700;color:#7c3aed;">${fwo.qty} ${satuan || "qty"}</span>
                                <span style="font-size:10px;font-weight:600;padding:1px 6px;border-radius:10px;background:${fPctBg};color:${fPctColor};">${fwoPct}%</span>
                                <button type="button" class="btn-copy-fwo" data-fwo-id="${fwo.id_fwo}"
                                    style="border:none;background:none;padding:1px 3px;color:#94a3b8;cursor:pointer;line-height:1;font-size:10px;"
                                    title="Salin FWO ini">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="progress mt-1" style="height:3px;border-radius:2px;">
                            <div style="width:${fwoPct}%;height:100%;background:${fPctColor};border-radius:2px;"></div>
                        </div>
                    </div>
                </div>`;
                          })
                          .join("")
                    : `<div style="font-size:11px;color:#94a3b8;padding:4px 0;">
                <i class="fa-solid fa-circle-minus me-1" style="font-size:9px;"></i>Belum ada FWO dipetakan
               </div>`;

            return `<div style="display:flex;border-bottom:1px solid #e2e8f0;gap:0;">
            <!-- Kolom BOQ -->
            <div style="flex:1;min-width:0;padding:12px 14px;border-right:1px solid #e2e8f0;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px;">
                    <div style="min-width:0;">
                        <a href="/boq?open=${id_wo}"
                            class="text-decoration-none"
                            style="font-size:13px;font-weight:600;color:#1a56db;">${escHtml(sec.point_name)}</a>
                        ${priceHtml}
                    </div>
                    <div style="display:flex;align-items:center;flex-shrink:0;">
                        <span style="font-size:11px;color:#64748b;white-space:nowrap;">${sec.fwo_qty} / ${sec.boq_qty}${satuan ? " " + satuan : ""}</span>
                        ${doneIcon}
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <div class="progress flex-grow-1" style="height:5px;border-radius:3px;">
                        <div style="width:${sec.progress_pct}%;height:100%;background:${secColor};border-radius:3px;transition:width .4s;"></div>
                    </div>
                    <span style="font-size:11px;font-weight:600;padding:1px 7px;border-radius:10px;background:${secBg};color:${secColor};white-space:nowrap;flex-shrink:0;">${sec.progress_pct}%</span>
                </div>
            </div>
            <!-- Kolom FWO -->
            <div style="flex:1;min-width:0;padding:12px 14px;">
                ${fwoColHtml}
            </div>
        </div>`;
        })
        .join("");

    return (
        summaryHtml +
        `
        <div style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
            ${headerRow}
            ${rowsHtml}
        </div>`
    );
}

function renderFwoProgressTable(data, id_wo) {
    const fwos = data.fwos || [];

    if (!fwos.length) {
        return (
            '<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
            "Belum ada FWO untuk Work Order ini</div>"
        );
    }

    const TH =
        'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';

    const rows = fwos
        .map(function (f, idx) {
            const tglMulai = f.tanggal_mulai
                ? f.tanggal_mulai.substring(0, 10)
                : "—";
            const tglSelesai = f.tanggal_selesai
                ? f.tanggal_selesai.substring(0, 10)
                : "—";
            const isCompleted = f.status === 'completed';
            const statusBadge = isCompleted
                ? `<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:20px;background:#f0fdf4;color:#15803d;font-size:11px;font-weight:600;border:1px solid #bbf7d0;white-space:nowrap;">
                       <i class="fa-solid fa-circle-check" style="font-size:10px;"></i> Completed
                   </span>`
                : `<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:20px;background:#fffbeb;color:#b45309;font-size:11px;font-weight:600;border:1px solid #fde68a;white-space:nowrap;">
                       <i class="fa-solid fa-hourglass-half" style="font-size:10px;"></i> Planned
                   </span>`;
            const search = [f.no_fwo, f.judul_pekerjaan, f.keterangan, f.status]
                .join(" ")
                .toLowerCase();

            return `<tr class="fwo-data-row" data-search="${escHtml(search)}">
            <td ${TD} style="text-align:center;color:#94a3b8;">${idx + 1}</td>
            <td ${TD}>
                <a href="/fieldworks?open=${f.id_fwo}"
                    class="fw-semibold text-decoration-none" style="color:#1a56db;white-space:nowrap;">
                    ${escHtml(f.no_fwo ?? "—")}
                </a>
            </td>
            <td ${TD} style="color:#374151;">${escHtml(f.judul_pekerjaan ?? "—")}</td>
            <td ${TD} style="color:#64748b;">${escHtml(f.keterangan ?? "—")}</td>
            <td ${TD}>${statusBadge}</td>
            <td ${TD} style="color:#64748b;white-space:nowrap;">${tglMulai}</td>
            <td ${TD} style="color:#64748b;white-space:nowrap;">${tglSelesai}</td>
            <td ${TD} style="white-space:nowrap;">
                <div class="d-flex align-items-center gap-1">
                <a href="/fieldworks?open=${f.id_fwo}"
                    class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" title="Buka detail FWO">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-copy-fwo"
                    style="font-size:11px;" title="Salin FWO ini" data-fwo-id="${f.id_fwo}">
                    <i class="fa-solid fa-copy"></i>
                </button>
                </div>
            </td>
        </tr>`;
        })
        .join("");

    const searchBar = `<div class="mb-2 d-flex align-items-center gap-2">
        <div class="input-group input-group-sm" style="max-width:280px;">
            <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0;">
                <i class="fa-solid fa-magnifying-glass text-muted" style="font-size:11px;"></i>
            </span>
            <input type="text" id="fwoSearchInput" class="form-control" placeholder="Cari No FWO atau judul..."
                style="border-color:#e2e8f0;font-size:12px;" data-no-disable>
            <button type="button" id="btnClearFwoSearch" class="btn btn-outline-secondary d-none"
                style="border-color:#e2e8f0;font-size:11px;" title="Hapus pencarian">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <span id="fwoSearchCount" class="text-muted" style="font-size:11px;"></span>
    </div>`;

    return (
        searchBar +
        `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;min-width:600px;white-space:nowrap;">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th ${TH} style="width:40px;">No</th>
                    <th ${TH} style="min-width:140px;">No FWO</th>
                    <th ${TH} style="min-width:200px;">Judul Pekerjaan</th>
                    <th ${TH} style="min-width:180px;">Keterangan</th>
                    <th ${TH} style="min-width:110px;">Status</th>
                    <th ${TH} style="min-width:110px;">Tgl Mulai</th>
                    <th ${TH} style="min-width:110px;">Tgl Selesai</th>
                    <th ${TH} style="min-width:100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`
    );
}

// ── Output Pekerjaan ───────────────────────────────────────────────────────────
function loadOutputProgress(id_wo) {
    currentOutputWoId = id_wo;
    $("#outputContent").html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>',
    );
    $.get(window.route.update + id_wo + "/outputs", function (data) {
        outputDataMap = {};
        (data || []).forEach(function (item) {
            outputDataMap[item.id_output] = item;
        });
        $("#outputContent").html(renderOutputTable(data));
    }).fail(function () {
        $("#outputContent").html(
            '<div class="text-center text-danger py-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data</div>',
        );
    });
}

function outputFileName(path) {
    var base = path.split("/").pop();
    var parts = base.split("_");
    // "output_pekerjaan_TIMESTAMP_originalname" → skip first 3 segments
    return parts.length > 3 ? parts.slice(3).join("_") : base;
}

function renderOutputTable(outputs) {
    const TH =
        'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';

    var bodyHtml;
    if (!outputs || !outputs.length) {
        bodyHtml =
            '<tr><td colspan="11" class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>Belum ada output pekerjaan</td></tr>';
    } else {
        bodyHtml = outputs
            .map(function (item, idx) {
                var attachHtml = "—";
                try {
                    var files =
                        typeof item.attachments === "string"
                            ? JSON.parse(item.attachments)
                            : item.attachments || [];
                    if (files && files.length) {
                        attachHtml = files
                            .map(function (p) {
                                return (
                                    '<a href="/storage/' +
                                    p +
                                    '" target="_blank" ' +
                                    'class="d-inline-flex align-items-center gap-1 me-1" ' +
                                    'style="font-size:11px;color:#1a56db;text-decoration:none;">' +
                                    '<i class="fa-solid fa-paperclip" style="font-size:10px;"></i>' +
                                    escHtml(outputFileName(p)) +
                                    "</a>"
                                );
                            })
                            .join("");
                    }
                } catch (e) {}
                // Status badge
                var statusMap = {
                    belum_siap: { label: 'Belum Siap', bg: '#fef2f2', color: '#dc2626', border: '#fecaca' },
                    siap:       { label: 'Siap',       bg: '#f0fdf4', color: '#16a34a', border: '#bbf7d0' },
                    terkirim:   { label: 'Terkirim',   bg: '#eff6ff', color: '#1d4ed8', border: '#bfdbfe' },
                };
                var st = statusMap[item.status] || statusMap['belum_siap'];
                var statusBadge = '<span style="font-size:11px;padding:2px 9px;border-radius:20px;background:' + st.bg + ';color:' + st.color + ';border:1px solid ' + st.border + ';white-space:nowrap;">'
                    + escHtml(st.label)
                    + '</span>';

                // Jenis dokumen & qty
                var jenisBadge = '—';
                if (item.jenis_dokumen) {
                    var jLabel = {copy:'Copy', asli:'Asli', asli_dan_copy:'Asli & Copy'}[item.jenis_dokumen] || item.jenis_dokumen;
                    jenisBadge = '<span style="font-size:11px;padding:2px 8px;border-radius:20px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;white-space:nowrap;">' + escHtml(jLabel) + '</span>';
                }
                var qtyCopyHtml = (item.qty_copy != null && (item.jenis_dokumen === 'copy' || item.jenis_dokumen === 'asli_dan_copy'))
                    ? '<span style="font-weight:600;color:#1d4ed8;">' + item.qty_copy + '</span>'
                    : '<span style="color:#94a3b8;">—</span>';
                var qtyAsliHtml = (item.qty_asli != null && (item.jenis_dokumen === 'asli' || item.jenis_dokumen === 'asli_dan_copy'))
                    ? '<span style="font-weight:600;color:#16a34a;">' + item.qty_asli + '</span>'
                    : '<span style="color:#94a3b8;">—</span>';
                // Link drive
                var driveHtml = item.link_drive
                    ? '<a href="' + escHtml(item.link_drive) + '" target="_blank" style="font-size:11px;color:#1a56db;"><i class="fa-brands fa-google-drive me-1"></i>Drive</a>'
                    : '—';

                return (
                    '<tr class="output-data-row" data-id="' +
                    item.id_output +
                    '">' +
                    "<td " + TD + ' style="width:40px;text-align:center;color:#94a3b8;white-space:nowrap;">' + (idx + 1) + "</td>" +
                    "<td " + TD + ' style="font-weight:500;">' + escHtml(item.judul_output) + "</td>" +
                    "<td " + TD + ' style="color:#64748b;">' + (item.judul_dokumen ? escHtml(item.judul_dokumen) : "—") + "</td>" +
                    "<td " + TD + ">" + statusBadge + "</td>" +
                    "<td " + TD + ">" + jenisBadge + "</td>" +
                    "<td " + TD + ' style="text-align:center;">' + qtyCopyHtml + "</td>" +
                    "<td " + TD + ' style="text-align:center;">' + qtyAsliHtml + "</td>" +
                    "<td " + TD + ' style="white-space:nowrap;">' + fmtDate(item.tanggal_mulai) + "</td>" +
                    "<td " + TD + ' style="white-space:nowrap;">' + fmtDate(item.tanggal_selesai) + "</td>" +
                    "<td " + TD + ">" + driveHtml + "</td>" +
                    "<td " + TD + ">" + attachHtml + "</td>" +
                    '<td ' + TD + ' style="white-space:nowrap;">' +
                    '<div class="d-flex align-items-center gap-1">' +
                    (currentWoData && currentWoData.status === 'completed'
                        ? '<span class="text-muted" style="font-size:11px;">—</span>'
                        : (item.status === 'belum_siap'
                            ? '<button type="button" class="btn btn-sm btn-success py-0 px-2 btn-output-status" data-id="' + item.id_output + '" data-status="siap" data-no-disable style="font-size:11px;"><i class="fa-solid fa-check me-1"></i>Siap</button>'
                            : '') +
                          '<button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-edit-output" ' +
                          'data-id="' + item.id_output + '" data-no-disable style="font-size:11px;"><i class="fa-solid fa-pen"></i></button>' +
                          '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-delete-output" ' +
                          'data-id="' + item.id_output + '" data-no-disable style="font-size:11px;"><i class="fa-solid fa-trash"></i></button>'
                    ) +
                    '</div>' +
                    "</td></tr>"
                );
            })
            .join("");
    }

    return (
        '<div class="table-responsive">' +
        '<table class="table table-sm table-hover mb-0" style="font-size:13px;white-space:nowrap;">' +
        '<thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;"><tr>' +
        "<th " + TH + ' style="width:40px;">No</th>' +
        "<th " + TH + ' style="min-width:200px;">Judul Output</th>' +
        "<th " + TH + ' style="min-width:160px;">Nomor Dokumen</th>' +
        "<th " + TH + ' style="min-width:120px;">Status</th>' +
        "<th " + TH + ' style="min-width:120px;">Jenis Dok.</th>' +
        "<th " + TH + ' style="min-width:80px;text-align:center;">Qty Copy</th>' +
        "<th " + TH + ' style="min-width:80px;text-align:center;">Qty Asli</th>' +
        "<th " + TH + ' style="min-width:105px;">Tgl Mulai</th>' +
        "<th " + TH + ' style="min-width:105px;">Tgl Selesai</th>' +
        "<th " + TH + ' style="min-width:80px;">Drive</th>' +
        "<th " + TH + ' style="min-width:150px;">Lampiran</th>' +
        "<th " + TH + ' style="min-width:130px;">Aksi</th>' +
        "</tr></thead>" +
        "<tbody>" +
        bodyHtml +
        "</tbody>" +
        "</table></div>"
    );
}

function showOutputForm(data) {
    var isEdit = data && data.id_output;
    var existingFilesHtml = "";
    if (isEdit && data.attachments) {
        try {
            var files =
                typeof data.attachments === "string"
                    ? JSON.parse(data.attachments)
                    : data.attachments;
            if (files && files.length) {
                existingFilesHtml = files
                    .map(function (p, i) {
                        return (
                            '<div class="d-inline-flex align-items-center gap-1 me-2 mb-1 existing-file-item" ' +
                            'style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:4px;padding:2px 8px;">' +
                            '<a href="/storage/' +
                            p +
                            '" target="_blank" style="font-size:11px;color:#166534;text-decoration:none;">' +
                            '<i class="fa-solid fa-paperclip me-1" style="font-size:10px;"></i>' +
                            escHtml(outputFileName(p)) +
                            "</a>" +
                            '<button type="button" class="btn-remove-existing-file" data-index="' +
                            i +
                            '" ' +
                            'style="border:none;background:none;color:#dc2626;padding:0 2px;cursor:pointer;font-size:11px;line-height:1;">' +
                            '<i class="fa-solid fa-times"></i></button>' +
                            '<input type="hidden" class="existing-file-path" value="' +
                            escHtml(p) +
                            '">' +
                            "</div>"
                        );
                    })
                    .join("");
            }
        } catch (e) {}
    }

    var html =
        (isEdit ? '<input type="hidden" id="outputEditId" value="' + data.id_output + '">' : '') +
        '<div class="row g-3">' +
        '<div class="col-md-6">' +
        '<label class="form-label form-label-sm text-muted mb-1">Judul Output <span class="text-danger">*</span></label>' +
        '<input type="text" id="outputJudulOutput" class="form-control form-control-sm" placeholder="Judul Output" maxlength="255" value="' + (isEdit ? escHtml(data.judul_output || '') : '') + '">' +
        '</div>' +
        '<div class="col-md-6">' +
        '<label class="form-label form-label-sm text-muted mb-1">Nomor Dokumen</label>' +
        '<input type="text" id="outputJudulDokumen" class="form-control form-control-sm" placeholder="Nomor Dokumen" maxlength="255" value="' + (isEdit ? escHtml(data.judul_dokumen || '') : '') + '">' +
        '</div>' +
        '<div class="col-md-3">' +
        '<label class="form-label form-label-sm text-muted mb-1">Status</label>' +
        '<select id="outputStatus" class="form-select form-select-sm">' +
        '<option value="belum_siap"' + (isEdit && data.status === 'belum_siap' ? ' selected' : (!isEdit ? ' selected' : '')) + '>Belum Siap</option>' +
        '<option value="siap"'      + (isEdit && data.status === 'siap'       ? ' selected' : '') + '>Siap</option>' +
        '</select>' +
        '</div>' +
        '<div class="col-md-3">' +
        '<label class="form-label form-label-sm text-muted mb-1">Jenis Dokumen</label>' +
        '<select id="outputJenisDokumen" class="form-select form-select-sm">' +
        '<option value="">— Pilih —</option>' +
        '<option value="copy"' + (isEdit && data.jenis_dokumen === 'copy' ? ' selected' : '') + '>Copy</option>' +
        '<option value="asli"' + (isEdit && data.jenis_dokumen === 'asli' ? ' selected' : '') + '>Asli</option>' +
        '<option value="asli_dan_copy"' + (isEdit && data.jenis_dokumen === 'asli_dan_copy' ? ' selected' : '') + '>Asli dan Copy</option>' +
        '</select>' +
        '</div>' +
        '<div id="outputQtyCopyWrap" class="col-md-3" style="display:none;">' +
        '<label class="form-label form-label-sm text-muted mb-1">Qty Copy</label>' +
        '<input type="number" id="outputQtyCopy" class="form-control form-control-sm" placeholder="Jumlah copy" min="1" value="' + (isEdit && data.qty_copy ? data.qty_copy : '') + '">' +
        '</div>' +
        '<div id="outputQtyAsliWrap" class="col-md-3" style="display:none;">' +
        '<label class="form-label form-label-sm text-muted mb-1">Qty Asli</label>' +
        '<input type="number" id="outputQtyAsli" class="form-control form-control-sm" placeholder="Jumlah asli" min="1" value="' + (isEdit && data.qty_asli ? data.qty_asli : '') + '">' +
        '</div>' +
        '<div class="col-md-3">' +
        '<label class="form-label form-label-sm text-muted mb-1">Tanggal Mulai</label>' +
        '<input type="text" id="outputTanggalMulai" name="tanggal_mulai" class="form-control form-control-sm fp-date" value="' + (isEdit && data.tanggal_mulai ? data.tanggal_mulai.substring(0,10) : '') + '" autocomplete="off">' +
        '</div>' +
        '<div class="col-md-3">' +
        '<label class="form-label form-label-sm text-muted mb-1">Tanggal Selesai</label>' +
        '<input type="text" id="outputTanggalSelesai" name="tanggal_selesai" class="form-control form-control-sm fp-date" value="' + (isEdit && data.tanggal_selesai ? data.tanggal_selesai.substring(0,10) : '') + '" autocomplete="off">' +
        '</div>' +
        '<div class="col-md-6">' +
        '<label class="form-label form-label-sm text-muted mb-1"><i class="fa-brands fa-google-drive me-1" style="color:#1a73e8;"></i>Link Drive</label>' +
        '<input type="url" id="outputLinkDrive" class="form-control form-control-sm" placeholder="https://drive.google.com/..." value="' + (isEdit && data.link_drive ? escHtml(data.link_drive) : '') + '">' +
        '</div>' +
        '<div class="col-md-12">' +
        '<label class="form-label form-label-sm text-muted mb-1">Lampiran</label>' +
        (existingFilesHtml ? '<div id="existingFilesWrap" class="mb-2">' + existingFilesHtml + '</div>' : '') +
        '<input type="file" id="outputAttachments" multiple>' +
        '</div></div>';

    if (outputFilePond) {
        outputFilePond.destroy();
        outputFilePond = null;
    }

    $('#modalOutputFormTitle').html(
        '<i class="fa-solid fa-' + (isEdit ? 'pen' : 'plus') + ' me-2" style="color:#0f766e;"></i>' +
        (isEdit ? 'Edit Output' : 'Tambah Output')
    );
    $('#modalOutputFormBody').html(html);
    initFpDate('#modalOutputFormBody');
    outputFilePond = createFileUploader('#outputAttachments');
    triggerOutputQtyVisibility(isEdit ? (data.jenis_dokumen || '') : '');

    var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalOutputForm'));
    modal.show();
    setTimeout(function () { $('#outputJudulOutput').focus(); }, 400);
}

function triggerOutputQtyVisibility(jenis) {
    $("#outputQtyCopyWrap").toggle(jenis === 'copy' || jenis === 'asli_dan_copy');
    $("#outputQtyAsliWrap").toggle(jenis === 'asli' || jenis === 'asli_dan_copy');
}

// ── Event handlers ─────────────────────────────────────────────────────────────
$(document).ready(function () {
    // KPI card → switch tab
    $(document).on("click", ".kpi-tab-link", function () {
        var target = $(this).data("tab-target");
        if (!target) return;
        var $btn = $('#woDetailTabs button[data-bs-target="' + target + '"]');
        if ($btn.length) $btn.trigger("click");
    });

    $(document).on("click", "#btnRefreshBoqProgress", function () {
        const woId = $(this).data("wo-id");
        const $icon = $(this).find("i");
        $icon.addClass("fa-spin");
        loadBoqProgress(woId);
        setTimeout(function () {
            $icon.removeClass("fa-spin");
        }, 600);
    });

    $(document).on("input", "#boqSearchInput", function () {
        const q = $(this).val().toLowerCase().trim();
        let visible = 0;
        $("#btnClearBoqSearch").toggleClass("d-none", !q);
        $(".boq-data-row").each(function () {
            const match =
                !q ||
                $(this)
                    .find("td:nth-child(2)")
                    .text()
                    .toLowerCase()
                    .includes(q);
            $(this).toggle(match);
            const boqId = $(this).data("boq-id");
            if (boqId && !match) {
                $(".fwo-sub-" + boqId).hide();
            }
            if (match) visible++;
        });
        const total = $(".boq-data-row").length;
        $("#boqSearchCount").text(
            q ? visible + " dari " + total + " item" : "",
        );
    });

    $(document).on("click", ".boq-expandable", function () {
        const boqId = $(this).data("boq-id");
        const $subs = $(".fwo-sub-" + boqId);
        const $chev = $(this).find(".boq-chevron");
        const isOpen = $subs.first().is(":visible");
        if (isOpen) {
            $subs.hide();
            $chev.css("transform", "");
        } else {
            $subs.show();
            $chev.css("transform", "rotate(90deg)");
        }
    });

    $(document).on("click", "#btnClearBoqSearch", function () {
        $("#boqSearchInput").val("").trigger("input");
    });

    // FWO Search
    $(document).on("input", "#fwoSearchInput", function () {
        const q = $(this).val().toLowerCase().trim();
        let visible = 0;
        $("#btnClearFwoSearch").toggleClass("d-none", !q);
        $(".fwo-data-row").each(function () {
            const match = !q || ($(this).data("search") || "").includes(q);
            $(this).toggle(match);
            if (match) visible++;
        });
        const total = $(".fwo-data-row").length;
        $("#fwoSearchCount").text(q ? visible + " dari " + total + " FWO" : "");
    });

    $(document).on("click", "#btnClearFwoSearch", function () {
        $("#fwoSearchInput").val("").trigger("input");
    });

    // ── Output Pekerjaan ─────────────────────────────────────────────────────
    $(document).on("click", "#btnAddOutput", function () {
        showOutputForm(null);
    });

    $(document).on("change", "#outputJenisDokumen", function () {
        triggerOutputQtyVisibility($(this).val());
    });

    // Tombol aksi status
    $(document).on("click", ".btn-output-status", function () {
        var $btn = $(this);
        var id = $btn.data('id');
        var newStatus = $btn.data('status');
        var label = 'Siap';

        Notify.confirm('Ubah status menjadi <b>' + label + '</b>?', function () {
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
            $.ajax({
                url: window.route.outputBase + '/' + id + '/status',
                method: 'POST',
                data: { _token: window.route.csrf, status: newStatus },
                success: function () {
                    Notify.success('Status diperbarui menjadi ' + label);
                    loadOutputProgress(currentOutputWoId);
                    loadBoqProgress(currentOutputWoId);
                },
                error: function () {
                    Notify.error('Gagal mengubah status');
                    $btn.prop('disabled', false);
                }
            });
        });
    });


    $('#modalOutputForm').on('hidden.bs.modal', function () {
        if (outputFilePond) {
            outputFilePond.destroy();
            outputFilePond = null;
        }
        $('#modalOutputFormBody').html('');
    });

    $(document).on("click", ".btn-edit-output", function () {
        var id = $(this).data("id");
        var item = outputDataMap[id];
        if (item) {
            showOutputForm(item);
        }
    });

    $(document).on("click", ".btn-remove-existing-file", function () {
        $(this).closest(".existing-file-item").remove();
    });


    $(document).on("click", "#btnSaveOutput", function () {
        var mulai = $("#outputTanggalMulai").val();
        var selesai = $("#outputTanggalSelesai").val();
        var woSelesai = currentWoData && currentWoData.tanggal_selesai
            ? currentWoData.tanggal_selesai.substring(0, 10) : null;
        if (selesai && woSelesai && selesai > woSelesai) {
            Swal.fire({
                icon: 'warning',
                title: 'Periksa Tanggal',
                text: 'Tanggal selesai output tidak boleh melebihi tanggal selesai WO (' + woSelesai + ')',
            });
            return;
        }

        var judulOutput = $("#outputJudulOutput").val().trim();
        if (!judulOutput) {
            Swal.fire({
                icon: "warning",
                title: "Field wajib diisi",
                text: "Judul Output harus diisi.",
            });
            return;
        }
        var isEdit = $("#outputEditId").length > 0;
        var editId = isEdit ? $("#outputEditId").val() : null;

        var fd = new FormData();
        fd.append("_token", window.route.csrf);
        fd.append("judul_output", judulOutput);
        fd.append("judul_dokumen", $("#outputJudulDokumen").val().trim());
        fd.append("status", $("#outputStatus").val());
        fd.append("jenis_dokumen", $("#outputJenisDokumen").val());
        fd.append("qty_copy", $("#outputQtyCopy").val() || '');
        fd.append("qty_asli", $("#outputQtyAsli").val() || '');
        fd.append("link_drive", $("#outputLinkDrive").val().trim());
        fd.append("tanggal_mulai", $("#outputTanggalMulai").val() || '');
        fd.append("tanggal_selesai", $("#outputTanggalSelesai").val() || '');
        if (!isEdit) {
            fd.append("id_wo", currentOutputWoId);
        }

        $("#existingFilesWrap .existing-file-path").each(function () {
            fd.append("existing_attachments[]", $(this).val());
        });

        if (outputFilePond) {
            outputFilePond.getFiles().forEach(function (f) {
                fd.append("attachments[]", f.file);
            });
        }

        var url = isEdit
            ? window.route.outputBase + '/' + editId
            : window.route.outputBase;
        var $btn = $("#btnSaveOutput");
        $btn.prop("disabled", true).html(
            '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...',
        );

        $.ajax({
            url: url,
            method: "POST",
            data: fd,
            processData: false,
            contentType: false,
            success: function () {
                Notify.success(isEdit ? "Output berhasil diperbarui" : "Output berhasil ditambahkan");
                $btn.prop("disabled", false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan');
                var modal = bootstrap.Modal.getInstance(document.getElementById('modalOutputForm'));
                if (modal) modal.hide();
                loadOutputProgress(currentOutputWoId);
                loadBoqProgress(currentOutputWoId);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || "Terjadi kesalahan");
                $btn.prop("disabled", false).html(
                    '<i class="fa-solid fa-floppy-disk me-1"></i> Simpan',
                );
            },
        });
    });

    $(document).on("click", ".btn-delete-output", function () {
        var id = $(this).data("id");
        Notify.confirm("Hapus output pekerjaan ini?", function () {
            $.ajax({
                url: window.route.outputBase + '/' + id,
                method: "POST",
                data: { _token: window.route.csrf, _method: "DELETE" },
                success: function () {
                    Notify.success("Output berhasil dihapus");
                    loadOutputProgress(currentOutputWoId);
                },
                error: function (xhr) {
                    Notify.error(
                        xhr.responseJSON?.message || "Terjadi kesalahan",
                    );
                },
            });
        });
    });

    // FWO Refresh
    $(document).on("click", "#btnRefreshFwoProgress", function () {
        const woId = $(this).data("wo-id");
        const $icon = $(this).find("i");
        $icon.addClass("fa-spin");
        loadBoqProgress(woId);
        setTimeout(function () {
            $icon.removeClass("fa-spin");
        }, 600);
    });

    // ── Copy FWO ─────────────────────────────────────────────────────────────
    $(document).on("click", ".btn-copy-fwo", function () {
        sourceFwoId = $(this).data("fwo-id");
        $("#modalCopyFwoBody").html(
            '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data FWO...</div>',
        );
        $("#btnConfirmCopyFwo").prop("disabled", true);
        new bootstrap.Modal($("#modalCopyFwo")[0]).show();

        $.when(
            $.get(window.route.fwoDetail + sourceFwoId),
            $.get(window.route.fwoBoqForCopy + sourceFwoId),
        )
            .done(function (fwoRes, boqRes) {
                const allFull = fillCopyFwoModal(fwoRes[0], boqRes[0]);
                $("#btnConfirmCopyFwo").prop("disabled", allFull);
            })
            .fail(function () {
                $("#modalCopyFwoBody").html(
                    '<div class="text-center text-danger py-4"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data FWO</div>',
                );
            });
    });

    $(document).on("click", "#btnAddCopyPersonel", function () {
        const row = $(renderCopyPersonelRow(null, null, ""));
        $("#copyFwoPersonelContainer").append(row);
        initCopyPersonelSelect2(row.find(".copy-personel-user-select"));
    });

    $(document).on("click", ".btn-remove-copy-personel", function () {
        $(this).closest(".copy-personel-row").remove();
    });

    $(document).on("click", "#btnConfirmCopyFwo", function () {
        const judul = $("#copyFwoJudul").val().trim();
        if (!judul) {
            Notify.warning("Judul pekerjaan wajib diisi");
            return;
        }

        const fwoTglMulai   = $("#copyFwoTglMulai").val();
        const fwoTglSelesai = $("#copyFwoTglSelesai").val();
        if (fwoTglMulai && fwoTglSelesai && fwoTglSelesai < fwoTglMulai) {
            Notify.warning("Tanggal selesai tidak boleh kurang dari tanggal mulai");
            return;
        }

        const personels = [];
        $("#copyFwoPersonelContainer .copy-personel-row").each(function () {
            const userId = $(this).find(".copy-personel-user-select").val();
            const role = $(this).find(".copy-personel-role-select").val();
            if (userId) {
                personels.push({ id_user: parseInt(userId), role: role || null });
            }
        });

        const sections = [];
        $(".copy-boq-qty").each(function () {
            const boqId = $(this).data("boq-id");
            const qty = parseInt($(this).val());
            if (boqId && !isNaN(qty) && qty > 0) {
                sections.push({ id_boq: parseInt(boqId), qty: qty });
            }
        });

        const payload = {
            judul_pekerjaan: judul,
            tanggal_mulai: $("#copyFwoTglMulai").val() || null,
            tanggal_selesai: $("#copyFwoTglSelesai").val() || null,
            keterangan: $("#copyFwoKeterangan").val() || null,
            personels,
            sections,
        };

        const $btn = $(this);
        $btn.prop("disabled", true).html(
            '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...',
        );

        $.ajax({
            url: window.route.fwoDuplicate + sourceFwoId + "/duplicate",
            method: "POST",
            contentType: "application/json",
            headers: { "X-CSRF-TOKEN": window.route.csrf },
            data: JSON.stringify(payload),
            success: function (res) {
                Notify.success("FWO berhasil disalin: " + res.no_fwo);
                bootstrap.Modal.getInstance("#modalCopyFwo").hide();
                if (currentBoqWoId) loadBoqProgress(currentBoqWoId);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || "Gagal menyalin FWO");
                $btn.prop("disabled", false).html(
                    '<i class="fa-solid fa-copy me-1"></i> Buat Salinan',
                );
            },
        });
    });

    $("#modalCopyFwo").on("hidden.bs.modal", function () {
        sourceFwoId = null;
        $("#btnConfirmCopyFwo")
            .prop("disabled", false)
            .html('<i class="fa-solid fa-copy me-1"></i> Buat Salinan');
    });

    // ── Selesaikan WO ────────────────────────────────────────────────────────
    $(document).on("click", "#btnSelesaikanWo", function () {
        const woId = $(this).data("wo-id");
        Notify.confirm("Selesaikan WO ini? Semua output harus berstatus siap.", function () {
            $.ajax({
                url: window.route.update + woId + "/complete",
                method: "POST",
                headers: { "X-CSRF-TOKEN": window.route.csrf },
                success: function (res) {
                    Notify.success(res.message || "Work Order berhasil diselesaikan");
                    page.loadDetail(parseInt(woId));
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: "warning",
                        title: "Tidak dapat diselesaikan",
                        text: xhr.responseJSON?.message || "Terjadi kesalahan",
                    });
                },
            });
        });
    });

    // ── Copy WO ──────────────────────────────────────────────────────────────
    $(document).on("click", ".btn-copy-wo", function () {
        sourceWoId = $(this).data("wo-id");
        $("#modalCopyWoBody").html(
            '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data WO...</div>',
        );
        $("#btnConfirmCopyWo").prop("disabled", true);
        new bootstrap.Modal($("#modalCopyWo")[0]).show();

        $.get(window.route.woDetail + sourceWoId + "/detail")
            .done(function (wo) {
                fillCopyWoModal(wo);
                $("#btnConfirmCopyWo").prop("disabled", false);
            })
            .fail(function () {
                $("#modalCopyWoBody").html(
                    '<div class="text-center text-danger py-4"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data WO</div>',
                );
            });
    });

    $(document).on("click", "#btnAddCopyWoBoq", function () {
        addCopyWoBoqRow();
    });

    $(document).on("click", ".btn-remove-copy-wo-boq", function () {
        $(this).closest(".copy-wo-boq-row").remove();
    });

    $(document).on("click", "#btnConfirmCopyWo", function () {
        const judul = $("#copyWoJudul").val().trim();
        if (!judul) {
            Notify.warning("Judul pekerjaan wajib diisi");
            return;
        }

        const tglMulai   = $("#copyWoTglMulai").val();
        const tglSelesai = $("#copyWoTglSelesai").val();
        $("#copyWoTglSelesaiError").remove();
        if (tglMulai && tglSelesai && tglSelesai < tglMulai) {
            $("#copyWoTglSelesai").after('<div id="copyWoTglSelesaiError" class="text-danger mt-1" style="font-size:12px;"><i class="fa-solid fa-circle-exclamation me-1"></i>Tanggal selesai tidak boleh lebih kecil dari tanggal mulai</div>');
            return;
        }

        // Collect BOQ rows
        const boq = [];
        $(".copy-wo-boq-row").each(function() {
            const $row = $(this);
            const tpSelect = $row.find('.copy-wo-boq-tp-select');
            const idTp = tpSelect.length ? tpSelect.val() : $row.data('id-testing-point');
            if (!idTp) return;
            const qty = $row.find('.copy-wo-boq-qty').val();
            let testingItemIds = [];
            try { testingItemIds = JSON.parse($row.attr('data-testing-item-ids') || '[]'); } catch(e) {}
            boq.push({
                id_testing_point:      parseInt(idTp),
                qty:                   qty ? parseInt(qty) : null,
                satuan:                $row.attr('data-satuan') || null,
                harga:                 $row.attr('data-harga') || null,
                keterangan:            $row.attr('data-keterangan') || null,
                item_produk_alternate: $row.attr('data-item-produk-alternate') || null,
                testing_item_ids:      testingItemIds,
            });
        });

        const payload = {
            judul_pekerjaan:             judul,
            tanggal_mulai:               tglMulai || null,
            tanggal_selesai:             tglSelesai || null,
            keterangan:                  $("#copyWoKeterangan").val() || null,
            id_pic_pelanggan_pekerjaan:  $("#copyWoPic").val() || null,
            no_urut_period:              $("#copyWoUrutan").val() || null,
            boq:                         boq,
        };

        const $btn = $(this);
        $btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: window.route.woDuplicate + sourceWoId + "/duplicate",
            method: "POST",
            contentType: "application/json",
            headers: { "X-CSRF-TOKEN": window.route.csrf },
            data: JSON.stringify(payload),
            success: function (res) {
                Notify.success("WO berhasil disalin: " + res.no_wo);
                bootstrap.Modal.getInstance($("#modalCopyWo")[0]).hide();
                if (currentBoqWoId) loadBoqProgress(currentBoqWoId);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || "Gagal menyalin WO");
                $btn.prop("disabled", false).html('<i class="fa-solid fa-copy me-1"></i> Buat Salinan');
            },
        });
    });

    $(document).on("change", "#copyWoTglMulai, #copyWoTglSelesai", function () {
        $("#copyWoTglSelesaiError").remove();
    });

    $("#modalCopyWo").on("hidden.bs.modal", function () {
        sourceWoId = null;
        $("#btnConfirmCopyWo")
            .prop("disabled", false)
            .html('<i class="fa-solid fa-copy me-1"></i> Buat Salinan');
    });

    page = new CrudPageController({
        primaryKey: "id_wo",
        renderForm: renderForm,
        afterLoad: function (res) {
            currentWoData = res;
            loadBoqProgress(res.id_wo);
            loadOutputProgress(res.id_wo);
            initFpDate('#detailContent');
        },
        onSave: function (id) {
            var mulai   = $("input[name='tanggal_mulai']").val();
            var selesai = $("input[name='tanggal_selesai']").val();
            $("#editTanggalSelesaiError").remove();
            if (mulai && selesai && selesai < mulai) {
                $("input[name='tanggal_selesai']").after(
                    '<div id="editTanggalSelesaiError" class="text-danger mt-1" style="font-size:12px;">' +
                    '<i class="fa-solid fa-circle-exclamation me-1"></i>' +
                    'Tanggal selesai tidak boleh lebih kecil dari tanggal mulai</div>'
                );
                Notify.error('Periksa kembali isian tanggal WO.');
                return;
            }
            submitCrudForm({
                id: id,
                reload: page.loadDetail.bind(page),
                filepond: page.pondEdit,
            });
        },
    });

    $(document).on("change", "input[name='tanggal_mulai'], input[name='tanggal_selesai']", function () {
        var mulai   = $("input[name='tanggal_mulai']").val();
        var selesai = $("input[name='tanggal_selesai']").val();
        $("#editTanggalSelesaiError").remove();
        if (mulai && selesai && selesai < mulai) {
            $("input[name='tanggal_selesai']").after(
                '<div id="editTanggalSelesaiError" class="text-danger mt-1" style="font-size:12px;">' +
                '<i class="fa-solid fa-circle-exclamation me-1"></i>' +
                'Tanggal selesai tidak boleh lebih kecil dari tanggal mulai</div>'
            );
        }
    });

    $(document).on("click", ".btn-delete-record", function () {
        const id = $(this).data("id");
        Notify.confirmDelete("Hapus Work Order?", function () {
            $.ajax({
                url: window.route.update + id,
                method: "POST",
                data: { _token: window.route.csrf, _method: "DELETE" },
                success: function (res) {
                    Notify.success(res.message || "Data berhasil dihapus");
                    const soId = currentWoData && currentWoData.id_so;
                    setTimeout(function () {
                        window.location.href = soId ? "/sales-orders?open=" + soId : window.location.pathname;
                    }, 1000);
                },
                error: function (xhr) {
                    Notify.error(
                        xhr.responseJSON?.message || "Terjadi kesalahan",
                    );
                },
            });
        });
    });
});

// ── Copy FWO helpers ───────────────────────────────────────────────────────────
function renderCopyPersonelRow(userId, userName, role) {
    const idx = copyFwoPersonelIdx++;
    const roleOptions = ["Leader", "Driver", "Anggota"]
        .map(function (r) {
            return `<option value="${r}" ${role === r ? "selected" : ""}>${r}</option>`;
        })
        .join("");
    return `<div class="copy-personel-row d-flex align-items-start gap-2" data-idx="${idx}">
        <div style="flex:1;min-width:0;">
            <select class="form-select form-select-sm copy-personel-user-select"
                data-user-id="${userId || ""}" data-user-name="${escHtml(userName || "")}"></select>
        </div>
        <div style="width:130px;flex-shrink:0;">
            <select class="form-select form-select-sm copy-personel-role-select">
                <option value="">-- Role --</option>
                ${roleOptions}
            </select>
        </div>
        <div style="flex-shrink:0;">
            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-copy-personel" title="Hapus">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    </div>`;
}

function initCopyPersonelSelect2($select) {
    const userId = $select.data("user-id");
    const userName = $select.data("user-name");
    $select.select2({
        width: "100%",
        placeholder: "Ketik nama personel...",
        allowClear: true,
        minimumInputLength: 0,
        dropdownParent: $("#modalCopyFwo"),
        ajax: {
            url: window.route.usersSelect2,
            dataType: "json",
            delay: 200,
            data: function (p) {
                return { q: p.term };
            },
            processResults: function (d) {
                return { results: d };
            },
            cache: true,
        },
    });
    if (userId) {
        const opt = new Option(userName, userId, true, true);
        $select.append(opt).trigger("change");
    }
}

function fillCopyWoModal(wo) {
    const dateMulai   = (wo.tanggal_mulai   || "").substring(0, 10);
    const dateSelesai = (wo.tanggal_selesai || "").substring(0, 10);
    const IL = {1:'Bulanan',2:'Bimulanan',3:'Triwulan',4:'Caturwulan',6:'Semester',12:'Annual'};
    const intervalLabel = wo.interval_bulan ? (IL[wo.interval_bulan] || wo.interval_bulan + ' bln') : '— Tidak ada —';

    const siteName = wo['Site Pelanggan'] ?? '';
    const siteHtml = siteName
        ? `<div style="display:flex;align-items:center;gap:6px;min-width:0;">
               <i class="fa-solid fa-location-dot" style="color:#0891b2;font-size:11px;flex-shrink:0;"></i>
               <span style="color:#0e7490;font-weight:600;white-space:nowrap;">${escHtml(siteName)}</span>
           </div>
           <div style="width:1px;height:16px;background:#e2e8f0;flex-shrink:0;"></div>`
        : '';

    // Hitung next urutan dari site_wos
    const siteWos = wo.site_wos || [];
    const maxUrut = siteWos.reduce(function(m, w) { return Math.max(m, w.no_urut_period || 0); }, 0);
    const nextUrut = maxUrut + 1;

    // Build WO list section
    let woListHtml = '';
    if (siteWos.length > 0) {
        const IL2 = {1:'Bulanan',2:'Bimulanan',3:'Triwulan',4:'Caturwulan',6:'Semester',12:'Annual'};
        const rows = siteWos.map(function(w) {
            const tglM = w.tanggal_mulai ? w.tanggal_mulai.substring(0,10) : '—';
            const tglS = w.tanggal_selesai ? w.tanggal_selesai.substring(0,10) : '—';
            const isSelf = w.id_wo == wo.id_wo;
            const periodeLabel = w.interval_bulan && w.no_urut_period
                ? (IL2[w.interval_bulan] || w.interval_bulan + ' bln') + ' ke-' + w.no_urut_period
                : '—';
            return `<tr style="font-size:12px;${isSelf ? 'background:#eff6ff;' : ''}">
                <td style="padding:5px 10px;font-weight:600;color:#1a56db;">${escHtml(w.no_wo)}${isSelf ? ' <span style="font-size:10px;padding:1px 5px;border-radius:8px;background:#dbeafe;color:#1e40af;">sumber</span>' : ''}</td>
                <td style="padding:5px 10px;color:#374151;">${escHtml(w.judul_pekerjaan || '—')}</td>
                <td style="padding:5px 10px;color:#64748b;white-space:nowrap;">${tglM}</td>
                <td style="padding:5px 10px;color:#64748b;white-space:nowrap;">${tglS}</td>
                <td style="padding:5px 10px;white-space:nowrap;">${periodeLabel !== '—' ? `<span style="font-size:11px;padding:2px 8px;border-radius:20px;background:#eff6ff;color:#1a56db;border:1px solid #bfdbfe;"><i class="fa-solid fa-calendar-days me-1" style="font-size:10px;"></i>${escHtml(periodeLabel)}</span>` : '<span style="color:#94a3b8;">—</span>'}</td>
            </tr>`;
        }).join('');
        woListHtml = `<div class="col-12">
            <div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                <div style="background:#f8fafc;padding:7px 12px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;cursor:pointer;"
                    onclick="var b=document.getElementById('copyWoSiteList');b.style.display=b.style.display==='none'?'block':'none';">
                    <span style="font-size:12px;font-weight:600;color:#1a56db;">
                        <i class="fa-solid fa-briefcase me-1"></i>WO yang sudah ada di lokasi ini
                        <span style="font-size:11px;font-weight:500;background:#dbeafe;color:#1e40af;padding:1px 7px;border-radius:20px;margin-left:4px;">${siteWos.length}</span>
                    </span>
                    <i class="fa-solid fa-chevron-down" style="font-size:10px;color:#94a3b8;"></i>
                </div>
                <div id="copyWoSiteList" style="display:none;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead style="background:#f8fafc;">
                            <tr style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">
                                <th style="padding:5px 10px;">No WO</th>
                                <th style="padding:5px 10px;">Judul</th>
                                <th style="padding:5px 10px;">Tgl Mulai</th>
                                <th style="padding:5px 10px;">Tgl Selesai</th>
                                <th style="padding:5px 10px;">Periode</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
    }

    const urutanRow = wo.interval_bulan ? `
        <div class="col-md-2">
            <label class="form-label">Urutan ke-</label>
            <input type="number" id="copyWoUrutan" class="form-control form-control-sm" value="${nextUrut}" min="1" style="width:80px;">
        </div>` : '';

    const picColClass = wo.interval_bulan ? 'col-md-4' : 'col-md-5';

    $("#modalCopyWoBody").html(`
        <div style="position:sticky;top:0;z-index:10;background:#fff;border-bottom:2px solid #e2e8f0;padding:10px 16px;margin:-16px -16px 16px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
            <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:13px;">
                ${siteHtml}
                <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                    <i class="fa-solid fa-file-contract" style="color:#1a56db;font-size:11px;flex-shrink:0;"></i>
                    <span style="font-weight:700;color:#1a56db;white-space:nowrap;">${escHtml(wo.no_so ?? '—')}</span>
                </div>
                <div style="width:1px;height:16px;background:#e2e8f0;flex-shrink:0;"></div>
                <div style="display:flex;align-items:center;gap:6px;min-width:0;flex:1;">
                    <i class="fa-solid fa-file-lines" style="color:#374151;font-size:11px;flex-shrink:0;"></i>
                    <span style="color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(wo.judul_pekerjaan ?? '—')}</span>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Sales Order</label>
                <input type="text" class="form-control form-control-sm" value="${escHtml(wo.no_so ?? '—')}" disabled>
            </div>
            <div class="col-12">
                <label class="form-label">Judul Order <span class="text-danger">*</span></label>
                <input type="text" id="copyWoJudul" class="form-control form-control-sm"
                    value="${escHtml(wo.judul_pekerjaan ?? '')}" placeholder="Judul pekerjaan">
            </div>
            <div class="col-md-5">
                <label class="form-label">Pelanggan</label>
                <input type="text" class="form-control form-control-sm" value="${escHtml(wo.Pelanggan ?? '—')}" disabled>
            </div>
            <div class="col-md-5">
                <label class="form-label">Pelanggan Site</label>
                <input type="text" class="form-control form-control-sm" value="${escHtml(wo['Site Pelanggan'] ?? '—')}" disabled>
            </div>
            <div class="col-md-2">
                <label class="form-label">Frekuensi</label>
                <input type="text" class="form-control form-control-sm" value="${escHtml(intervalLabel)}" disabled>
            </div>
            ${urutanRow}
            <div class="${picColClass}">
                <label class="form-label">PIC Pekerjaan</label>
                <select id="copyWoPic" class="form-select form-select-sm"></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Mulai</label>
                <input type="text" id="copyWoTglMulai" name="tanggal_mulai" class="form-control form-control-sm fp-date" value="${dateMulai}" autocomplete="off">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Selesai</label>
                <input type="text" id="copyWoTglSelesai" name="tanggal_selesai" class="form-control form-control-sm fp-date" value="${dateSelesai}" autocomplete="off">
            </div>
            ${woListHtml}
            <div class="col-12">
                <label class="form-label">Keterangan</label>
                <textarea id="copyWoKeterangan" class="form-control form-control-sm" rows="2">${escHtml(wo.keterangan ?? '')}</textarea>
            </div>
        </div>
    `);

    $("#copyWoPic").select2({
        width: '100%',
        placeholder: 'Pilih PIC',
        allowClear: true,
        dropdownParent: $("#modalCopyWo"),
        ajax: {
            url: '/users/select2',
            dataType: 'json',
            delay: 250,
            data: (params) => ({ q: params.term }),
            processResults: (data) => ({ results: data }),
            cache: true,
        },
        escapeMarkup: (m) => m,
    });

    if (wo.id_pic_pelanggan_pekerjaan) {
        const opt = new Option(wo.nama_pic_pelanggan_pekerjaan || wo.id_pic_pelanggan_pekerjaan, wo.id_pic_pelanggan_pekerjaan, true, true);
        $("#copyWoPic").append(opt).trigger("change");
    }

    // Render BOQ section
    renderCopyWoBoq(wo.boq_items || []);

    initFpDate('#modalCopyWoBody');
}

let copyWoBoqIdx = 0;

function renderCopyWoBoq(sourceItems) {
    const hasSource = sourceItems.length > 0;
    let sourceHtml = '';
    if (hasSource) {
        sourceHtml = sourceItems.map(function(item, i) {
            const satuan = item.satuan ? escHtml(item.satuan) : '';
            return `<div class="copy-wo-boq-row d-flex align-items-center gap-2 p-2"
                style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;"
                data-id-testing-point="${item.id_testing_point}"
                data-testing-item-ids="${escHtml(JSON.stringify(item.testing_item_ids || []))}"
                data-satuan="${escHtml(item.satuan || '')}"
                data-harga="${item.harga || ''}"
                data-keterangan="${escHtml(item.keterangan || '')}"
                data-item-produk-alternate="${escHtml(item.item_produk_alternate || '')}">
                <div style="flex:1;min-width:0;">
                    <div class="fw-semibold" style="font-size:12px;color:#1a56db;">${escHtml(item.point_name)}</div>
                    ${satuan ? `<div style="font-size:11px;color:#64748b;">Satuan: ${satuan}</div>` : ''}
                </div>
                <div style="width:90px;flex-shrink:0;">
                    <input type="number" class="form-control form-control-sm text-end copy-wo-boq-qty"
                        value="${item.qty || ''}" min="1" placeholder="qty"
                        style="font-size:12px;">
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-copy-wo-boq py-0 px-2" title="Hapus">
                    <i class="fa-solid fa-times" style="font-size:11px;"></i>
                </button>
            </div>`;
        }).join('');
    }

    const boqSectionHtml = `<div class="col-12" id="copyWoBoqSection">
        <label class="form-label fw-semibold">
            <i class="fa-solid fa-layer-group me-1 text-success"></i>BOQ
            ${hasSource ? `<span style="font-size:11px;font-weight:400;color:#64748b;margin-left:4px;">(dari WO sumber — edit qty sesuai kebutuhan)</span>` : ''}
        </label>
        <div id="copyWoBoqContainer" class="d-flex flex-column gap-2">
            ${sourceHtml || '<div style="font-size:12px;color:#94a3b8;padding:4px 0;"><i class="fa-solid fa-circle-minus me-1"></i>Belum ada BOQ di WO ini</div>'}
        </div>
        <button type="button" id="btnAddCopyWoBoq" class="btn btn-outline-success btn-sm mt-2" style="font-size:12px;">
            <i class="fa-solid fa-plus me-1"></i> Tambah Item BOQ
        </button>
    </div>`;

    // Insert before keterangan
    const $ket = $("#copyWoKeterangan").closest(".col-12");
    $ket.before(boqSectionHtml);
}

function addCopyWoBoqRow(tpId, tpText, satuan) {
    const idx = copyWoBoqIdx++;
    const row = $(`<div class="copy-wo-boq-row d-flex align-items-center gap-2 p-2"
        style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;"
        data-id-testing-point="" data-testing-item-ids="[]" data-satuan="" data-harga="" data-keterangan="" data-item-produk-alternate="">
        <div style="flex:1;min-width:0;">
            <select class="form-select form-select-sm copy-wo-boq-tp-select" style="font-size:12px;"></select>
        </div>
        <div style="width:90px;flex-shrink:0;">
            <input type="number" class="form-control form-control-sm text-end copy-wo-boq-qty"
                min="1" placeholder="qty" style="font-size:12px;">
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-copy-wo-boq py-0 px-2" title="Hapus">
            <i class="fa-solid fa-times" style="font-size:11px;"></i>
        </button>
    </div>`);

    $("#copyWoBoqContainer").append(row);

    const $sel = row.find('.copy-wo-boq-tp-select');
    $sel.select2({
        width: '100%',
        placeholder: 'Pilih testing point...',
        allowClear: true,
        minimumInputLength: 0,
        dropdownParent: $("#modalCopyWo"),
        ajax: {
            url: window.route.tpSelect2,
            dataType: 'json',
            delay: 250,
            data: (p) => ({ q: p.term }),
            processResults: (d) => ({ results: d }),
            cache: true,
        },
        escapeMarkup: (m) => m,
    });

    $sel.on('select2:select', function(e) {
        const d = e.params.data;
        row.attr('data-id-testing-point', d.id || '');
    });
}

function fillCopyFwoModal(fwo, boqs) {
    copyFwoPersonelIdx = 0;
    const dateMulai = (fwo.tanggal_mulai || "").substring(0, 10);
    const dateSelesai = (fwo.tanggal_selesai || "").substring(0, 10);

    let personelHtml = (fwo.personels || [])
        .map(function (p) {
            return renderCopyPersonelRow(p.id_user, p.user_name, p.role);
        })
        .join("");
    if (!personelHtml) {
        personelHtml = renderCopyPersonelRow(null, null, "");
    }

    let boqHtml = "";
    if (boqs && boqs.length > 0) {
        function renderBoqCard(sec) {
            const unallocated = sec.unallocated_qty ?? 0;
            const isFull = unallocated <= 0;
            const satuan = sec.satuan ? " " + escHtml(sec.satuan) : "";
            const defaultQty = isFull
                ? ""
                : Math.min(sec.qty || 0, unallocated);

            const sisaHtml = isFull
                ? `<span style="font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px;background:#dcfce7;color:#166534;">Terpenuhi ✓</span>`
                : `<span style="font-size:10px;color:#64748b;">Sisa: <strong style="color:${unallocated < (sec.qty || 0) ? "#dc2626" : "#1d4ed8"};">${unallocated}${satuan}</strong></span>`;

            const input = isFull
                ? `<div style="width:90px;text-align:center;font-size:11px;color:#94a3b8;">—</div>`
                : `<input type="number" class="form-control form-control-sm text-end copy-boq-qty"
                    data-boq-id="${sec.id_boq}" value="${defaultQty}"
                    min="1" max="${unallocated}" placeholder="qty"
                    oninput="this.classList.toggle('is-invalid', this.value > ${unallocated})">`;

            const fullNotice = isFull
                ? `<div style="font-size:11px;color:#dc2626;margin-top:4px;">
                       <i class="fa-solid fa-circle-xmark me-1"></i>Qty sudah terpenuhi, tidak bisa ditambahkan
                   </div>`
                : "";

            return `<div class="d-flex align-items-center gap-3 p-2"
                style="background:${isFull ? "#f0fdf4" : "#f8fafc"};border:1px solid ${isFull ? "#bbf7d0" : "#e2e8f0"};border-radius:8px;">
                <div style="flex:1;min-width:0;">
                    <div class="fw-semibold small">${escHtml(sec.point_name)}</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span style="font-size:11px;color:#64748b;">Total BOQ: ${sec.boq_qty}${satuan}</span>
                        ${sisaHtml}
                    </div>
                    ${fullNotice}
                </div>
                <div style="width:90px;">${input}</div>
            </div>`;
        }

        // Pisahkan: item dari source FWO (qty !== null) vs item tambahan dari WO
        const sourceItems = boqs.filter(function (s) {
            return s.qty !== null;
        });
        const extraItems = boqs.filter(function (s) {
            return s.qty === null;
        });

        const sourceRows = sourceItems.map(renderBoqCard).join("");
        const extraRows = extraItems.map(renderBoqCard).join("");

        const divider =
            extraItems.length > 0
                ? `<div style="display:flex;align-items:center;gap:10px;margin:4px 0;">
                   <hr style="flex:1;border-color:#e2e8f0;margin:0;">
                   <span style="font-size:11px;color:#94a3b8;white-space:nowrap;font-weight:600;letter-spacing:.3px;">
                       <i class="fa-solid fa-plus me-1" style="font-size:10px;"></i>Tambahkan item lainnya
                   </span>
                   <hr style="flex:1;border-color:#e2e8f0;margin:0;">
               </div>
               <div class="d-flex flex-column gap-2">${extraRows}</div>`
                : "";

        const allFull = boqs.every(function (sec) {
            return (sec.unallocated_qty ?? 0) <= 0;
        });
        const allFullBanner = allFull
            ? `<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:8px;font-size:12px;color:#dc2626;display:flex;align-items:center;gap:8px;">
                   <i class="fa-solid fa-triangle-exclamation"></i>
                   <span>Semua BOQ item sudah terpenuhi. FWO baru tidak dapat menyertakan BOQ pada WO ini.</span>
               </div>`
            : "";

        boqHtml = `<div class="mb-3">
            <label class="form-label fw-semibold">Qty per BOQ Item</label>
            ${allFullBanner}
            <div class="d-flex flex-column gap-2">
                ${sourceRows}
            </div>
            ${divider}
        </div>`;
    }

    $("#modalCopyFwoBody").html(`
        <div style="position:sticky;top:0;z-index:10;background:#fff;border-bottom:2px solid #e2e8f0;padding:10px 16px;margin:-16px -16px 16px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
            <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:13px;">
                <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                    <i class="fa-solid fa-location-dot" style="color:#0891b2;font-size:11px;flex-shrink:0;"></i>
                    <span style="color:#0e7490;font-weight:600;white-space:nowrap;">${escHtml(fwo.wo_site_name || '—')}</span>
                </div>
                <div style="width:1px;height:16px;background:#e2e8f0;flex-shrink:0;"></div>
                <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                    <i class="fa-solid fa-briefcase" style="color:#1a56db;font-size:11px;flex-shrink:0;"></i>
                    <span style="font-weight:700;color:#1a56db;white-space:nowrap;">${escHtml(fwo.wo_no_wo || '—')}</span>
                </div>
                <div style="width:1px;height:16px;background:#e2e8f0;flex-shrink:0;"></div>
                <div style="display:flex;align-items:center;gap:6px;min-width:0;flex:1;">
                    <i class="fa-solid fa-file-lines" style="color:#374151;font-size:11px;flex-shrink:0;"></i>
                    <span style="color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(fwo.wo_judul_pekerjaan || '—')}</span>
                </div>
            </div>
        </div>
        <div class="alert alert-light border mb-3 py-2" style="font-size:12px;">
            <i class="fa-solid fa-copy me-1 text-primary"></i>
            Menyalin dari: <strong>${escHtml(fwo.no_fwo)}</strong>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Judul Pekerjaan <span class="text-danger">*</span></label>
            <input type="text" id="copyFwoJudul" class="form-control"
                value="${escHtml(fwo.judul_pekerjaan || "")}" maxlength="500">
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tanggal Mulai</label>
                <input type="text" id="copyFwoTglMulai" name="tanggal_mulai" class="form-control fp-date" value="${dateMulai}" autocomplete="off">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tanggal Selesai</label>
                <input type="text" id="copyFwoTglSelesai" name="tanggal_selesai" class="form-control fp-date" value="${dateSelesai}" autocomplete="off">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Keterangan</label>
            <textarea id="copyFwoKeterangan" class="form-control" rows="2">${escHtml(fwo.keterangan || "")}</textarea>
        </div>
        ${boqHtml}
        <label class="form-label fw-semibold">Personel</label>
        <div id="copyFwoPersonelContainer" class="d-flex flex-column gap-2 mb-2">${personelHtml}</div>
        <button type="button" id="btnAddCopyPersonel" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Tambah Personel
        </button>
        <div style="height:220px;"></div>
    `);

    initFpDate("#modalCopyFwoBody");

    $("#copyFwoPersonelContainer .copy-personel-user-select").each(function () {
        initCopyPersonelSelect2($(this));
    });

    const allFull =
        boqs &&
        boqs.length > 0 &&
        boqs.every(function (sec) {
            return (sec.unallocated_qty ?? 0) <= 0;
        });
    return allFull;
}

// ── BUDGET (WO level) ───────────────────────────────────────────────────────────
// Sistem terpisah dari FWO Budget (fwo_budgets/fwo_budget_items/fwo_budget_actuals)
// — WO bisa punya Budget Plan sendiri tanpa perlu FWO. Lock pakai work_orders.status,
// bukan fieldworks.status. Lihat Project Reference bagian "Budget WO".

function woFmtRp(val) {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
}

function woFmtTgl(val) {
    if (!val) return '-';
    const d = new Date(val);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function loadWoBudgetData(idWo) {
    const $wrap = $('#woBudgetWrap');
    $wrap.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');

    $.get(`/wo-budgets/${idWo}/list`)
        .done(function (res) {
            const plans    = res.data || [];
            const woStatus = res.wo_status || '';
            const isLocked = woStatus === 'completed';

            if (!plans.length) {
                $wrap.html(`<div class="text-center text-muted py-4" style="font-size:13px;">
                    <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                    Belum ada Budget Plan untuk WO ini
                </div>`);
                return;
            }
            window._woBudgetPlans = plans;
            $wrap.html(renderWoBudgetList(plans, isLocked));
        })
        .fail(function () {
            $wrap.html('<div class="text-center text-danger py-3">Gagal memuat data budget.</div>');
        });
}

function renderWoBudgetList(plans, isLocked) {
    const cards = plans.map(function (p) {
        const selisih      = p.total_budget - p.total_actual;
        const selisihColor = selisih >= 0 ? '#15803d' : '#dc2626';
        const planLocked   = isLocked || p.status === 'completed';
        const statusBadge  = p.status === 'completed'
            ? `<span class="badge ms-1" style="background:#dcfce7;color:#15803d;font-size:10px;font-weight:500;"><i class="fa-solid fa-lock me-1"></i>Completed</span>`
            : `<span class="badge ms-1" style="background:#eff6ff;color:#1d4ed8;font-size:10px;font-weight:500;">Open</span>`;

        const itemRows = p.items.map(function (item, itemIdx) {
            const actualTotal = item.nominal_actual || 0;
            const sel         = item.nominal_budget - actualTotal;
            const struks      = item.actuals || [];
            const struksHtml  = struks.map(function (a) {
                const files = JSON.parse(a.attachments || '[]');
                const fileLinks = files.map(function (f) {
                    return `<a href="/storage/${f}" target="_blank" class="badge bg-light text-dark border me-1" style="font-size:10px;">
                        <i class="fa-solid fa-file me-1"></i>${f.split('/').pop()}
                    </a>`;
                }).join('');
                const verifBadge = {
                    menunggu:  `<span class="badge" style="background:#fef3c7;color:#92400e;font-size:10px;font-weight:500;">Menunggu</span>`,
                    disetujui: `<span class="badge" style="background:#dcfce7;color:#15803d;font-size:10px;font-weight:500;">Disetujui</span>`,
                    ditolak:   `<span class="badge" style="background:#fee2e2;color:#dc2626;font-size:10px;font-weight:500;">Ditolak</span>`,
                }[a.status_verifikasi || 'menunggu'];

                const catatanVerif = a.catatan_verifikasi
                    ? `<span class="text-muted ms-1" style="font-size:10px;">— ${escHtml(a.catatan_verifikasi)}</span>` : '';

                return `<div class="d-flex align-items-start gap-2 mb-1 p-1 rounded" style="background:#f8fafc;font-size:11px;">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-semibold">${woFmtRp(a.nominal_actual)}</span>
                            ${a.keterangan ? `<span class="text-muted">— ${escHtml(a.keterangan)}</span>` : ''}
                            ${verifBadge}${catatanVerif}
                        </div>
                        <div class="mt-1">${fileLinks}</div>
                    </div>
                    ${!planLocked ? `<div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 btn-wo-actual-edit"
                            data-id="${a.id_actual}" data-wo-id="${p.id_wo || ''}" style="font-size:10px;" data-no-disable title="Edit">
                            <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 btn-wo-actual-delete"
                            data-id="${a.id_actual}" style="font-size:10px;" data-no-disable title="Hapus">
                            <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
                        </button>
                    </div>` : ''}
                </div>`;
            }).join('');

            const caBadge = item.is_cash_advance
                ? `<span class="badge ms-1" style="background:#eff6ff;color:#1d4ed8;font-size:10px;font-weight:500;border:1px solid #bfdbfe;">CA</span>`
                : '';

            const categoryCell = item.nama_category
                ? `<span style="font-size:11px;color:#6b7280;background:#f1f5f9;padding:1px 6px;border-radius:4px;">${escHtml(item.nama_category)}</span>`
                : `<span class="text-muted" style="font-size:11px;">—</span>`;

            return `<tr>
                <td style="width:36px;text-align:center;color:#94a3b8;font-size:11px;">${itemIdx + 1}</td>
                <td>${categoryCell}</td>
                <td><span class="fw-semibold">${escHtml(item.nama_account)}</span>${caBadge}</td>
                <td style="color:#1d4ed8;font-weight:600;">${woFmtRp(item.nominal_budget)}</td>
                <td>
                    ${struksHtml || '<span class="text-muted" style="font-size:11px;">Belum ada realisasi</span>'}
                </td>
                <td style="color:${selisihColor};font-weight:600;">${woFmtRp(actualTotal)}</td>
                <td style="color:${sel >= 0 ? '#15803d' : '#dc2626'};font-weight:600;">${woFmtRp(sel)}</td>
                <td class="text-center">
                    ${!planLocked ? `<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-wo-actual-add"
                        data-id-budget-item="${item.id_budget_item}" data-wo-id="${p.id_wo || ''}"
                        data-nominal-budget="${item.nominal_budget}"
                        data-account-name="${escHtml(item.kode_account + ' – ' + item.nama_account)}"
                        data-no-disable title="Catat Pengeluaran" style="font-size:11px;">
                        <i class="fa-solid fa-plus" style="color:#0f766e;"></i>
                    </button>` : ''}
                </td>
            </tr>`;
        }).join('');

        const collapseId = `woBudgetCollapse_${p.id_budget}`;
        return `<div class="mb-3 border rounded">
            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                style="background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:calc(0.375rem - 1px) calc(0.375rem - 1px) 0 0;">
                <div class="d-flex align-items-center flex-wrap gap-1">
                    <span class="fw-bold" style="font-size:13px;">${escHtml(p.label)}</span>
                    ${statusBadge}
                    ${(p.tanggal_mulai || p.tanggal_selesai) ? `<span class="text-muted ms-1" style="font-size:11px;">
                        <i class="fa-regular fa-calendar me-1"></i>${woFmtTgl(p.tanggal_mulai)}${p.tanggal_selesai ? ' – ' + woFmtTgl(p.tanggal_selesai) : ''}
                    </span>` : ''}
                    ${p.keterangan ? `<span class="text-muted ms-1" style="font-size:11px;">· ${escHtml(p.keterangan)}</span>` : ''}
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span style="font-size:12px;">Budget: <b style="color:#1d4ed8;">${woFmtRp(p.total_budget)}</b></span>
                    <span style="font-size:12px;">Actual: <b>${woFmtRp(p.total_actual)}</b></span>
                    <span style="font-size:12px;">Selisih: <b style="color:${selisihColor};">${woFmtRp(selisih)}</b></span>
                    ${p.dokumen_realisasi ? `
                    <a href="/storage/${p.dokumen_realisasi}" target="_blank"
                        class="btn btn-sm py-0 px-2" data-no-disable
                        style="font-size:11px;background:#f0fdf4;color:#15803d;border:1px solid #86efac;"
                        title="Download Dokumen Realisasi yang sudah ditandatangani">
                        <i class="fa-solid fa-file-arrow-down me-1"></i>Dok. Realisasi
                    </a>` : ''}
                    ${!planLocked && p.items.some(i => (i.actuals || []).length > 0) ? `
                    <button type="button" class="btn btn-sm btn-plan-verify btn-wo-bulk-verify"
                        data-id-budget="${p.id_budget}" data-label="${escHtml(p.label)}"
                        data-no-disable>
                        <i class="fa-solid fa-check-double me-1"></i>Verifikasi
                    </button>` : ''}
                    <div class="dropdown" data-no-disable>
                        <button type="button" class="btn btn-sm btn-plan-icon"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            data-no-disable>
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="font-size:12px;min-width:160px;">
                            <li>
                                <a class="dropdown-item" href="/wo-budgets/${p.id_budget}/print" target="_blank">
                                    <i class="fa-solid fa-print me-2 text-muted"></i>Print Serah Terima
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/wo-budgets/${p.id_budget}/print-realisasi" target="_blank">
                                    <i class="fa-solid fa-file-invoice me-2 text-muted"></i>Print Realisasi
                                </a>
                            </li>
                            ${!planLocked ? `
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button type="button" class="dropdown-item btn-wo-budget-edit"
                                    data-id="${p.id_budget}" data-no-disable>
                                    <i class="fa-solid fa-pen-to-square me-2" style="color:#1e40af;"></i>Edit Plan
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item btn-wo-budget-close"
                                    data-id="${p.id_budget}" data-label="${escHtml(p.label)}" data-no-disable>
                                    <i class="fa-solid fa-circle-check me-2" style="color:#15803d;"></i>Selesaikan Plan
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button type="button" class="dropdown-item btn-wo-budget-delete"
                                    data-id="${p.id_budget}" data-label="${escHtml(p.label)}" data-no-disable>
                                    <i class="fa-solid fa-trash me-2" style="color:#dc2626;"></i>Hapus Plan
                                </button>
                            </li>` : ''}
                        </ul>
                    </div>
                    <button type="button" class="btn btn-sm btn-plan-icon btn-wo-budget-collapse"
                        data-target="#${collapseId}"
                        data-no-disable title="Sembunyikan / Tampilkan">
                        <i class="fa-solid fa-chevron-up"></i>
                    </button>
                </div>
            </div>
            <div id="${collapseId}">
                <div class="table-responsive">
                    <table class="pm-table">
                        <thead>
                            <tr>
                                <th style="width:36px;">No</th>
                                <th style="min-width:110px;">Category</th>
                                <th>Account</th>
                                <th style="min-width:130px;">Budget</th>
                                <th style="min-width:200px;">Realisasi</th>
                                <th style="min-width:130px;">Total Actual</th>
                                <th style="min-width:110px;">Selisih</th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody>${itemRows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
    }).join('');

    if (!plans.length) return cards;

    const grandBudget  = plans.reduce(function (s, p) { return s + (p.total_budget || 0); }, 0);
    const grandActual  = plans.reduce(function (s, p) { return s + (p.total_actual || 0); }, 0);
    const grandSelisih = grandBudget - grandActual;
    const selColor     = grandSelisih >= 0 ? '#15803d' : '#dc2626';
    const selLabel     = grandSelisih >= 0 ? 'Surplus' : 'Defisit';

    const summary = `
    <div class="d-flex align-items-center gap-4 px-3 py-2 mb-3 rounded"
        style="background:#f8fafc;border:1px solid #e2e8f0;font-size:12px;">
        <span style="color:#64748b;font-weight:500;">Ringkasan ${plans.length} Plan:</span>
        <span>Total Budget: <b style="color:#1d4ed8;">${woFmtRp(grandBudget)}</b></span>
        <span>Total Realisasi: <b>${woFmtRp(grandActual)}</b></span>
        <span>${selLabel}: <b style="color:${selColor};">${woFmtRp(Math.abs(grandSelisih))}</b></span>
    </div>`;

    return summary + cards;
}

function buildWoBudgetItemRow(item) {
    return `<tr class="wo-budget-item-row">
        <td>
            <input type="hidden" class="bi-id" value="${item ? item.id_budget_item : ''}">
            <select class="form-select form-select-sm bi-account" data-no-disable style="min-width:160px;">
                ${item ? `<option value="${item.id_account}" selected>${escHtml(item.nama_account)}</option>` : '<option value="">Pilih Account</option>'}
            </select>
        </td>
        <td>
            <input type="text" inputmode="numeric"
                class="form-control form-control-sm input-num-mask input-num-int bi-nominal"
                value="${item ? Number(item.nominal_budget).toLocaleString('en-US') : ''}"
                placeholder="0" data-no-disable>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm bi-keterangan"
                value="${item ? escHtml(item.keterangan || '') : ''}"
                placeholder="Opsional" data-no-disable>
        </td>
        <td class="text-center">
            <div class="form-check d-flex justify-content-center mb-0">
                <input type="checkbox" class="form-check-input bi-ca" data-no-disable
                    ${item && item.is_cash_advance ? 'checked' : ''}
                    title="Cash Advance" style="width:18px;height:18px;cursor:pointer;">
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 btn-wo-remove-row"
                data-no-disable title="Hapus baris" style="font-size:11px;">
                <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
            </button>
        </td>
    </tr>`;
}

function initWoBudgetAccountSelect2() {
    $('#woBudgetItemsBody .bi-account').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) return;
        $(this).select2({
            dropdownParent: $('#woBudgetPlanModal'),
            placeholder: 'Pilih Account',
            allowClear: false,
            width: '100%',
            minimumInputLength: 0,
            ajax: {
                url: '/budget-accounts/select2',
                dataType: 'json',
                delay: 200,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data }; },
                cache: true,
            },
        });
    });
}

function recalcWoBudgetTotal() {
    let total = 0;
    $('#woBudgetItemsBody .bi-nominal').each(function () {
        total += parseInt($(this).val().replace(/,/g, ''), 10) || 0;
    });
    $('#woBudgetModalTotal').text(woFmtRp(total));
}

function openWoBudgetModal(idWo, budgetData) {
    const isEdit = !!budgetData;
    $('#woBudgetPlanModalLabel').html(
        `<i class="fa-solid fa-wallet me-2" style="color:#0f766e;"></i>${isEdit ? 'Edit' : 'Tambah'} Budget Plan`
    );
    $('#woBudgetModal-id').val(isEdit ? budgetData.id_budget : '');
    $('#woBudgetModal-id-wo').val(idWo);
    $('#woBudgetModal-label').val(isEdit ? budgetData.label : '');
    $('#woBudgetModal-keterangan').val(isEdit ? (budgetData.keterangan || '') : '');

    const $body = $('#woBudgetItemsBody').empty();
    const items = isEdit ? (budgetData.items || []) : [];
    if (items.length) {
        items.forEach(function (item) { $body.append(buildWoBudgetItemRow(item)); });
    } else {
        $body.append(buildWoBudgetItemRow(null));
    }

    initWoBudgetAccountSelect2();
    initNumericMask(document.getElementById('woBudgetPlanModal'));
    recalcWoBudgetTotal();

    const modal = new bootstrap.Modal(document.getElementById('woBudgetPlanModal'));
    modal.show();

    document.getElementById('woBudgetPlanModal').addEventListener('shown.bs.modal', function handler() {
        const elMulai   = document.getElementById('woBudgetModal-tgl-mulai');
        const elSelesai = document.getElementById('woBudgetModal-tgl-selesai');
        if (elMulai._flatpickr)   elMulai._flatpickr.destroy();
        if (elSelesai._flatpickr) elSelesai._flatpickr.destroy();

        const fpMulai = flatpickr(elMulai, {
            dateFormat: 'Y-m-d',
            allowInput: false,
            defaultDate: isEdit ? (budgetData.tanggal_mulai || null) : null,
        });
        const fpSelesai = flatpickr(elSelesai, {
            dateFormat: 'Y-m-d',
            allowInput: false,
            defaultDate: isEdit ? (budgetData.tanggal_selesai || null) : null,
            minDate: isEdit && budgetData.tanggal_mulai ? budgetData.tanggal_mulai : null,
        });

        fpMulai.config.onChange.push(function (dates) {
            fpSelesai.set('minDate', dates[0] || null);
            if (fpSelesai.selectedDates[0] && fpSelesai.selectedDates[0] < dates[0]) {
                fpSelesai.clear();
            }
        });

        this.removeEventListener('shown.bs.modal', handler);
    }, { once: true });
}

// ── Tombol Tambah Budget Plan ──
$(document).off('click.wobudget', '.btn-wo-budget-add').on('click.wobudget', '.btn-wo-budget-add', function () {
    const idWo = $(this).data('wo-id');
    openWoBudgetModal(idWo, null);
});

// ── Tambah baris item di modal ──
$(document).off('click.wobudget', '#btnWoBudgetAddRow').on('click.wobudget', '#btnWoBudgetAddRow', function () {
    $('#woBudgetItemsBody').append(buildWoBudgetItemRow(null));
    initWoBudgetAccountSelect2();
    initNumericMask(document.getElementById('woBudgetPlanModal'));
});

// ── Hapus baris item di modal ──
$(document).off('click.wobudget', '.btn-wo-remove-row').on('click.wobudget', '.btn-wo-remove-row', function () {
    $(this).closest('tr').remove();
    recalcWoBudgetTotal();
});

// ── Recalc total saat nominal berubah ──
$(document).off('input.wobudget', '#woBudgetItemsBody .bi-nominal').on('input.wobudget', '#woBudgetItemsBody .bi-nominal', function () {
    recalcWoBudgetTotal();
});

// ── Simpan Budget Plan ──
$(document).off('click.wobudget', '#woBudgetModal-btn-save').on('click.wobudget', '#woBudgetModal-btn-save', function () {
    const id    = $('#woBudgetModal-id').val();
    const idWo  = $('#woBudgetModal-id-wo').val();
    const label = $('#woBudgetModal-label').val().trim();

    if (!label) return Swal.fire('Perhatian', 'Label wajib diisi.', 'warning');

    const items = [];
    let valid = true;
    $('#woBudgetItemsBody .wo-budget-item-row').each(function () {
        const idAccount = $(this).find('.bi-account').val();
        const nominal   = parseInt($(this).find('.bi-nominal').val().replace(/,/g, ''), 10) || 0;
        const ket       = $(this).find('.bi-keterangan').val();
        const biId      = $(this).find('.bi-id').val();
        const isCa      = $(this).find('.bi-ca').is(':checked') ? 1 : 0;
        if (!idAccount) { valid = false; return false; }
        items.push({ id_budget_item: biId || null, id_account: idAccount, nominal_budget: nominal, keterangan: ket, is_cash_advance: isCa });
    });

    if (!valid) return Swal.fire('Perhatian', 'Semua baris harus memilih Account.', 'warning');
    if (!items.length) return Swal.fire('Perhatian', 'Minimal 1 item anggaran.', 'warning');

    const payload = {
        _token:          window.route.csrf,
        id_wo:           idWo,
        label:           label,
        keterangan:      $('#woBudgetModal-keterangan').val(),
        tanggal_mulai:   $('#woBudgetModal-tgl-mulai').val() || null,
        tanggal_selesai: $('#woBudgetModal-tgl-selesai').val() || null,
        items:           items,
    };

    const isEdit = !!id;
    const url    = isEdit ? `/wo-budgets/${id}` : '/wo-budgets';
    if (isEdit) payload._method = 'PUT';

    $('#woBudgetModal-btn-save').prop('disabled', true);
    $.post(url, payload)
        .done(function () {
            bootstrap.Modal.getInstance(document.getElementById('woBudgetPlanModal'))?.hide();
            loadWoBudgetData(idWo);
            Swal.fire({ icon: 'success', title: 'Tersimpan', timer: 1200, showConfirmButton: false });
        })
        .fail(function (xhr) {
            const errs = xhr.responseJSON?.errors;
            const msg  = errs ? Object.values(errs).flat().join('<br>') : (xhr.responseJSON?.message || 'Terjadi kesalahan.');
            Swal.fire('Gagal', msg, 'error');
        })
        .always(function () { $('#woBudgetModal-btn-save').prop('disabled', false); });
});

// ── Edit Budget Plan ──
$(document).off('click.wobudget', '.btn-wo-budget-edit').on('click.wobudget', '.btn-wo-budget-edit', function () {
    const id   = $(this).data('id');
    const idWo = $('#woTabActionsBudget .btn-wo-budget-add').data('wo-id');
    $.get(`/wo-budgets/${id}`)
        .done(function (data) { openWoBudgetModal(idWo, data); });
});

// ── Collapse / Expand panel item ──
$(document).off('click.wobudget', '.btn-wo-budget-collapse').on('click.wobudget', '.btn-wo-budget-collapse', function () {
    const $icon   = $(this).find('i');
    const $target = $($(this).data('target'));
    $target.slideToggle(180, function () {
        const visible = $target.is(':visible');
        $icon.toggleClass('fa-chevron-up', visible).toggleClass('fa-chevron-down', !visible);
    });
});

// ── Selesaikan Budget Plan ──
$(document).off('click.wobudget', '.btn-wo-budget-close').on('click.wobudget', '.btn-wo-budget-close', function () {
    const id    = $(this).data('id');
    const label = $(this).data('label');
    const idWo  = $('#woTabActionsBudget .btn-wo-budget-add').data('wo-id');

    const plans   = window._woBudgetPlans || [];
    const plan    = plans.find(function (p) { return p.id_budget == id; });
    const surplus = plan ? (plan.total_budget - plan.total_actual) : 0;

    $('#woClosePlanModal-id').val(id);
    $('#woClosePlanModal-id-wo').val(idWo);
    $('#woClosePlanModal-file').val('');
    $('#woClosePlanModalLabel').html(
        `<i class="fa-solid fa-circle-check me-2" style="color:#15803d;"></i>Selesaikan Plan — ${escHtml(label)}`
    );

    if (surplus > 0) {
        const fmt = 'Rp ' + surplus.toLocaleString('id-ID');
        $('#woClosePlanModal-surplus-amount').text(fmt);
        $('#woClosePlanModal-print-link').attr('href', `/wo-budgets/${id}/print-realisasi`);
        $('#woClosePlanModal-surplus-info').show();
        $('#woClosePlanModal-upload-section').show();
        $('#woClosePlanModal-noSurplus-info').hide();
    } else {
        $('#woClosePlanModal-surplus-info').hide();
        $('#woClosePlanModal-upload-section').hide();
        $('#woClosePlanModal-noSurplus-info').show();
    }

    $('#woClosePlanModal').modal('show');
});

// Submit Selesaikan Plan
$(document).off('click.wobudget', '#woClosePlanModal-btn-save').on('click.wobudget', '#woClosePlanModal-btn-save', function () {
    const id   = $('#woClosePlanModal-id').val();
    const idWo = $('#woClosePlanModal-id-wo').val();
    const plans  = window._woBudgetPlans || [];
    const plan   = plans.find(function (p) { return p.id_budget == id; });
    const surplus = plan ? (plan.total_budget - plan.total_actual) : 0;

    if (surplus > 0) {
        const file = $('#woClosePlanModal-file')[0].files[0];
        if (!file) {
            Swal.fire('Upload Diperlukan', 'Harap upload Laporan Realisasi Anggaran yang sudah ditandatangani.', 'warning');
            return;
        }
        const fd = new FormData();
        fd.append('_token', window.route.csrf);
        fd.append('dokumen_realisasi', file);
        $.ajax({
            url: `/wo-budgets/${id}/close`,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
        }).done(function () {
            $('#woClosePlanModal').modal('hide');
            loadWoBudgetData(idWo);
        }).fail(function (xhr) {
            Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
        });
    } else {
        $.post(`/wo-budgets/${id}/close`, { _token: window.route.csrf })
            .done(function () {
                $('#woClosePlanModal').modal('hide');
                loadWoBudgetData(idWo);
            })
            .fail(function (xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
            });
    }
});

$(document).off('click.wobudget', '.btn-wo-budget-delete').on('click.wobudget', '.btn-wo-budget-delete', function () {
    const id    = $(this).data('id');
    const label = $(this).data('label');
    const idWo  = $('#woTabActionsBudget .btn-wo-budget-add').data('wo-id');
    Swal.fire({
        title: 'Hapus Budget Plan?',
        html: `<b>${label}</b> akan dihapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal',
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({ url: `/wo-budgets/${id}`, type: 'DELETE', data: { _token: window.route.csrf } })
            .done(function () {
                loadWoBudgetData(idWo);
                Swal.fire({ icon: 'success', title: 'Dihapus', timer: 1200, showConfirmButton: false });
            })
            .fail(function () { Swal.fire('Gagal', 'Tidak dapat menghapus.', 'error'); });
    });
});

// ── Tambah Actual ──
$(document).off('click.wobudget', '.btn-wo-actual-add').on('click.wobudget', '.btn-wo-actual-add', function () {
    const idBudgetItem  = $(this).data('id-budget-item');
    const idWo          = $(this).data('wo-id') || $('#woTabActionsBudget .btn-wo-budget-add').data('wo-id');
    const nominalBudget = $(this).data('nominal-budget') || 0;
    const accountName   = $(this).data('account-name') || '';
    $('#woActualModal-id').val('');
    $('#woActualModal-id-budget-item').val(idBudgetItem);
    $('#woActualModal-id-wo').val(idWo);
    $('#woActualModal-nominal').val('');
    $('#woActualModal-keterangan').val('');
    $('#woActualModal-files').val('');
    $('#woActualModal-existing-files').empty();
    $('#woActualModalLabel').html('<i class="fa-solid fa-receipt me-2" style="color:#1d4ed8;"></i>Catat Pengeluaran');
    $('#woActualModal-account-name').text(accountName);
    $('#woActualModal-budget-nominal').text(woFmtRp(nominalBudget));
    $('#woActualModal-budget-info').show();
    initNumericMask(document.getElementById('woActualModal'));
    new bootstrap.Modal(document.getElementById('woActualModal')).show();
});

// ── Edit Actual ──
$(document).off('click.wobudget', '.btn-wo-actual-edit').on('click.wobudget', '.btn-wo-actual-edit', function () {
    const id   = $(this).data('id');
    const idWo = $(this).data('wo-id') || $('#woTabActionsBudget .btn-wo-budget-add').data('wo-id');
    $.get(`/wo-budget-actuals/${id}`)
        .done(function (r) {
            $('#woActualModal-id').val(r.id_actual);
            $('#woActualModal-id-budget-item').val(r.id_budget_item);
            $('#woActualModal-id-wo').val(idWo);
            $('#woActualModal-nominal').val(Number(r.nominal_actual).toLocaleString('en-US'));
            $('#woActualModal-keterangan').val(r.keterangan || '');
            $('#woActualModal-files').val('');
            $('#woActualModalLabel').html('<i class="fa-solid fa-receipt me-2" style="color:#1d4ed8;"></i>Edit Pengeluaran');
            $('#woActualModal-budget-info').hide();

            const files = JSON.parse(r.attachments || '[]');
            const $ex   = $('#woActualModal-existing-files').empty();
            files.forEach(function (f) {
                $ex.append(`<div class="d-flex align-items-center gap-2 mb-1" style="font-size:11px;">
                    <input type="checkbox" class="existing-file-check" value="${f}" checked data-no-disable>
                    <a href="/storage/${f}" target="_blank">${f.split('/').pop()}</a>
                </div>`);
            });

            initNumericMask(document.getElementById('woActualModal'));
            new bootstrap.Modal(document.getElementById('woActualModal')).show();
        });
});

// ── Hapus Actual ──
$(document).off('click.wobudget', '.btn-wo-actual-delete').on('click.wobudget', '.btn-wo-actual-delete', function () {
    const id   = $(this).data('id');
    const idWo = $('#woTabActionsBudget .btn-wo-budget-add').data('wo-id');
    Swal.fire({
        title: 'Hapus Realisasi?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal',
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({ url: `/wo-budget-actuals/${id}`, type: 'DELETE', data: { _token: window.route.csrf } })
            .done(function () {
                loadWoBudgetData(idWo);
                Swal.fire({ icon: 'success', title: 'Dihapus', timer: 1200, showConfirmButton: false });
            })
            .fail(function () { Swal.fire('Gagal', 'Tidak dapat menghapus.', 'error'); });
    });
});

// ── Simpan Actual ──
$(document).off('click.wobudget', '#woActualModal-btn-save').on('click.wobudget', '#woActualModal-btn-save', function () {
    const id           = $('#woActualModal-id').val();
    const idBudgetItem = $('#woActualModal-id-budget-item').val();
    const idWo         = $('#woActualModal-id-wo').val();
    const nominal      = parseInt($('#woActualModal-nominal').val().replace(/,/g, ''), 10) || 0;

    if (!nominal) return Swal.fire('Perhatian', 'Nominal wajib diisi.', 'warning');

    const fd = new FormData();
    fd.append('_token', window.route.csrf);
    fd.append('id_budget_item', idBudgetItem);
    fd.append('nominal_actual', nominal);
    fd.append('keterangan', $('#woActualModal-keterangan').val());

    if (id) fd.append('_method', 'POST');

    const files = $('#woActualModal-files')[0].files;
    for (let i = 0; i < files.length; i++) fd.append('attachments[]', files[i]);

    $('#woActualModal-existing-files .existing-file-check:checked').each(function () {
        fd.append('existing_attachments[]', $(this).val());
    });

    const url = id ? `/wo-budget-actuals/${id}` : '/wo-budget-actuals';
    $('#woActualModal-btn-save').prop('disabled', true);
    $.ajax({ url, type: 'POST', data: fd, processData: false, contentType: false })
        .done(function () {
            bootstrap.Modal.getInstance(document.getElementById('woActualModal'))?.hide();
            loadWoBudgetData(idWo);
            Swal.fire({ icon: 'success', title: 'Tersimpan', timer: 1200, showConfirmButton: false });
        })
        .fail(function (xhr) {
            const errs = xhr.responseJSON?.errors;
            const msg  = errs ? Object.values(errs).flat().join('<br>') : (xhr.responseJSON?.message || 'Terjadi kesalahan.');
            Swal.fire('Gagal', msg, 'error');
        })
        .always(function () { $('#woActualModal-btn-save').prop('disabled', false); });
});

// ── Buka Modal Bulk Verifikasi per Plan ──
$(document).off('click.wobudget', '.btn-wo-bulk-verify').on('click.wobudget', '.btn-wo-bulk-verify', function () {
    const idBudget = $(this).data('id-budget');
    const label    = $(this).data('label');
    const idWo     = $('#woTabActionsBudget .btn-wo-budget-add').data('wo-id') ||
                     $('#woTabActionsBudget').find('[data-wo-id]').data('wo-id');

    $('#woBulkVerifyModal-id-wo').val(idWo);
    $('#woBulkVerifyModalLabel').html(
        `<i class="fa-solid fa-check-double me-2" style="color:#0f766e;"></i>Verifikasi — ${escHtml(label)}`
    );

    const plan = window._woBudgetPlans && window._woBudgetPlans.find(p => p.id_budget == idBudget);
    if (!plan) return;

    const rows = [];
    plan.items.forEach(function (item) {
        (item.actuals || []).forEach(function (a) {
            rows.push({ item, actual: a });
        });
    });

    if (!rows.length) return;

    const tableRows = rows.map(function (r, i) {
        const a    = r.actual;
        const item = r.item;
        const st   = a.status_verifikasi || 'menunggu';
        const styleSetujui = st === 'disetujui'
            ? 'border-color:#15803d;background:#dcfce7;'
            : 'border-color:#e2e8f0;';
        const styleTolak = st === 'ditolak'
            ? 'border-color:#dc2626;background:#fee2e2;'
            : 'border-color:#e2e8f0;';

        return `<tr data-id-actual="${a.id_actual}">
            <td style="width:30px;text-align:center;color:#94a3b8;font-size:11px;">${i + 1}</td>
            <td style="font-size:11px;">
                <span class="fw-semibold">${escHtml(item.kode_account)}</span>
                <span class="text-muted ms-1">${escHtml(item.nama_account)}</span>
            </td>
            <td style="font-size:11px;font-weight:600;color:#1d4ed8;">${woFmtRp(a.nominal_actual)}</td>
            <td style="font-size:11px;color:#64748b;">${escHtml(a.keterangan || '-')}</td>
            <td style="min-width:160px;">
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm flex-fill bv-choice" data-value="disetujui"
                        style="font-size:10px;padding:2px 6px;border:2px solid;${styleSetujui}" data-no-disable>
                        &#10003; Setuju
                    </button>
                    <button type="button" class="btn btn-sm flex-fill bv-choice" data-value="ditolak"
                        style="font-size:10px;padding:2px 6px;border:2px solid;${styleTolak}" data-no-disable>
                        &#10007; Tolak
                    </button>
                </div>
                <input type="hidden" class="bv-status" value="${st}">
            </td>
            <td style="min-width:160px;">
                <input type="text" class="form-control form-control-sm bv-catatan"
                    value="${escHtml(a.catatan_verifikasi || '')}"
                    placeholder="${st === 'ditolak' ? 'Wajib diisi' : 'Opsional'}"
                    style="font-size:11px;" data-no-disable>
            </td>
        </tr>`;
    }).join('');

    $('#woBulkVerifyModal-body').html(`
        <div class="table-responsive">
            <table class="pm-table" id="woBulkVerifyTable">
                <thead>
                    <tr>
                        <th style="width:30px;">No</th>
                        <th>Account</th>
                        <th style="min-width:110px;">Nominal</th>
                        <th style="min-width:120px;">Keterangan</th>
                        <th style="min-width:160px;">Status</th>
                        <th style="min-width:160px;">Catatan</th>
                    </tr>
                </thead>
                <tbody>${tableRows}</tbody>
            </table>
        </div>
    `);

    new bootstrap.Modal(document.getElementById('woBulkVerifyModal')).show();
});

// ── Toggle pilihan status per baris ──
$(document).off('click.wobudget', '#woBulkVerifyTable .bv-choice').on('click.wobudget', '#woBulkVerifyTable .bv-choice', function () {
    const val  = $(this).data('value');
    const $row = $(this).closest('tr');
    $row.find('.bv-status').val(val);
    $row.find('.bv-choice').css({ 'border-color': '#e2e8f0', 'background': '' });
    $(this).css({
        'border-color': val === 'disetujui' ? '#15803d' : '#dc2626',
        'background':   val === 'disetujui' ? '#dcfce7' : '#fee2e2',
    });
    $row.find('.bv-catatan').attr('placeholder', val === 'ditolak' ? 'Wajib diisi' : 'Opsional');
});

// ── Simpan Semua Verifikasi ──
$(document).off('click.wobudget', '#woBulkVerifyModal-btn-save').on('click.wobudget', '#woBulkVerifyModal-btn-save', function () {
    const idWo  = $('#woBulkVerifyModal-id-wo').val();
    const items = [];
    let valid = true;

    $('#woBulkVerifyTable tbody tr').each(function () {
        const idActual = $(this).data('id-actual');
        const status    = $(this).find('.bv-status').val();
        const catatan   = $(this).find('.bv-catatan').val().trim();
        if (status === 'ditolak' && !catatan) {
            valid = false;
            $(this).find('.bv-catatan').addClass('is-invalid');
        } else {
            $(this).find('.bv-catatan').removeClass('is-invalid');
        }
        items.push({ id_actual: idActual, status_verifikasi: status, catatan_verifikasi: catatan });
    });

    if (!valid) return Swal.fire('Perhatian', 'Catatan wajib diisi untuk semua baris yang Ditolak.', 'warning');

    $('#woBulkVerifyModal-btn-save').prop('disabled', true);
    $.post('/wo-budget-actuals/bulk-verify', {
        _token: window.route.csrf,
        items:  items,
    })
    .done(function () {
        bootstrap.Modal.getInstance(document.getElementById('woBulkVerifyModal'))?.hide();
        loadWoBudgetData(idWo);
    })
    .fail(function (xhr) {
        Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
    })
    .always(function () { $('#woBulkVerifyModal-btn-save').prop('disabled', false); });
});
