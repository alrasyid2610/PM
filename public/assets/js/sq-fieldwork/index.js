// SQ Fieldwork — modul mandiri (Fase 3), meniru pola SQ Work Order persis.
// Scope Fase 3 dibatasi ke tab Informasi/BOQ/Budget (kesepakatan user
// 2026-09-27) — lihat Obsidian Modules/Sales Quotation.md.
let currentSqFwoId = null;
let currentSqFwoData = null;

window.datatableHeaderLabels = Object.assign({}, window.datatableHeaderLabels, {
    no_sq_fwo: 'No FWO',
    no_sq_wo: 'No WO',
    no_sq: 'No SQ',
    judul_pekerjaan: 'Judul Pekerjaan',
    hari_ke: 'Hari Ke-',
    durasi_hari: 'Durasi',
});

// PIC di-scope oleh Perusahaan (id_br) WO induk — pola sama FWO asli
// (fieldworks/create.blade.php: filter by id_br, BUKAN by Site). id_br
// diambil dari id_br_pekerjaan yang sudah disertakan show() controller.
function initSqFwoPicField() {
    const $pic = $('#detail_id_pic_pelanggan_pekerjaan');
    if (!$pic.length) return;
    if ($pic.hasClass('select2-hidden-accessible')) $pic.select2('destroy');

    $pic.select2({
        width: '100%',
        dropdownParent: $('#detailContent'),
        placeholder: 'Pilih PIC',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: '/business-relation-contacts/select2',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term || '', id_br: (currentSqFwoData && currentSqFwoData.id_br_pekerjaan) || '' };
            },
            processResults: function (data) { return { results: data }; },
            cache: false,
        },
        language: {
            noResults: function () {
                return '<span>Tidak ditemukan. <a href="/business-relation-contacts/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>';
            },
        },
        escapeMarkup: function (m) { return m; },
    });
}

function initSqFwoSiteField() {
    const $site = $('#detail_id_site_pelanggan_pekerjaan');
    if (!$site.length) return;
    if ($site.hasClass('select2-hidden-accessible')) $site.select2('destroy');

    $site.select2({
        width: '100%',
        dropdownParent: $('#detailContent'),
        placeholder: 'Pilih Site',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: '/business-relations/sites/select2',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term || '' }; },
            processResults: function (data) { return { results: data }; },
            cache: false,
        },
        escapeMarkup: function (m) { return m; },
    });
}

$(document).ready(function () {
    if ($('#sq-fieldworks-table').length === 0) return;

    new CrudPageController({
        primaryKey: 'id_sq_fwo',
        renderForm: renderForm,
        detailTitle: function (res) { return res.no_sq_fwo || res.judul_pekerjaan || 'SQ Fieldwork'; },
        initSelect: function () {
            initNumericMask(document.getElementById('detailContent'));
        },
        afterLoad: function (res) {
            currentSqFwoId = res.id_sq_fwo;
            currentSqFwoData = res;
            initSqFwoSiteField();
            initSqFwoPicField();
            initFpDate('#detailContent');
        },
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus SQ Fieldwork ini beserta seluruh BOQ & Budget-nya?', function () {
            $.ajax({
                url: window.route.update + id,
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' },
                success: function (res) {
                    Notify.success(res.message || 'Data berhasil dihapus');
                    setTimeout(function () { window.location.href = window.location.pathname; }, 1000);
                },
                error: function (xhr) {
                    Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus data');
                },
            });
        });
    });
});

// ── Tab switch ───────────────────────────────────────────────────────────
$(document).on('shown.bs.tab', '#sqFwoDetailTabs button[data-bs-toggle="tab"]', function (e) {
    const target = $(e.target).data('bs-target');
    $('#sqFwoTabActionsInfo, #sqFwoTabActionsBoq, #sqFwoTabActionsBudget').addClass('d-none').removeClass('d-flex');
    if (target === '#tabFwoInfo') $('#sqFwoTabActionsInfo').removeClass('d-none');
    if (target === '#tabFwoBoq') { $('#sqFwoTabActionsBoq').removeClass('d-none').addClass('d-flex'); loadSqFwoBoq(currentSqFwoId); }
    if (target === '#tabFwoBudget') { $('#sqFwoTabActionsBudget').removeClass('d-none').addClass('d-flex'); loadSqFwoBudget(currentSqFwoId); }
});

// ── Tab BOQ ──────────────────────────────────────────────────────────────
let sqFwoBoqData = [];

function loadSqFwoBoq(idSqFwo) {
    $('#sqFwoBoqSummary').html('<div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');
    $.get(window.sqFwoRoute.boqByFwo + idSqFwo, function (data) {
        sqFwoBoqData = data || [];
        const isLocked = currentSqFwoData && currentSqFwoData.sq_status && currentSqFwoData.sq_status !== 'draft';
        $('#sqFwoBoqSummary').html(renderSqFwoBoqView(sqFwoBoqData, isLocked));
    }).fail(function () {
        $('#sqFwoBoqSummary').html('<div class="text-center text-danger py-3">Gagal memuat data BOQ.</div>');
    });
}

$(document).on('click', '.btn-kelola-sq-fwo-boq', function () {
    openBulkSqFwoBoqModal();
});

function openBulkSqFwoBoqModal() {
    $('#sqFwoBulkBoqLoading').removeClass('d-none');
    $('#sqFwoBulkBoqEmpty, #sqFwoBulkBoqList').addClass('d-none');
    $('#btnSaveSqFwoBulkBoq').prop('disabled', true);
    new bootstrap.Modal('#modalBulkAddSqFwoBoq').show();

    $.get(window.sqFwoRoute.boqSelectByWo + currentSqFwoData.id_sq_wo + '?id_sq_fwo=' + currentSqFwoId, function (data) {
        $('#sqFwoBulkBoqLoading').addClass('d-none');
        if (!data || !data.length) {
            $('#sqFwoBulkBoqEmpty').removeClass('d-none');
            return;
        }
        $('#sqFwoBulkBoqList').html(renderSqFwoBulkBoqList(data, sqFwoBoqData)).removeClass('d-none');
        $('#btnSaveSqFwoBulkBoq').prop('disabled', false);
    }).fail(function () {
        $('#sqFwoBulkBoqLoading').addClass('d-none');
        $('#sqFwoBulkBoqEmpty').removeClass('d-none');
    });
}

// Validasi real-time qty bulk BOQ — pola sama .bulk-boq-qty di fieldworks/index.js
$(document).on('input', '.sq-fwo-bulk-boq-qty', function () {
    const $input = $(this);
    const qty = parseInt($input.val()) || 0;
    const maxRaw = $input.data('max');
    const max = (maxRaw !== '' && maxRaw !== undefined) ? parseInt(maxRaw) : null;
    const isOver = max !== null && qty > max;
    const $hint = $input.siblings('.sq-fwo-bulk-qty-hint');

    if (isOver) {
        $input.css({ 'border-color': '#f87171', 'background': '#fef2f2' });
        if (!$hint.length) {
            $input.after(`<div class="sq-fwo-bulk-qty-hint" style="color:#dc2626;font-size:11px;margin-top:2px;">Maks: ${max}</div>`);
        }
    } else {
        $input.css({ 'border-color': '', 'background': '' });
        $hint.remove();
    }

    const hasError = $('#sqFwoBulkBoqList .sq-fwo-bulk-boq-qty').toArray().some(function (el) {
        const q = parseInt($(el).val()) || 0;
        const m = $(el).data('max');
        const mx = (m !== '' && m !== undefined) ? parseInt(m) : null;
        return mx !== null && q > mx;
    });
    $('#btnSaveSqFwoBulkBoq').prop('disabled', hasError);
});

$(document).on('click', '#btnSaveSqFwoBulkBoq', function () {
    const $btn = $(this);
    const sections = [];
    let hasError = false;

    $('#sqFwoBulkBoqList .sq-fwo-bulk-boq-qty').each(function () {
        const qty = parseInt($(this).val()) || 0;
        if (qty <= 0) return;

        const maxRaw = $(this).data('max');
        const max = maxRaw !== '' && maxRaw !== undefined ? parseInt(maxRaw) : null;
        if (max !== null && qty > max) {
            const namaItem = $(this).closest('tr').find('td:nth-child(2)').clone().find('.btn-sq-fwo-bulk-eye, .sq-fwo-bulk-boq-items-detail').remove().end().text().trim();
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                html: '<strong>' + escHtml(namaItem) + '</strong><br><span style="font-size:14px;">Qty melebihi sisa yang tersedia (maks: ' + max + ')</span>',
            });
            hasError = true;
            return false;
        }

        const idSqBoq = parseInt($(this).data('boq-id'));
        const ket = $(this).closest('tr').find('.sq-fwo-bulk-boq-ket').val() || null;
        sections.push({ id_sq_boq: idSqBoq, qty: qty, keterangan: ket });
    });

    if (hasError) return;
    if (!sections.length) {
        Notify.warning('Isi minimal 1 item dengan Qty lebih dari 0');
        return;
    }

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
    $.ajax({
        url: window.sqFwoRoute.boqUpdate + currentSqFwoId,
        method: 'PUT',
        contentType: 'application/json',
        headers: { 'X-CSRF-TOKEN': window.sqFwoRoute.csrf },
        data: JSON.stringify({ sections: sections }),
        success: function () {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan');
            Notify.success('BOQ berhasil disimpan');
            bootstrap.Modal.getInstance('#modalBulkAddSqFwoBoq').hide();
            loadSqFwoBoq(currentSqFwoId);
        },
        error: function (xhr) {
            Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan BOQ');
            $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan');
        },
    });
});

// Eye toggle detail items di modal bulk — pola sama .btn-bulk-eye
$(document).on('click', '.btn-sq-fwo-bulk-eye', function () {
    const $btn = $(this);
    const idSqBoq = $btn.data('boq-id');
    const $detail = $btn.siblings('.sq-fwo-bulk-boq-items-detail');
    const $icon = $btn.find('i');

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

    $.get(window.sqFwoRoute.boqSectionItems + idSqBoq + '?id_sq_fwo=' + currentSqFwoId, function (res) {
        const items = res.items || [];
        if (!items.length) {
            $detail.html('<span class="text-muted small">Tidak ada item</span>');
        } else {
            $detail.html('<div style="border-left:3px solid #e2e8f0;padding-left:8px;margin-top:4px;">' +
                items.map(function (item, j) {
                    return `<div class="text-muted small py-1" style="${j > 0 ? 'border-top:1px solid #f1f5f9;' : ''}">
                        <span class="fw-semibold text-dark">${j + 1}.</span>
                        ${escHtml(item.judul_indonesia ?? '—')}
                        <span class="text-muted">/ ${escHtml(item.judul_inggris ?? '—')}</span>
                        <span class="item-meta-badge ms-1">${escHtml(item.kode_unit || '—')} · ${escHtml(String(item.nilai ?? '—'))}</span>
                    </div>`;
                }).join('') + '</div>');
        }
        $detail.data('loaded', true);
    }).fail(function () {
        $detail.html('<span class="text-danger small">Gagal memuat</span>');
    });
});

// Hapus 1 item BOQ dari view mode — pola sama .btn-fwo-boq-delete
$(document).on('click', '.btn-sq-fwo-boq-delete', function () {
    const boqId = String($(this).data('boq-id'));
    const ptName = $(this).closest('tr').find('td:nth-child(2)').text().trim();

    Swal.fire({
        icon: 'warning',
        title: 'Hapus Item BOQ?',
        html: '<strong>' + escHtml(ptName) + '</strong>',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        reverseButtons: true,
    }).then(function (result) {
        if (!result.isConfirmed) return;

        const remaining = (sqFwoBoqData || []).filter(function (s) { return String(s.id_sq_boq) !== boqId; });
        const sections = remaining.map(function (s) { return { id_sq_boq: s.id_sq_boq, qty: s.qty, keterangan: s.keterangan }; });

        $.ajax({
            url: window.sqFwoRoute.boqUpdate + currentSqFwoId,
            method: 'PUT',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': window.sqFwoRoute.csrf },
            data: JSON.stringify({ sections: sections }),
            success: function () {
                Notify.success('Item BOQ berhasil dihapus');
                loadSqFwoBoq(currentSqFwoId);
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus item BOQ');
            },
        });
    });
});

// ── Tab Budget ───────────────────────────────────────────────────────────
function loadSqFwoBudget(idSqFwo) {
    $('#sqFwoBudgetContent').html('<div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');
    $.get(window.sqFwoRoute.budgetList + idSqFwo + '/list', function (plans) {
        $('#sqFwoBudgetContent').html(renderSqFwoBudgetList(plans || []));
    }).fail(function () {
        $('#sqFwoBudgetContent').html('<div class="text-center text-danger py-3">Gagal memuat Budget.</div>');
    });
}

$(document).on('click', '.btn-sq-fwo-budget-add', function () {
    openSqFwoBudgetModal(null);
});

$(document).on('click', '.btn-edit-sq-fwo-budget', function () {
    const id = $(this).closest('[data-id-sq-budget]').data('idSqBudget');
    $.get(window.sqFwoRoute.budgetShow + id, function (data) { openSqFwoBudgetModal(data); });
});

$(document).on('click', '.btn-remove-sq-fwo-budget', function () {
    const id = $(this).closest('[data-id-sq-budget]').data('idSqBudget');
    Notify.confirmDelete('Hapus Budget Plan ini?', function () {
        $.ajax({
            url: window.sqFwoRoute.budgetDelete + id,
            method: 'DELETE',
            data: { _token: window.sqFwoRoute.csrf },
            success: function () { Notify.success('Budget Plan dihapus'); loadSqFwoBudget(currentSqFwoId); },
            error: function () { Notify.error('Gagal menghapus Budget Plan'); },
        });
    });
});

function initSqFwoBudgetAccountSelect2() {
    $('#sqFwoBudgetItemsBody .bi-account').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) return;
        $(this).select2({
            dropdownParent: $('#sqFwoBudgetPlanModal'),
            placeholder: 'Pilih Account', allowClear: false, width: '100%', minimumInputLength: 0,
            ajax: {
                url: window.sqFwoRoute.select2Account, dataType: 'json', delay: 200,
                data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true,
            },
        });
    });
}

function recalcSqFwoBudgetTotal() {
    let total = 0;
    $('#sqFwoBudgetItemsBody .bi-nominal').each(function () {
        total += parseInt(($(this).val() || '').replace(/,/g, ''), 10) || 0;
    });
    $('#sqFwoBudgetModalTotal').text(formatRupiahSqFwo(total));
}

function openSqFwoBudgetModal(data) {
    const isEdit = !!data;
    $('#sqFwoBudgetPlanModalLabel').html(`<i class="fa-solid fa-wallet me-2" style="color:#0f766e;"></i>${isEdit ? 'Edit' : 'Tambah'} Budget Plan`);
    $('#sqFwoBudgetModal-id').val(isEdit ? data.id_sq_budget : '');
    $('#sqFwoBudgetModal-id-sq-fwo').val(currentSqFwoId);
    $('#sqFwoBudgetModal-label').val(isEdit ? data.label : '');
    $('#sqFwoBudgetModal-keterangan').val(isEdit ? (data.keterangan || '') : '');
    $('#sqFwoBudgetModal-hari-mulai').val(isEdit ? (data.hari_mulai || '') : '');
    $('#sqFwoBudgetModal-hari-selesai').val(isEdit ? (data.hari_selesai || '') : '');

    const $body = $('#sqFwoBudgetItemsBody').empty();
    const items = isEdit ? (data.items || []) : [];
    if (items.length) items.forEach((item) => $body.append(buildSqFwoBudgetItemRow(item)));
    else $body.append(buildSqFwoBudgetItemRow(null));

    initSqFwoBudgetAccountSelect2();
    initNumericMask(document.getElementById('sqFwoBudgetPlanModal'));
    recalcSqFwoBudgetTotal();

    new bootstrap.Modal(document.getElementById('sqFwoBudgetPlanModal')).show();
}

$(document).on('click', '#btnSqFwoBudgetAddRow', function () {
    $('#sqFwoBudgetItemsBody').append(buildSqFwoBudgetItemRow(null));
    initSqFwoBudgetAccountSelect2();
    initNumericMask(document.getElementById('sqFwoBudgetPlanModal'));
});

$(document).on('click', '.btn-sq-fwo-budget-remove-row', function () {
    $(this).closest('tr').remove();
    recalcSqFwoBudgetTotal();
});

$(document).on('input', '#sqFwoBudgetItemsBody .bi-nominal', function () {
    recalcSqFwoBudgetTotal();
});

$(document).on('click', '#sqFwoBudgetModal-btn-save', function () {
    const id = $('#sqFwoBudgetModal-id').val();
    const idSqFwo = $('#sqFwoBudgetModal-id-sq-fwo').val();
    const label = $('#sqFwoBudgetModal-label').val().trim();
    if (!label) { Notify.warning('Label wajib diisi.'); return; }

    const items = [];
    let valid = true;
    $('#sqFwoBudgetItemsBody .sq-fwo-budget-item-row').each(function () {
        const idAccount = $(this).find('.bi-account').val();
        const nominal = parseInt(($(this).find('.bi-nominal').val() || '').replace(/,/g, ''), 10) || 0;
        const ket = $(this).find('.bi-keterangan').val();
        const biId = $(this).find('.bi-id').val();
        const isCa = $(this).find('.bi-ca').is(':checked') ? 1 : 0;
        if (!idAccount) { valid = false; return false; }
        items.push({ id_sq_budget_item: biId || null, id_account: idAccount, nominal_budget: nominal, keterangan: ket, is_cash_advance: isCa });
    });
    if (!valid) { Notify.warning('Semua baris harus memilih Account.'); return; }
    if (!items.length) { Notify.warning('Minimal 1 item anggaran.'); return; }

    const payload = {
        _token: window.sqFwoRoute.csrf,
        id_sq_fwo: idSqFwo,
        label: label,
        keterangan: $('#sqFwoBudgetModal-keterangan').val(),
        hari_mulai: $('#sqFwoBudgetModal-hari-mulai').val() || null,
        hari_selesai: $('#sqFwoBudgetModal-hari-selesai').val() || null,
        items: items,
    };
    const isEdit = !!id;
    if (isEdit) payload._method = 'PUT';
    const $btn = $(this);
    $btn.prop('disabled', true);
    $.post(isEdit ? window.sqFwoRoute.budgetUpdate + id : window.sqFwoRoute.budgetStore, payload)
        .done(function () {
            bootstrap.Modal.getInstance(document.getElementById('sqFwoBudgetPlanModal'))?.hide();
            Notify.success('Budget Plan tersimpan');
            loadSqFwoBudget(currentSqFwoId);
        })
        .fail(function (xhr) { Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan Budget Plan'); })
        .always(function () { $btn.prop('disabled', false); });
});
