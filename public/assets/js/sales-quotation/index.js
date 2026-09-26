window.datatableHeaderLabels = Object.assign({}, window.datatableHeaderLabels, {
    no_sq: 'No SQ',
    revisi: 'Rev',
    judul_order: 'Judul Order',
    tanggal_sq: 'Tanggal SQ',
    berlaku_sampai: 'Berlaku Sampai',
});

window.datatableColumnRenderers = Object.assign({}, window.datatableColumnRenderers, {
    status: function (data) {
        var map = {
            draft:     { bg: '#f1f5f9', color: '#475569', border: '#e2e8f0', icon: 'fa-pen',          label: 'Draft' },
            terkirim:  { bg: '#eff6ff', color: '#1d4ed8', border: '#bfdbfe', icon: 'fa-paper-plane',  label: 'Terkirim' },
            diterima:  { bg: '#f0fdf4', color: '#15803d', border: '#bbf7d0', icon: 'fa-circle-check', label: 'Diterima' },
            ditolak:   { bg: '#fef2f2', color: '#b91c1c', border: '#fecaca', icon: 'fa-circle-xmark', label: 'Ditolak' },
            cancel:    { bg: '#fff7ed', color: '#c2410c', border: '#fed7aa', icon: 'fa-ban',          label: 'Cancel' },
            expired:   { bg: '#fefce8', color: '#a16207', border: '#fde68a', icon: 'fa-clock',        label: 'Expired' },
            deleted:   { bg: '#fef2f2', color: '#b91c1c', border: '#fecaca', icon: 'fa-trash',        label: 'Deleted' },
        };
        var s = map[data] || { bg: '#f1f5f9', color: '#475569', border: '#e2e8f0', icon: 'fa-circle', label: data || '-' };
        return '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:6px;background:' + s.bg + ';color:' + s.color + ';font-size:11px;font-weight:600;border:1px solid ' + s.border + ';white-space:nowrap;">'
            + '<i class="fa-solid ' + s.icon + '" style="font-size:10px;"></i> ' + s.label + '</span>';
    },
    no_sq: function (data, type, row) {
        return escHtml(data) + (row.revisi > 0 ? ' <span class="pm-badge" style="font-size:10px;background:#f5f3ff;color:#6d28d9;">Rev.' + row.revisi + '</span>' : '');
    },
});

// PIC yang muncul = union dari Perusahaan yang sudah dipilih di ketiga
// kategori (Pemesan, Pengiriman, Pembayaran) — pola sama seperti Sales Order.
function _sqSelectedCompanyIds() {
    return [
        $('#detail_id_pelanggan').val(),
        $('#detail_id_pelanggan_delivery').val(),
        $('#detail_id_pelanggan_payment').val(),
    ].filter(function (v) { return !!v; });
}

function _sqGetSelect2Data(selector) {
    const $el = $(selector);
    if (!$el.length || !$el.val()) return null;
    const data = $el.select2('data');
    if (!data || !data.length) return null;
    return { id: $el.val(), text: data[0].text };
}

function _syncSqPemesanToOthers() {
    const company = _sqGetSelect2Data('#detail_id_pelanggan');
    const site = _sqGetSelect2Data('#detail_id_site_pelanggan');
    if (!company || !site) return;

    const pic = _sqGetSelect2Data('#detail_id_pic_pelanggan');

    const targets = [
        { company: '#detail_id_pelanggan_delivery', site: '#detail_id_site_pelanggan_delivery', pic: '#detail_id_pic_pelanggan_delivery' },
        { company: '#detail_id_pelanggan_payment',  site: '#detail_id_site_pelanggan_payment',  pic: '#detail_id_pic_pelanggan_payment' },
    ];

    targets.forEach(function (t) {
        const $c = $(t.company);
        if ($c.length && !$c.val()) $c.append(new Option(company.text, company.id, true, true)).trigger('change');
        const $s = $(t.site);
        if ($s.length && !$s.val()) $s.append(new Option(site.text, site.id, true, true)).trigger('change');
        if (pic) {
            const $p = $(t.pic);
            if ($p.length && !$p.val()) $p.append(new Option(pic.text, pic.id, true, true)).trigger('change');
        }
    });
}

function initSqSiteFields() {
    const pairs = [
        { company: '#detail_id_pelanggan', site: '#detail_id_site_pelanggan' },
        { company: '#detail_id_pelanggan_delivery', site: '#detail_id_site_pelanggan_delivery' },
        { company: '#detail_id_pelanggan_payment', site: '#detail_id_site_pelanggan_payment' },
    ];

    pairs.forEach(function (pair) {
        const $el = $(pair.site);
        if (!$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

        $el.select2({
            width: '100%',
            dropdownParent: $('#detailContent'),
            placeholder: 'Pilih Data',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: 'business-relations/sites/select2',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '', id_br: $(pair.company).val() || '' }; },
                processResults: function (data) { return { results: data }; },
                cache: false,
            },
            language: {
                noResults: function () {
                    return '<span>Tidak ditemukan. <a href="/business-relations/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>';
                },
            },
            escapeMarkup: function (m) { return m; },
        });
    });

    pairs.forEach(function (pair) {
        $(pair.company)
            .off('select2:select.sqCompanySiteClear select2:clear.sqCompanySiteClear')
            .on('select2:select.sqCompanySiteClear select2:clear.sqCompanySiteClear', function () {
                $(pair.site).val(null).trigger('change');
            });
    });
}

function initSqPicFields() {
    const selectors = [
        '#detail_id_pic_pelanggan',
        '#detail_id_pic_pelanggan_delivery',
        '#detail_id_pic_pelanggan_payment',
    ];

    selectors.forEach(function (sel) {
        const $el = $(sel);
        if (!$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

        $el.select2({
            width: '100%',
            dropdownParent: $('#detailContent'),
            placeholder: 'Pilih Data',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: 'business-relation-contacts/select2',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '', id_br: _sqSelectedCompanyIds(), with_site: 1 }; },
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
    });

    ['#detail_id_pelanggan', '#detail_id_pelanggan_delivery', '#detail_id_pelanggan_payment'].forEach(function (sel, i) {
        const picSel = selectors[i];
        $(sel).off('select2:select.sqCompanyPicClear select2:clear.sqCompanyPicClear')
            .on('select2:select.sqCompanyPicClear select2:clear.sqCompanyPicClear', function () {
                $(picSel).val(null).trigger('change');
            });
    });

    ['#detail_id_pelanggan', '#detail_id_site_pelanggan', '#detail_id_pic_pelanggan'].forEach(function (sel) {
        $(sel).off('select2:select.sqSyncOthers').on('select2:select.sqSyncOthers', _syncSqPemesanToOthers);
    });
}

// ── Tab "Work Order" — ringkasan read-only, sama pola dengan tab "Work
// Order" di Sales Order (loadWoProgress): daftar + link ke halaman SQ Work
// Order sendiri, BUKAN modal/accordion di sini — WO sekarang modul mandiri
// (menu "SQ Work Order") sejak Fase 0 dibatalkan 2026-09-26.
function formatRupiahSq(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

function loadSqWoList(idSq, done) {
    $.get('/sq-work-orders/' + idSq + '/list', function (wos) {
        renderSqWoSummary(wos || []);
        if (done) done();
    }).fail(function () {
        $('#sqWoContent').html('<div class="text-center text-danger py-4">Gagal memuat data Work Order.</div>');
        if (done) done();
    });
}

function renderSqWoSummary(wos) {
    if (!wos.length) {
        $('#sqWoContent').html(`<div class="text-center text-muted py-4">
            <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
            Belum ada Work Order. Klik tombol "+ WO" untuk mulai.
        </div>`);
        return;
    }

    const rows = wos.map(function (wo, i) {
        const searchText = [wo.no_sq_wo, wo.judul_pekerjaan].filter(Boolean).join(' ').toLowerCase();
        return `<tr data-search="${escHtml(searchText)}">
            <td style="text-align:center;color:#9ca3af;font-size:12px;">${i + 1}</td>
            <td style="white-space:nowrap;font-weight:600;color:#1a56db;">${escHtml(wo.no_sq_wo || '—')}</td>
            <td><a href="/sq-work-orders?open=${wo.id_sq_wo}" class="fw-semibold text-decoration-none">${escHtml(wo.judul_pekerjaan || '(belum diberi judul)')}</a></td>
            <td style="text-align:center;white-space:nowrap;">Ke-${wo.hari_mulai}${wo.durasi_hari ? ' · ' + wo.durasi_hari + ' hr' : ''}</td>
            <td style="text-align:center;"><span class="pm-badge pm-badge--blue" style="font-size:10px;">${wo.boq_count} item</span></td>
            <td style="text-align:right;white-space:nowrap;font-weight:600;">${wo.total_boq > 0 ? formatRupiahSq(wo.total_boq) : '—'}</td>
            <td style="text-align:right;white-space:nowrap;">
                <a href="/sq-work-orders?open=${wo.id_sq_wo}" class="btn-plan-icon"><i class="fa-solid fa-arrow-up-right-from-square"></i> Buka</a>
            </td>
        </tr>`;
    }).join('');

    $('#sqWoContent').html(`
    <div class="table-responsive">
        <table class="pm-table">
            <thead>
                <tr>
                    <th style="width:36px;text-align:center;">#</th>
                    <th>No WO</th>
                    <th>Judul Pekerjaan</th>
                    <th style="text-align:center;">Hari</th>
                    <th style="text-align:center;">BOQ</th>
                    <th style="text-align:right;">Total BOQ</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`);
}

// ── "+ WO" — buka modal iframe ke halaman Create SQ Work Order (persis pola
// "+ WO" di Sales Order yang membuka iframe ke /work-orders/create).
$(document).on('click', '.btn-add-sq-wo-modal', function () {
    const sqId = $(this).data('sq-id');
    openIframeModal('#modalCreateSqWo', 'iframeCreateSqWo', 'loaderCreateSqWo', '/sq-work-orders/create?id_sq=' + sqId + '&embed=1');
});

window.addEventListener('storage', function (e) {
    if (e.key === 'sq_wo_created' && e.newValue) {
        try {
            const data = JSON.parse(e.newValue);
            loadSqWoList(data.id_sq);
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalCreateSqWo'));
            if (modal) {
                modal.hide();
                document.getElementById('iframeCreateSqWo').src = '';
            }
        } catch (_) {}
    }
});

$(document).ready(function () {
    if ($('#sales-quotations-table').length === 0) return;

    new CrudPageController({
        primaryKey: 'id_sq',
        renderForm: renderForm,
        detailTitle: function (res) { return res.no_sq + (res.revisi > 0 ? ' Rev.' + res.revisi : ''); },
        initSelect: function () {
            initSqPicFields();
            initNumericMask(document.getElementById('detailContent'));
        },
        afterLoad: function (res) {
            initSqSiteFields();
            initFpDate('#detailContent');
            loadSqWoList(res.id_sq);
        },
    });

    $(document).on('shown.bs.tab', '#sqDetailTabs button[data-bs-toggle="tab"]', function (e) {
        const target = $(e.target).data('bs-target');
        $('#sqTabActionsInfo, #sqTabActionsWo').addClass('d-none').removeClass('d-flex');
        if (target === '#tabInfoSq') $('#sqTabActionsInfo').removeClass('d-none');
        if (target === '#tabSqWo') $('#sqTabActionsWo').removeClass('d-none').addClass('d-flex');
    });

    $(document).on('click', '#btnRefreshSqWo', function () {
        const $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        loadSqWoList($(this).data('sq-id'), function () { $icon.removeClass('fa-spin'); });
    });

    $(document).on('input', '#sqWoSearch', function () {
        const q = $(this).val().toLowerCase().trim();
        $('#sqWoContent tbody tr').each(function () {
            $(this).toggle(!q || ($(this).data('search') || '').toString().includes(q));
        });
        $('#btnClearSqWoSearch').toggleClass('d-none', !q);
    });
    $(document).on('click', '#btnClearSqWoSearch', function () {
        $('#sqWoSearch').val('').trigger('input');
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus Sales Quotation?', function () {
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
