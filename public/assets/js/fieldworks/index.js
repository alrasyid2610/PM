let page;
let currentFwoData        = null;
let fwoBoqData            = [];
let fwoBoqViewHtml        = null;
let fwoBoqSnapshot        = null;
let addedBoqIds           = new Set();
let selectedBoq           = null;
let currentPersonelData   = [];
let personelViewHtml      = null;
let personelEditIdx       = 0;
let fwoBoqDirectMode      = false;
let fwoAttachmentData     = [];
let fwoAttachmentViewHtml = null;
let fwoAttPondInstances   = [];
let fwoAttGroupIdx        = 0;
let sampleFilePond        = null;

// ── Tab switch: show/hide action buttons ──────────────────────────────────────
$(document).on('shown.bs.tab', '#fwoDetailTabs button[data-bs-toggle="tab"]', function (e) {
    const target = $(e.target).data('bs-target');
    $('#fwoTabActionsInfo, #fwoTabActionsPersonel, #fwoTabActionsBoq, #fwoTabActionsAttachment, #fwoTabActionsBudget, #fwoTabActionsSample, #fwoTabActionsBoqOther, #fwoTabActionsBoqSampling')
        .addClass('d-none').removeClass('d-flex');
    if (target === '#tabFwoInfo')       $('#fwoTabActionsInfo').removeClass('d-none');
    if (target === '#tabFwoPersonel')   $('#fwoTabActionsPersonel').removeClass('d-none');
    if (target === '#tabFwoBoq')        $('#fwoTabActionsBoq').removeClass('d-none');
    if (target === '#tabFwoAttachment') $('#fwoTabActionsAttachment').removeClass('d-none');
    if (target === '#tabFwoBudget') {
        $('#fwoTabActionsBudget').removeClass('d-none').addClass('d-flex');
        const idFwo = $(e.target).data('id-fwo');
        loadBudgetData(idFwo);
    }
    if (target === '#tabFwoSample') {
        $('#fwoTabActionsSample').removeClass('d-none').addClass('d-flex');
        const idFwo = $(e.target).data('id-fwo');
        loadSampleData(idFwo);
    }
    if (target === '#tabFwoBoqOther') {
        $('#fwoTabActionsBoqOther').removeClass('d-none').addClass('d-flex');
        const idFwo = $(e.target).data('id-fwo');
        loadBoqTambahanData('other', idFwo);
    }
    if (target === '#tabFwoBoqSampling') {
        $('#fwoTabActionsBoqSampling').removeClass('d-none').addClass('d-flex');
        const idFwo = $(e.target).data('id-fwo');
        loadBoqTambahanData('sampling', idFwo);
    }
});

// ── BOQ TAMBAHAN (BOQ Other / BOQ Sampling) ─────────────────────────────────────

const BOQ_TAMBAHAN_ROUTE = { other: 'fwo-boq-other', sampling: 'fwo-boq-sampling' };
const BOQ_TAMBAHAN_LABEL = { other: 'BOQ Other', sampling: 'BOQ Sampling' };
const BOQ_TAMBAHAN_ICON  = { other: 'fa-file-invoice', sampling: 'fa-vial-virus' };

function loadBoqTambahanData(jenis, idFwo) {
    const $wrap = $(jenis === 'other' ? '#fwoBoqOtherContent' : '#fwoBoqSamplingContent');
    $wrap.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');

    $.get(`/${BOQ_TAMBAHAN_ROUTE[jenis]}/${idFwo}/list`)
        .done(function (res) {
            const rows     = res.data || [];
            const isLocked = res.fwo_status === 'completed';
            $wrap.html(renderBoqTambahanList(jenis, rows, res.total || 0, isLocked));
        })
        .fail(function () {
            $wrap.html('<div class="text-center text-danger py-4">Gagal memuat data.</div>');
        });
}

function renderBoqTambahanList(jenis, rows, total, isLocked) {
    if (!rows.length) {
        return `<div class="text-center text-muted py-4">
            <i class="fa-solid ${BOQ_TAMBAHAN_ICON[jenis]} fa-2x d-block mb-2 opacity-25"></i>
            Belum ada item ${BOQ_TAMBAHAN_LABEL[jenis]}
        </div>`;
    }

    const rowsHtml = rows.map(function (r, i) {
        const subtotal = (r.qty || 0) * (r.harga || 0);
        return `<tr data-id="${r.id_fwo_boq_tambahan}">
            <td style="font-size:12px;color:#94a3b8;width:36px;">${i + 1}</td>
            <td style="font-size:12px;">${escHtml(r.nama_item)}</td>
            <td style="font-size:12px;text-align:right;white-space:nowrap;">${r.qty ?? 0} ${escHtml(r.satuan || '')}</td>
            <td style="font-size:12px;text-align:right;white-space:nowrap;">Rp ${Number(r.harga || 0).toLocaleString('id-ID')}</td>
            <td style="font-size:12px;text-align:right;white-space:nowrap;font-weight:600;">Rp ${Number(subtotal).toLocaleString('id-ID')}</td>
            <td style="font-size:12px;">${escHtml(r.keterangan || '—')}</td>
            <td class="text-center" style="width:72px;white-space:nowrap;">
                ${!isLocked ? `
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-boq-tambahan-edit"
                    data-id="${r.id_fwo_boq_tambahan}" data-jenis="${jenis}" title="Edit" style="font-size:11px;">
                    <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-boq-tambahan-delete"
                    data-id="${r.id_fwo_boq_tambahan}" data-jenis="${jenis}" data-nama="${escHtml(r.nama_item)}"
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

function initBoqTambahanSatuanSelect2(idVal, labelVal) {
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
    const idFwo  = $(this).data('id-fwo');
    const $modal = $('#boqTambahanModal');

    $modal.find('#boqTambahanModalLabel').html(`<i class="fa-solid ${BOQ_TAMBAHAN_ICON[jenis]} me-2" style="color:#b45309;"></i>Tambah Item ${BOQ_TAMBAHAN_LABEL[jenis]}`);
    $modal.find('#boqTambahanModal-id').val('');
    $modal.find('#boqTambahanModal-jenis').val(jenis);
    $modal.find('#boqTambahanModal-id-fwo').val(idFwo);
    $modal.find('#boqTambahanModal-nama').val('');
    $modal.find('#boqTambahanModal-qty').val('');
    $modal.find('#boqTambahanModal-harga').val('');
    $modal.find('#boqTambahanModal-keterangan').val('');
    initBoqTambahanSatuanSelect2();

    new bootstrap.Modal($modal[0]).show();
    initNumericMask($modal[0]);
});

$(document).on('click', '.btn-boq-tambahan-edit', function (e) {
    e.stopPropagation();
    const id     = $(this).data('id');
    const jenis  = $(this).data('jenis');
    const $modal = $('#boqTambahanModal');

    $modal.find('#boqTambahanModalLabel').html(`<i class="fa-solid ${BOQ_TAMBAHAN_ICON[jenis]} me-2" style="color:#b45309;"></i>Edit Item ${BOQ_TAMBAHAN_LABEL[jenis]}`);
    $modal.find('#boqTambahanModal-id').val(id);
    $modal.find('#boqTambahanModal-jenis').val(jenis);

    $.get(`/${BOQ_TAMBAHAN_ROUTE[jenis]}/${id}`)
        .done(function (r) {
            $modal.find('#boqTambahanModal-id-fwo').val(r.id_fwo);
            $modal.find('#boqTambahanModal-nama').val(r.nama_item || '');
            const qtyEl = $modal.find('#boqTambahanModal-qty')[0];
            if (qtyEl && qtyEl._cleave) qtyEl._cleave.setRawValue(r.qty || ''); else $modal.find('#boqTambahanModal-qty').val(r.qty || '');
            const hargaEl = $modal.find('#boqTambahanModal-harga')[0];
            if (hargaEl && hargaEl._cleave) hargaEl._cleave.setRawValue(r.harga || ''); else $modal.find('#boqTambahanModal-harga').val(r.harga || '');
            $modal.find('#boqTambahanModal-keterangan').val(r.keterangan || '');
            initBoqTambahanSatuanSelect2(r.id_satuan, r.satuan);
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
    const idFwo  = $modal.find('#boqTambahanModal-id-fwo').val();
    const $btn   = $(this);

    const payload = {
        _token:      window.route.csrf,
        id_fwo:      idFwo,
        nama_item:   $modal.find('#boqTambahanModal-nama').val().trim(),
        qty:         rawNumVal($modal.find('#boqTambahanModal-qty')[0]),
        id_satuan:   $modal.find('#boqTambahanModal-satuan').val() || null,
        harga:       rawNumVal($modal.find('#boqTambahanModal-harga')[0]),
        keterangan:  $modal.find('#boqTambahanModal-keterangan').val().trim() || null,
    };

    if (!payload.nama_item) { Notify.warning('Nama item wajib diisi.'); return; }
    if (!payload.qty)       { Notify.warning('Qty wajib diisi.'); return; }

    const isEdit = !!id;
    const url    = isEdit ? `/${BOQ_TAMBAHAN_ROUTE[jenis]}/${id}` : `/${BOQ_TAMBAHAN_ROUTE[jenis]}`;
    if (isEdit) payload._method = 'PUT';

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');
    $.post(url, payload)
        .done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance($modal[0])?.hide();
                Notify.success('Item berhasil disimpan.');
                loadBoqTambahanData(jenis, idFwo);
            } else {
                Notify.error(res.message || 'Gagal menyimpan item.');
            }
        })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan item.');
        })
        .always(function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan'); });
});

$(document).on('click', '.btn-boq-tambahan-delete', function () {
    const id    = $(this).data('id');
    const jenis = $(this).data('jenis');
    const nama  = $(this).data('nama');
    const idFwo = $(this).closest('.card-body').attr('id') === 'fwoBoqOtherContent'
        ? $('#fwoDetailTabs button[data-bs-target="#tabFwoBoqOther"]').data('id-fwo')
        : $('#fwoDetailTabs button[data-bs-target="#tabFwoBoqSampling"]').data('id-fwo');

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
            url: `/${BOQ_TAMBAHAN_ROUTE[jenis]}/${id}`,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.route.csrf },
            success: function () {
                Notify.success('Item berhasil dihapus.');
                loadBoqTambahanData(jenis, idFwo);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal menghapus item.');
            },
        });
    });
});

// ── BUDGET ─────────────────────────────────────────────────────────────────────

function loadBudgetData(idFwo) {
    const $wrap = $('#fwoBudgetContent');
    $wrap.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');

    $.get(`/fwo-budgets/${idFwo}/list`)
        .done(function (res) {
            const plans     = res.data || [];
            const fwoStatus = res.fwo_status || '';
            const isLocked  = fwoStatus === 'completed';

            if (!plans.length) {
                $wrap.html(`<div class="text-center text-muted py-4" style="font-size:13px;">
                    <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                    Belum ada Budget Plan untuk FWO ini
                </div>`);
                return;
            }
            window._budgetPlans = plans;
            $wrap.html(renderBudgetList(plans, isLocked));
        })
        .fail(function () {
            $wrap.html('<div class="text-center text-danger py-3">Gagal memuat data budget.</div>');
        });
}

function fmtRp(val) {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
}

function fmtTgl(val) {
    if (!val) return '-';
    const d = new Date(val);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function renderBudgetList(plans, isLocked) {
    const cards = plans.map(function (p) {
        const selisih     = p.total_budget - p.total_actual;
        const selisihColor = selisih >= 0 ? '#15803d' : '#dc2626';
        const planLocked  = isLocked || p.status === 'completed';
        const statusBadge = p.status === 'completed'
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
                            <span class="fw-semibold">${fmtRp(a.nominal_actual)}</span>
                            ${a.keterangan ? `<span class="text-muted">— ${escHtml(a.keterangan)}</span>` : ''}
                            ${verifBadge}${catatanVerif}
                        </div>
                        <div class="mt-1">${fileLinks}</div>
                    </div>
                    ${!planLocked ? `<div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 btn-actual-edit"
                            data-id="${a.id_actual}" data-id-fwo="${p.id_fwo || ''}" style="font-size:10px;" data-no-disable title="Edit">
                            <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 btn-actual-delete"
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
                <td style="color:#1d4ed8;font-weight:600;">${fmtRp(item.nominal_budget)}</td>
                <td>
                    ${struksHtml || '<span class="text-muted" style="font-size:11px;">Belum ada realisasi</span>'}
                </td>
                <td style="color:${selisihColor};font-weight:600;">${fmtRp(actualTotal)}</td>
                <td style="color:${sel >= 0 ? '#15803d' : '#dc2626'};font-weight:600;">${fmtRp(sel)}</td>
                <td class="text-center">
                    ${!planLocked ? `<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-actual-add"
                        data-id-budget-item="${item.id_budget_item}" data-id-fwo="${p.id_fwo || ''}"
                        data-nominal-budget="${item.nominal_budget}"
                        data-account-name="${escHtml(item.kode_account + ' – ' + item.nama_account)}"
                        data-no-disable title="Catat Pengeluaran" style="font-size:11px;">
                        <i class="fa-solid fa-plus" style="color:#0f766e;"></i>
                    </button>` : ''}
                </td>
            </tr>`;
        }).join('');

        const collapseId = `budgetCollapse_${p.id_budget}`;
        return `<div class="mb-3 border rounded">
            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                style="background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:calc(0.375rem - 1px) calc(0.375rem - 1px) 0 0;">
                <div class="d-flex align-items-center flex-wrap gap-1">
                    <span class="fw-bold" style="font-size:13px;">${escHtml(p.label)}</span>
                    ${statusBadge}
                    ${(p.tanggal_mulai || p.tanggal_selesai) ? `<span class="text-muted ms-1" style="font-size:11px;">
                        <i class="fa-regular fa-calendar me-1"></i>${fmtTgl(p.tanggal_mulai)}${p.tanggal_selesai ? ' – ' + fmtTgl(p.tanggal_selesai) : ''}
                    </span>` : ''}
                    ${p.keterangan ? `<span class="text-muted ms-1" style="font-size:11px;">· ${escHtml(p.keterangan)}</span>` : ''}
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span style="font-size:12px;">Budget: <b style="color:#1d4ed8;">${fmtRp(p.total_budget)}</b></span>
                    <span style="font-size:12px;">Actual: <b>${fmtRp(p.total_actual)}</b></span>
                    <span style="font-size:12px;">Selisih: <b style="color:${selisihColor};">${fmtRp(selisih)}</b></span>
                    ${p.dokumen_realisasi ? `
                    <a href="/storage/${p.dokumen_realisasi}" target="_blank"
                        class="btn btn-sm py-0 px-2" data-no-disable
                        style="font-size:11px;background:#f0fdf4;color:#15803d;border:1px solid #86efac;"
                        title="Download Dokumen Realisasi yang sudah ditandatangani">
                        <i class="fa-solid fa-file-arrow-down me-1"></i>Dok. Realisasi
                    </a>` : ''}
                    ${!planLocked && p.items.some(i => (i.actuals || []).length > 0) ? `
                    <button type="button" class="btn btn-sm btn-plan-verify btn-bulk-verify"
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
                                <a class="dropdown-item" href="/fwo-budgets/${p.id_budget}/print" target="_blank">
                                    <i class="fa-solid fa-print me-2 text-muted"></i>Print Serah Terima
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/fwo-budgets/${p.id_budget}/print-realisasi" target="_blank">
                                    <i class="fa-solid fa-file-invoice me-2 text-muted"></i>Print Realisasi
                                </a>
                            </li>
                            ${!planLocked ? `
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button type="button" class="dropdown-item btn-budget-edit"
                                    data-id="${p.id_budget}" data-no-disable>
                                    <i class="fa-solid fa-pen-to-square me-2" style="color:#1e40af;"></i>Edit Plan
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item btn-budget-close"
                                    data-id="${p.id_budget}" data-label="${escHtml(p.label)}" data-no-disable>
                                    <i class="fa-solid fa-circle-check me-2" style="color:#15803d;"></i>Selesaikan Plan
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button type="button" class="dropdown-item btn-budget-delete"
                                    data-id="${p.id_budget}" data-label="${escHtml(p.label)}" data-no-disable>
                                    <i class="fa-solid fa-trash me-2" style="color:#dc2626;"></i>Hapus Plan
                                </button>
                            </li>` : ''}
                        </ul>
                    </div>
                    <button type="button" class="btn btn-sm btn-plan-icon btn-budget-collapse"
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
        <span>Total Budget: <b style="color:#1d4ed8;">${fmtRp(grandBudget)}</b></span>
        <span>Total Realisasi: <b>${fmtRp(grandActual)}</b></span>
        <span>${selLabel}: <b style="color:${selColor};">${fmtRp(Math.abs(grandSelisih))}</b></span>
    </div>`;

    return summary + cards;
}

function buildBudgetItemRow(item) {
    return `<tr class="budget-item-row">
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
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 btn-remove-row"
                data-no-disable title="Hapus baris" style="font-size:11px;">
                <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
            </button>
        </td>
    </tr>`;
}

function initBudgetAccountSelect2() {
    $('#budgetItemsBody .bi-account').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) return;
        $(this).select2({
            dropdownParent: $('#budgetPlanModal'),
            placeholder: 'Pilih Account',
            allowClear: false,
            width: '100%',
            minimumInputLength: 0,
            ajax: {
                url: '/budget-accounts/select2',
                dataType: 'json',
                delay: 200,
                data: function (params) { return { q: params.term }; },
                // Response sudah grouped: [{text:'Kategori', children:[...]}]
                processResults: function (data) { return { results: data }; },
                cache: true,
            },
        });
    });
}

function recalcBudgetTotal() {
    let total = 0;
    $('#budgetItemsBody .bi-nominal').each(function () {
        total += parseInt($(this).val().replace(/,/g, ''), 10) || 0;
    });
    $('#budgetModalTotal').text(fmtRp(total));
}

function openBudgetModal(idFwo, budgetData) {
    const isEdit = !!budgetData;
    $('#budgetPlanModalLabel').html(
        `<i class="fa-solid fa-wallet me-2" style="color:#0f766e;"></i>${isEdit ? 'Edit' : 'Tambah'} Budget Plan`
    );
    $('#budgetModal-id').val(isEdit ? budgetData.id_budget : '');
    $('#budgetModal-id-fwo').val(idFwo);
    $('#budgetModal-label').val(isEdit ? budgetData.label : '');
    $('#budgetModal-keterangan').val(isEdit ? (budgetData.keterangan || '') : '');

    const $body = $('#budgetItemsBody').empty();
    const items = isEdit ? (budgetData.items || []) : [];
    if (items.length) {
        items.forEach(function (item) { $body.append(buildBudgetItemRow(item)); });
    } else {
        $body.append(buildBudgetItemRow(null));
    }

    initBudgetAccountSelect2();
    initNumericMask(document.getElementById('budgetPlanModal'));
    recalcBudgetTotal();

    // Init Flatpickr tanggal setelah modal ada di DOM
    const modal = new bootstrap.Modal(document.getElementById('budgetPlanModal'));
    modal.show();

    document.getElementById('budgetPlanModal').addEventListener('shown.bs.modal', function handler() {
        // Destroy dulu jika sudah ada instance
        const elMulai   = document.getElementById('budgetModal-tgl-mulai');
        const elSelesai = document.getElementById('budgetModal-tgl-selesai');
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

        // Saat tanggal mulai berubah → update minDate selesai
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
$(document).off('click.budget', '.btn-budget-add').on('click.budget', '.btn-budget-add', function () {
    const idFwo = $(this).data('id-fwo');
    openBudgetModal(idFwo, null);
});

// ── Tambah baris item di modal ──
$(document).off('click.budget', '#btnBudgetAddRow').on('click.budget', '#btnBudgetAddRow', function () {
    $('#budgetItemsBody').append(buildBudgetItemRow(null));
    initBudgetAccountSelect2();
    initNumericMask(document.getElementById('budgetPlanModal'));
});

// ── Hapus baris item di modal ──
$(document).off('click.budget', '.btn-remove-row').on('click.budget', '.btn-remove-row', function () {
    $(this).closest('tr').remove();
    recalcBudgetTotal();
});

// ── Recalc total saat nominal berubah ──
$(document).off('input.budget', '#budgetItemsBody .bi-nominal').on('input.budget', '#budgetItemsBody .bi-nominal', function () {
    recalcBudgetTotal();
});

// ── Simpan Budget Plan ──
$(document).off('click.budget', '#budgetModal-btn-save').on('click.budget', '#budgetModal-btn-save', function () {
    const id    = $('#budgetModal-id').val();
    const idFwo = $('#budgetModal-id-fwo').val();
    const label = $('#budgetModal-label').val().trim();

    if (!label) return Swal.fire('Perhatian', 'Label wajib diisi.', 'warning');

    const items = [];
    let valid = true;
    $('#budgetItemsBody .budget-item-row').each(function () {
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
        _token:         window.route.csrf,
        id_fwo:         idFwo,
        label:          label,
        keterangan:     $('#budgetModal-keterangan').val(),
        tanggal_mulai:  $('#budgetModal-tgl-mulai').val() || null,
        tanggal_selesai: $('#budgetModal-tgl-selesai').val() || null,
        items:          items,
    };

    const isEdit = !!id;
    const url    = isEdit ? `/fwo-budgets/${id}` : '/fwo-budgets';
    if (isEdit) payload._method = 'PUT';

    $('#budgetModal-btn-save').prop('disabled', true);
    $.post(url, payload)
        .done(function () {
            bootstrap.Modal.getInstance(document.getElementById('budgetPlanModal'))?.hide();
            loadBudgetData(idFwo);
            Swal.fire({ icon: 'success', title: 'Tersimpan', timer: 1200, showConfirmButton: false });
        })
        .fail(function (xhr) {
            const errs = xhr.responseJSON?.errors;
            const msg  = errs ? Object.values(errs).flat().join('<br>') : (xhr.responseJSON?.message || 'Terjadi kesalahan.');
            Swal.fire('Gagal', msg, 'error');
        })
        .always(function () { $('#budgetModal-btn-save').prop('disabled', false); });
});

// ── Edit Budget Plan ──
$(document).off('click.budget', '.btn-budget-edit').on('click.budget', '.btn-budget-edit', function () {
    const id    = $(this).data('id');
    const idFwo = $('#fwoTabActionsBudget .btn-budget-add').data('id-fwo');
    $.get(`/fwo-budgets/${id}`)
        .done(function (data) { openBudgetModal(idFwo, data); });
});

// ── Hapus Budget Plan ──
// ── Collapse / Expand panel item ──
$(document).off('click.budget', '.btn-budget-collapse').on('click.budget', '.btn-budget-collapse', function () {
    const $icon   = $(this).find('i');
    const $target = $($(this).data('target'));
    $target.slideToggle(180, function () {
        const visible = $target.is(':visible');
        $icon.toggleClass('fa-chevron-up', visible).toggleClass('fa-chevron-down', !visible);
    });
});

$(document).off('click.sample', '.btn-sample-collapse').on('click.sample', '.btn-sample-collapse', function () {
    const $icon   = $(this).find('i');
    const $target = $($(this).data('target'));
    $target.slideToggle(180, function () {
        const visible = $target.is(':visible');
        $icon.toggleClass('fa-chevron-up', visible).toggleClass('fa-chevron-down', !visible);
    });
});

// ── Selesaikan Budget Plan ──
$(document).off('click.budget', '.btn-budget-close').on('click.budget', '.btn-budget-close', function () {
    const id    = $(this).data('id');
    const label = $(this).data('label');
    const idFwo = $('#fwoTabActionsBudget .btn-budget-add').data('id-fwo');

    // Cari data plan dari cache
    const plans   = window._budgetPlans || [];
    const plan    = plans.find(function (p) { return p.id_budget == id; });
    const surplus = plan ? (plan.total_budget - plan.total_actual) : 0;

    // Set nilai modal
    $('#closePlanModal-id').val(id);
    $('#closePlanModal-id-fwo').val(idFwo);
    $('#closePlanModal-file').val('');
    $('#closePlanModalLabel').html(
        `<i class="fa-solid fa-circle-check me-2" style="color:#15803d;"></i>Selesaikan Plan — ${escHtml(label)}`
    );

    if (surplus > 0) {
        const fmt = 'Rp ' + surplus.toLocaleString('id-ID');
        $('#closePlanModal-surplus-amount').text(fmt);
        $('#closePlanModal-print-link').attr('href', `/fwo-budgets/${id}/print-realisasi`);
        $('#closePlanModal-surplus-info').show();
        $('#closePlanModal-upload-section').show();
        $('#closePlanModal-noSurplus-info').hide();
    } else {
        $('#closePlanModal-surplus-info').hide();
        $('#closePlanModal-upload-section').hide();
        $('#closePlanModal-noSurplus-info').show();
    }

    $('#closePlanModal').modal('show');
});

// Submit Selesaikan Plan
$(document).off('click.budget', '#closePlanModal-btn-save').on('click.budget', '#closePlanModal-btn-save', function () {
    const id    = $('#closePlanModal-id').val();
    const idFwo = $('#closePlanModal-id-fwo').val();
    const plans  = window._budgetPlans || [];
    const plan   = plans.find(function (p) { return p.id_budget == id; });
    const surplus = plan ? (plan.total_budget - plan.total_actual) : 0;

    if (surplus > 0) {
        const file = $('#closePlanModal-file')[0].files[0];
        if (!file) {
            Swal.fire('Upload Diperlukan', 'Harap upload Laporan Realisasi Anggaran yang sudah ditandatangani.', 'warning');
            return;
        }
        const fd = new FormData();
        fd.append('_token', window.route.csrf);
        fd.append('dokumen_realisasi', file);
        $.ajax({
            url: `/fwo-budgets/${id}/close`,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
        }).done(function () {
            $('#closePlanModal').modal('hide');
            loadBudgetData(idFwo);
        }).fail(function (xhr) {
            Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
        });
    } else {
        $.post(`/fwo-budgets/${id}/close`, { _token: window.route.csrf })
            .done(function () {
                $('#closePlanModal').modal('hide');
                loadBudgetData(idFwo);
            })
            .fail(function (xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
            });
    }
});

$(document).off('click.budget', '.btn-budget-delete').on('click.budget', '.btn-budget-delete', function () {
    const id    = $(this).data('id');
    const label = $(this).data('label');
    const idFwo = $('#fwoTabActionsBudget .btn-budget-add').data('id-fwo');
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
        $.ajax({ url: `/fwo-budgets/${id}`, type: 'DELETE', data: { _token: window.route.csrf } })
            .done(function () {
                loadBudgetData(idFwo);
                Swal.fire({ icon: 'success', title: 'Dihapus', timer: 1200, showConfirmButton: false });
            })
            .fail(function () { Swal.fire('Gagal', 'Tidak dapat menghapus.', 'error'); });
    });
});

// ── Tambah Actual ──
$(document).off('click.budget', '.btn-actual-add').on('click.budget', '.btn-actual-add', function () {
    const idBudgetItem   = $(this).data('id-budget-item');
    const idFwo          = $(this).data('id-fwo') || $('#fwoTabActionsBudget .btn-budget-add').data('id-fwo');
    const nominalBudget  = $(this).data('nominal-budget') || 0;
    const accountName    = $(this).data('account-name') || '';
    $('#actualModal-id').val('');
    $('#actualModal-id-budget-item').val(idBudgetItem);
    $('#actualModal-id-fwo').val(idFwo);
    $('#actualModal-nominal').val('');
    $('#actualModal-keterangan').val('');
    $('#actualModal-files').val('');
    $('#actualModal-existing-files').empty();
    $('#actualModalLabel').html('<i class="fa-solid fa-receipt me-2" style="color:#1d4ed8;"></i>Catat Pengeluaran');
    $('#actualModal-account-name').text(accountName);
    $('#actualModal-budget-nominal').text(fmtRp(nominalBudget));
    $('#actualModal-budget-info').show();
    initNumericMask(document.getElementById('actualModal'));
    new bootstrap.Modal(document.getElementById('actualModal')).show();
});

// ── Edit Actual ──
$(document).off('click.budget', '.btn-actual-edit').on('click.budget', '.btn-actual-edit', function () {
    const id    = $(this).data('id');
    const idFwo = $(this).data('id-fwo') || $('#fwoTabActionsBudget .btn-budget-add').data('id-fwo');
    $.get(`/fwo-budget-actuals/${id}`)
        .done(function (r) {
            $('#actualModal-id').val(r.id_actual);
            $('#actualModal-id-budget-item').val(r.id_budget_item);
            $('#actualModal-id-fwo').val(idFwo);
            $('#actualModal-nominal').val(Number(r.nominal_actual).toLocaleString('en-US'));
            $('#actualModal-keterangan').val(r.keterangan || '');
            $('#actualModal-files').val('');
            $('#actualModalLabel').html('<i class="fa-solid fa-receipt me-2" style="color:#1d4ed8;"></i>Edit Pengeluaran');
            $('#actualModal-budget-info').hide();

            const files = JSON.parse(r.attachments || '[]');
            const $ex   = $('#actualModal-existing-files').empty();
            files.forEach(function (f) {
                $ex.append(`<div class="d-flex align-items-center gap-2 mb-1" style="font-size:11px;">
                    <input type="checkbox" class="existing-file-check" value="${f}" checked data-no-disable>
                    <a href="/storage/${f}" target="_blank">${f.split('/').pop()}</a>
                </div>`);
            });

            initNumericMask(document.getElementById('actualModal'));
            new bootstrap.Modal(document.getElementById('actualModal')).show();
        });
});

// ── Hapus Actual ──
// ── Buka Modal Verifikasi ──
// ── Buka Modal Bulk Verifikasi per Plan ──
$(document).off('click.budget', '.btn-bulk-verify').on('click.budget', '.btn-bulk-verify', function () {
    const idBudget = $(this).data('id-budget');
    const label    = $(this).data('label');
    const idFwo    = $('#fwoTabActionsBudget .btn-budget-add').data('id-fwo') ||
                     $('#fwoTabActionsBudget').find('[data-id-fwo]').data('id-fwo');

    $('#bulkVerifyModal-id-fwo').val(idFwo);
    $('#bulkVerifyModalLabel').html(
        `<i class="fa-solid fa-check-double me-2" style="color:#0f766e;"></i>Verifikasi — ${escHtml(label)}`
    );

    // Kumpulkan semua aktual dari plan ini (dari DOM yang sudah dirender)
    // Gunakan data dari plans yang tersimpan
    const plan = window._budgetPlans && window._budgetPlans.find(p => p.id_budget == idBudget);
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
            <td style="font-size:11px;font-weight:600;color:#1d4ed8;">${fmtRp(a.nominal_actual)}</td>
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

    $('#bulkVerifyModal-body').html(`
        <div class="table-responsive">
            <table class="pm-table" id="bulkVerifyTable">
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

    new bootstrap.Modal(document.getElementById('bulkVerifyModal')).show();
});

// ── Toggle pilihan status per baris ──
$(document).off('click.budget', '#bulkVerifyTable .bv-choice').on('click.budget', '#bulkVerifyTable .bv-choice', function () {
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
$(document).off('click.budget', '#bulkVerifyModal-btn-save').on('click.budget', '#bulkVerifyModal-btn-save', function () {
    const idFwo = $('#bulkVerifyModal-id-fwo').val();
    const items = [];
    let valid = true;

    $('#bulkVerifyTable tbody tr').each(function () {
        const idActual = $(this).data('id-actual');
        const status   = $(this).find('.bv-status').val();
        const catatan  = $(this).find('.bv-catatan').val().trim();
        if (status === 'ditolak' && !catatan) {
            valid = false;
            $(this).find('.bv-catatan').addClass('is-invalid');
        } else {
            $(this).find('.bv-catatan').removeClass('is-invalid');
        }
        items.push({ id_actual: idActual, status_verifikasi: status, catatan_verifikasi: catatan });
    });

    if (!valid) return Swal.fire('Perhatian', 'Catatan wajib diisi untuk semua baris yang Ditolak.', 'warning');

    $('#bulkVerifyModal-btn-save').prop('disabled', true);
    $.post('/fwo-budget-actuals/bulk-verify', {
        _token: window.route.csrf,
        items:  items,
    })
    .done(function () {
        bootstrap.Modal.getInstance(document.getElementById('bulkVerifyModal'))?.hide();
        loadBudgetData(idFwo);
    })
    .fail(function (xhr) {
        Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
    })
    .always(function () { $('#bulkVerifyModal-btn-save').prop('disabled', false); });
});

$(document).off('click.budget', '.btn-actual-delete').on('click.budget', '.btn-actual-delete', function () {
    const id    = $(this).data('id');
    const idFwo = $('#fwoTabActionsBudget .btn-budget-add').data('id-fwo');
    Swal.fire({
        title: 'Hapus Realisasi?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal',
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({ url: `/fwo-budget-actuals/${id}`, type: 'DELETE', data: { _token: window.route.csrf } })
            .done(function () {
                loadBudgetData(idFwo);
                Swal.fire({ icon: 'success', title: 'Dihapus', timer: 1200, showConfirmButton: false });
            })
            .fail(function () { Swal.fire('Gagal', 'Tidak dapat menghapus.', 'error'); });
    });
});

// ── Simpan Actual ──
$(document).off('click.budget', '#actualModal-btn-save').on('click.budget', '#actualModal-btn-save', function () {
    const id           = $('#actualModal-id').val();
    const idBudgetItem = $('#actualModal-id-budget-item').val();
    const idFwo        = $('#actualModal-id-fwo').val();
    const nominal      = parseInt($('#actualModal-nominal').val().replace(/,/g, ''), 10) || 0;

    if (!nominal) return Swal.fire('Perhatian', 'Nominal wajib diisi.', 'warning');

    const fd = new FormData();
    fd.append('_token', window.route.csrf);
    fd.append('id_budget_item', idBudgetItem);
    fd.append('nominal_actual', nominal);
    fd.append('keterangan', $('#actualModal-keterangan').val());

    if (id) fd.append('_method', 'POST');

    // File baru
    const files = $('#actualModal-files')[0].files;
    for (let i = 0; i < files.length; i++) fd.append('attachments[]', files[i]);

    // File lama yang tetap disimpan
    $('#actualModal-existing-files .existing-file-check:checked').each(function () {
        fd.append('existing_attachments[]', $(this).val());
    });

    const url = id ? `/fwo-budget-actuals/${id}` : '/fwo-budget-actuals';
    $('#actualModal-btn-save').prop('disabled', true);
    $.ajax({ url, type: 'POST', data: fd, processData: false, contentType: false })
        .done(function () {
            bootstrap.Modal.getInstance(document.getElementById('actualModal'))?.hide();
            loadBudgetData(idFwo);
            Swal.fire({ icon: 'success', title: 'Tersimpan', timer: 1200, showConfirmButton: false });
        })
        .fail(function (xhr) {
            const errs = xhr.responseJSON?.errors;
            const msg  = errs ? Object.values(errs).flat().join('<br>') : (xhr.responseJSON?.message || 'Terjadi kesalahan.');
            Swal.fire('Gagal', msg, 'error');
        })
        .always(function () { $('#actualModal-btn-save').prop('disabled', false); });
});

// ── Init ───────────────────────────────────────────────────────────────────────
window.datatableColumnRenderers = {
    status: function (data) {
        var map = {
            planned:   { bg: '#fffbeb', color: '#92400e', border: '#fde68a', icon: 'fa-hourglass-half', label: 'Planned' },
            completed: { bg: '#f0fdf4', color: '#15803d', border: '#bbf7d0', icon: 'fa-circle-check',  label: 'Completed' },
            deleted:   { bg: '#fef2f2', color: '#b91c1c', border: '#fecaca', icon: 'fa-trash',         label: 'Deleted' },
        };
        var s = map[data] || { bg: '#f1f5f9', color: '#475569', border: '#e2e8f0', icon: 'fa-circle', label: data || '-' };
        return '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:6px;background:' + s.bg + ';color:' + s.color + ';font-size:11px;font-weight:600;border:1px solid ' + s.border + ';white-space:nowrap;">'
            + '<i class="fa-solid ' + s.icon + '" style="font-size:10px;"></i> ' + s.label + '</span>';
    },
};

$(document).ready(function () {

    // Select2 untuk modal (di-init tanpa URL dulu — URL disetel saat modal buka)
    $('#selectFwoBoq').select2({
        dropdownParent: $('#modalAddFwoBoq'),
        placeholder: 'Ketik nama Testing Point...',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: function () {
                return window.route.boqSelect2ByWo + (currentFwoData?.id_wo ?? 0) + '?id_fwo=' + (currentFwoData?.id_fwo ?? 0);
            },
            dataType: 'json',
            delay: 250,
            data: p => ({ q: p.term }),
            processResults: function (d) {
                return {
                    results: d.map(function (item) {
                        return addedBoqIds.has(String(item.id))
                            ? Object.assign({}, item, { disabled: true })
                            : item;
                    }),
                };
            },
            cache: false,
        },
        templateResult: function (item) {
            if (!item.id) return item.text;
            if (addedBoqIds.has(String(item.id))) {
                const $el = $('<span>').css({ display: 'flex', alignItems: 'center', gap: '8px' });
                $('<i>').addClass('fa-solid fa-circle-check').css({ color: '#22c55e', fontSize: '13px', flexShrink: '0' }).appendTo($el);
                $('<span>').text(item.text).css({ textDecoration: 'line-through', color: '#94a3b8' }).appendTo($el);
                $('<span>').text('Sudah ditambahkan').css({
                    fontSize: '11px', background: '#f0fdf4', color: '#16a34a',
                    borderRadius: '10px', padding: '1px 8px', border: '1px solid #bbf7d0',
                    flexShrink: '0',
                }).appendTo($el);
                return $el;
            }
            return item.text;
        },
    });

    $('#selectFwoBoq').on('select2:select', function (e) {
        const d = e.params.data;
        if (addedBoqIds.has(String(d.id))) {
            Notify.warning('BOQ section ini sudah ditambahkan.');
            $(this).val(null).trigger('change');
            selectedBoq = null;
            resetFwoBoqModal();
            return;
        }
        selectedBoq = d;
        loadFwoBoqSectionPreview(d.id);
    });

    $('#selectFwoBoq').on('select2:clear', function () {
        selectedBoq = null;
        resetFwoBoqModal();
        $('#btnConfirmFwoBoq').prop('disabled', true);
    });

    // Tombol tambah BOQ langsung (bulk mode — tampil semua BOQ sekaligus)
    $(document).on('click', '#btnAddFwoBoqDirect', function () {
        openBulkBoqModal();
    });

    // Reset modal lama saat ditutup
    $('#modalAddFwoBoq').on('hidden.bs.modal', function () {
        selectedBoq = null;
        fwoBoqDirectMode = false;
        $('#selectFwoBoq').val(null).trigger('change');
        resetFwoBoqModal();
    });

    // Simpan bulk BOQ
    $(document).on('click', '#btnSaveBulkBoq', function () {
        saveBulkBoq($(this));
    });

    // Validasi real-time qty bulk BOQ
    $(document).on('input', '.bulk-boq-qty', function () {
        const $input  = $(this);
        const qty     = parseInt($input.val()) || 0;
        const maxRaw  = $input.data('max');
        const max     = (maxRaw !== '' && maxRaw !== undefined) ? parseInt(maxRaw) : null;
        const isOver  = max !== null && qty > max;
        const $hint   = $input.siblings('.bulk-qty-hint');

        if (isOver) {
            $input.css({ 'border-color': '#f87171', 'background': '#fef2f2' });
            if (!$hint.length) {
                $input.after(`<div class="bulk-qty-hint" style="color:#dc2626;font-size:11px;margin-top:2px;">Maks: ${max}</div>`);
            }
        } else {
            $input.css({ 'border-color': '', 'background': '' });
            $hint.remove();
        }

        const hasError = $('#bulkBoqList .bulk-boq-qty').toArray().some(function (el) {
            const q   = parseInt($(el).val()) || 0;
            const m   = $(el).data('max');
            const max = (m !== '' && m !== undefined) ? parseInt(m) : null;
            return max !== null && q > max;
        });
        $('#btnSaveBulkBoq').prop('disabled', hasError);
    });

    // Hapus satu item BOQ dari view mode
    $(document).on('click', '.btn-fwo-boq-delete', function () {
        const boqId   = String($(this).data('boq-id'));
        const ptName  = $(this).closest('tr').find('td:nth-child(2)').text().trim();

        Swal.fire({
            icon:              'warning',
            title:             'Hapus Item BOQ?',
            html:              '<strong>' + escHtml(ptName) + '</strong>',
            showCancelButton:  true,
            confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Hapus',
            cancelButtonText:  'Batal',
            confirmButtonColor: '#dc2626',
            cancelButtonColor:  '#6b7280',
            reverseButtons:    true,
        }).then(function (result) {
            if (!result.isConfirmed) return;

            const remaining = (fwoBoqData || []).filter(function (s) {
                return String(s.id_boq) !== boqId;
            });

            if (!remaining.length) {
                Notify.warning('Tidak dapat menghapus — minimal harus ada 1 item BOQ');
                return;
            }

            const sections = remaining.map(function (s) {
                return { id_boq: s.id_boq, qty: s.qty, keterangan: s.keterangan };
            });

            $.ajax({
                url:         window.route.fwoBoqUpdate + currentFwoData.id_fwo,
                method:      'PUT',
                contentType: 'application/json',
                headers:     { 'X-CSRF-TOKEN': window.route.csrf },
                data:        JSON.stringify({ sections }),
                success: function () {
                    Notify.success('Item BOQ berhasil dihapus');
                    loadFwoBoqList(currentFwoData.id_fwo);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Gagal menghapus item BOQ');
                },
            });
        });
    });

    // Hapus file existing dalam edit mode attachment
    $(document).on('click', '.btn-remove-att-existing', function () {
        $(this).closest('.att-existing-file').remove();
    });

    // Hapus grup attachment
    $(document).on('click', '.btn-remove-att-group', function () {
        const $group = $(this).closest('.fwo-att-group');
        const idx    = $group.data('idx');
        const pond   = fwoAttPondInstances[idx];
        if (pond) { pond.destroy(); delete fwoAttPondInstances[idx]; }
        $group.remove();
    });

    // Eye toggle untuk detail items di bulk modal
    $(document).on('click', '.btn-bulk-eye', function () {
        const $btn     = $(this);
        const id_boq   = $btn.data('boq-id');
        const $detail  = $btn.siblings('.bulk-boq-items-detail');
        const $icon    = $btn.find('i');

        if ($detail.is(':visible')) {
            $detail.hide();
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            return;
        }

        if ($detail.data('loaded')) {
            $detail.show();
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            return;
        }

        $detail.html('<span class="text-muted small"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</span>').show();
        $icon.removeClass('fa-eye').addClass('fa-eye-slash');

        $.get(window.route.boqSectionItems + id_boq + '/section-items?id_fwo=' + (currentFwoData?.id_fwo ?? 0), function (res) {
            const items = res.items ?? [];
            if (!items.length) {
                $detail.html('<span class="text-muted small">Tidak ada item</span>');
            } else {
                $detail.html(
                    '<div style="border-left:3px solid #e2e8f0;padding-left:8px;margin-top:4px;">' +
                    items.map(function (item, j) {
                        return `<div class="text-muted small py-1" style="${j > 0 ? 'border-top:1px solid #f1f5f9;' : ''}">
                            <span class="fw-semibold text-dark">${j + 1}.</span>
                            ${escHtml(item.judul_indonesia ?? '—')}
                            <span class="text-muted">/ ${escHtml(item.judul_inggris ?? '—')}</span>
                            <span class="item-meta-badge ms-1">${escHtml(item.kode_unit || '—')} · ${escHtml(String(item.nilai ?? '—'))}</span>
                        </div>`;
                    }).join('') +
                    '</div>'
                );
            }
            $detail.data('loaded', true);
        }).fail(function () {
            $detail.html('<span class="text-danger small">Gagal memuat</span>');
        });
    });

    // Konfirmasi tambah section
    $('#btnConfirmFwoBoq').on('click', function () {
        if (!selectedBoq) return;
        const qty = $('#fwoBoqQtyInput').val() ? parseInt($('#fwoBoqQtyInput').val()) : null;
        const ket = $('#fwoBoqKetInput').val() || null;

        const maxAllowed = selectedBoq.remaining_qty ?? selectedBoq.qty_boq ?? null;
        if (qty && maxAllowed !== null && qty > maxAllowed) {
            const boqHint = selectedBoq.qty_boq ? ` (maks BOQ: ${selectedBoq.qty_boq})` : ` (maks: ${maxAllowed})`;
            Notify.warning('Qty tidak boleh melebihi batas' + boqHint);
            return;
        }

        if (fwoBoqDirectMode) {
            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

            const existingSections = (fwoBoqData || []).map(function (s) {
                return { id_boq: s.id_boq, qty: s.qty, keterangan: s.keterangan };
            });
            const allSections = existingSections.concat([{
                id_boq:     selectedBoq.id,
                qty:        qty,
                keterangan: ket,
            }]);

            $.ajax({
                url:         window.route.fwoBoqUpdate + currentFwoData.id_fwo,
                method:      'PUT',
                contentType: 'application/json',
                headers:     { 'X-CSRF-TOKEN': window.route.csrf },
                data:        JSON.stringify({ sections: allSections }),
                success: function () {
                    Notify.success('BOQ berhasil ditambahkan');
                    bootstrap.Modal.getInstance('#modalAddFwoBoq').hide();
                    loadFwoBoqList(currentFwoData.id_fwo);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Gagal menambahkan BOQ');
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Tambah Item');
                },
            });
        } else {
            addFwoBoqSection({
                id_boq:           selectedBoq.id,
                id_testing_point: selectedBoq.id_testing_point,
                point_name:       selectedBoq.text,
                qty:              qty,
                boq_qty:          selectedBoq.qty_boq,
                remaining_qty:    selectedBoq.remaining_qty,
                satuan:           selectedBoq.satuan,
                keterangan:       ket,
                items:            window._fwoBoqPreviewItems ?? [],
            });
            bootstrap.Modal.getInstance('#modalAddFwoBoq').hide();
        }
    });

    // Toggle items di modal tambah BOQ
    $(document).on('click', '#btnToggleModalItems', function () {
        const $list = $('#fwoBoqModalItemsList');
        const $icon = $(this).find('i');
        const isVisible = $list.is(':visible');
        $list.toggle(!isVisible);
        $icon.toggleClass('fa-eye', isVisible).toggleClass('fa-eye-slash', !isVisible);
    });

    // Toggle items di section edit mode
    $(document).on('click', '.btn-toggle-boq-items', function () {
        const $items = $(this).closest('.card-body').find('.fwo-boq-items');
        const $icon  = $(this).find('i');
        const isVisible = $items.is(':visible');
        $items.toggle(!isVisible);
        $icon.toggleClass('fa-eye', isVisible).toggleClass('fa-eye-slash', !isVisible);
    });

    // Hapus baris personel (delegasi)
    $(document).on('click', '.btn-remove-personel-row', function () {
        $(this).closest('.personel-edit-row').remove();
        syncPersonelEditEmpty();
    });

    // Hapus section (delegasi)
    $(document).on('click', '.btn-remove-fwo-boq', function () {
        const boqId = String($(this).closest('.fwo-boq-section').data('boq-id'));
        addedBoqIds.delete(boqId);
        $(this).closest('.fwo-boq-section').remove();
        checkFwoBoqEmpty();
    });

    // Selesaikan FWO
    $(document).on('click', '#btnCompleteFwo', function () {
        const fwoId = $(this).data('fwo-id');
        const $btn  = $(this);
        Swal.fire({
            title: 'Selesaikan FWO?',
            html:  'Status akan berubah menjadi <strong>Completed</strong>.<br><span style="font-size:13px;color:#6b7280;">Status tidak dapat dikembalikan ke Planned.</span>',
            icon:  'question',
            showCancelButton:    true,
            confirmButtonText:   '<i class="fa-solid fa-circle-check me-1"></i> Ya, Selesaikan',
            cancelButtonText:    'Batal',
            confirmButtonColor:  '#16a34a',
            cancelButtonColor:   '#6b7280',
            reverseButtons:      true,
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin" style="font-size:10px;"></i> Memproses...');
            $.ajax({
                url:     window.route.fwoComplete + fwoId + '/complete',
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': window.route.csrf },
                success: function () {
                    page.loadDetail(page.selectedRow.id);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Gagal menyelesaikan FWO');
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-circle-check" style="font-size:10px;"></i> Selesaikan');
                },
            });
        });
    });

    page = new CrudPageController({
        primaryKey: 'id_fwo',
        renderForm: renderFwoForm,
        initSelect: function () {
            $('#detail_id_wo').on('select2:select', function (e) {
                $('input[name="judul_pekerjaan"]').val(e.params.data.judul || '');
            });
        },
        afterLoad: function (res) {
            currentFwoData      = res;
            currentPersonelData = res.personels || [];
            try {
                fwoAttachmentData = res.attachments
                    ? (typeof res.attachments === 'string' ? JSON.parse(res.attachments) : res.attachments)
                    : [];
            } catch (e) { fwoAttachmentData = []; }
            $('#fwoPersonelContent').html(renderPersonelView(currentPersonelData));
            $('#fwoAttachmentContent').html(renderFwoAttachmentView(fwoAttachmentData));
            loadFwoBoqList(res.id_fwo);
            initFpDate('#detailContent');

            // Waktu Kedatangan tidak boleh melewati Tanggal Selesai FWO
            const $tanggalSelesaiFwo = $('#detailContent input[name="tanggal_selesai"]');
            if ($tanggalSelesaiFwo[0] && $tanggalSelesaiFwo[0]._fp) {
                $tanggalSelesaiFwo[0]._fp.set('onChange', function (selectedDates, dateStr) {
                    const $tiba = $('#detailContent input[name="waktu_kedatangan"]');
                    if (!$tiba[0] || !$tiba[0]._fp) return;
                    $tiba[0]._fp.set('maxDate', dateStr ? dateStr + ' 23:59' : null);
                    const tibaVal = $tiba.val();
                    if (dateStr && tibaVal && tibaVal.substring(0, 10) > dateStr) {
                        $tiba[0]._fp.clear();
                    }
                });
            }
        },
    });

    page.bindEditBehaviour = function () {
        bindEditToggle({
            container: '#detailContent',
            onEditStart: function () {
                enterPersonelEditMode();
                enterFwoBoqEditMode();
                enterFwoAttachmentEditMode();
            },
            onEditCancel: function () {
                exitPersonelEditMode();
                exitFwoBoqEditMode();
                exitFwoAttachmentEditMode();
            },
            onSave: function () {
                saveAll(page.selectedRow.id);
            },
        });
    };

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus Fieldwork?', function () {
            $.ajax({
                url: window.route.update + id,
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' },
                success: function (res) {
                    Notify.success(res.message || 'Data berhasil dihapus');
                    const woId = currentFwoData && currentFwoData.id_wo;
                    setTimeout(function () {
                        window.location.href = woId ? '/work-orders?open=' + woId : window.location.pathname;
                    }, 1000);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                },
            });
        });
    });
});

// ── Personel edit mode ─────────────────────────────────────────────────────────
function enterPersonelEditMode() {
    personelViewHtml = $('#fwoPersonelContent').html();
    personelEditIdx  = 0;

    const editBar = `<div class="d-flex justify-content-start align-items-center mb-3">
        <button type="button" id="btnAddPersonelRow" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Tambah Personel
        </button>
    </div>`;

    const emptyMsg = `<div id="personelEditEmpty" style="display:none;text-align:center;padding:16px;border:1px dashed #e2e8f0;border-radius:8px;color:#94a3b8;font-size:13px;">
        Belum ada personel. Klik <strong>+ Tambah Personel</strong>.
    </div>`;

    $('#fwoPersonelContent').html(editBar + '<div id="personelEditRows"></div>' + emptyMsg);

    // Pre-load existing personels
    if (currentPersonelData.length > 0) {
        currentPersonelData.forEach(function (p) {
            addPersonelEditRow({ id: p.id_personnel, text: p.user_name }, p.role);
        });
    } else {
        syncPersonelEditEmpty();
    }

    $('#btnAddPersonelRow').on('click', function () {
        addPersonelEditRow(null, '');
    });
}

function exitPersonelEditMode() {
    personelEditIdx  = 0;
    personelViewHtml && $('#fwoPersonelContent').html(personelViewHtml);
    personelViewHtml = null;
}

function addPersonelEditRow(userData, roleVal) {
    const idx = personelEditIdx++;
    const roleOptions = ['Leader', 'Driver', 'Anggota', 'PIC Project'].map(function (r) {
        return `<option value="${r}" ${roleVal === r ? 'selected' : ''}>${r}</option>`;
    }).join('');

    const row = $(`
        <div class="personel-edit-row d-flex align-items-end gap-2 mb-2" data-idx="${idx}">
            <div style="flex:1;min-width:0;">
                <label class="form-label form-label-sm text-muted mb-1">Personel</label>
                <select class="form-select personel-edit-user" data-idx="${idx}"></select>
            </div>
            <div style="width:160px;flex-shrink:0;">
                <label class="form-label form-label-sm text-muted mb-1">Role</label>
                <select class="form-select personel-edit-role">
                    <option value="">— Pilih Role —</option>
                    ${roleOptions}
                </select>
            </div>
            <div style="flex-shrink:0;padding-bottom:2px;">
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-personel-row" title="Hapus">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>
    `);

    $('#personelEditRows').append(row);

    const $select = row.find('.personel-edit-user');
    $select.select2({
        width: '100%',
        placeholder: 'Ketik nama personel...',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: window.route.personnelSelect2,
            dataType: 'json',
            delay: 200,
            data: p => ({ q: p.term }),
            processResults: d => ({ results: d }),
            cache: true,
        },
    });

    row.find('.personel-edit-role').select2({
        width: '100%',
        minimumResultsForSearch: Infinity,
    });

    if (userData) {
        const opt = new Option(userData.text, userData.id, true, true);
        $select.append(opt).trigger('change');
    }

    syncPersonelEditEmpty();
}

function syncPersonelEditEmpty() {
    const has = $('#personelEditRows .personel-edit-row').length > 0;
    $('#personelEditEmpty').toggle(!has);
}

function collectPersonelRows() {
    const rows = [];
    $('#personelEditRows .personel-edit-row').each(function () {
        const id_personnel = $(this).find('.personel-edit-user').val();
        const role         = $(this).find('.personel-edit-role').val() || null;
        if (id_personnel) rows.push({ id_personnel: parseInt(id_personnel), role });
    });
    return rows;
}

// ── Load & render view mode ────────────────────────────────────────────────────
function loadFwoBoqList(id_fwo) {
    $('#fwoBoqContent').html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>'
    );

    $.get(window.route.fwoBoqByFwo + id_fwo, function (data) {
        fwoBoqData = data ?? [];
        const isLocked = currentFwoData && (currentFwoData.status === 'completed' || !!currentFwoData.deleted_at);
        $('#fwoBoqContent').html(renderFwoBoqView(fwoBoqData, isLocked));
    }).fail(function () {
        $('#fwoBoqContent').html(
            '<div class="text-center text-danger py-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data</div>'
        );
    });
}

// ── Enter BOQ edit mode ────────────────────────────────────────────────────────
function enterFwoBoqEditMode() {
    fwoBoqViewHtml = $('#fwoBoqContent').html();
    addedBoqIds.clear();

    let editHtml = renderFwoBoqEditBar();
    editHtml += '<div id="fwoBoqSections"></div>';
    editHtml += '<div id="fwoBoqEmpty" style="display:none;">' +
        '<div class="card"><div class="card-body text-center text-muted py-4">' +
        '<i class="fa-solid fa-layer-group fa-2x mb-2 d-block opacity-25"></i>' +
        'Belum ada item. Klik <strong>+ Tambah Item</strong> untuk menambahkan.' +
        '</div></div></div>';

    $('#fwoBoqContent').html(editHtml);

    // Pre-load existing sections
    if (fwoBoqData.length > 0) {
        fwoBoqData.forEach(function (sec) {
            addFwoBoqSection(sec);
        });
    } else {
        $('#fwoBoqEmpty').show();
    }

    fwoBoqSnapshot = JSON.stringify(collectFwoBoqSections());

    $('#btnAddFwoBoqSection').on('click', function () {
        fwoBoqDirectMode = false;
        selectedBoq = null;
        resetFwoBoqModal();
        $('#selectFwoBoq').val(null).trigger('change');
        new bootstrap.Modal('#modalAddFwoBoq').show();
    });
}

function exitFwoBoqEditMode() {
    addedBoqIds.clear();
    fwoBoqSnapshot = null;
    $('#fwoBoqContent').html(fwoBoqViewHtml);
    fwoBoqViewHtml = null;
}

// ── Save all (FWO info + personel + BOQ) ──────────────────────────────────────
function saveAll(id_fwo) {
    const formData    = $('#detailForm').serialize();
    const personels   = collectPersonelRows();
    const boqSections = collectFwoBoqSections();

    for (const sec of boqSections) {
        const $sec     = $(`.fwo-boq-section[data-boq-id="${sec.id_boq}"]`);
        const rawRem   = $sec.data('remaining-qty');
        const rawBoq   = $sec.data('boq-qty');
        const maxAllow = rawRem !== '' && rawRem !== undefined ? parseInt(rawRem) : null;
        const boqTotal = rawBoq !== '' && rawBoq !== undefined ? parseInt(rawBoq) : null;
        if (sec.qty && maxAllow !== null && sec.qty > maxAllow) {
            const ptName = $sec.find('.fw-semibold').first().text();
            const hint   = boqTotal ? ` (maks BOQ: ${boqTotal})` : ` (maks: ${maxAllow})`;
            Notify.warning(`Qty section "${ptName}" melebihi batas${hint}`);
            return;
        }
    }

    const tanggalMulai     = $('[name="tanggal_mulai"]').val();
    const tanggalSelesai   = $('[name="tanggal_selesai"]').val();
    const waktuKedatangan  = $('[name="waktu_kedatangan"]').val();
    if (tanggalMulai && waktuKedatangan) {
        const tglMulai = new Date(tanggalMulai);
        const tglTiba  = new Date(waktuKedatangan);
        if (tglTiba < tglMulai) {
            Notify.warning('Waktu kedatangan tidak boleh lebih kecil dari tanggal mulai');
            return;
        }
    }
    if (tanggalSelesai && waktuKedatangan && waktuKedatangan.substring(0, 10) > tanggalSelesai) {
        Notify.warning('Waktu kedatangan tidak boleh lebih besar dari tanggal selesai');
        return;
    }

    Notify.confirm('Simpan semua perubahan?', function () {
        const $saveBtn = $('.btn-save-context');
        $saveBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url:    window.route.update + id_fwo,
            method: 'POST',
            data:   formData,
            success: function () {
                $.ajax({
                    url:         window.route.personelUpdate + id_fwo + '/personels',
                    method:      'PUT',
                    contentType: 'application/json',
                    headers:     { 'X-CSRF-TOKEN': window.route.csrf },
                    data:        JSON.stringify({ personels }),
                    success: function () {
                        $.ajax({
                            url:         window.route.fwoBoqUpdate + id_fwo,
                            method:      'PUT',
                            contentType: 'application/json',
                            headers:     { 'X-CSRF-TOKEN': window.route.csrf },
                            data:        JSON.stringify({ sections: boqSections }),
                            success: function () {
                                saveAttachments(id_fwo, function () {
                                    Notify.success('Data berhasil disimpan');
                                    page.loadDetail(id_fwo);
                                });
                            },
                            error: function (xhr) {
                                Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan Fieldwork BOQ');
                                $saveBtn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Simpan');
                            },
                        });
                    },
                    error: function (xhr) {
                        Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan personel');
                        $saveBtn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Simpan');
                    },
                });
            },
            error: function (xhr) {
                const errs = xhr.responseJSON?.errors;
                const msg  = errs
                    ? Object.values(errs).flat().join(' ')
                    : (xhr.responseJSON?.message || 'Gagal menyimpan data fieldwork');
                Notify.error(msg);
                $saveBtn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Simpan');
            },
        });
    });
}

// ── Section management ─────────────────────────────────────────────────────────
function addFwoBoqSection(sec) {
    const html = renderFwoBoqSectionEdit(sec);
    const $el  = $(html);
    $el.attr('data-boq-qty', sec.boq_qty ?? '');
    $('#fwoBoqEmpty').hide();
    $('#fwoBoqSections').append($el);
    initNumericMask($el);
    addedBoqIds.add(String(sec.id_boq));
}

function checkFwoBoqEmpty() {
    if ($('.fwo-boq-section').length === 0) $('#fwoBoqEmpty').show();
}

function collectFwoBoqSections() {
    const sections = [];
    $('.fwo-boq-section').each(function () {
        const $sec = $(this);
        sections.push({
            id_boq:     parseInt($sec.data('boq-id')),
            qty:        rawNumVal($sec.find('.input-fwo-qty')[0]),
            keterangan: $sec.find('.input-fwo-ket').val() || null,
        });
    });
    return sections;
}

// ── Modal helpers ──────────────────────────────────────────────────────────────
function loadFwoBoqSectionPreview(id_boq) {
    resetFwoBoqModal();
    $('#fwoBoqModalLoading').removeClass('d-none');

    const sectionUrl = window.route.boqSectionItems + id_boq + '/section-items?id_fwo=' + (currentFwoData?.id_fwo ?? 0);
    $.get(sectionUrl, function (res) {
        $('#fwoBoqModalLoading').addClass('d-none');
        const items = res.items ?? [];

        if (!items.length) {
            $('#fwoBoqModalEmpty').removeClass('d-none');
        } else {
            $('#fwoBoqModalItemsList').html(
                items.map((item, j) => renderFwoBoqModalItem(item, j)).join('')
            );
        }

        // Pakai remaining_qty (sisa setelah FWO lain) sebagai batas, bukan qty_boq total
        const maxVal = res.remaining_qty ?? res.qty_boq ?? null;
        if (maxVal !== null) {
            const satuan = res.satuan ? ' ' + res.satuan : '';
            $('#fwoBoqMaxHint').html(`<span class="text-danger small">(sisa ${maxVal}${satuan})</span>`);
            $('#fwoBoqQtyInput').attr('max', maxVal);
        }

        // Simpan remaining_qty ke selectedBoq untuk validasi saat konfirmasi
        if (selectedBoq) {
            selectedBoq.remaining_qty = res.remaining_qty ?? res.qty_boq ?? null;
        }

        window._fwoBoqPreviewItems = items;
        $('#fwoBoqModalPreview').removeClass('d-none');
        $('#btnConfirmFwoBoq').prop('disabled', false);
    }).fail(function () {
        $('#fwoBoqModalLoading').addClass('d-none');
        $('#fwoBoqModalEmpty').removeClass('d-none');
    });
}

// ── Bulk BOQ modal (direct mode) ───────────────────────────────────────────────
function openBulkBoqModal() {
    $('#bulkBoqLoading').removeClass('d-none');
    $('#bulkBoqEmpty, #bulkBoqList').addClass('d-none');
    $('#btnSaveBulkBoq').prop('disabled', true);
    new bootstrap.Modal('#modalBulkAddFwoBoq').show();

    $.get(
        window.route.boqSelect2ByWo + (currentFwoData?.id_wo ?? 0) +
        '?id_fwo=' + (currentFwoData?.id_fwo ?? 0),
        function (data) {
            $('#bulkBoqLoading').addClass('d-none');
            if (!data || !data.length) {
                $('#bulkBoqEmpty').removeClass('d-none');
                return;
            }
            $('#bulkBoqList').html(renderBulkBoqList(data)).removeClass('d-none');
            $('#btnSaveBulkBoq').prop('disabled', false);
        }
    ).fail(function () {
        $('#bulkBoqLoading').addClass('d-none');
        $('#bulkBoqEmpty').removeClass('d-none');
    });
}

function renderBulkBoqList(boqItems) {
    const TH = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 10px;vertical-align:middle;"';

    const added    = boqItems.filter(function (item) {
        return (fwoBoqData || []).some(function (s) { return String(s.id_boq) === String(item.id); });
    });
    const notAdded = boqItems.filter(function (item) {
        return !(fwoBoqData || []).some(function (s) { return String(s.id_boq) === String(item.id); });
    });

    function buildRow(item, num) {
        const existing    = (fwoBoqData || []).find(function (s) { return String(s.id_boq) === String(item.id); });
        const existingQty = existing ? (existing.qty ?? '') : '';
        const existingKet = existing ? (existing.keterangan ?? '') : '';
        const satuan      = item.satuan ? ' ' + escHtml(item.satuan) : '';
        const sisaColor   = (item.remaining_qty > 0) ? '#1d4ed8' : '#dc2626';

        return `<tr>
            <td ${TD} style="padding:8px 12px;color:#94a3b8;text-align:center;font-size:12px;">${num}</td>
            <td ${TD} style="padding:8px 12px;color:#1e293b;font-weight:500;">
                ${escHtml(item.text ?? '—')}
                <button type="button" class="btn-bulk-eye"
                    data-boq-id="${item.id}" title="Lihat detail items"
                    style="background:none;border:none;padding:0 0 0 4px;cursor:pointer;color:#94a3b8;font-size:12px;vertical-align:middle;line-height:1;">
                    <i class="fa-solid fa-eye"></i>
                </button>
                <div class="bulk-boq-items-detail mt-1" style="display:none;"></div>
            </td>
            <td ${TD} style="padding:8px 12px;color:#475569;white-space:nowrap;">${item.qty_boq ?? '—'}${satuan}</td>
            <td ${TD} style="padding:8px 12px;white-space:nowrap;">
                <span style="color:${sisaColor};font-weight:600;">${item.remaining_qty ?? '—'}${satuan}</span>
            </td>
            <td ${TD} style="padding:8px 10px;width:110px;">
                <input type="number" class="form-control form-control-sm bulk-boq-qty"
                    data-boq-id="${item.id}"
                    data-max="${item.remaining_qty ?? ''}"
                    min="0" placeholder="0" value="${escHtml(String(existingQty))}">
            </td>
            <td ${TD} style="padding:8px 10px;">
                <input type="text" class="form-control form-control-sm bulk-boq-ket"
                    placeholder="opsional" value="${escHtml(existingKet)}">
            </td>
        </tr>`;
    }

    const addedRows    = added.map(function (item, i) { return buildRow(item, i + 1); }).join('');
    const notAddedRows = notAdded.map(function (item, i) { return buildRow(item, added.length + i + 1); }).join('');

    const dividerRow = (added.length && notAdded.length)
        ? `<tr>
            <td colspan="6" style="padding:4px 0;">
                <hr style="margin:4px 12px;border-color:#e2e8f0;">
                <span style="display:block;text-align:center;color:#94a3b8;font-size:11px;font-weight:600;letter-spacing:.4px;margin-bottom:4px;">
                    + Tambahkan item lainnya
                </span>
            </td>
           </tr>`
        : '';

    return `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th ${TH} style="width:40px;text-align:center;">#</th>
                    <th ${TH}>Item BOQ</th>
                    <th ${TH}>Qty Kontrak</th>
                    <th ${TH}>Sisa</th>
                    <th ${TH} style="width:110px;">Qty FWO</th>
                    <th ${TH}>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                ${addedRows}
                ${dividerRow}
                ${notAddedRows}
            </tbody>
        </table>
    </div>`;
}

function saveBulkBoq($btn) {
    const sections = [];
    let hasError   = false;

    $('#bulkBoqList .bulk-boq-qty').each(function () {
        const qty = parseInt($(this).val()) || 0;
        if (qty <= 0) return;

        const maxRaw  = $(this).data('max');
        const max     = maxRaw !== '' && maxRaw !== undefined ? parseInt(maxRaw) : null;
        if (max !== null && qty > max) {
            const namaItem = $(this).closest('tr').find('td:nth-child(2)').clone().find('.btn-bulk-eye, .bulk-boq-items-detail').remove().end().text().trim();
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                html: '<strong>' + escHtml(namaItem) + '</strong><br><span style="font-size:14px;">Qty melebihi sisa yang tersedia (maks: ' + max + ')</span>',
            });
            hasError = true;
            return false;
        }

        const id_boq = parseInt($(this).data('boq-id'));
        const ket    = $(this).closest('tr').find('.bulk-boq-ket').val() || null;
        sections.push({ id_boq, qty, keterangan: ket });
    });

    if (hasError) return;
    if (!sections.length) {
        Notify.warning('Isi minimal 1 item dengan Qty lebih dari 0');
        return;
    }

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

    $.ajax({
        url:         window.route.fwoBoqUpdate + currentFwoData.id_fwo,
        method:      'PUT',
        contentType: 'application/json',
        headers:     { 'X-CSRF-TOKEN': window.route.csrf },
        data:        JSON.stringify({ sections }),
        success: function () {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan');
            Notify.success('BOQ berhasil disimpan');
            bootstrap.Modal.getInstance('#modalBulkAddFwoBoq').hide();
            loadFwoBoqList(currentFwoData.id_fwo);
        },
        error: function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan BOQ');
            $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan');
        },
    });
}

function resetFwoBoqModal() {
    $('#fwoBoqModalLoading, #fwoBoqModalEmpty, #fwoBoqModalPreview').addClass('d-none');
    $('#fwoBoqModalItemsList').empty().hide();
    $('#btnToggleModalItems').find('i').removeClass('fa-eye-slash').addClass('fa-eye');
    $('#fwoBoqQtyInput').val('').removeAttr('max');
    $('#fwoBoqKetInput').val('');
    $('#fwoBoqMaxHint').html('');
    $('#btnConfirmFwoBoq').prop('disabled', true);
    window._fwoBoqPreviewItems = [];
}

// ── Attachment edit mode ───────────────────────────────────────────────────────
function enterFwoAttachmentEditMode() {
    fwoAttachmentViewHtml = $('#fwoAttachmentContent').html();
    fwoAttPondInstances   = [];
    fwoAttGroupIdx        = 0;

    let html = renderFwoAttachmentEditBar();
    html += '<div id="fwoAttGroups"></div>';
    html += '<div id="fwoAttEmpty" class="text-center text-muted py-4" style="display:none;">' +
        '<i class="fa-solid fa-paperclip fa-2x d-block mb-2 opacity-25"></i>' +
        'Klik <strong>+ Tambah Tipe Dokumen</strong> untuk menambahkan.' +
        '</div>';

    $('#fwoAttachmentContent').html(html);

    if (fwoAttachmentData.length > 0) {
        fwoAttachmentData.forEach(function (group) { addAttachmentGroup(group); });
    } else {
        $('#fwoAttEmpty').show();
    }

    $(document).on('click.attgroup', '#btnAddAttachmentGroup', function () {
        addAttachmentGroup({ type: FWO_ATTACHMENT_TYPES[0], files: [] });
    });
}

function addAttachmentGroup(group) {
    $('#fwoAttEmpty').hide();
    const idx  = fwoAttGroupIdx++;
    const html = renderFwoAttachmentGroupEdit(group, idx);
    const $el  = $(html);
    $('#fwoAttGroups').append($el);

    const pond = FilePond.create($el.find('.fwo-att-filepond')[0], {
        allowMultiple: true,
        labelIdle: 'Drag & Drop file atau <span class="filepond--label-action">Browse</span>',
        acceptedFileTypes: ['image/*', 'application/pdf',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    });
    fwoAttPondInstances[idx] = pond;
}

function exitFwoAttachmentEditMode() {
    Object.values(fwoAttPondInstances).forEach(function (p) { try { p.destroy(); } catch (e) {} });
    fwoAttPondInstances = [];
    fwoAttGroupIdx      = 0;
    $(document).off('click.attgroup');
    if (fwoAttachmentViewHtml !== null) {
        $('#fwoAttachmentContent').html(fwoAttachmentViewHtml);
        fwoAttachmentViewHtml = null;
    }
}

function collectFwoAttachmentData() {
    const groups = [];
    $('#fwoAttGroups .fwo-att-group').each(function () {
        const idx      = $(this).data('idx');
        const type     = $(this).find('.fwo-att-type').val();
        const existing = $(this).find('.fwo-att-existing').map(function () { return $(this).val(); }).get();
        const pond     = fwoAttPondInstances[idx];
        const newFiles = pond ? pond.getFiles().map(function (f) { return f.file; }) : [];
        groups.push({ type, existing, newFiles });
    });
    return groups;
}

function saveAttachments(id_fwo, callback) {
    const groups = collectFwoAttachmentData();

    const hasNew      = groups.some(function (g) { return g.newFiles.length > 0; });
    const hasExisting = groups.some(function (g) { return g.existing.length > 0; });

    if (!hasNew && !hasExisting && !fwoAttachmentData.length) {
        callback();
        return;
    }

    const fd = new FormData();
    fd.append('_token', window.route.csrf);
    fd.append('_method', 'POST');

    groups.forEach(function (group, i) {
        fd.append('groups[' + i + '][type]', group.type);
        group.existing.forEach(function (path, j) {
            fd.append('groups[' + i + '][existing][' + j + ']', path);
        });
        group.newFiles.forEach(function (file, j) {
            fd.append('groups[' + i + '][files][' + j + ']', file);
        });
    });

    $.ajax({
        url:         window.route.fwoAttachments + id_fwo + '/attachments',
        method:      'POST',
        data:        fd,
        processData: false,
        contentType: false,
        success:     function () { callback(); },
        error:       function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan attachment');
        },
    });
}

// ── SAMPLE ─────────────────────────────────────────────────────────────────────

function loadSampleData(idFwo) {
    const $wrap = $('#fwoSampleContent');
    $wrap.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');

    $.get(`/lab-samples/${idFwo}/list`)
        .done(function (res) {
            const boqs      = res.data || [];
            const fwoStatus = res.fwo_status || '';
            const isLocked  = fwoStatus === 'completed';
            const idSite    = res.id_site || null;

            if (isLocked) {
                $('#fwoTabActionsSample').html(
                    '<span style="font-size:11px;color:#dc2626;display:flex;align-items:center;gap:5px;"><i class="fa-solid fa-lock" style="font-size:10px;"></i> FWO sudah selesai, data tidak dapat diubah</span>'
                );
            }

            if (!boqs.length) {
                $wrap.html('<div class="text-center text-muted py-4">Tidak ada BOQ pada FWO ini.</div>');
                return;
            }

            // Load sampling points dari site FWO, lalu render
            window._samplingPoints = [];
            const renderAll = function () {
                $wrap.html(renderSampleList(boqs, isLocked));
                initSampleStatusSelect2($wrap[0]);
                initSampleTitikSelect2($wrap[0]);
                if (!isLocked) initSampleModalTitikSelect2();
            };

            if (idSite) {
                $.get(`/lab-samples/sampling-points/${idSite}`)
                    .done(function (data) { window._samplingPoints = data || []; })
                    .always(renderAll);
            } else {
                renderAll();
            }
        })
        .fail(function () {
            $wrap.html('<div class="text-center text-danger py-4">Gagal memuat data sample.</div>');
        });
}

function _spOptions(usedLocations, currentVal) {
    // value = text label (bukan id_sp) agar tersimpan langsung ke kolom titik_lokasi
    return [{ id: '', text: '' }].concat(
        (window._samplingPoints || []).map(function (sp) {
            const isUsed = usedLocations && usedLocations.includes(sp.text) && sp.text !== currentVal;
            return { id: sp.text, text: sp.text, _used: isUsed };
        })
    );
}

function initSampleStatusSelect2(container) {
    $(container).find('.sample-inline-status').each(function () {
        const $sel = $(this);
        if ($sel.data('select2')) $sel.select2('destroy');
        $sel.select2({
            width: 'resolve',
            minimumResultsForSearch: Infinity,
            dropdownParent: $sel.closest('td'),
        });
    });
}

function initSampleTitikSelect2(container) {
    // Kumpulkan lokasi yang sudah terpakai per BOQ (per tbody)
    $(container).find('table').each(function () {
        const $table = $(this);
        const usedLocations = [];
        $table.find('.sample-inline-titik').each(function () {
            const val = $(this).data('current') || '';
            if (val) usedLocations.push(val);
        });

        $table.find('.sample-inline-titik').each(function () {
            const $sel    = $(this);
            const current = $sel.data('current') || '';
            if ($sel.data('select2')) $sel.select2('destroy');
            $sel.select2({
                width: 'resolve',
                placeholder: 'Pilih titik…',
                allowClear: true,
                dropdownParent: $sel.closest('td'),
                data: _spOptions(usedLocations, current),
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
            // Blokir klik pada opsi yang sudah terpakai
            .on('select2:selecting', function (e) {
                if (e.params && e.params.args && e.params.args.data && e.params.args.data._used) {
                    e.preventDefault();
                }
            })
            .val(current).trigger('change.select2');
        });
    });
}

function initSampleModalTitikSelect2() {
    const $sel = $('#sampleModal-titik');
    if ($sel.data('select2')) $sel.select2('destroy');
    $sel.select2({
        width: '100%',
        placeholder: 'Pilih titik lokasi…',
        allowClear: true,
        dropdownParent: $('#sampleModal'),
        data: _spOptions(),
    });
}

const SAMPLE_STATUS_LABEL = { belum_diambil: 'Belum Diambil', diambil: 'Diambil', dikirim: 'Dikirim' };
const SAMPLE_STATUS_COLOR = { belum_diambil: '#64748b', diambil: '#0369a1', dikirim: '#15803d' };
const JENIS_LABEL = { env: 'ENV', we: 'WE', mp: 'MP', product: 'Product' };
const KONDISI_LABEL = { baik: 'Baik', rusak: 'Rusak', tidak_lengkap: 'Tidak Lengkap' };
const KONDISI_COLOR = { baik: '#15803d', rusak: '#dc2626', tidak_lengkap: '#b45309' };

const SAMPLE_MONTH_SHORT = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
function fmtSampleDate(str) {
    if (!str) return null;
    const d = new Date(str);
    if (isNaN(d)) return null;
    return d.getDate() + '-' + SAMPLE_MONTH_SHORT[d.getMonth()] + '-' + d.getFullYear();
}

function renderSampleList(boqs, isLocked) {
    return boqs.map(function (boq) {
        const total    = (boq.samples || []).length;
        const diambil  = (boq.samples || []).filter(s => s.status === 'diambil' || s.status === 'dikirim').length;
        const dikirim  = (boq.samples || []).filter(s => s.status === 'dikirim').length;
        const pct      = total > 0 ? Math.round((diambil / total) * 100) : 0;
        const barColor = pct === 100 ? '#15803d' : '#0369a1';
        const sisa     = Math.max(0, (boq.qty || 0) - total);

        const slotRows = total === 0
            ? `<tr><td colspan="9" class="text-center text-muted py-3" style="font-size:12px;font-style:italic;">Belum ada sample — gunakan tombol "Tambah Sample" di atas</td></tr>`
            : (boq.samples || []).map(function (s) {
                const statusVal = s.status || 'belum_diambil';
                const jenisTag    = s.jenis_sample
                    ? `<span style="font-size:10px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:4px;padding:1px 6px;">${JENIS_LABEL[s.jenis_sample] || s.jenis_sample}</span>`
                    : `<span style="font-size:10px;color:#94a3b8;font-style:italic;">–</span>`;
                const noSample = s.no_sample
                    ? `<span style="font-size:11px;font-weight:600;">${escHtml(s.no_sample)}</span>`
                    : `<span class="text-muted" style="font-size:11px;font-style:italic;">–</span>`;

                const attCount = (s.attachments || []).length;
                const attBadge = attCount > 0
                    ? `<span class="btn-sample-edit" data-id="${s.id_lab_sample}"
                            title="${attCount} lampiran" style="font-size:11px;color:#0369a1;cursor:pointer;white-space:nowrap;">
                            <i class="fa-solid fa-paperclip"></i> ${attCount}
                       </span>`
                    : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

                const titikVal  = s.titik_lokasi || '';
                const titikCell = !isLocked
                    ? `<select class="form-select form-select-sm sample-inline-titik"
                            data-id="${s.id_lab_sample}"
                            data-current="${escHtml(titikVal)}"
                            style="font-size:11px;width:260px;"></select>`
                    : (titikVal ? escHtml(titikVal) : '<span style="color:#94a3b8;font-style:italic;">–</span>');

                const tglCell = fmtSampleDate(s.tanggal_pengambilan)
                    ? `<span style="font-size:11px;white-space:nowrap;">${fmtSampleDate(s.tanggal_pengambilan)}</span>`
                    : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

                const kondisiCell = s.kondisi_sample
                    ? `<span style="font-size:11px;font-weight:600;color:${KONDISI_COLOR[s.kondisi_sample]||'#64748b'};">${KONDISI_LABEL[s.kondisi_sample]||s.kondisi_sample}</span>`
                    : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

                const keteranganCell = s.keterangan
                    ? `<span style="font-size:11px;" title="${escHtml(s.keterangan)}">${escHtml(s.keterangan)}</span>`
                    : `<span style="color:#94a3b8;font-style:italic;font-size:11px;">–</span>`;

                return `
                <tr data-id-sample="${s.id_lab_sample}" data-boq-name="${escHtml(boq.nama_boq)}">
                    <td style="font-size:12px;color:#64748b;width:36px;">${s.no_urut}</td>
                    <td style="font-size:12px;white-space:nowrap;">${jenisTag} ${noSample}</td>
                    <td style="font-size:12px;">${titikCell}</td>
                    <td style="width:100px;">${tglCell}</td>
                    <td style="width:100px;">${kondisiCell}</td>
                    <td style="font-size:12px;width:150px;">
                        ${!isLocked
                            ? `<select class="form-select form-select-sm sample-inline-status"
                                    data-id="${s.id_lab_sample}"
                                    style="font-size:11px;width:140px;">
                                    <option value="belum_diambil"${statusVal==='belum_diambil'?' selected':''}>Belum Diambil</option>
                                    <option value="diambil"${statusVal==='diambil'?' selected':''}>Diambil</option>
                                    <option value="dikirim"${statusVal==='dikirim'?' selected':''}>Dikirim ke Lab</option>
                               </select>`
                            : `<span style="font-size:11px;font-weight:600;color:${SAMPLE_STATUS_COLOR[statusVal]||'#64748b'};">${SAMPLE_STATUS_LABEL[statusVal]||statusVal}</span>`
                        }
                    </td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${keteranganCell}</td>
                    <td style="width:80px;text-align:center;">${attBadge}</td>
                    <td class="text-center" style="width:72px;white-space:nowrap;">
                        ${!isLocked ? `
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-sample-edit"
                            data-id="${s.id_lab_sample}" title="Edit" style="font-size:11px;">
                            <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-sample-delete"
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
                <button type="button" class="btn btn-sm py-0 px-2 btn-sample-bulk-fill"
                    data-id-fwo-boq="${boq.id_fwo_boq}"
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
                            <button type="button" class="dropdown-item btn-sample-generate"
                                data-id-fwo-boq="${boq.id_fwo_boq}"
                                data-sisa="${sisa}">
                                <i class="fa-solid fa-wand-magic-sparkles me-2 text-primary"></i>
                                Buat Otomatis
                                ${sisa > 0 ? `<span class="badge bg-primary ms-1">${sisa}</span>` : ''}
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item btn-sample-add-one"
                                data-id-fwo-boq="${boq.id_fwo_boq}">
                                <i class="fa-solid fa-plus me-2 text-success"></i>
                                Tambah 1 Sample
                            </button>
                        </li>
                    </ul>
                </div>
            </div>` : '';

        const collapseId = `sampleCollapse_${boq.id_fwo_boq}`;
        return `
        <div class="mb-3 border rounded" style="background:#fff;">
            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                style="background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:calc(0.375rem - 1px) calc(0.375rem - 1px) 0 0;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span style="font-size:13px;font-weight:600;color:#1e293b;">${escHtml(boq.nama_boq)}</span>
                    <span class="text-muted sample-progress-text" style="font-size:11px;">Target: ${boq.qty || 0} &nbsp;·&nbsp; ${diambil}/${total} diambil &nbsp;·&nbsp; ${dikirim} dikirim</span>
                    ${sisa > 0 && !isLocked ? `<span style="font-size:11px;color:#b45309;background:#fef3c7;border:1px solid #fde68a;border-radius:4px;padding:1px 6px;">${sisa} slot belum dibuat</span>` : ''}
                </div>
                <div class="d-flex align-items-center gap-2">
                    ${addBtn}
                    <button type="button" class="btn btn-sm btn-plan-icon btn-sample-collapse"
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

// ── Buka modal edit sample ──
$(document).off('click.sample', '.btn-sample-edit').on('click.sample', '.btn-sample-edit', function (e) {
    e.stopPropagation();
    const idSample = $(this).data('id');
    const $tr      = $(this).closest('tr');
    const boqName  = $tr.data('boq-name') || '';

    const $modal = $('#sampleDetailModal');
    $modal.find('#sampleDetailModalLabel').text(`Sample – ${boqName}`);
    $modal.find('#sampleModal-id').val(idSample);

    // Reset form dulu
    $modal.find('#sampleModal-jenis').val('');
    $modal.find('#sampleModal-no').val('');
    $modal.find('#sampleModal-tanggal').val('');
    const $titikSel = $modal.find('#sampleModal-titik');
    $titikSel.data('_pendingVal', '');
    if ($titikSel.data('select2')) $titikSel.val(null).trigger('change');
    $modal.find('#sampleModal-kondisi').val('');
    $modal.find('#sampleModal-status').val('belum_diambil');
    $modal.find('#sampleModal-keterangan').val('');
    $modal.find('#sampleModal-existing-files').empty();
    if (sampleFilePond) { sampleFilePond.destroy(); sampleFilePond = null; }

    // Load data terkini dari server
    $.get(`/lab-samples/detail/${idSample}`)
        .done(function (res) {
            if (res.data) {
                const s = res.data;
                $modal.find('#sampleModal-jenis').val(s.jenis_sample || '');
                $modal.find('#sampleModal-no').val(s.no_sample || '');
                $modal.find('#sampleModal-tanggal').val(s.tanggal_pengambilan || '');
                $modal.find('#sampleModal-titik').data('_pendingVal', s.titik_lokasi || '');
                $modal.find('#sampleModal-kondisi').val(s.kondisi_sample || '');
                $modal.find('#sampleModal-status').val(s.status || 'belum_diambil');
                $modal.find('#sampleModal-keterangan').val(s.keterangan || '');

                const files = s.attachments || [];
                const filesHtml = files.length
                    ? files.map(f => `<div class="d-flex align-items-center gap-2 mb-1" style="font-size:12px;">
                            <i class="fa-solid fa-paperclip"></i>
                            <a href="/storage/${f}" target="_blank">${escHtml(f.split('/').pop())}</a>
                            <a href="#" class="btn-remove-existing-sample-file" style="font-size:11px;color:#dc2626;cursor:pointer;">Hapus</a>
                            <input type="hidden" class="existing-sample-file-path" value="${escHtml(f)}">
                        </div>`).join('')
                    : '';
                $modal.find('#sampleModal-existing-files').html(filesHtml);
            }
        })
        .always(function () {
            if (sampleFilePond) { sampleFilePond.destroy(); sampleFilePond = null; }
            sampleFilePond = createFileUploader('#sampleModal-attachments');

            // Re-init flatpickr
            initFpDate('#sampleDetailModal');

            // Init Select2 untuk field enum
            const s2Opts = { width: '100%', dropdownParent: $modal, allowClear: true };
            ['#sampleModal-jenis', '#sampleModal-kondisi', '#sampleModal-status'].forEach(function (sel) {
                const $el = $modal.find(sel);
                if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
                $el.select2({ ...s2Opts, placeholder: $el.find('option:first').text() || '-- Pilih --' });
                $el.trigger('change');
            });

            // Re-init Select2 titik lokasi (supaya options ter-refresh & value bisa di-set)
            initSampleModalTitikSelect2();
            const curTitik = $modal.find('#sampleModal-titik').data('_pendingVal');
            if (curTitik !== undefined) {
                $modal.find('#sampleModal-titik').val(curTitik || null).trigger('change');
                $modal.find('#sampleModal-titik').removeData('_pendingVal');
            }

            bootstrap.Modal.getOrCreateInstance($modal[0]).show();
        });
});

// ── Simpan sample ──
$(document).off('click.sample', '#sampleModal-btn-save').on('click.sample', '#sampleModal-btn-save', function () {
    const $btn     = $(this);
    const idSample = $('#sampleDetailModal #sampleModal-id').val();
    if (!idSample) return;

    const fd = new FormData();
    fd.append('_token', window.route.csrf);
    fd.append('jenis_sample', $('#sampleModal-jenis').val() || '');
    fd.append('tanggal_pengambilan', $('#sampleModal-tanggal').val() || '');
    fd.append('titik_lokasi', ($('#sampleModal-titik').val() || '').trim());
    fd.append('kondisi_sample', $('#sampleModal-kondisi').val() || '');
    fd.append('status', $('#sampleModal-status').val());
    fd.append('keterangan', $('#sampleModal-keterangan').val().trim());

    $('#sampleModal-existing-files .existing-sample-file-path').each(function () {
        fd.append('existing_attachments[]', $(this).val());
    });
    if (sampleFilePond) {
        sampleFilePond.getFiles().forEach(function (f) {
            fd.append('attachments[]', f.file);
        });
    }

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

    $.ajax({
        url: `/lab-samples/${idSample}`,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
    })
        .done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('sampleDetailModal'))?.hide();
                Notify.success('Sample berhasil disimpan.');
                // Reload sample list
                const idFwo = $('#fwoDetailTabs button[data-bs-target="#tabFwoSample"]').data('id-fwo');
                if (idFwo) loadSampleData(idFwo);
            } else {
                Notify.error(res.message || 'Gagal menyimpan.');
            }
        })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan sample.');
        })
        .always(function () {
            $btn.prop('disabled', false).html('Simpan');
        });
});

$(document).off('click.sample', '.btn-remove-existing-sample-file').on('click.sample', '.btn-remove-existing-sample-file', function (e) {
    e.preventDefault();
    $(this).closest('div').remove();
});

$('#sampleDetailModal').on('hidden.bs.modal', function () {
    if (sampleFilePond) { sampleFilePond.destroy(); sampleFilePond = null; }
});

// ── Inline Select2 titik lokasi: set value setelah Select2 diinit ──
// (dihandle di initSampleTitikSelect2 via data-current)
$(document).off('change.sample-titik', '.sample-inline-titik').on('change.sample-titik', '.sample-inline-titik', function () {
    const $sel   = $(this);
    const id     = $sel.data('id');
    const val    = $sel.val() || null;

    // Update data-current lalu reinit semua titik dalam tabel yang sama
    $sel.data('current', val || '');
    const $table = $sel.closest('table');
    initSampleTitikSelect2($table.closest('div')[0]);

    $.post(`/lab-samples/${id}/field`, { _token: window.route.csrf, field: 'titik_lokasi', value: val })
        .fail(function () { Swal.fire({ icon: 'error', title: 'Gagal menyimpan lokasi', timer: 1500, showConfirmButton: false }); });
});

// ── Inline Select2 status ──
$(document).off('change.sample-status', '.sample-inline-status').on('change.sample-status', '.sample-inline-status', function () {
    const $sel  = $(this);
    const id    = $sel.data('id');
    const val   = $sel.val();
    const $card = $sel.closest('.mb-3.border.rounded');

    $.post(`/lab-samples/${id}/field`, { _token: window.route.csrf, field: 'status', value: val })
        .done(function (res) {
            if (res.success) {
                // Hitung ulang progress dari semua select status dalam card ini
                const $allStatus = $card.find('.sample-inline-status');
                const total    = $allStatus.length;
                const diambil  = $allStatus.filter(function () { const v = $(this).val(); return v === 'diambil' || v === 'dikirim'; }).length;
                const dikirim  = $allStatus.filter(function () { return $(this).val() === 'dikirim'; }).length;
                const qty      = parseInt($card.find('.sample-progress-text').text().match(/Target:\s*(\d+)/)?.[1] || 0);
                $card.find('.sample-progress-text').html(`Target: ${qty} &nbsp;·&nbsp; ${diambil}/${total} diambil &nbsp;·&nbsp; ${dikirim} dikirim`);

                // Update progress bar
                const pct      = total > 0 ? Math.round((diambil / total) * 100) : 0;
                const barColor = pct === 100 ? '#15803d' : '#0369a1';
                $card.find('.progress-bar').css({ width: pct + '%', background: barColor });
            }
        })
        .fail(function () { Swal.fire({ icon: 'error', title: 'Gagal menyimpan status', timer: 1500, showConfirmButton: false }); });
});

// ── Buka modal Isi Bersama ──
$(document).off('click.sample', '.btn-sample-bulk-fill').on('click.sample', '.btn-sample-bulk-fill', function (e) {
    e.stopPropagation();
    const idFwoBoq = $(this).data('id-fwo-boq');
    const $modal   = $('#sampleBulkFillModal');

    $modal.find('#bulkFillModal-id-fwo-boq').val(idFwoBoq);
    $modal.find('#bulkFill-jenis').val('').trigger('change');
    $modal.find('#bulkFill-tanggal').val('');
    $modal.find('#bulkFill-status').val('').trigger('change');
    $modal.find('#bulkFill-kondisi').val('').trigger('change');

    // Init Select2
    const s2Opts = { width: '100%', dropdownParent: $modal, allowClear: false };
    ['#bulkFill-jenis', '#bulkFill-status', '#bulkFill-kondisi'].forEach(function (sel) {
        const $el = $modal.find(sel);
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        $el.select2(s2Opts);
    });
    initFpDate('#sampleBulkFillModal');

    bootstrap.Modal.getOrCreateInstance($modal[0]).show();
});

// ── Simpan Isi Bersama ──
$(document).off('click.sample', '#sampleBulkFillModal-btn-save').on('click.sample', '#sampleBulkFillModal-btn-save', function () {
    const $btn     = $(this);
    const idFwoBoq = $('#bulkFillModal-id-fwo-boq').val();
    const payload  = {
        _token:              window.route.csrf,
        jenis_sample:        $('#bulkFill-jenis').val() || null,
        tanggal_pengambilan: $('#bulkFill-tanggal').val() || null,
        status:              $('#bulkFill-status').val() || null,
        kondisi_sample:      $('#bulkFill-kondisi').val() || null,
    };

    const hasValue = Object.entries(payload).some(([k, v]) => k !== '_token' && v);
    if (!hasValue) { Notify.warning('Isi minimal satu field terlebih dahulu.'); return; }

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
    $.post(`/lab-samples/boq/${idFwoBoq}/bulk-fill`, payload)
        .done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('sampleBulkFillModal'))?.hide();
                Notify.success('Semua sample berhasil diperbarui.');
                const idFwo = $('#fwoDetailTabs button[data-bs-target="#tabFwoSample"]').data('id-fwo');
                if (idFwo) loadSampleData(idFwo);
            } else {
                Notify.error(res.message || 'Gagal.');
            }
        })
        .fail(function (xhr) { Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan.'); })
        .always(function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-wand-magic-sparkles me-1"></i> Apply ke Semua Sample'); });
});

// ── Hapus sample ──
$(document).off('click.sample', '.btn-sample-delete').on('click.sample', '.btn-sample-delete', function (e) {
    e.stopPropagation();
    const id  = $(this).data('id');
    const no  = $(this).data('no');

    Swal.fire({
        title: 'Hapus Sample?',
        text: `${no} akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: `/lab-samples/${id}`,
            type: 'DELETE',
            data: { _token: window.route.csrf },
        }).done(function (res) {
            if (res.success) {
                Notify.success('Sample berhasil dihapus.');
                const idFwo = $('#fwoDetailTabs button[data-bs-target="#tabFwoSample"]').data('id-fwo');
                if (idFwo) loadSampleData(idFwo);
            } else {
                Notify.error(res.message || 'Gagal menghapus.');
            }
        }).fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menghapus.');
        });
    });
});

// ── Buat otomatis semua slot yang belum ada ──
$(document).off('click.sample', '.btn-sample-generate').on('click.sample', '.btn-sample-generate', function () {
    const idFwoBoq = $(this).data('id-fwo-boq');
    const sisa     = $(this).data('sisa');
    if (!idFwoBoq) return;

    if (sisa === 0) {
        Notify.info('Semua slot sudah dibuat.');
        return;
    }

    $(this).prop('disabled', true);
    $.post(`/lab-samples/boq/${idFwoBoq}/generate`, { _token: window.route.csrf })
        .done(function (res) {
            if (res.success) {
                Notify.success('Sample berhasil dibuat otomatis.');
                const idFwo = $('#fwoDetailTabs button[data-bs-target="#tabFwoSample"]').data('id-fwo');
                if (idFwo) loadSampleData(idFwo);
            } else {
                Notify.error(res.message || 'Gagal membuat sample.');
            }
        })
        .fail(function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal membuat sample.');
        });
});

// ── Tambah 1 slot manual ──
$(document).off('click.sample', '.btn-sample-add-one').on('click.sample', '.btn-sample-add-one', function () {
    const idFwoBoq = $(this).data('id-fwo-boq');
    const sisa     = parseInt($(this).closest('.dropdown').siblings('.btn-sample-generate').data('sisa') ?? $(this).closest('[data-sisa]').data('sisa') ?? 999);
    if (!idFwoBoq) return;

    // Cek di frontend: ambil dari badge "X slot belum dibuat" atau data-sisa di generate button
    const $card    = $(this).closest('.mb-3');
    const $genBtn  = $card.find('.btn-sample-generate');
    const sisaQty  = parseInt($genBtn.data('sisa') ?? 999);

    if (sisaQty === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Batas Maksimum',
            text: 'Jumlah sample sudah mencapai batas maksimum sesuai qty BOQ.',
            confirmButtonText: 'OK',
        });
        return;
    }

    $(this).prop('disabled', true);
    $.post(`/lab-samples/boq/${idFwoBoq}/add-one`, { _token: window.route.csrf })
        .done(function (res) {
            if (res.success) {
                const idFwo = $('#fwoDetailTabs button[data-bs-target="#tabFwoSample"]').data('id-fwo');
                if (idFwo) loadSampleData(idFwo);
            } else {
                Swal.fire({ icon: 'warning', title: 'Tidak Dapat Ditambahkan', text: res.message || 'Gagal menambah sample.', confirmButtonText: 'OK' });
            }
        })
        .fail(function (xhr) {
            Swal.fire({ icon: 'warning', title: 'Tidak Dapat Ditambahkan', text: xhr.responseJSON?.message || 'Gagal menambah sample.', confirmButtonText: 'OK' });
        });
});
