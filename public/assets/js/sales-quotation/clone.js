// Clone SQ & Clone SQ WO — mengikuti cara Clone SO (public/assets/js/sales-order/clone.js):
// halaman penuh, hierarki SQ → WO → (BOQ | FWO | Budget) dirender DI DALAM card accordion WO,
// tiap WO/FWO/baris/Budget Plan punya checkbox "Sertakan", body card dibangun LAZY saat pertama dibuka.
// Beda dari Clone SO: SQ memakai "hari ke-N" (bukan tanggal), tanpa Personel & Realisasi, Discount BOQ dalam Rupiah.
//
// Penambahan BOQ baru: BOQ (Testing Point + checklist Testing Item), BOQ Other, BOQ Sampling.
// Fieldwork BOQ baru tidak ditambah di sini — alokasi FWO diatur dari halaman SQ Fieldwork setelah clone.

let cloneSourceData = null;
let woIndexCounter = 0;
let fwoIndexCounter = 0;
// Mode Clone SQ WO: 1 WO disalin ke SQ-nya sendiri — tanpa form SQ.
const WO_ONLY = !!window.cloneRoute.woOnly;
const INTERVAL_LABELS = { 1: 'Bulanan', 2: 'Bimulanan', 3: 'Triwulan', 4: 'Caturwulan', 6: 'Semester', 12: 'Annual' };

$(document).ready(function () {
    loadCloneSourceData();

    $('#cloneSqForm').on('submit', function (e) {
        e.preventDefault();
        submitClone();
    });

    $('#woSearch').on('input', function () {
        const q = $(this).val().toLowerCase().trim();
        $('#woAccordion .wo-card').each(function () {
            $(this).toggle(!q || ($(this).data('search') || '').toString().includes(q));
        });
        $('#woSearchClear').toggleClass('d-none', !q);
    });
    $('#woSearchClear').on('click', function () {
        $('#woSearch').val('').trigger('input');
    });

    // Tinggi header WO berubah saat lebar layar berubah → acuan sticky header FWO di dalamnya ikut diukur ulang
    let resizeTimer;
    $(window).on('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            $('#woAccordion .wo-card.wo-active').each(function () {
                $(this).css('--wo-header-h', $(this).find('> .accordion-header').outerHeight() + 'px');
            });
        }, 150);
    });
});

function loadCloneSourceData() {
    $.get(window.cloneRoute.data)
        .done(function (res) {
            cloneSourceData = res;
            renderClonePage(res);
        })
        .fail(function (xhr) {
            $('#cloneLoading').addClass('d-none');
            $('#cloneLoadError').removeClass('d-none').text(xhr.responseJSON?.message || 'Gagal memuat data Sales Quotation untuk di-clone.');
        });
}

function renderClonePage(res) {
    if (WO_ONLY) {
        const w = (res.wos || [])[0];
        $('#cloneSourceLabel').text(w ? (w.no_sq_wo || '') + (w.judul_pekerjaan ? ' — ' + w.judul_pekerjaan : '') : '-');
    } else {
        $('#cloneSourceLabel').text(res.sq.no_sq + (res.sq.revisi > 0 ? ' Rev.' + res.sq.revisi : '') + (res.sq.judul_order ? ' — ' + res.sq.judul_order : ''));
        fillSqForm(res.sq);
    }
    renderWoAccordion(res.wos || []);

    $('#cloneLoading').addClass('d-none');
    $('#cloneSqForm').removeClass('d-none');

    // Mode Clone SQ WO cuma ada 1 card — langsung dibuka supaya field bisa diedit tanpa klik tambahan
    if (WO_ONLY) $('#woAccordion .wo-toggle').first().trigger('click');
}

// ── Form SQ ──────────────────────────────────────────────────────────────

function today() {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function fillSqForm(sq) {
    // Tanggal SQ = hari ini; Berlaku Sampai & Rencana Mulai dikosongkan (sudah basi untuk SQ baru, isi ulang)
    $('input[name="tanggal_sq"]').val(today());
    $('input[name="judul_order"]').val(sq.judul_order);
    $('textarea[name="cara_pembayaran"]').val(sq.cara_pembayaran);
    $('textarea[name="keterangan"]').val(sq.keterangan);

    initFpDate('#cloneSqForm');

    $('#sq_id_office').select2({
        width: '100%', allowClear: true, placeholder: 'Pilih Office', minimumInputLength: 0,
        ajax: {
            url: window.cloneRoute.select2Office, dataType: 'json', delay: 200,
            data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true,
        },
        language: {
            noResults: function () {
                return `<span>Tidak ditemukan. <a href="${window.cloneRoute.createOffice}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`;
            },
        },
        escapeMarkup: (m) => m,
    });
    if (sq.id_office) {
        $('#sq_id_office').append(new Option(sq.nama_office || ('#' + sq.id_office), sq.id_office, true, true)).trigger('change');
    }

    initCompanySelect('#sq_id_pelanggan', sq.id_pelanggan, sq.nama_pelanggan);
    initCompanySelect('#sq_id_pelanggan_delivery', sq.id_pelanggan_delivery, sq.nama_pelanggan_delivery);
    initCompanySelect('#sq_id_pelanggan_payment', sq.id_pelanggan_payment, sq.nama_pelanggan_payment);

    initSiteSelect('#sq_id_site_pelanggan', '#sq_id_pelanggan', sq.id_site_pelanggan, sq.nama_site_pelanggan);
    initSiteSelect('#sq_id_site_pelanggan_delivery', '#sq_id_pelanggan_delivery', sq.id_site_pelanggan_delivery, sq.nama_site_delivery);
    initSiteSelect('#sq_id_site_pelanggan_payment', '#sq_id_pelanggan_payment', sq.id_site_pelanggan_payment, sq.nama_site_payment);

    const companyIds = () => [$('#sq_id_pelanggan').val(), $('#sq_id_pelanggan_delivery').val(), $('#sq_id_pelanggan_payment').val()].filter(Boolean);
    // PIC yang muncul = gabungan PIC dari Site yang dipilih di ketiga kategori
    const siteIds = () => [$('#sq_id_site_pelanggan').val(), $('#sq_id_site_pelanggan_delivery').val(), $('#sq_id_site_pelanggan_payment').val()].filter(Boolean);

    initPicSelect('#sq_id_pic_pelanggan', companyIds, siteIds, sq.id_pic_pelanggan, sq.pic_pelanggan);
    initPicSelect('#sq_id_pic_pelanggan_delivery', companyIds, siteIds, sq.id_pic_pelanggan_delivery, sq.pic_delivery);
    initPicSelect('#sq_id_pic_pelanggan_payment', companyIds, siteIds, sq.id_pic_pelanggan_payment, sq.pic_payment);

    // Hanya Site & PIC Pemesan yang memicu auto-fill ke Pengiriman & Pembayaran (bukan Perusahaan)
    ['#sq_id_site_pelanggan', '#sq_id_pic_pelanggan'].forEach(function (sel) {
        $(sel).on('select2:select', syncPemesanToOthers);
    });

    // PIC Input = user yang sedang login (bukan dari SQ sumber), tetap bisa diubah
    initUserSelect('#sq_pic_input', window.cloneRoute.authUser.id, window.cloneRoute.authUser.name);
    initUserSelect('#sq_pic_order', sq.pic_order, sq.nama_pic_order);
    initUserSelect('#sq_pic_marketing_internal', sq.pic_marketing_internal, sq.nama_marketing_internal);
    initUserSelect('#sq_pic_marketing_eksternal', sq.pic_marketing_eksternal, sq.nama_marketing_eksternal);
}

// ── Helper Select2 ───────────────────────────────────────────────────────

function initCompanySelect(sel, initialId, initialText) {
    const $el = $(sel);
    $el.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih Perusahaan', minimumInputLength: 0,
        ajax: { url: window.cloneRoute.select2Br, dataType: 'json', delay: 250, data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true },
        escapeMarkup: (m) => m,
    });
    if (initialId) $el.append(new Option(initialText || ('#' + initialId), initialId, true, true)).trigger('change');
}

function initSiteSelect(sel, companySel, initialId, initialText) {
    const $el = $(sel);
    $el.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih Site', minimumInputLength: 0,
        ajax: {
            url: window.cloneRoute.select2Site, dataType: 'json', delay: 250,
            // Belum pilih Perusahaan → tidak ada Site yang tampil
            data: (p) => ({ q: p.term, id_br: $(companySel).val() || '-1' }),
            processResults: (d) => ({ results: d }), cache: false,
        },
        escapeMarkup: (m) => m,
    });
    if (initialId) $el.append(new Option(initialText || ('#' + initialId), initialId, true, true)).trigger('change');
    $(companySel).on('select2:select select2:clear', function () { $el.val(null).trigger('change'); });
}

function initPicSelect(sel, companyIdsFn, siteIdsFn, initialId, initialText) {
    const $el = $(sel);
    $el.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih PIC', minimumInputLength: 0,
        ajax: {
            url: window.cloneRoute.select2Contact, dataType: 'json', delay: 250,
            data: (p) => ({ q: p.term, id_br: companyIdsFn(), id_sites: siteIdsFn(), with_site: 1 }),
            processResults: (d) => ({ results: d }), cache: false,
        },
        escapeMarkup: (m) => m,
    });
    if (initialId) $el.append(new Option(initialText || ('#' + initialId), initialId, true, true)).trigger('change');
}

function initUserSelect(sel, initialId, initialText, dropdownParent) {
    const $el = $(sel);
    const opts = {
        width: '100%', allowClear: true, placeholder: 'Pilih User', minimumInputLength: 0,
        ajax: { url: window.cloneRoute.select2User, dataType: 'json', delay: 200, data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true },
        escapeMarkup: (m) => m,
    };
    if (dropdownParent) opts.dropdownParent = dropdownParent;
    $el.select2(opts);
    if (initialId) $el.append(new Option(initialText || ('#' + initialId), initialId, true, true)).trigger('change');
}

function select2Data(selector) {
    const $el = $(selector);
    if (!$el.length || !$el.val()) return null;
    const data = $el.select2('data');
    return data && data.length ? { id: $el.val(), text: data[0].text } : null;
}

// Pemesan menimpa Pengiriman & Pembayaran (Perusahaan, Site, PIC). PIC kosong di Pemesan mengosongkan PIC tujuan.
function syncPemesanToOthers() {
    const company = select2Data('#sq_id_pelanggan');
    const site = select2Data('#sq_id_site_pelanggan');
    if (!company || !site) return;
    const pic = select2Data('#sq_id_pic_pelanggan');

    [
        { company: '#sq_id_pelanggan_delivery', site: '#sq_id_site_pelanggan_delivery', pic: '#sq_id_pic_pelanggan_delivery' },
        { company: '#sq_id_pelanggan_payment', site: '#sq_id_site_pelanggan_payment', pic: '#sq_id_pic_pelanggan_payment' },
    ].forEach(function (t) {
        const $c = $(t.company);
        if (!$c.find('option[value="' + company.id + '"]').length) $c.append(new Option(company.text, company.id));
        $c.val(company.id).trigger('change');
        $(t.site).empty().append(new Option(site.text, site.id, true, true)).trigger('change');
        const $p = $(t.pic);
        if (pic) $p.append(new Option(pic.text, pic.id, true, true)).trigger('change');
        else $p.val(null).trigger('change');
    });
}

function initSatuanSelectForRow($row, $card, sourceRow) {
    const $satuan = $row.find('.row-satuan');
    $satuan.select2({
        width: '100%', allowClear: true, placeholder: 'Satuan', minimumInputLength: 0, dropdownParent: $card,
        ajax: { url: window.cloneRoute.select2Satuan, dataType: 'json', delay: 200, data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true },
        escapeMarkup: (m) => m,
    });
    if (sourceRow && sourceRow.id_satuan) {
        $satuan.append(new Option(sourceRow.satuan || ('#' + sourceRow.id_satuan), sourceRow.id_satuan, true, true)).trigger('change');
    }
}

function bindRowIncludeToggle($row) {
    $row.find('.row-include').on('change', function () {
        const on = $(this).is(':checked');
        $row.find('input, select').not('.row-include').prop('disabled', !on);
        $row.toggleClass('opacity-50', !on);
    });
}

function formatRupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

// ── Accordion WO ─────────────────────────────────────────────────────────

function renderWoAccordion(wos) {
    const sum = (key) => wos.reduce((s, w) => s + (w[key]?.length || 0), 0);
    $('#woSummary').text(`${wos.length} WO tersedia — ${sum('boq')} BOQ, ${sum('boq_other')} BOQ Other, ${sum('boq_sampling')} BOQ Sampling, ${sum('fwos')} FWO, ${sum('budgets')} Budget Plan`);

    const $accordion = $('#woAccordion').empty();
    if (!wos.length) {
        $accordion.html('<p class="text-muted fst-italic mb-0">Tidak ada Work Order pada Sales Quotation ini.</p>');
        return;
    }

    wos.forEach((wo, woPos) => {
        const idx = woIndexCounter++;
        const collapseId = 'woCollapse' + idx;
        const searchText = [wo.no_sq_wo, wo.judul_pekerjaan, wo.nama_site].filter(Boolean).join(' ').toLowerCase();

        // Buka/tutup ditangani manual (toggle d-none) — class accordion Bootstrap hanya dipakai untuk tampilan
        const $card = $(`
            <div class="accordion-item wo-card" data-wo-index="${idx}" data-source-id-wo="${wo.id_sq_wo}" data-search="${escHtml(searchText)}">
                <h2 class="accordion-header d-flex align-items-center">
                    <div class="form-check ms-3 me-1" onclick="event.stopPropagation()">
                        <input type="checkbox" class="form-check-input wo-include" checked title="Sertakan WO ini">
                    </div>
                    <span class="me-2" style="color:#94a3b8;font-size:12px;font-weight:600;min-width:20px;text-align:center;flex-shrink:0;">${woPos + 1}</span>
                    <button class="accordion-button collapsed wo-toggle" type="button">
                        <strong class="me-2">${escHtml(wo.no_sq_wo || '-')}</strong> ${escHtml(wo.judul_pekerjaan || '-')}
                        <span class="pm-badge pm-badge--blue ms-2">${(wo.boq || []).length} BOQ</span>
                        ${(wo.boq_other || []).length ? `<span class="pm-badge ms-1">${wo.boq_other.length} Other</span>` : ''}
                        ${(wo.boq_sampling || []).length ? `<span class="pm-badge ms-1">${wo.boq_sampling.length} Sampling</span>` : ''}
                        ${(wo.fwos || []).length ? `<span class="pm-badge ms-1">${wo.fwos.length} FWO</span>` : ''}
                    </button>
                </h2>
                <div id="${collapseId}" class="accordion-collapse d-none">
                    <div class="accordion-body"></div>
                </div>
            </div>
        `);

        $card.find('.wo-toggle').on('click', function () {
            const $btn = $(this);
            const $collapse = $card.find(`#${collapseId}`);
            const opening = $collapse.hasClass('d-none');

            // Exclusive — cuma 1 WO terbuka sekaligus supaya halaman tidak menumpuk
            if (opening) {
                $accordion.find('.accordion-collapse').not($collapse).addClass('d-none');
                $accordion.find('.wo-toggle').not($btn).addClass('collapsed');
                $accordion.find('.wo-card').not($card).removeClass('wo-active');
            }

            $collapse.toggleClass('d-none', !opening);
            $btn.toggleClass('collapsed', !opening);
            $card.toggleClass('wo-active', opening);

            // Build & init HARUS setelah d-none dilepas (select2/pengukuran lebar butuh elemen visible)
            if (opening && !$collapse.data('loaded')) {
                $collapse.find('.accordion-body').html(buildWoBodyHtml(wo));
                initWoBodyPlugins($card, wo);
                $collapse.data('loaded', true);
            }

            if (opening) {
                $card.css('--wo-header-h', $card.find('> .accordion-header').outerHeight() + 'px');
                $card[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        $card.find('.wo-include').on('change', function () {
            const on = $(this).is(':checked');
            $card.find('.accordion-body').find('input, select, textarea, button').not('.wo-include').prop('disabled', !on);
            $card.toggleClass('opacity-50', !on);
        });

        $accordion.append($card);
    });
}

function buildWoBodyHtml(wo) {
    const intervalOptions = [['', '— Tidak ada —']].concat(Object.entries(INTERVAL_LABELS))
        .map(([val, label]) => `<option value="${val}" ${String(wo.interval_bulan ?? '') === String(val) ? 'selected' : ''}>${label}</option>`)
        .join('');

    return `
        <div class="row g-3 mb-3">
            <div class="col-md-12 col-12">
                <label class="form-label required">Judul Pekerjaan</label>
                <input type="text" class="form-control wo-judul" value="${escHtml(wo.judul_pekerjaan || '')}">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label required">Hari Mulai (ke-)</label>
                <input type="number" min="1" class="form-control wo-hari-mulai" value="${wo.hari_mulai ?? 1}">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label">Durasi (hari)</label>
                <input type="number" min="1" class="form-control wo-durasi" value="${wo.durasi_hari ?? ''}">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label">Frekuensi</label>
                <select class="form-select wo-interval">${intervalOptions}</select>
            </div>
            <div class="col-md-3 col-6 wo-urutan-wrap" style="${wo.interval_bulan ? '' : 'display:none;'}">
                <label class="form-label">Urutan ke-</label>
                <input type="number" min="1" class="form-control wo-urutan" value="${wo.no_urut_period ?? ''}">
            </div>
            <div class="col-md-6 col-12">
                <label class="form-label">Site Pekerjaan</label>
                <select class="form-select wo-site"></select>
            </div>
            <div class="col-md-6 col-12">
                <label class="form-label">PIC Pekerjaan</label>
                <select class="form-select wo-pic"></select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Keterangan</label>
                <textarea class="form-control wo-keterangan" rows="2">${escHtml(wo.keterangan || '')}</textarea>
            </div>
        </div>

        <ul class="nav nav-tabs wo-subtabs mb-3">
            <li class="nav-item"><button type="button" class="nav-link active wo-subtab-btn" data-target="boq"><i class="fa-solid fa-table-list me-1"></i> BOQ</button></li>
            <li class="nav-item"><button type="button" class="nav-link wo-subtab-btn" data-target="fwo"><i class="fa-solid fa-helmet-safety me-1"></i> Fieldwork Order (FWO)</button></li>
            <li class="nav-item"><button type="button" class="nav-link wo-subtab-btn" data-target="budget"><i class="fa-solid fa-wallet me-1"></i> Budget</button></li>
        </ul>

        <div class="wo-subtab-pane" data-pane="boq">
            <h6 class="mb-2"><i class="fa-solid fa-table-list me-1 text-primary"></i> BOQ</h6>
            ${buildBoqTableHtml(wo.boq || [])}

            <h6 class="mb-2 mt-3"><i class="fa-solid fa-layer-group me-1 text-warning"></i> BOQ Other</h6>
            ${buildBoqTambahanTableHtml(wo.boq_other || [], 'boq-other')}

            <h6 class="mb-2 mt-3"><i class="fa-solid fa-vial me-1 text-success"></i> BOQ Sampling</h6>
            ${buildBoqTambahanTableHtml(wo.boq_sampling || [], 'boq-sampling')}
        </div>

        <div class="wo-subtab-pane d-none" data-pane="fwo">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="pm-search">
                    <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="fwo-search" placeholder="Cari No. FWO / Judul...">
                    <button type="button" class="pm-search-clear d-none fwo-search-clear" title="Hapus"><i class="fa-solid fa-times"></i></button>
                </div>
            </div>
            <div class="mb-2 text-muted small fwo-summary"></div>
            <div class="accordion fwo-list"></div>
        </div>

        <div class="wo-subtab-pane d-none" data-pane="budget">
            <div class="wo-budget-list">${buildBudgetPlanListHtml(wo.budgets || [], 'wo-budget')}</div>
        </div>
    `;
}

function initWoBodyPlugins($card, wo) {
    const $body = $card.find('.accordion-body');

    initNumericMask($body[0]);

    // Site bebas dipilih (clone SQ biasanya untuk pelanggan lain); Perusahaan WO diturunkan dari Site di server
    const $site = $body.find('.wo-site');
    $site.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih Site', minimumInputLength: 0, dropdownParent: $card,
        ajax: { url: window.cloneRoute.select2Site, dataType: 'json', delay: 250, data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: false },
        escapeMarkup: (m) => m,
    });
    if (wo.id_site_pelanggan_pekerjaan) {
        $site.append(new Option(wo.nama_site || ('#' + wo.id_site_pelanggan_pekerjaan), wo.id_site_pelanggan_pekerjaan, true, true)).trigger('change');
    }

    // PIC Pekerjaan WO = user Pramatek (sama seperti SQ WO asli)
    initUserSelect($body.find('.wo-pic'), wo.id_pic_pelanggan_pekerjaan, wo.nama_pic, $card);

    $body.find('.wo-interval').on('change', function () {
        $body.find('.wo-urutan-wrap').toggle(!!$(this).val());
    });

    $body.find('.boq-row, .boq-other-row, .boq-sampling-row').each(function () {
        const $row = $(this);
        initSatuanSelectForRow($row, $card, findSourceRow($row, wo));
        bindRowIncludeToggle($row);
    });
    $body.find('.wo-budget-row').each(function () { bindRowIncludeToggle($(this)); });
    bindBudgetCardToggle($body.find('.wo-budget-list'));

    // Uncheck BOQ WO → Fieldwork BOQ yang memakai Testing Point itu ikut tidak disertakan (kalau tidak, tampak
    // tercentang padahal dilewati backend)
    $body.on('change', '.boq-section .boq-row:not(.boq-new-row) .row-include', function () {
        const $row = $(this).closest('.boq-row');
        syncFieldworkBoqInclude($body, $row.data('id-testing-point'), $(this).is(':checked'));
    });

    // Tambah BOQ baru (Testing Point + checklist Testing Item)
    $body.on('click', '.btn-add-boq', function () {
        const $rows = $(newBoqRowHtml());
        $(this).closest('.boq-section').find('.boq-table tbody').append($rows);
        initNewBoqRow($rows.first(), $card, $body);
    });
    $body.on('click', '.btn-remove-new-boq', function () {
        const $itemsRow = $(this).closest('tr');
        $itemsRow.prev('.boq-new-row').remove();
        $itemsRow.remove();
    });

    $body.on('click', '.btn-add-boq-tambahan', function () {
        const rowClass = $(this).data('rowclass');
        const $row = $(newBoqTambahanRowHtml(rowClass));
        $(this).closest('.boq-section').find(`.${rowClass}-table tbody`).append($row);
        initNumericMask($row[0]);
        initSatuanSelectForRow($row, $card, null);
        bindRowIncludeToggle($row);
    });

    // Sub-tab BOQ / FWO / Budget — toggle d-none manual
    $body.on('click', '.wo-subtab-btn', function () {
        $card.find('.wo-subtab-btn').removeClass('active');
        $(this).addClass('active');
        $card.find('.wo-subtab-pane').addClass('d-none');
        $card.find(`.wo-subtab-pane[data-pane="${$(this).data('target')}"]`).removeClass('d-none');
    });

    renderFwoAccordion($body, wo);

    $body.on('input', '.fwo-search', function () {
        const q = $(this).val().toLowerCase().trim();
        $body.find('.fwo-card').each(function () {
            $(this).toggle(!q || ($(this).data('search') || '').toString().includes(q));
        });
        $(this).closest('.pm-search').find('.fwo-search-clear').toggleClass('d-none', !q);
    });
    $body.on('click', '.fwo-search-clear', function () {
        $(this).closest('.pm-search').find('.fwo-search').val('').trigger('input');
    });
}

function findSourceRow($row, wo) {
    if ($row.data('source-id-boq')) {
        return (wo.boq || []).find((r) => String(r.source_id_boq) === String($row.data('source-id-boq')));
    }
    if ($row.data('source-id-boq-tambahan')) {
        return (wo.boq_other || []).concat(wo.boq_sampling || []).find((r) => String(r.source_id_boq_tambahan) === String($row.data('source-id-boq-tambahan')));
    }
    return null;
}

// ── Tabel BOQ ────────────────────────────────────────────────────────────

function buildBoqTableHtml(items) {
    const rows = items.map((r) => {
        const name = r.point_name || r.item_produk_alternate || '-';
        return `
            <tr class="boq-row" data-source-id-boq="${r.source_id_boq}" data-id-testing-point="${r.id_testing_point ?? ''}">
                <td class="text-center"><input type="checkbox" class="form-check-input row-include" checked title="Sertakan baris ini"></td>
                <td style="max-width:260px;"><span class="fw-semibold text-truncate d-inline-block" style="max-width:100%;vertical-align:bottom;" title="${escHtml(name)}">${escHtml(name)}</span></td>
                <td style="width:90px;"><input type="number" class="form-control form-control-sm row-qty" value="${r.qty ?? ''}"></td>
                <td style="width:150px;"><select class="form-select form-select-sm row-satuan"></select></td>
                <td style="width:130px;"><input type="text" class="form-control form-control-sm input-num-mask input-num-int row-harga" value="${r.harga ?? 0}"></td>
                <td style="width:130px;"><input type="text" class="form-control form-control-sm input-num-mask input-num-int row-discount" value="${r.discount ?? 0}"></td>
                <td><input type="text" class="form-control form-control-sm row-keterangan" value="${escHtml(r.keterangan || '')}"></td>
            </tr>`;
    }).join('');

    return `
        <div class="boq-section">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0 boq-table">
                    <thead class="table-light">
                        <tr><th style="width:36px;"></th><th>Testing Point</th><th>Qty</th><th>Satuan</th><th>Harga</th><th>Discount (Rp)</th><th>Keterangan</th></tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            ${!items.length ? '<p class="text-muted fst-italic small mt-1">Tidak ada BOQ.</p>' : ''}
            <button type="button" class="btn btn-outline-primary btn-sm mt-2 btn-add-boq"><i class="fa-solid fa-plus me-1"></i> Tambah BOQ</button>
        </div>`;
}

// SEPASANG <tr>: baris field utama + baris checklist Testing Item (dipasangkan/dihapus bersamaan)
function newBoqRowHtml() {
    return `
        <tr class="boq-row boq-new-row">
            <td class="text-center"><input type="checkbox" class="form-check-input row-include" checked title="Sertakan baris ini"></td>
            <td style="max-width:260px;"><select class="form-select form-select-sm new-boq-point"></select></td>
            <td style="width:90px;"><input type="number" class="form-control form-control-sm row-qty" value=""></td>
            <td style="width:150px;"><select class="form-select form-select-sm row-satuan"></select></td>
            <td style="width:130px;"><input type="text" class="form-control form-control-sm input-num-mask input-num-int row-harga" value="0"></td>
            <td style="width:130px;"><input type="text" class="form-control form-control-sm input-num-mask input-num-int row-discount" value="0"></td>
            <td><input type="text" class="form-control form-control-sm row-keterangan" value=""></td>
        </tr>
        <tr class="boq-new-row-items">
            <td></td>
            <td colspan="6">
                <div class="new-boq-items-toggle d-none mb-1" style="cursor:pointer;">
                    <i class="fa-solid fa-chevron-down me-1 toggle-icon" style="font-size:10px;transition:transform .15s;"></i>
                    <span class="fw-semibold small">Testing Item</span>
                    <span class="text-muted small ms-1">(<span class="checked-count">0</span> dipilih)</span>
                </div>
                <div class="new-boq-items-checklist text-muted small fst-italic">Pilih Testing Point dulu untuk memilih Testing Item-nya.</div>
                <button type="button" class="btn btn-outline-danger btn-sm mt-1 btn-remove-new-boq"><i class="fa-solid fa-trash me-1"></i> Hapus BOQ ini</button>
            </td>
        </tr>`;
}

// Testing Point yang sudah dipakai di WO ini (baris clone + baris baru lain) — 1 WO cuma boleh 1 BOQ per Testing Point
function usedTestingPointIds($body, excludeSelect) {
    const ids = new Set();
    $body.find('.boq-row:not(.boq-new-row)').each(function () {
        const tp = $(this).data('id-testing-point');
        if (tp) ids.add(String(tp));
    });
    $body.find('.new-boq-point').each(function () {
        if (excludeSelect && this === excludeSelect[0]) return;
        if ($(this).val()) ids.add(String($(this).val()));
    });
    return ids;
}

function initNewBoqRow($mainRow, $card, $body) {
    const $point = $mainRow.find('.new-boq-point');
    const $itemsRow = $mainRow.next('.boq-new-row-items');
    const $toggle = $itemsRow.find('.new-boq-items-toggle');
    const $checklist = $itemsRow.find('.new-boq-items-checklist');
    const $count = $toggle.find('.checked-count');
    const emptyHint = '<span class="text-muted fst-italic">Pilih Testing Point dulu untuk memilih Testing Item-nya.</span>';

    initNumericMask($mainRow[0]);
    initSatuanSelectForRow($mainRow, $card, null);
    bindRowIncludeToggle($mainRow);

    const updateCheckedCount = () => $count.text($checklist.find('.new-boq-item-check:checked').length);

    $toggle.on('click', function () {
        const collapsed = $checklist.hasClass('d-none');
        $checklist.toggleClass('d-none', !collapsed);
        $toggle.find('.toggle-icon').css('transform', collapsed ? 'rotate(0deg)' : 'rotate(-90deg)');
    });
    $itemsRow.on('change', '.new-boq-item-check', updateCheckedCount);

    $point.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih Testing Point', minimumInputLength: 1, dropdownParent: $card,
        ajax: { url: window.cloneRoute.select2TestingPoint, dataType: 'json', delay: 250, data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true },
        escapeMarkup: (m) => m,
    });

    $point.on('select2:select', function (e) {
        const pointId = e.params.data.id;

        if (usedTestingPointIds($body, $point).has(String(pointId))) {
            Notify.error('Testing Point ini sudah dipakai di BOQ lain pada WO yang sama.');
            $point.val(null).trigger('change');
            $toggle.addClass('d-none');
            $checklist.removeClass('d-none').html(emptyHint);
            return;
        }

        $toggle.addClass('d-none');
        $checklist.removeClass('d-none').html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat Testing Item...');
        $.get(window.cloneRoute.itemsByPoint + pointId, function (res) {
            const items = res.data || [];
            if (!items.length) {
                $checklist.html('<span class="text-muted fst-italic">Testing Point ini belum punya Testing Item.</span>');
                return;
            }
            $checklist.html(items.map((it) => `
                <div class="form-check">
                    <input type="checkbox" class="form-check-input new-boq-item-check" value="${it.id_testing_item}" id="ntpi_${pointId}_${it.id_testing_item}" checked>
                    <label class="form-check-label small" for="ntpi_${pointId}_${it.id_testing_item}">
                        ${escHtml(it.judul_indonesia || '-')} <span class="text-muted">/ ${escHtml(it.judul_inggris || '-')}</span>
                    </label>
                </div>`).join(''));
            $toggle.removeClass('d-none');
            $toggle.find('.toggle-icon').css('transform', 'rotate(0deg)');
            updateCheckedCount();
        }).fail(function () {
            $checklist.html('<span class="text-danger">Gagal memuat Testing Item.</span>');
        });
    });

    $point.on('select2:clear', function () {
        $toggle.addClass('d-none');
        $checklist.removeClass('d-none').html(emptyHint);
    });
}

function buildBoqTambahanTableHtml(items, rowClass) {
    const rows = items.map((r) => `
        <tr class="${rowClass}-row" data-source-id-boq-tambahan="${r.source_id_boq_tambahan}">
            <td class="text-center"><input type="checkbox" class="form-check-input row-include" checked title="Sertakan baris ini"></td>
            <td><input type="text" class="form-control form-control-sm row-nama" value="${escHtml(r.nama_item || '')}"></td>
            <td style="width:90px;"><input type="number" class="form-control form-control-sm row-qty" value="${r.qty ?? ''}"></td>
            <td style="width:150px;"><select class="form-select form-select-sm row-satuan"></select></td>
            <td style="width:130px;"><input type="text" class="form-control form-control-sm input-num-mask input-num-int row-harga" value="${r.harga ?? 0}"></td>
            <td><input type="text" class="form-control form-control-sm row-keterangan" value="${escHtml(r.keterangan || '')}"></td>
        </tr>`).join('');

    return `
        <div class="boq-section">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0 ${rowClass}-table">
                    <thead class="table-light">
                        <tr><th style="width:36px;"></th><th>Nama Item</th><th>Qty</th><th>Satuan</th><th>Harga</th><th>Keterangan</th></tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            ${!items.length ? '<p class="text-muted fst-italic small mt-1">Tidak ada data.</p>' : ''}
            <button type="button" class="btn btn-outline-primary btn-sm mt-2 btn-add-boq-tambahan" data-rowclass="${rowClass}"><i class="fa-solid fa-plus me-1"></i> Tambah Item</button>
        </div>`;
}

function newBoqTambahanRowHtml(rowClass) {
    return `
        <tr class="${rowClass}-row">
            <td class="text-center"><input type="checkbox" class="form-check-input row-include" checked title="Sertakan baris ini"></td>
            <td><input type="text" class="form-control form-control-sm row-nama" placeholder="Nama item" value=""></td>
            <td style="width:90px;"><input type="number" class="form-control form-control-sm row-qty" value=""></td>
            <td style="width:150px;"><select class="form-select form-select-sm row-satuan"></select></td>
            <td style="width:130px;"><input type="text" class="form-control form-control-sm input-num-mask input-num-int row-harga" value="0"></td>
            <td><input type="text" class="form-control form-control-sm row-keterangan" value=""></td>
        </tr>`;
}

// ── Budget Plan (dipilih per Plan; Item disalin utuh dari DB di server, hanya tampil read-only di sini) ──

function buildBudgetPlanListHtml(budgets, rowClass) {
    if (!budgets.length) return '<p class="text-muted fst-italic mb-0">Tidak ada Budget Plan.</p>';
    return budgets.map((b) => buildBudgetPlanCardHtml(b, rowClass)).join('');
}

function buildBudgetPlanCardHtml(b, rowClass) {
    const items = b.items || [];
    const total = items.reduce((s, it) => s + Number(it.nominal_budget || 0), 0);
    const itemRows = items.map((it) => `
        <tr>
            <td>${escHtml(it.nama_account || '-')} ${Number(it.is_cash_advance) ? '<span class="pm-badge pm-badge--blue ms-1" style="font-size:9px;">CA</span>' : ''}</td>
            <td class="text-end" style="white-space:nowrap;">${formatRupiah(it.nominal_budget)}</td>
        </tr>`).join('');

    return `
        <div class="boq-section budget-card ${rowClass}-row" data-source-id-budget="${b.source_id_budget}"
            style="background:#fff;border:1px solid #d6dce5;border-left:4px solid #0f766e;border-radius:10px;padding:14px 16px;margin-bottom:16px;box-shadow:0 1px 3px rgba(15,23,42,.06);">
            <div class="d-flex align-items-start gap-2">
                <input type="checkbox" class="form-check-input row-include mt-1" checked title="Sertakan Plan ini">
                <div class="flex-grow-1 row g-2 align-items-center">
                    <div class="col-md-6 col-9">
                        <label class="form-label small mb-1">Label Plan</label>
                        <input type="text" class="form-control form-control-sm row-label" value="${escHtml(b.label || '')}">
                    </div>
                    <div class="col-md-3 col-3 text-end">
                        <div class="small text-muted">Total Budget</div>
                        <div class="fw-semibold">${formatRupiah(total)}</div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary budget-card-toggle" title="Tampilkan/sembunyikan detail"><i class="fa-solid fa-chevron-up"></i></button>
            </div>
            <div class="budget-card-body mt-2">
                <div class="row g-2">
                    <div class="col-md-3 col-6">
                        <label class="form-label small mb-1">Hari Mulai (ke-)</label>
                        <input type="number" min="1" class="form-control form-control-sm row-hari-mulai" value="${b.hari_mulai ?? ''}">
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label small mb-1">Hari Selesai (ke-)</label>
                        <input type="number" min="1" class="form-control form-control-sm row-hari-selesai" value="${b.hari_selesai ?? ''}">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label small mb-1">Keterangan</label>
                        <textarea class="form-control form-control-sm row-keterangan" rows="1">${escHtml(b.keterangan || '')}</textarea>
                    </div>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm table-bordered align-middle mb-1">
                        <thead class="table-light"><tr><th>Account</th><th class="text-end" style="width:130px;">Budget</th></tr></thead>
                        <tbody>${itemRows || '<tr><td colspan="2" class="text-muted fst-italic small">Tidak ada item.</td></tr>'}</tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end small text-muted"><span>Total Budget: <b>${formatRupiah(total)}</b></span></div>
            </div>
        </div>`;
}

function bindBudgetCardToggle($body) {
    $body.off('click.budgetCardToggle', '.budget-card-toggle').on('click.budgetCardToggle', '.budget-card-toggle', function () {
        $(this).closest('.budget-card').find('.budget-card-body').slideToggle(150);
        $(this).find('i').toggleClass('fa-chevron-up fa-chevron-down');
    });
}

// ── Accordion FWO (nested di body WO) ────────────────────────────────────

function renderFwoAccordion($woBody, wo) {
    const $list = $woBody.find('.fwo-list');
    const fwos = wo.fwos || [];

    $woBody.find('.fwo-summary').text(
        fwos.length ? `${fwos.length} FWO — ${fwos.reduce((s, f) => s + (f.fieldwork_boq?.length || 0), 0)} Fieldwork BOQ, ${fwos.reduce((s, f) => s + (f.budgets?.length || 0), 0)} Budget Plan` : ''
    );

    if (!fwos.length) {
        $list.html('<p class="text-muted fst-italic mb-0">Tidak ada FWO pada WO ini.</p>');
        return;
    }

    fwos.forEach((fwo, fwoPos) => {
        const idx = fwoIndexCounter++;
        const collapseId = 'fwoCollapse' + idx;
        const searchText = [fwo.no_sq_fwo, fwo.judul_pekerjaan].filter(Boolean).join(' ').toLowerCase();

        const $card = $(`
            <div class="accordion-item fwo-card" data-fwo-index="${idx}" data-source-id-fwo="${fwo.id_sq_fwo}" data-search="${escHtml(searchText)}">
                <h2 class="accordion-header d-flex align-items-center">
                    <div class="form-check ms-3 me-1" onclick="event.stopPropagation()">
                        <input type="checkbox" class="form-check-input fwo-include" checked title="Sertakan FWO ini">
                    </div>
                    <span class="me-2" style="color:#94a3b8;font-size:12px;font-weight:600;min-width:20px;text-align:center;flex-shrink:0;">${fwoPos + 1}</span>
                    <button class="accordion-button collapsed fwo-toggle" type="button">
                        <strong class="me-2">${escHtml(fwo.no_sq_fwo || '-')}</strong> ${escHtml(fwo.judul_pekerjaan || '-')}
                        <span class="pm-badge pm-badge--blue ms-2">${(fwo.fieldwork_boq || []).length} Fieldwork BOQ</span>
                        ${(fwo.budgets || []).length ? `<span class="pm-badge ms-1">${fwo.budgets.length} Budget</span>` : ''}
                    </button>
                </h2>
                <div id="${collapseId}" class="accordion-collapse d-none">
                    <div class="accordion-body"></div>
                </div>
            </div>
        `);

        $card.find('.fwo-toggle').on('click', function () {
            const $btn = $(this);
            const $collapse = $card.find(`#${collapseId}`);
            const opening = $collapse.hasClass('d-none');

            if (opening) {
                $list.find('.accordion-collapse').not($collapse).addClass('d-none');
                $list.find('.fwo-toggle').not($btn).addClass('collapsed');
                $list.find('.fwo-card').not($card).removeClass('fwo-active');
            }

            $collapse.toggleClass('d-none', !opening);
            $btn.toggleClass('collapsed', !opening);
            $card.toggleClass('fwo-active', opening);

            if (opening && !$collapse.data('loaded')) {
                $collapse.find('.accordion-body').html(buildFwoBodyHtml(fwo));
                initFwoBodyPlugins($card, fwo, $woBody);
                $collapse.data('loaded', true);
            }
            if (opening) $card[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        $card.find('.fwo-include').on('change', function () {
            const on = $(this).is(':checked');
            $card.find('.accordion-body').find('input, select, textarea, button').not('.fwo-include').prop('disabled', !on);
            $card.toggleClass('opacity-50', !on);
        });

        $list.append($card);
    });
}

function buildFwoBodyHtml(fwo) {
    const rows = (fwo.fieldwork_boq || []).map((r) => `
        <tr class="fieldwork-boq-row" data-source-id-fwo-boq="${r.source_id_fwo_boq}" data-id-testing-point="${r.id_testing_point ?? ''}">
            <td class="text-center"><input type="checkbox" class="form-check-input row-include" checked title="Sertakan baris ini"></td>
            <td style="max-width:300px;"><span class="fw-semibold text-truncate d-inline-block" style="max-width:100%;vertical-align:bottom;" title="${escHtml(r.point_name || '')}">${escHtml(r.point_name || '-')}</span></td>
            <td style="width:100px;"><input type="number" class="form-control form-control-sm row-qty" value="${r.qty ?? ''}"></td>
            <td><input type="text" class="form-control form-control-sm row-keterangan" value="${escHtml(r.keterangan || '')}"></td>
        </tr>`).join('');

    return `
        <div class="row g-3 mb-3">
            <div class="col-md-12 col-12">
                <label class="form-label required">Judul Pekerjaan</label>
                <input type="text" class="form-control fwo-judul" value="${escHtml(fwo.judul_pekerjaan || '')}">
            </div>
            <div class="col-md-6 col-12">
                <label class="form-label">Site Pekerjaan</label>
                <select class="form-select fwo-site"></select>
            </div>
            <div class="col-md-6 col-12">
                <label class="form-label">PIC Pekerjaan <span class="text-muted">(kontak pelanggan)</span></label>
                <select class="form-select fwo-pic"></select>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label">Hari ke-</label>
                <input type="number" min="1" class="form-control fwo-hari-ke" value="${fwo.hari_ke ?? 1}">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label">Durasi (hari)</label>
                <input type="number" min="1" class="form-control fwo-durasi" value="${fwo.durasi_hari ?? 1}">
            </div>
            <div class="col-md-12">
                <label class="form-label">Keterangan</label>
                <textarea class="form-control fwo-keterangan" rows="2">${escHtml(fwo.keterangan || '')}</textarea>
            </div>
        </div>

        <div class="boq-section mb-3">
            <h6 class="mb-2"><i class="fa-solid fa-table-list me-1 text-primary"></i> Fieldwork BOQ</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0 fieldwork-boq-table">
                    <thead class="table-light"><tr><th style="width:36px;"></th><th>Testing Point</th><th>Qty</th><th>Keterangan</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            ${!(fwo.fieldwork_boq || []).length ? '<p class="text-muted fst-italic small mt-1">Tidak ada Fieldwork BOQ.</p>' : ''}
            <div class="form-text">Alokasi Fieldwork BOQ baru ditambahkan dari halaman SQ Fieldwork setelah disalin.</div>
        </div>

        <h6 class="mb-2"><i class="fa-solid fa-wallet me-1 text-success"></i> Budget</h6>
        <div class="fwo-budget-list">${buildBudgetPlanListHtml(fwo.budgets || [], 'fwo-budget')}</div>
    `;
}

function initFwoBodyPlugins($fwoCard, fwo, $woBody) {
    const $body = $fwoCard.find('.accordion-body');
    const dropdownParent = $fwoCard;

    // PIC FWO = kontak pelanggan di level Perusahaan Site yang dipilih (pola SQ FWO asli, filter by id_br)
    let currentBr = fwo.id_br_site || '';

    const $site = $body.find('.fwo-site');
    $site.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih Site', minimumInputLength: 0, dropdownParent,
        ajax: { url: window.cloneRoute.select2Site, dataType: 'json', delay: 250, data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: false },
        escapeMarkup: (m) => m,
    });
    if (fwo.id_site_pelanggan_pekerjaan) {
        $site.append(new Option(fwo.nama_site || ('#' + fwo.id_site_pelanggan_pekerjaan), fwo.id_site_pelanggan_pekerjaan, true, true)).trigger('change');
    }

    const $pic = $body.find('.fwo-pic');
    $pic.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih PIC', minimumInputLength: 0, dropdownParent,
        ajax: {
            url: window.cloneRoute.select2Contact, dataType: 'json', delay: 250,
            data: (p) => ({ q: p.term, id_br: currentBr }), processResults: (d) => ({ results: d }), cache: false,
        },
        escapeMarkup: (m) => m,
    });
    if (fwo.id_pic_pelanggan_pekerjaan) {
        $pic.append(new Option(fwo.nama_pic || ('#' + fwo.id_pic_pelanggan_pekerjaan), fwo.id_pic_pelanggan_pekerjaan, true, true)).trigger('change');
    }

    // Ganti Site → Perusahaan acuan PIC ikut berubah, PIC lama dikosongkan
    $site.on('select2:select', function (e) {
        currentBr = e.params.data.id_br || '';
        $pic.val(null).trigger('change');
    });
    $site.on('select2:clear', function () {
        currentBr = '';
        $pic.val(null).trigger('change');
    });

    $body.find('.fieldwork-boq-row').each(function () { bindRowIncludeToggle($(this)); });
    $body.find('.fwo-budget-row').each(function () { bindRowIncludeToggle($(this)); });
    bindBudgetCardToggle($body.find('.fwo-budget-list'));

    // BOQ WO yang sudah di-uncheck sebelum FWO ini dibuka → Fieldwork BOQ terkait langsung ikut tidak disertakan
    $woBody.find('.boq-row:not(.boq-new-row)').each(function () {
        if (!$(this).find('.row-include').is(':checked')) syncFieldworkBoqInclude($woBody, $(this).data('id-testing-point'), false);
    });
}

// Samakan status "Sertakan" Fieldwork BOQ (semua FWO yang sudah dibuka di WO ini) dengan BOQ WO ber-Testing Point sama
function syncFieldworkBoqInclude($woBody, testingPointId, included) {
    if (!testingPointId) return;
    $woBody.find('.fieldwork-boq-row').each(function () {
        const $row = $(this);
        if (String($row.data('id-testing-point')) !== String(testingPointId)) return;
        $row.find('.row-include').prop('checked', included).prop('disabled', !included);
        $row.find('input, select').not('.row-include').prop('disabled', !included);
        $row.toggleClass('opacity-50', !included);
    });
}

// ── Payload ──────────────────────────────────────────────────────────────

function collectBudgetsPayload($body, rowClass) {
    return $body.find(`.${rowClass}-row`).map(function () {
        const $row = $(this);
        return {
            source_id_budget: $row.data('source-id-budget') || null,
            include: $row.find('.row-include').is(':checked'),
            label: $row.find('.row-label').val(),
            keterangan: $row.find('.row-keterangan').val() || null,
            hari_mulai: $row.find('.row-hari-mulai').val() || null,
            hari_selesai: $row.find('.row-hari-selesai').val() || null,
        };
    }).get();
}

function collectWoPayload($card, wo) {
    const $body = $card.find('.accordion-body');

    const collectBoq = () => $body.find('.boq-row').map(function () {
        const $row = $(this);
        const base = {
            source_id_boq: $row.data('source-id-boq') || null,
            include: $row.find('.row-include').is(':checked'),
            qty: rawNumVal($row.find('.row-qty')[0]) ?? null,
            id_satuan: $row.find('.row-satuan').val() || null,
            harga: rawNumVal($row.find('.row-harga')[0]) ?? 0,
            discount: rawNumVal($row.find('.row-discount')[0]) ?? 0,
            keterangan: $row.find('.row-keterangan').val() || null,
        };
        // Baris BOQ baru: butuh Testing Point + Testing Item yang dicentang di checklist pasangannya
        if ($row.hasClass('boq-new-row')) {
            base.id_testing_point = $row.find('.new-boq-point').val() || null;
            base.id_testing_items = $row.next('.boq-new-row-items').find('.new-boq-item-check:checked').map(function () { return $(this).val(); }).get();
        }
        return base;
    }).get();

    const collectTambahan = (rowClass) => $body.find(`.${rowClass}-row`).map(function () {
        const $row = $(this);
        return {
            source_id_boq_tambahan: $row.data('source-id-boq-tambahan') || null,
            include: $row.find('.row-include').is(':checked'),
            nama_item: $row.find('.row-nama').val(),
            qty: rawNumVal($row.find('.row-qty')[0]) ?? null,
            id_satuan: $row.find('.row-satuan').val() || null,
            harga: rawNumVal($row.find('.row-harga')[0]) ?? 0,
            keterangan: $row.find('.row-keterangan').val() || null,
        };
    }).get();

    return {
        source_id_wo: wo.id_sq_wo,
        include: $card.find('.wo-include').is(':checked'),
        judul_pekerjaan: $body.find('.wo-judul').val() || wo.judul_pekerjaan,
        id_site_pelanggan_pekerjaan: $body.find('.wo-site').val() || null,
        id_pic_pelanggan_pekerjaan: $body.find('.wo-pic').val() || null,
        hari_mulai: $body.find('.wo-hari-mulai').val() || null,
        durasi_hari: $body.find('.wo-durasi').val() || null,
        interval_bulan: $body.find('.wo-interval').val() || null,
        no_urut_period: $body.find('.wo-urutan').val() || null,
        keterangan: $body.find('.wo-keterangan').val() || null,
        boq: collectBoq(),
        boq_other: collectTambahan('boq-other'),
        boq_sampling: collectTambahan('boq-sampling'),
        budgets: collectBudgetsPayload($body, 'wo-budget'),
        fwos: collectFwosPayload($body, wo),
    };
}

// Card yang tidak pernah dibuka tidak punya body di DOM — kirim apa adanya dari data sumber supaya tidak hilang diam-diam
function fallbackBudgets(budgets) {
    return (budgets || []).map((b) => ({ source_id_budget: b.source_id_budget, include: true, label: b.label, keterangan: b.keterangan, hari_mulai: b.hari_mulai, hari_selesai: b.hari_selesai }));
}

function fallbackFwoPayload(fwo, include) {
    return {
        source_id_fwo: fwo.id_sq_fwo,
        include: include !== false,
        judul_pekerjaan: fwo.judul_pekerjaan,
        id_site_pelanggan_pekerjaan: fwo.id_site_pelanggan_pekerjaan,
        id_pic_pelanggan_pekerjaan: fwo.id_pic_pelanggan_pekerjaan,
        hari_ke: fwo.hari_ke,
        durasi_hari: fwo.durasi_hari,
        keterangan: fwo.keterangan,
        fieldwork_boq: (fwo.fieldwork_boq || []).map((r) => ({ source_id_fwo_boq: r.source_id_fwo_boq, include: true, qty: r.qty, keterangan: r.keterangan })),
        budgets: fallbackBudgets(fwo.budgets),
    };
}

function collectFwosPayload($woBody, wo) {
    const result = [];
    $woBody.find('.fwo-card').each(function () {
        const $fwoCard = $(this);
        const fwo = (wo.fwos || []).find((f) => f.id_sq_fwo === Number($fwoCard.data('source-id-fwo')));
        if (!fwo) return;

        if (!$fwoCard.find('.accordion-body').children().length) {
            result.push(fallbackFwoPayload(fwo, $fwoCard.find('.fwo-include').is(':checked')));
            return;
        }

        const $body = $fwoCard.find('.accordion-body');
        result.push({
            source_id_fwo: fwo.id_sq_fwo,
            include: $fwoCard.find('.fwo-include').is(':checked'),
            judul_pekerjaan: $body.find('.fwo-judul').val() || fwo.judul_pekerjaan,
            id_site_pelanggan_pekerjaan: $body.find('.fwo-site').val() || null,
            id_pic_pelanggan_pekerjaan: $body.find('.fwo-pic').val() || null,
            hari_ke: $body.find('.fwo-hari-ke').val() || null,
            durasi_hari: $body.find('.fwo-durasi').val() || null,
            keterangan: $body.find('.fwo-keterangan').val() || null,
            fieldwork_boq: $body.find('.fieldwork-boq-row').map(function () {
                const $row = $(this);
                return {
                    source_id_fwo_boq: $row.data('source-id-fwo-boq') || null,
                    include: $row.find('.row-include').is(':checked'),
                    qty: rawNumVal($row.find('.row-qty')[0]) ?? null,
                    keterangan: $row.find('.row-keterangan').val() || null,
                };
            }).get(),
            budgets: collectBudgetsPayload($body, 'fwo-budget'),
        });
    });
    return result;
}

// Validasi di frontend supaya pesannya jelas dan muncul sebelum request; backend tetap memvalidasi ulang semuanya.
function validateWosPayload(wosPayload) {
    const errors = [];
    const budgetErrors = (list, prefix) => (list || []).forEach((b) => {
        if (!b.include) return;
        if (!b.label || !String(b.label).trim()) errors.push(`${prefix}: isi Label untuk salah satu Budget Plan.`);
        if (b.hari_mulai && b.hari_selesai && Number(b.hari_selesai) < Number(b.hari_mulai)) {
            errors.push(`${prefix}: Hari Selesai Budget Plan tidak boleh sebelum Hari Mulai.`);
        }
    });

    wosPayload.forEach((wo) => {
        if (!wo.include) return;

        const source = (cloneSourceData.wos || []).find((w) => w.id_sq_wo === wo.source_id_wo);
        const woLabel = source ? (source.no_sq_wo || `WO #${wo.source_id_wo}`) : `WO #${wo.source_id_wo}`;

        if (!wo.judul_pekerjaan || !String(wo.judul_pekerjaan).trim()) errors.push(`${woLabel}: Judul Pekerjaan wajib diisi.`);
        if (!wo.hari_mulai || Number(wo.hari_mulai) < 1) errors.push(`${woLabel}: Hari Mulai wajib diisi (minimal 1).`);

        budgetErrors(wo.budgets, woLabel);

        (wo.boq || []).forEach((r) => {
            if (!r.include) return;
            if (!r.qty || r.qty < 1) errors.push(`${woLabel}: isi Qty (minimal 1) untuk salah satu baris BOQ.`);
            if (!r.source_id_boq) {
                if (!r.id_testing_point) errors.push(`${woLabel}: ada baris BOQ baru yang belum memilih Testing Point.`);
                else if (!r.id_testing_items || !r.id_testing_items.length) errors.push(`${woLabel}: pilih minimal 1 Testing Item untuk BOQ baru yang ditambahkan.`);
            }
        });

        [['boq_other', 'BOQ Other'], ['boq_sampling', 'BOQ Sampling']].forEach(([key, label]) => {
            (wo[key] || []).forEach((r) => {
                if (!r.include) return;
                if (!r.nama_item || !String(r.nama_item).trim()) errors.push(`${woLabel}: isi Nama Item untuk salah satu baris ${label}.`);
                if (!r.qty || r.qty < 1) errors.push(`${woLabel}: isi Qty (minimal 1) untuk salah satu baris ${label}.`);
            });
        });

        (wo.fwos || []).forEach((fwo) => {
            if (!fwo.include) return;
            const fs = source ? (source.fwos || []).find((f) => f.id_sq_fwo === fwo.source_id_fwo) : null;
            const fLabel = `${woLabel} — ${fs ? (fs.no_sq_fwo || `FWO #${fwo.source_id_fwo}`) : `FWO #${fwo.source_id_fwo}`}`;

            if (!fwo.judul_pekerjaan || !String(fwo.judul_pekerjaan).trim()) errors.push(`${fLabel}: Judul Pekerjaan wajib diisi.`);
            budgetErrors(fwo.budgets, fLabel);
            (fwo.fieldwork_boq || []).forEach((r) => {
                if (r.include && (!r.qty || r.qty < 1)) errors.push(`${fLabel}: isi Qty (minimal 1) untuk salah satu baris Fieldwork BOQ.`);
            });
        });
    });

    return [...new Set(errors)];
}

function submitClone() {
    if (!cloneSourceData) return;

    let sqPayload = null;
    if (!WO_ONLY) {
        const v = (sel) => $(sel).val() || null;
        sqPayload = {
            tanggal_sq: $('input[name="tanggal_sq"]').val(),
            judul_order: $('input[name="judul_order"]').val(),
            berlaku_sampai: v('input[name="berlaku_sampai"]'),
            rencana_mulai: v('input[name="rencana_mulai"]'),
            id_office: v('#sq_id_office'),
            id_pelanggan: v('#sq_id_pelanggan'),
            id_site_pelanggan: v('#sq_id_site_pelanggan'),
            id_pic_pelanggan: v('#sq_id_pic_pelanggan'),
            id_pelanggan_delivery: v('#sq_id_pelanggan_delivery'),
            id_site_pelanggan_delivery: v('#sq_id_site_pelanggan_delivery'),
            id_pic_pelanggan_delivery: v('#sq_id_pic_pelanggan_delivery'),
            id_pelanggan_payment: v('#sq_id_pelanggan_payment'),
            id_site_pelanggan_payment: v('#sq_id_site_pelanggan_payment'),
            id_pic_pelanggan_payment: v('#sq_id_pic_pelanggan_payment'),
            pic_input: v('#sq_pic_input'),
            pic_order: v('#sq_pic_order'),
            pic_marketing_internal: v('#sq_pic_marketing_internal'),
            pic_marketing_eksternal: v('#sq_pic_marketing_eksternal'),
            cara_pembayaran: v('textarea[name="cara_pembayaran"]'),
            keterangan: v('textarea[name="keterangan"]'),
            salin_keterangan: $('#sq_salin_keterangan').is(':checked') ? 1 : 0,
        };

        const wajib = [
            ['tanggal_sq', 'Tanggal SQ'], ['judul_order', 'Judul Order'],
            ['id_pelanggan', 'Perusahaan (Data Pemesan)'], ['id_site_pelanggan', 'Site (Data Pemesan)'], ['id_pic_pelanggan', 'PIC (Data Pemesan)'],
            ['id_pelanggan_delivery', 'Perusahaan (Data Pengiriman)'], ['id_site_pelanggan_delivery', 'Site (Data Pengiriman)'], ['id_pic_pelanggan_delivery', 'PIC (Data Pengiriman)'],
            ['id_pelanggan_payment', 'Perusahaan (Data Pembayaran)'], ['id_site_pelanggan_payment', 'Site (Data Pembayaran)'], ['id_pic_pelanggan_payment', 'PIC (Data Pembayaran)'],
            ['pic_input', 'PIC Input'], ['pic_order', 'PIC Order'], ['pic_marketing_internal', 'Marketing Internal'],
        ];
        const kosong = wajib.filter(([k]) => !sqPayload[k]).map(([, label]) => label);
        if (kosong.length) {
            Notify.error('Wajib diisi: ' + kosong.join(', ') + '.');
            return;
        }
        if (sqPayload.berlaku_sampai && sqPayload.berlaku_sampai < sqPayload.tanggal_sq) {
            Notify.error('Berlaku Sampai tidak boleh sebelum Tanggal SQ.');
            return;
        }
    }

    const wosPayload = [];
    $('#woAccordion .wo-card').each(function () {
        const wo = (cloneSourceData.wos || []).find((w) => w.id_sq_wo === Number($(this).data('source-id-wo')));
        if (!wo) return;

        // Card belum pernah dibuka → body belum ada: WO tetap disalin persis seperti data sumber
        if (!$(this).find('.accordion-body').children().length) {
            wosPayload.push({
                source_id_wo: wo.id_sq_wo,
                include: $(this).find('.wo-include').is(':checked'),
                judul_pekerjaan: wo.judul_pekerjaan,
                id_site_pelanggan_pekerjaan: wo.id_site_pelanggan_pekerjaan,
                id_pic_pelanggan_pekerjaan: wo.id_pic_pelanggan_pekerjaan,
                hari_mulai: wo.hari_mulai,
                durasi_hari: wo.durasi_hari,
                interval_bulan: wo.interval_bulan,
                no_urut_period: wo.no_urut_period,
                keterangan: wo.keterangan,
                boq: (wo.boq || []).map((r) => ({ source_id_boq: r.source_id_boq, include: true, qty: r.qty, id_satuan: r.id_satuan, harga: r.harga, discount: r.discount, keterangan: r.keterangan })),
                boq_other: (wo.boq_other || []).map((r) => ({ source_id_boq_tambahan: r.source_id_boq_tambahan, include: true, nama_item: r.nama_item, qty: r.qty, id_satuan: r.id_satuan, harga: r.harga, keterangan: r.keterangan })),
                boq_sampling: (wo.boq_sampling || []).map((r) => ({ source_id_boq_tambahan: r.source_id_boq_tambahan, include: true, nama_item: r.nama_item, qty: r.qty, id_satuan: r.id_satuan, harga: r.harga, keterangan: r.keterangan })),
                budgets: fallbackBudgets(wo.budgets),
                fwos: (wo.fwos || []).map((f) => fallbackFwoPayload(f)),
            });
            return;
        }

        wosPayload.push(collectWoPayload($(this), wo));
    });

    const errors = validateWosPayload(wosPayload);
    if (errors.length) {
        Notify.error(errors.join('<br>'));
        return;
    }

    const includedCount = wosPayload.filter((w) => w.include).length;
    if (!includedCount) {
        Notify.error('Centang minimal 1 WO untuk disalin.');
        return;
    }

    const confirmMsg = WO_ONLY
        ? 'Buat salinan SQ Work Order ini beserta BOQ, FWO, dan Budget-nya?'
        : `Buat salinan Sales Quotation ini beserta ${includedCount} WO yang dicentang?`;

    Notify.confirm(confirmMsg, function () {
        const $btn = $('#btnSubmitClone');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...');

        $.ajax({
            url: window.cloneRoute.submit,
            method: 'POST',
            data: JSON.stringify(WO_ONLY
                ? { _token: window.cloneRoute.csrf, wo_only: true, wos: wosPayload }
                : { _token: window.cloneRoute.csrf, sq: sqPayload, wos: wosPayload }),
            contentType: 'application/json',
            success: function (res) {
                Notify.success(res.message || 'Berhasil disalin');
                setTimeout(function () {
                    window.location.href = WO_ONLY
                        ? '/sq-work-orders?open=' + (res.id_sq_wo || '')
                        : '/sales-quotations?open=' + res.id_sq;
                }, 800);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-copy me-1"></i> Buat Salinan');
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Notify.error(Object.values(xhr.responseJSON.errors).map((e) => e[0]).join('<br>'));
                } else {
                    Notify.error(xhr.responseJSON?.message || 'Gagal membuat salinan. Tidak ada data yang tersimpan.');
                }
            },
        });
    });
}
