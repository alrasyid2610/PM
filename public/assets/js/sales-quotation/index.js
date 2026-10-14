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
            final:     { bg: '#eff6ff', color: '#1d4ed8', border: '#bfdbfe', icon: 'fa-lock',         label: 'Final' },
            completed: { bg: '#f0fdf4', color: '#15803d', border: '#bbf7d0', icon: 'fa-circle-check', label: 'Completed' },
            cancel:    { bg: '#fff7ed', color: '#c2410c', border: '#fed7aa', icon: 'fa-ban',          label: 'Cancel' },
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

// PIC yang muncul = gabungan PIC dari Site yang dipilih di ketiga kategori
function _sqSelectedSiteIds() {
    return [
        $('#detail_id_site_pelanggan').val(),
        $('#detail_id_site_pelanggan_delivery').val(),
        $('#detail_id_site_pelanggan_payment').val(),
    ].filter(function (v) { return !!v; });
}

// Pemesan menimpa Pengiriman & Pembayaran (Perusahaan, Site, PIC). PIC yang kosong
// di Pemesan mengosongkan PIC tujuan.
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
        if ($c.length) {
            if (!$c.find('option[value="' + company.id + '"]').length) $c.append(new Option(company.text, company.id));
            $c.val(company.id).trigger('change');
        }
        // Site disalin dari Pemesan: opsi lama dibuang, lalu Site Pemesan dipilih
        const $s = $(t.site);
        if ($s.length) {
            $s.empty().append(new Option(site.text, site.id, true, true)).trigger('change');
        }
        const $p = $(t.pic);
        if ($p.length) {
            if (pic) $p.append(new Option(pic.text, pic.id, true, true)).trigger('change');
            else $p.val(null).trigger('change');
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
                // Belum pilih Perusahaan → tidak ada Site yang tampil
                data: function (params) { return { q: params.term || '', id_br: $(pair.company).val() || '-1' }; },
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
                data: function (params) { return { q: params.term || '', id_br: _sqSelectedCompanyIds(), id_sites: _sqSelectedSiteIds(), with_site: 1 }; },
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

    // Hanya Site & PIC Pemesan yang memicu auto-fill (bukan Perusahaan, karena
    // ganti Perusahaan juga mengosongkan Site → auto-fill jalan dengan Site lama)
    ['#detail_id_site_pelanggan', '#detail_id_pic_pelanggan'].forEach(function (sel) {
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

let sqWoCache = [];

function loadSqWoList(idSq, done) {
    $.get('/sq-work-orders/' + idSq + '/list', function (wos) {
        sqWoCache = wos || [];
        renderSqSummary(sqWoCache);
        renderSqWoView();
        if (done) done();
    }).fail(function () {
        $('#sqWoContent').html('<div class="text-center text-danger py-4">Gagal memuat data Work Order.</div>');
        if (done) done();
    });
}

// Baris KPI di atas tab — sama dengan renderSoSummary() di Sales Order (Total WO,
// Total FWO, Total QTY, Total Nilai + breakdown BOQ/Other/Sampling), tanpa kartu
// progress karena SQ tidak punya realisasi.
function renderSqSummary(wos) {
    const sum = (key) => wos.reduce((s, w) => s + (Number(w[key]) || 0), 0);
    const totalBoq      = sum('total_boq');
    const totalOther    = sum('total_boq_other');
    const totalSampling = sum('total_boq_sampling');
    const totalNilai    = totalBoq + totalOther + totalSampling;
    const fmtRp = (n) => 'Rp ' + Number(n || 0).toLocaleString('en-US');

    const kpiCard = (icon, iconBg, label, value) => `<div class="pm-kpi-card">
        <div class="pm-kpi-icon" style="background:${iconBg};"><i class="fa-solid ${icon}"></i></div>
        <div>
            <div class="pm-kpi-label">${label}</div>
            <div class="pm-kpi-value">${value}</div>
        </div>
    </div>`;

    const categories = [
        { label: 'BOQ',          value: totalBoq,      bg: '#eff6ff', color: '#1d4ed8', border: '#bfdbfe' },
        { label: 'BOQ Other',    value: totalOther,    bg: '#fff7ed', color: '#c2410c', border: '#fed7aa' },
        { label: 'BOQ Sampling', value: totalSampling, bg: '#f0fdf4', color: '#15803d', border: '#bbf7d0' },
    ];
    const badgesHtml = categories
        .filter((c) => c.value > 0)
        .map((c) => `<span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:${c.bg};color:${c.color};border:1px solid ${c.border};white-space:nowrap;">${c.label}: ${fmtRp(c.value)}</span>`)
        .join('');

    const totalNilaiCard = `<div class="pm-kpi-card" style="flex-direction:column;align-items:flex-start;gap:8px;min-width:220px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <div class="pm-kpi-icon" style="background:#7c3aed;flex-shrink:0;"><i class="fa-solid fa-sack-dollar"></i></div>
            <div>
                <div class="pm-kpi-label">Total Nilai</div>
                <div class="pm-kpi-value">${totalNilai > 0 ? fmtRp(totalNilai) : '—'}</div>
            </div>
        </div>
        ${badgesHtml ? `<div style="display:flex;gap:6px;flex-wrap:wrap;">${badgesHtml}</div>` : ''}
    </div>`;

    $('#sqSummaryCard').html(
        kpiCard('fa-briefcase', 'var(--primary-500,#1a5fbe)', 'Total WO', wos.length + ' WO') +
        kpiCard('fa-hard-hat', 'var(--primary-700,#18386b)', 'Total FWO', sum('fwo_count') + ' FWO') +
        kpiCard('fa-layer-group', '#0891b2', 'Total QTY', sum('boq_qty') + ' qty') +
        totalNilaiCard
    );
}

function renderSqWoView() {
    const term = ($('#sqWoSearch').val() || '').trim().toLowerCase();
    const filtered = !term ? sqWoCache : sqWoCache.filter(function (wo) {
        return (wo.no_sq_wo || '').toLowerCase().includes(term) || (wo.judul_pekerjaan || '').toLowerCase().includes(term);
    });
    $('#btnClearSqWoSearch').toggleClass('d-none', !term);

    if (!sqWoCache.length) {
        $('#sqWoContent').html(`<div class="text-center text-muted py-4">
            <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
            Belum ada Work Order. Klik tombol "+ WO" untuk mulai.
        </div>`);
    } else if (!filtered.length) {
        $('#sqWoContent').html(`<div class="text-center text-muted py-4">
            <i class="fa-solid fa-magnifying-glass fa-2x d-block mb-2 opacity-25"></i>
            Tidak ditemukan hasil untuk <strong>&ldquo;${escHtml(term)}&rdquo;</strong></div>`);
    } else {
        $('#sqWoContent').html(renderSqWoTable(filtered));
    }
}

const SQ_INTERVAL_LABELS = { 1: 'Bulanan', 2: 'Bimulanan', 3: 'Triwulan', 4: 'Caturwulan', 6: 'Semester', 12: 'Annual' };

// Struktur sama dengan renderWoProgressTable() di sales-order/index.js:
// accordion per Site → sub-grup per Frekuensi → tabel WO.
function renderSqWoTable(wos) {
    const siteGroups = new Map();
    wos.forEach(function (wo) {
        const key = wo.nama_site || '—';
        if (!siteGroups.has(key)) siteGroups.set(key, []);
        siteGroups.get(key).push(wo);
    });

    const TH = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';
    const dash = '<span style="color:#94a3b8;">—</span>';

    function buildRow(wo, idx) {
        return `<tr>
            <td ${TD} style="text-align:center;color:#94a3b8;font-size:12px;">${idx}</td>
            <td ${TD}>
                <a href="/sq-work-orders?open=${wo.id_sq_wo}" class="pm-link-record" style="white-space:nowrap;font-weight:normal;">
                    ${escHtml(wo.no_sq_wo ?? '—')}
                </a>
            </td>
            <td ${TD} style="color:#374151;">${escHtml(wo.judul_pekerjaan ?? '—')}</td>
            <td ${TD} style="color:#64748b;">${wo.keterangan ? escHtml(wo.keterangan) : dash}</td>
            <td ${TD} style="text-align:center;">${wo.no_urut_period ? wo.no_urut_period : dash}</td>
            <td ${TD} style="text-align:center;white-space:nowrap;color:#374151;">Ke-${wo.hari_mulai}</td>
            <td ${TD} style="text-align:center;white-space:nowrap;color:#374151;">${wo.durasi_hari ? wo.durasi_hari + ' hr' : dash}</td>
            <td ${TD} style="text-align:center;color:#1a56db;font-weight:600;">${wo.boq_count}</td>
            <td ${TD} style="text-align:right;white-space:nowrap;font-weight:600;">${wo.total_boq > 0 ? formatRupiahSq(wo.total_boq) : dash}</td>
            <td ${TD} style="text-align:center;white-space:nowrap;">
                <a href="/sq-work-orders?open=${wo.id_sq_wo}"
                    class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" title="Buka detail SQ WO">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
            </td>
        </tr>`;
    }

    function buildPeriodSection(list, interval) {
        const label = interval ? SQ_INTERVAL_LABELS[interval] || interval + ' bln' : 'Tidak Ada Periode';
        const badgeColor = interval ? '#0d9488' : '#64748b';
        const badgeBg = interval ? '#ccfbf1' : '#f1f5f9';
        const badgeBorder = interval ? '#5eead4' : '#e2e8f0';
        const rows = list.map(function (wo, i) { return buildRow(wo, i + 1); }).join('');

        return `<div style="border-top:1px solid #e2e8f0;">
            <div class="wo-period-header" style="display:flex;align-items:center;gap:8px;padding:8px 14px 8px 32px;background:#fafbfc;cursor:pointer;user-select:none;">
                <i class="fa-solid fa-chevron-down" style="color:${badgeColor};font-size:10px;transition:transform .2s;transform:rotate(-90deg);"></i>
                <i class="fa-solid fa-calendar-days" style="color:${badgeColor};font-size:11px;"></i>
                <span style="font-size:12px;font-weight:700;color:${badgeColor};text-transform:uppercase;letter-spacing:.5px;">${escHtml(label)}</span>
                <span style="font-size:11px;font-weight:700;padding:1px 8px;border-radius:20px;background:${badgeBg};color:${badgeColor};border:1px solid ${badgeBorder};">${list.length} WO</span>
            </div>
            <div class="wo-period-body" style="display:none;">
                <div class="table-responsive" style="padding-left:32px;">
                    <table class="table table-sm table-hover table-striped mb-0" style="font-size:13px;min-width:1000px;">
                        <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                            <tr>
                                <th ${TH} style="width:44px;text-align:center;white-space:nowrap;">No</th>
                                <th ${TH} style="width:130px;white-space:nowrap;">No SQ WO</th>
                                <th ${TH} style="white-space:nowrap;">Judul Pekerjaan</th>
                                <th ${TH} style="width:160px;white-space:nowrap;">Keterangan</th>
                                <th ${TH} style="width:90px;text-align:center;white-space:nowrap;">Urutan ke-</th>
                                <th ${TH} style="width:100px;text-align:center;white-space:nowrap;">Hari Mulai</th>
                                <th ${TH} style="width:90px;text-align:center;white-space:nowrap;">Durasi</th>
                                <th ${TH} style="width:80px;text-align:center;white-space:nowrap;">BOQ</th>
                                <th ${TH} style="width:130px;text-align:right;white-space:nowrap;">Total BOQ</th>
                                <th ${TH} style="width:70px;text-align:center;white-space:nowrap;">Action</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
    }

    let items = '';
    siteGroups.forEach(function (list, site) {
        const periodGroups = new Map();
        list.forEach(function (wo) {
            const key = wo.interval_bulan || null;
            if (!periodGroups.has(key)) periodGroups.set(key, []);
            periodGroups.get(key).push(wo);
        });
        const sortedPeriods = Array.from(periodGroups.entries()).sort(function (a, b) {
            if (a[0] === null) return 1;
            if (b[0] === null) return -1;
            return Number(a[0]) - Number(b[0]);
        });
        const periodSections = sortedPeriods.map(function (entry) { return buildPeriodSection(entry[1], entry[0]); }).join('');

        items += `<div class="pm-accordion-item">
            <div class="pm-accordion-header" aria-expanded="false">
                <div class="pm-accordion-toggle">
                    <i class="fa-solid fa-chevron-right pm-accordion-chevron"></i>
                    <i class="fa-solid fa-location-dot" style="color:#1a56db;font-size:12px;flex-shrink:0;"></i>
                    <span style="font-size:13px;font-weight:600;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(site)}</span>
                </div>
                <div class="pm-accordion-meta">
                    <span class="pm-badge pm-badge--blue">${list.length} WO</span>
                </div>
            </div>
            <div class="pm-accordion-collapse" style="display:none;">
                <div class="pm-accordion-body" style="padding:0;">${periodSections}</div>
            </div>
        </div>`;
    });

    return `<div class="pm-accordion" style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">${items}</div>`;
}

$(document).on('click', '#sqWoContent .pm-accordion-header', function (e) {
    if ($(e.target).closest('a, button').length) return;
    const $header = $(this);
    const isOpen = $header.attr('aria-expanded') === 'true';
    $header.attr('aria-expanded', !isOpen);
    $header.next('.pm-accordion-collapse').slideToggle(150);
});

$(document).on('click', '#sqWoContent .wo-period-header', function (e) {
    if ($(e.target).closest('a, button').length) return;
    const $header = $(this);
    const $body = $header.next('.wo-period-body');
    const isOpen = $body.is(':visible');
    $body.slideToggle(150);
    $header.find('.fa-chevron-down').css('transform', isOpen ? 'rotate(-90deg)' : 'rotate(0deg)');
});

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

// ── Status SQ: Finalkan & Batalkan (dengan konfirmasi) ──────────────────
function sqStatusAction(url, data, doneMsg) {
    $.ajax({
        url: url,
        method: 'POST',
        data: Object.assign({ _token: window.route.csrf }, data || {}),
        success: function (res) {
            Notify.success(res.message || doneMsg);
            setTimeout(function () { window.location.reload(); }, 800);
        },
        error: function (xhr) {
            Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Aksi gagal diproses.');
        },
    });
}

function finalizeSq(idSq) {
    Swal.fire({
        title: 'Finalkan Sales Quotation?',
        html: 'Setelah difinalkan, SQ <strong>terkunci</strong> dan tidak bisa diedit lagi (header, WO, BOQ, Budget). SQ siap diterbitkan menjadi SO.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Finalkan',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then(function (result) {
        if (result.isConfirmed) sqStatusAction('/sales-quotations/' + idSq + '/finalize', {}, 'SQ difinalkan.');
    });
}

function cancelSq(idSq) {
    Swal.fire({
        title: 'Batalkan Sales Quotation?',
        html: 'SQ akan berstatus <strong>Cancel</strong> dan terkunci. Tindakan ini tidak bisa dibatalkan.',
        input: 'text',
        inputPlaceholder: 'Alasan (opsional), mis. ditolak pelanggan',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Batalkan SQ',
        cancelButtonText: 'Kembali',
        confirmButtonColor: '#dc2626',
        reverseButtons: true,
    }).then(function (result) {
        if (result.isConfirmed) sqStatusAction('/sales-quotations/' + idSq + '/cancel', { keterangan_status: result.value || '' }, 'SQ dibatalkan.');
    });
}

// Revisi: bikin SQ baru (Draft) dari SQ Final ini; SQ ini sendiri jadi Cancel
// ("digantikan"). Beda dari cancelSq — bukan pembatalan, tapi penerus.
function reviseSq(idSq) {
    Swal.fire({
        title: 'Buat Revisi SQ?',
        html: 'SQ baru berstatus <strong>Draft</strong> akan dibuat sebagai salinan penuh (WO, BOQ, BOQ Other/Sampling, Budget Plan). SQ ini sendiri otomatis berstatus <strong>Cancel</strong> (digantikan revisi baru).',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Buat Revisi',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: '/sales-quotations/' + idSq + '/revise',
            method: 'POST',
            data: { _token: window.route.csrf },
            success: function (res) {
                Notify.success(res.message || 'Revisi dibuat.');
                setTimeout(function () { window.location.href = '/sales-quotations?open=' + res.id_sq; }, 800);
            },
            error: function (xhr) {
                Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal membuat revisi.');
            },
        });
    });
}

function showRiwayatRevisiSq(idSq) {
    $.get('/sales-quotations/' + idSq, function (res) {
        const rows = (res.riwayat_revisi || []).map(function (r) {
            const badge = { draft: 'secondary', final: 'primary', completed: 'success', cancel: 'warning' }[r.status] || 'secondary';
            const current = r.id_sq === idSq ? ' <strong>(sedang dibuka)</strong>' : '';
            return `<tr>
                <td style="text-align:center;">${r.revisi === 0 ? '—' : 'Rev.' + r.revisi}</td>
                <td>${r.id_sq === idSq ? escHtml(r.no_sq) : `<a href="/sales-quotations?open=${r.id_sq}">${escHtml(r.no_sq)}</a>`}${current}</td>
                <td><span class="badge bg-${badge}">${escHtml(r.status)}</span></td>
                <td>${r.created_at ? new Date(r.created_at).toLocaleDateString('id-ID') : '-'}</td>
            </tr>`;
        }).join('');
        Swal.fire({
            title: 'Riwayat Revisi',
            html: `<div class="table-responsive text-start"><table class="table table-sm mb-0">
                <thead><tr><th>Rev.</th><th>No. SQ</th><th>Status</th><th>Dibuat</th></tr></thead>
                <tbody>${rows}</tbody>
            </table></div>`,
            width: 560,
            confirmButtonText: 'Tutup',
        });
    });
}

// ── Convert SQ → SO ─────────────────────────────────────────────────────
// Modal kecil: user isi Tanggal SO, Tanggal Mulai (hari ke-1), PIC Order,
// PO. Hari ke-N tiap WO dihitung jadi tanggal dari Tanggal Mulai itu.
let convertSqId = null;

function convertDateLabel(startStr, hari, durasi) {
    if (!startStr) return '—';
    const start = new Date(startStr + 'T00:00:00');
    const mulai = new Date(start); mulai.setDate(mulai.getDate() + Math.max(0, hari - 1));
    const selesai = new Date(mulai); selesai.setDate(selesai.getDate() + Math.max(0, (durasi || 1) - 1));
    const f = function (d) { return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }); };
    return f(mulai) + ' s/d ' + f(selesai);
}

function openConvertSqModal(idSq) {
    convertSqId = idSq;
    $('#btnConvertSqSubmit').prop('disabled', true);
    $('#convertSqBody').html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConvertSq')).show();

    $.get('/sales-quotations/' + idSq + '/convert-preview', function (p) {
        $('#convertSqNo').text(p.no_sq + (p.revisi > 0 ? ' Rev.' + p.revisi : ''));

        if (p.status !== 'final' && !p.sudah_convert) {
            $('#convertSqBody').html('<div class="alert alert-warning mb-0">Hanya SQ berstatus <strong>Final</strong> yang bisa diterbitkan menjadi SO. Finalkan SQ ini dulu.</div>');
            return;
        }
        if (p.sudah_convert) {
            $('#convertSqBody').html(`<div class="alert alert-warning mb-0">
                SQ ini sudah diterbitkan menjadi
                <a href="/sales-orders?open=${p.sudah_convert.id_so}" class="fw-semibold">${escHtml(p.sudah_convert.no_so)}</a>.
                Satu SQ hanya boleh diterbitkan satu kali.</div>`);
            return;
        }
        if (p.missing.length) {
            $('#convertSqBody').html(`<div class="alert alert-danger mb-0">
                Data SQ belum lengkap untuk menerbitkan SO. Lengkapi dulu di tab Informasi:
                <ul class="mb-0 mt-2">${p.missing.map(function (m) { return '<li>' + escHtml(m) + '</li>'; }).join('')}</ul></div>`);
            return;
        }

        const woRows = p.wos.length ? p.wos.map(function (w, i) {
            return `<tr data-hari="${w.hari_mulai}" data-durasi="${w.durasi_hari || ''}">
                <td style="text-align:center;color:#94a3b8;">${i + 1}</td>
                <td>${escHtml(w.no_sq_wo || '—')}</td>
                <td>${escHtml(w.judul_pekerjaan || '—')}<div style="font-size:11px;color:#94a3b8;">${escHtml(w.nama_site || 'Site: ikut Pemesan')}</div></td>
                <td style="text-align:center;">${w.boq_count} / ${w.tambahan_count} / ${w.budget_count} / ${w.fwo_count}</td>
                <td class="convert-wo-tgl" style="white-space:nowrap;">—</td>
            </tr>`;
        }).join('') : '<tr><td colspan="5" class="text-center text-muted py-3">Belum ada Work Order</td></tr>';

        $('#convertSqBody').html(`
        <form id="convertSqForm" class="row g-3">
            <div class="col-md-4">
                <label class="form-label required">Tanggal SO</label>
                <input type="text" name="tanggal_so" class="form-control fp-date" value="${new Date().toISOString().slice(0, 10)}">
            </div>
            <div class="col-md-4">
                <label class="form-label required">Tanggal Mulai (hari ke-1)</label>
                <input type="text" name="tanggal_mulai" id="convertTanggalMulai" class="form-control fp-date">
            </div>
            <div class="col-md-4">
                <label class="form-label required">PIC Order</label>
                <select name="pic_order" id="convertPicOrder" class="form-select"></select>
            </div>
            <div class="col-md-4">
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" name="tidak_ada_po" id="convertTidakAdaPo" value="1">
                    <label class="form-check-label" for="convertTidakAdaPo">Tidak ada PO</label>
                </div>
            </div>
            <div class="col-md-4 convert-po"><label class="form-label">No. PO</label><input type="text" name="no_po" class="form-control" maxlength="50"></div>
            <div class="col-md-4 convert-po"><label class="form-label">Tanggal PO</label><input type="text" name="tanggal_po" class="form-control fp-date"></div>
            <div class="col-12">
                <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">
                    Work Order yang dibuat <span style="font-weight:400;color:#94a3b8;">(BOQ / BOQ Other+Sampling / Budget Plan / FWO ikut terbawa)</span>
                </div>
                <div class="table-responsive"><table class="pm-table">
                    <thead><tr><th style="width:36px;text-align:center;">#</th><th>No SQ WO</th><th>Judul / Site</th><th style="text-align:center;">BOQ / Other+Sampling / Budget / FWO</th><th>Jadwal di SO</th></tr></thead>
                    <tbody>${woRows}</tbody>
                </table></div>
                <div style="font-size:11px;color:#94a3b8;margin-top:6px;">
                    Setelah convert: status SQ otomatis jadi Diterima, PIC Pekerjaan tiap WO diisi manual di detail WO (SQ menyimpan kontak pelanggan, WO menyimpan user), FWO dibuat belakangan di SO.
                </div>
            </div>
        </form>`);

        initFpDate('#convertSqForm');
        // Default Tanggal Mulai (hari ke-1) = Rencana Mulai SQ, tetap bisa diubah user
        if (p.rencana_mulai) {
            const fp = document.getElementById('convertTanggalMulai')._flatpickr;
            if (fp) fp.setDate(String(p.rencana_mulai).slice(0, 10), true);
            else $('#convertTanggalMulai').val(String(p.rencana_mulai).slice(0, 10)).trigger('change');
        }
        $('#convertPicOrder').select2({
            width: '100%', dropdownParent: $('#modalConvertSq'), allowClear: true, placeholder: 'Pilih User', minimumInputLength: 0,
            ajax: { url: '/users/select2', dataType: 'json', delay: 200, data: function (q) { return { q: q.term }; }, processResults: function (d) { return { results: d }; }, cache: true },
            escapeMarkup: function (m) { return m; },
        });
        $('#btnConvertSqSubmit').prop('disabled', false);
    }).fail(function () {
        $('#convertSqBody').html('<div class="text-danger text-center py-4">Gagal memuat data SQ.</div>');
    });
}

$(document).on('change input', '#convertTanggalMulai', function () {
    const v = $(this).val();
    $('#convertSqForm tbody tr[data-hari]').each(function () {
        $(this).find('.convert-wo-tgl').text(convertDateLabel(v, Number($(this).data('hari')), Number($(this).data('durasi')) || 0));
    });
});

$(document).on('change', '#convertTidakAdaPo', function () {
    $('#convertSqForm .convert-po').toggle(!this.checked);
});

$(document).on('click', '#btnConvertSqSubmit', function () {
    const $btn = $(this);
    const data = $('#convertSqForm').serializeArray();
    data.push({ name: '_token', value: window.route.csrf });
    $btn.prop('disabled', true);
    $.ajax({
        url: '/sales-quotations/' + convertSqId + '/convert',
        method: 'POST',
        data: $.param(data),
        success: function (res) {
            Notify.success(res.message || 'Berhasil diterbitkan');
            setTimeout(function () { window.location.href = '/sales-orders?open=' + res.id_so; }, 900);
        },
        error: function (xhr) {
            $btn.prop('disabled', false);
            const errs = xhr.responseJSON && xhr.responseJSON.errors;
            Notify.error((errs ? Object.values(errs)[0][0] : (xhr.responseJSON && xhr.responseJSON.message)) || 'Terbitkan SO gagal');
        },
    });
});

// ── Tab "Keterangan" — poin keterangan penawaran terstruktur ─────────────
// Poin dipilih dari master (Poin Keterangan), hanya isian berparameter (hari,
// rekening, kontak) yang diubah per SQ. Draft = bisa diubah; selain itu hanya baca
// (teks sudah dikunci saat Final).
const SQ_KET_TOKEN = /\{([a-z0-9_]+):([a-z]+)(?:=([^{}]*))?\}/g;
let sqKetState = { idSq: null, editable: false, rows: [], katalog: [] };
let sqKetDrake = null;

function sqKetText(row, editable) {
    if (!editable) return escHtml(row.teks).replace(/\n/g, '<br>');

    const byKey = {};
    row.isian.forEach(function (i) { byKey[i.key] = i; });

    let out = '', last = 0, m;
    const re = new RegExp(SQ_KET_TOKEN.source, 'g');
    const plain = function (s) { return escHtml(s).replace(/\n/g, '<br>'); };
    while ((m = re.exec(row.template)) !== null) {
        out += plain(row.template.slice(last, m.index));
        last = re.lastIndex;
        const i = byKey[m[1]];
        const base = `data-row="${row.id_sq_keterangan}" data-key="${m[1]}" data-no-disable`;
        if (!i) { out += escHtml(m[0]); continue; }
        if (i.tipe === 'angka') {
            out += `<input type="number" min="0" max="9999" class="form-control form-control-sm d-inline-block sq-ket-angka"
                style="width:76px;vertical-align:middle;" ${base} value="${escHtml(i.value_label ?? '')}">`;
        } else if (i.tipe === 'rekening' || i.tipe === 'user') {
            const opt = i.value ? `<option value="${i.value}" selected>${escHtml(i.value_label ?? '')}</option>` : '<option value=""></option>';
            out += `<span class="d-inline-block" style="min-width:320px;vertical-align:middle;">
                <select class="sq-ket-sel" data-tipe="${i.tipe}" ${base}>${opt}</select></span>`;
        } else if (i.tipe === 'termin') {
            out += sqKetTerminEditor(row.id_sq_keterangan, m[1], Array.isArray(i.value) ? i.value : []);
        } else {
            out += `<em class="text-muted">[${escHtml(m[1])}]</em>`;
        }
    }
    return out + plain(row.template.slice(last));
}

// Editor daftar tahap pembayaran (isian bertipe termin): persen + pemicu (pilihan tetap)
function sqKetTerminRow(n, t) {
    const opts = (sqKetState.pemicu || []).map(function (p) {
        return `<option value="${p.value}"${t && t.pemicu === p.value ? ' selected' : ''}>${escHtml(p.label)}</option>`;
    }).join('');
    return `<div class="d-flex gap-2 mb-1 align-items-center sq-termin-row">
        <span style="width:58px;color:#64748b;">Tahap ${n}</span>
        <input type="number" min="1" max="100" class="form-control form-control-sm sq-termin-persen" style="width:72px;" value="${t ? t.persen : ''}" data-no-disable>
        <span>%</span>
        <select class="form-select form-select-sm sq-termin-pemicu" style="width:400px;max-width:100%;" data-no-disable>${opts}</select>
        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 sq-termin-del" title="Hapus tahap" data-no-disable><i class="fa-solid fa-xmark" style="color:#dc2626;"></i></button>
    </div>`;
}

function sqKetTerminEditor(rowId, key, tahap) {
    const total = tahap.reduce(function (s, t) { return s + (Number(t.persen) || 0); }, 0);
    const ok = total === 100;
    return `<div class="sq-ket-termin mt-1" data-row="${rowId}" data-key="${key}">
        ${tahap.map(function (t, i) { return sqKetTerminRow(i + 1, t); }).join('')}
        <div class="d-flex gap-2 align-items-center mt-1">
            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 sq-termin-add" data-no-disable><i class="fa-solid fa-plus me-1"></i>Tahap</button>
            <span class="sq-termin-total" style="font-size:11px;font-weight:600;color:${ok ? '#15803d' : '#b45309'};">Total ${total}%${ok ? '' : ' (harus 100%)'}</span>
        </div>
    </div>`;
}

// Total % & nomor "Tahap n" diperbarui langsung di tempat (tanpa render ulang daftar, supaya fokus tidak hilang)
function sqKetUpdateTerminTotal($wrap) {
    let total = 0;
    $wrap.find('.sq-termin-row').each(function (i) {
        $(this).find('span').first().text('Tahap ' + (i + 1));
        total += Number($(this).find('.sq-termin-persen').val()) || 0;
    });
    const ok = total === 100;
    $wrap.find('.sq-termin-total')
        .text('Total ' + total + '%' + (ok ? '' : ' (harus 100%)'))
        .css('color', ok ? '#15803d' : '#b45309');
}

function sqKetSimpanTermin($wrap) {
    sqKetUpdateTerminTotal($wrap);
    const key = $wrap.data('key');
    const data = {};
    let n = 0;
    $wrap.find('.sq-termin-row').each(function () {
        const persen = $(this).find('.sq-termin-persen').val();
        if (persen === '') return; // baris belum lengkap dilewati
        data['nilai[' + key + '][' + n + '][persen]'] = persen;
        data['nilai[' + key + '][' + n + '][pemicu]'] = $(this).find('.sq-termin-pemicu').val();
        n++;
    });
    if (!n) data['nilai[' + key + ']'] = '';
    sqKetSend('PUT', '/sales-quotations/' + sqKetState.idSq + '/keterangan/' + $wrap.data('row'), data, function () {
        Notify.toast('Berhasil disimpan', 'success', 2000);
        sqKetRefreshStatus();
    });
}

$(document).on('change', '#sqKetContent .sq-termin-persen, #sqKetContent .sq-termin-pemicu', function () {
    sqKetSimpanTermin($(this).closest('.sq-ket-termin'));
});
$(document).on('click', '#sqKetContent .sq-termin-del', function () {
    const $wrap = $(this).closest('.sq-ket-termin');
    $(this).closest('.sq-termin-row').remove();
    sqKetSimpanTermin($wrap);
});
$(document).on('click', '#sqKetContent .sq-termin-add', function () {
    const $wrap = $(this).closest('.sq-ket-termin');
    const n = $wrap.find('.sq-termin-row').length + 1;
    $(this).closest('div').before(sqKetTerminRow(n, null));
});

function renderSqKetList() {
    const { rows, editable } = sqKetState;
    if (!rows.length) {
        $('#sqKetContent').html(`<div class="text-center text-muted py-4">
            <i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>
            Belum ada poin keterangan.${editable ? ' Tambahkan poin atau gunakan "Isi Poin Standar".' : ''}</div>`);
        return;
    }

    // Jumlah poin per kategori — lebih dari 1 diberi penanda (bisa bertentangan, mis. dua poin pelunasan)
    const perKategori = {};
    rows.forEach(function (r) { perKategori[r.kategori] = (perKategori[r.kategori] || 0) + 1; });

    const html = rows.map(function (r, idx) {
        // Selalu dirender saat editable; disembunyikan bila lengkap (diperbarui di tempat oleh sqKetRefreshStatus)
        const warn = editable
            ? `<span class="pm-badge sq-ket-warn${r.lengkap ? ' d-none' : ''}" style="background:#fef2f2;color:#b91c1c;font-size:10px;">Isian belum lengkap</span>` : '';
        const dup = editable && perKategori[r.kategori] > 1
            ? `<span class="pm-badge" style="background:#fffbeb;color:#b45309;font-size:10px;margin-left:4px;" title="Ada lebih dari 1 poin di kategori ini — pastikan isinya tidak bertentangan">${perKategori[r.kategori]} poin di kategori ini</span>` : '';
        const tools = editable ? `<div class="d-flex gap-1 flex-shrink-0">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 sq-ket-ganti" data-row="${r.id_sq_keterangan}" data-kategori="${escHtml(r.kategori)}" title="Ganti varian poin ini (posisi tetap)" data-no-disable><i class="fa-solid fa-right-left" style="color:#1d4ed8;"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 sq-ket-del" data-row="${r.id_sq_keterangan}" title="Hapus" data-no-disable><i class="fa-solid fa-trash" style="color:#dc2626;"></i></button>
            </div>` : '';
        const handle = editable
            ? `<button type="button" class="drag-handle-btn" tabindex="-1" title="Geser untuk urutkan" data-no-disable><i class="fa-solid fa-grip-vertical"></i></button>`
            : '';
        return `<div class="d-flex align-items-start gap-3 py-2 sq-ket-row" style="border-bottom:1px solid #e2e8f0;background:#fff;" data-row="${r.id_sq_keterangan}">
            <div class="d-flex align-items-center gap-1 flex-shrink-0" style="padding-top:2px;">${handle}<span class="sq-ket-no" style="min-width:20px;text-align:center;color:#94a3b8;font-weight:600;">${idx + 1}</span></div>
            <div class="flex-grow-1" style="font-size:13px;line-height:2;">
                <span class="pm-badge" style="background:#f5f3ff;color:#6d28d9;font-size:10px;margin-right:6px;">${escHtml(r.kategori_label)}</span>${warn}${dup}<br>
                ${sqKetText(r, editable)}
            </div>
            ${tools}
        </div>`;
    }).join('');
    $('#sqKetContent').html(`<div id="sqKetList" style="border:1px solid #e2e8f0;border-radius:8px;padding:4px 14px;">${html}</div>`);

    if (sqKetDrake) { sqKetDrake.destroy(); sqKetDrake = null; }
    if (editable && typeof dragula !== 'undefined') {
        // Drag-and-drop urutan lewat handle grip (pola sama Testing Points / tableForm.js)
        sqKetDrake = dragula([document.getElementById('sqKetList')], {
            moves: function (el, source, handle) { return $(handle).closest('.drag-handle-btn').length > 0; },
        });
        sqKetDrake.on('drop', function () {
            const ids = $('#sqKetList .sq-ket-row').map(function () { return Number($(this).data('row')); }).get();
            $('#sqKetList .sq-ket-no').each(function (i) { $(this).text(i + 1); });
            // Nomor sudah diperbarui di tempat di atas — tidak perlu render ulang daftar
            sqKetSend('PUT', '/sales-quotations/' + sqKetState.idSq + '/keterangan/urut', { ids: ids }, function () {
                Notify.toast('Berhasil disimpan', 'success', 2000);
            });
        });
    }

    if (editable) {
        $('#sqKetContent .sq-ket-sel').each(function () {
            const $el = $(this);
            const tipe = $el.data('tipe');
            $el.select2({
                width: '100%', dropdownParent: $('#detailContent'), allowClear: true,
                placeholder: tipe === 'rekening' ? 'Pilih rekening' : 'Pilih user', minimumInputLength: 0,
                ajax: {
                    url: '/sales-quotations/keterangan/opsi', dataType: 'json', delay: 200,
                    data: function (p) { return { q: p.term || '', tipe: tipe }; },
                    processResults: function (d) { return { results: d }; },
                },
            });
        });
    }
}

function renderSqKetToolbar() {
    if (!sqKetState.editable) {
        $('#sqKetToolbar').html(`<span style="font-size:11px;color:#64748b;"><i class="fa-solid fa-lock me-1"></i>
            Keterangan terkunci mengikuti status SQ.</span>`);
        return;
    }
    $('#sqKetToolbar').html(`<div class="d-flex flex-wrap gap-2 align-items-center">
        <div style="width:420px;max-width:100%;"><select id="sqKetTambah" style="width:100%" data-no-disable></select></div>
        <button type="button" id="btnSqKetStandar" class="btn btn-sm btn-outline-primary" data-no-disable>
            <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Isi Poin Standar
        </button>
        <span class="text-muted" style="font-size:11px;">Poin dipilih dari master; hanya isian (hari, rekening, kontak) yang diubah.</span>
    </div>`);

    $.get('/sales-quotations/keterangan/katalog', { id_sq: sqKetState.idSq }, function (list) {
        sqKetState.katalog = list; // dipakai juga oleh "Ganti varian"
        const groups = {};
        // Poin yang sudah ada di SQ ini ditampilkan abu-abu & tidak bisa dipilih lagi
        list.forEach(function (p) {
            (groups[p.kategori_label] = groups[p.kategori_label] || []).push(
                { id: p.id, text: p.dipakai ? p.text + '  (sudah ada)' : p.text, disabled: !!p.dipakai });
        });
        const data = [{ id: '', text: '' }].concat(Object.keys(groups).map(function (g) { return { text: g, children: groups[g] }; }));
        $('#sqKetTambah').select2({
            width: '100%', dropdownParent: $('#detailContent'), placeholder: '+ Tambah poin keterangan...', data: data,
        });
    });
}

function sqKetSend(method, url, data, done) {
    $.ajax({
        url: url, method: 'POST',
        data: Object.assign({ _token: window.route.csrf, _method: method }, data || {}),
        success: function (res) { if (done) done(res); },
        error: function (xhr) {
            Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan keterangan.');
            loadSqKeterangan(sqKetState.idSq);
        },
    });
}

function loadSqKeterangan(idSq) {
    sqKetState.idSq = idSq;
    $.get('/sales-quotations/' + idSq + '/keterangan', function (res) {
        sqKetState.editable = res.status === 'draft' && can('sales-quotations', 'can_update');
        sqKetState.rows = res.data || [];
        sqKetState.pemicu = res.pemicu || [];
        renderSqKetToolbar();
        renderSqKetList();
        sqKetRenderSummary();
    }).fail(function () {
        $('#sqKetContent').html('<div class="text-center text-danger py-4">Gagal memuat keterangan.</div>');
    });
}

$(document).on('select2:select', '#sqKetTambah', function () {
    const id = $(this).val();
    if (!id) return;
    sqKetSend('PUT', '/sales-quotations/' + sqKetState.idSq + '/keterangan/tambah', { id_poin: id }, function () {
        Notify.toast('Berhasil disimpan', 'success', 2000);
        loadSqKeterangan(sqKetState.idSq);
    });
});

$(document).on('click', '#btnSqKetStandar', function () {
    sqKetSend('PUT', '/sales-quotations/' + sqKetState.idSq + '/keterangan/isi-standar', {}, function (res) {
        Notify.toast(res.message || 'Berhasil disimpan', 'success', 2000);
        loadSqKeterangan(sqKetState.idSq);
    });
});

function sqKetSimpanNilai(row, key, val) {
    const data = {};
    data['nilai[' + key + ']'] = val === null ? '' : val;
    sqKetSend('PUT', '/sales-quotations/' + sqKetState.idSq + '/keterangan/' + row, data, function () {
        Notify.toast('Berhasil disimpan', 'success', 2000);
        // Perbarui penanda "belum lengkap" & ringkasan di tempat (tanpa render ulang → fokus/Tab tetap jalan)
        sqKetRefreshStatus();
    });
}

// Ambil status kelengkapan terbaru dan perbarui penanda tiap baris + ringkasan, tanpa merender ulang daftar
function sqKetRefreshStatus() {
    $.get('/sales-quotations/' + sqKetState.idSq + '/keterangan', function (res) {
        sqKetState.rows = res.data || [];
        sqKetState.rows.forEach(function (r) {
            $('#sqKetList .sq-ket-row[data-row="' + r.id_sq_keterangan + '"] .sq-ket-warn').toggleClass('d-none', !!r.lengkap);
        });
        sqKetRenderSummary();
    });
}

// Banner kelengkapan di atas daftar + angka di judul tab (terlihat sebelum user klik Finalkan)
function sqKetRenderSummary() {
    const $badge = $('#sqKetTabBadge');
    const { rows, editable } = sqKetState;
    if (!editable || !rows.length) {
        $('#sqKetSummary').empty();
        $badge.addClass('d-none').text('');
        return;
    }
    const belum = rows.filter(function (r) { return !r.lengkap; }).length;
    if (belum > 0) {
        $badge.removeClass('d-none').text(belum);
        $('#sqKetSummary').html(`<div class="d-flex align-items-center gap-2 mb-2 px-3 py-2" style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:12px;color:#92400e;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><b>${belum} dari ${rows.length} poin</b> isiannya belum lengkap — lengkapi sebelum Finalkan SQ.</span>
            <a href="#" class="ms-auto sq-ket-lompat" style="font-weight:600;white-space:nowrap;">Lihat poin pertama</a>
        </div>`);
    } else {
        $badge.addClass('d-none').text('');
        $('#sqKetSummary').html(`<div class="mb-2 px-3 py-2" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:12px;color:#166534;">
            <i class="fa-solid fa-circle-check me-1"></i>Semua ${rows.length} poin sudah lengkap.</div>`);
    }
}

$(document).on('click', '#sqKetSummary .sq-ket-lompat', function (e) {
    e.preventDefault();
    const $row = $('#sqKetList .sq-ket-row').filter(function () { return $(this).find('.sq-ket-warn:not(.d-none)').length > 0; }).first();
    if (!$row.length) return;
    $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    $row.css({ transition: 'background .3s', background: '#fef9c3' });
    setTimeout(function () { $row.css('background', '#fff'); }, 1400);
});

// Ganti varian: pilih poin lain di kategori yang sama, posisi baris tetap
$(document).on('click', '#sqKetContent .sq-ket-ganti', function () {
    const $row = $(this).closest('.sq-ket-row');
    const $panel = $row.find('.sq-ket-ganti-panel');
    if ($panel.length) { $panel.remove(); return; } // klik lagi = tutup

    const kategori = $(this).data('kategori');
    const rowId = $(this).data('row');
    const opsi = (sqKetState.katalog || []).filter(function (p) { return p.kategori === kategori && !p.dipakai; });
    if (!opsi.length) {
        Notify.warning('Tidak ada varian lain di kategori ini. Tambahkan varian baru di master Poin Keterangan.');
        return;
    }
    const $p = $(`<div class="sq-ket-ganti-panel mt-1" style="max-width:640px;"><select style="width:100%" data-no-disable></select></div>`);
    $row.find('.flex-grow-1').append($p);
    const $sel = $p.find('select');
    $sel.select2({
        width: '100%', dropdownParent: $('#detailContent'), placeholder: 'Pilih varian pengganti...',
        data: [{ id: '', text: '' }].concat(opsi.map(function (p) { return { id: p.id, text: p.text }; })),
    });
    $sel.select2('open');
    $sel.on('select2:select', function () {
        sqKetSend('PUT', '/sales-quotations/' + sqKetState.idSq + '/keterangan/' + rowId + '/ganti', { id_poin: $sel.val() }, function () {
            Notify.toast('Varian diganti', 'success', 2000);
            loadSqKeterangan(sqKetState.idSq);
        });
    });
});

$(document).on('change', '#sqKetContent .sq-ket-angka', function () {
    sqKetSimpanNilai($(this).data('row'), $(this).data('key'), $(this).val());
});
$(document).on('select2:select select2:clear', '#sqKetContent .sq-ket-sel', function () {
    sqKetSimpanNilai($(this).data('row'), $(this).data('key'), $(this).val() || null);
});

$(document).on('click', '#sqKetContent .sq-ket-del', function () {
    const row = $(this).data('row');
    Notify.confirmDelete('Hapus poin keterangan ini?', function () {
        sqKetSend('PUT', '/sales-quotations/' + sqKetState.idSq + '/keterangan/' + row + '/hapus', {}, function () {
            Notify.toast('Berhasil disimpan', 'success', 2000);
            loadSqKeterangan(sqKetState.idSq);
        });
    });
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
            loadSqKeterangan(res.id_sq);
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

    $(document).on('input', '#sqWoSearch', renderSqWoView);
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
