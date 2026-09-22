// Clone SO — Fase 1 (SO + WO + BOQ + BOQ Other/Sampling).
// Halaman penuh (bukan modal) — hierarki tetap kejaga: BOQ/BOQ Other/BOQ
// Sampling milik 1 WO dirender DI DALAM card accordion WO yang sama, bukan
// section terpisah, jadi kalau WO-nya di-skip semua turunannya ikut skip.

let cloneSourceData = null;
let woIndexCounter = 0;
let fwoIndexCounter = 0;
const PERSONEL_ROLES = ['Leader', 'Driver', 'Anggota', 'PIC Project'];

$(document).ready(function () {
    loadCloneSourceData();

    $('#cloneSoForm').on('submit', function (e) {
        e.preventDefault();
        submitClone();
    });

    $('#woSearch').on('input', function () {
        const q = $(this).val().toLowerCase().trim();
        $('#woAccordion .wo-card').each(function () {
            const text = ($(this).data('search') || '').toString();
            $(this).toggle(!q || text.includes(q));
        });
        $('#woSearchClear').toggleClass('d-none', !q);
    });
    $('#woSearchClear').on('click', function () {
        $('#woSearch').val('').trigger('input');
    });

    $('#btnApplyDateShift').on('click', function () {
        const days = parseInt($('#dateShiftDays').val(), 10);
        if (!days) {
            Notify.warning('Isi jumlah hari dulu (boleh negatif untuk mundur).');
            return;
        }
        let applied = 0;
        $('#woAccordion .wo-card').each(function () {
            const $card = $(this);
            if (!$card.find('.wo-include').is(':checked')) return;
            shiftDateField($card.find('.wo-tgl-mulai'), days);
            shiftDateField($card.find('.wo-tgl-selesai'), days);
            applied++;
        });
        Notify.toast(`Tanggal ${applied} WO digeser ${days > 0 ? 'maju' : 'mundur'} ${Math.abs(days)} hari`);
    });

    // Re-ukur --wo-header-h tiap WO yang sedang terbuka kalau viewport
    // di-resize — lebar berubah bisa bikin teks header WO ganti jumlah baris,
    // jadi tinggi acuan buat sticky header FWO di dalamnya juga perlu di-update.
    let resizeTimer;
    $(window).on('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            $('#woAccordion .wo-card.wo-active').each(function () {
                const $card = $(this);
                $card.css('--wo-header-h', $card.find('> .accordion-header').outerHeight() + 'px');
            });
        }, 150);
    });
});

function shiftDateField($input, days) {
    const val = $input.val();
    if (!val) return;
    const d = new Date(val + 'T00:00:00');
    if (isNaN(d)) return;
    d.setDate(d.getDate() + days);
    const newVal = d.toISOString().slice(0, 10);
    const fp = $input[0]._flatpickr;
    if (fp) fp.setDate(newVal, true);
    else $input.val(newVal);
}

function loadCloneSourceData() {
    $.get(window.cloneRoute.data)
        .done(function (res) {
            cloneSourceData = res;
            renderClonePage(res);
        })
        .fail(function (xhr) {
            $('#cloneLoading').addClass('d-none');
            $('#cloneLoadError').removeClass('d-none').text(
                xhr.responseJSON?.message || 'Gagal memuat data Sales Order untuk di-clone.'
            );
        });
}

function renderClonePage(res) {
    $('#cloneSourceLabel').text(res.so.no_so + (res.so.judul_order ? ' — ' + res.so.judul_order : ''));
    fillSoForm(res.so);
    renderWoAccordion(res.wos || []);

    $('#cloneLoading').addClass('d-none');
    $('#cloneSoForm').removeClass('d-none');
}

// ── SO form ──────────────────────────────────────────────────────────────

function fillSoForm(so) {
    $('input[name="tanggal_so"]').val(so.tanggal_so);
    $('input[name="judul_order"]').val(so.judul_order);
    $('input[name="tanggal_mulai"]').val(so.tanggal_mulai);
    $('input[name="tanggal_selesai"]').val(so.tanggal_selesai);
    $('#so_tidak_ada_po').prop('checked', Number(so.tidak_ada_po) === 1);
    $('input[name="tanggal_po"]').val(so.tanggal_po);
    $('input[name="no_po"]').val(so.no_po);
    $('textarea[name="keterangan_status"]').val(so.keterangan_status);
    $('textarea[name="cara_pembayaran"]').val(so.cara_pembayaran);
    $('textarea[name="keterangan"]').val(so.keterangan);

    initFpDate('#cloneSoForm');

    // Ganti Tanggal Mulai/Selesai SO → kosongkan Tanggal Mulai/Selesai semua
    // WO (baik yang sudah di-expand maupun belum). Tanpa ini, tanggal WO
    // tetap ikut nilai sumber padahal periode SO-nya sudah beda — bisa jadi
    // rancu (mis. SO mulai minggu ke-3, WO-nya masih nempel minggu ke-1).
    // Dipasang setelah initFpDate supaya tidak ikut kepicu saat pre-fill
    // awal (flatpickr tidak trigger 'change' native untuk defaultDate).
    $('input[name="tanggal_mulai"], input[name="tanggal_selesai"]').on('change', function () {
        clearAllWoDates();
    });

    $('#so_id_office').select2({
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
    if (so.id_office) {
        $('#so_id_office').append(new Option(so.nama_office || ('#' + so.id_office), so.id_office, true, true)).trigger('change');
    }

    // SO Reference — manual, sengaja TIDAK preselect ke SO sumber clone ini
    // (disepakati user: traceability ini bukan otomatis, user yang pilih
    // sendiri SO referensinya kalau perlu, kosong by default).
    $('#so_id_so_referensi').select2({
        width: '100%', allowClear: true, placeholder: 'Cari No. SO / Judul... (opsional)', minimumInputLength: 1,
        ajax: {
            url: window.cloneRoute.select2SalesOrder, dataType: 'json', delay: 250,
            data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true,
        },
        escapeMarkup: (m) => m,
    });

    initCompanySelect('#so_id_pelanggan', so.id_pelanggan, so.nama_pelanggan);
    initCompanySelect('#so_id_pelanggan_delivery', so.id_pelanggan_delivery, so.nama_pelanggan_delivery);
    initCompanySelect('#so_id_pelanggan_payment', so.id_pelanggan_payment, so.nama_pelanggan_payment);

    initSiteSelect('#so_id_site_pelanggan', '#so_id_pelanggan', so.id_site_pelanggan, so.nama_site_pelanggan);
    initSiteSelect('#so_id_site_pelanggan_delivery', '#so_id_pelanggan_delivery', so.id_site_pelanggan_delivery, so.nama_site_delivery);
    initSiteSelect('#so_id_site_pelanggan_payment', '#so_id_pelanggan_payment', so.id_site_pelanggan_payment, so.nama_site_payment);

    const soCompanyIds = () => [
        $('#so_id_pelanggan').val(),
        $('#so_id_pelanggan_delivery').val(),
        $('#so_id_pelanggan_payment').val(),
    ].filter(Boolean);

    initPicSelect('#so_id_pic_pelanggan', soCompanyIds, so.id_pic_pelanggan, so.pic_pelanggan);
    initPicSelect('#so_id_pic_pelanggan_delivery', soCompanyIds, so.id_pic_pelanggan_delivery, so.pic_delivery);
    initPicSelect('#so_id_pic_pelanggan_payment', soCompanyIds, so.id_pic_pelanggan_payment, so.pic_payment);

    initUserSelect('#so_pic_input', so.pic_input, so.nama_pic_input);
    initUserSelect('#so_pic_order', so.pic_order, so.nama_pic_order);
    initUserSelect('#so_pic_marketing_internal', so.pic_marketing_internal, so.nama_marketing_internal);
    initUserSelect('#so_pic_marketing_eksternal', so.pic_marketing_eksternal, so.nama_marketing_eksternal);
}

// ── Select2 helper (dipakai berulang untuk SO & tiap card WO) ──────────────

function initCompanySelect(sel, initialId, initialText) {
    const $el = $(sel);
    $el.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih Perusahaan', minimumInputLength: 0,
        ajax: {
            url: window.cloneRoute.select2Br, dataType: 'json', delay: 250,
            data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true,
        },
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
            data: (p) => ({ q: p.term, id_br: $(companySel).val() || '' }),
            processResults: (d) => ({ results: d }), cache: false,
        },
        escapeMarkup: (m) => m,
    });
    if (initialId) $el.append(new Option(initialText || ('#' + initialId), initialId, true, true)).trigger('change');
    $(companySel).on('select2:select select2:clear', function () { $el.val(null).trigger('change'); });
}

function initPicSelect(sel, companyIdsFn, initialId, initialText) {
    const $el = $(sel);
    $el.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih PIC', minimumInputLength: 0,
        ajax: {
            url: window.cloneRoute.select2Contact, dataType: 'json', delay: 250,
            data: (p) => ({ q: p.term, id_br: companyIdsFn(), with_site: 1 }),
            processResults: (d) => ({ results: d }), cache: false,
        },
        escapeMarkup: (m) => m,
    });
    if (initialId) $el.append(new Option(initialText || ('#' + initialId), initialId, true, true)).trigger('change');
}

function initUserSelect(sel, initialId, initialText) {
    const $el = $(sel);
    $el.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih User', minimumInputLength: 0,
        ajax: {
            url: window.cloneRoute.select2User, dataType: 'json', delay: 200,
            data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true,
        },
        escapeMarkup: (m) => m,
    });
    if (initialId) $el.append(new Option(initialText || ('#' + initialId), initialId, true, true)).trigger('change');
}

// ── WO accordion ────────────────────────────────────────────────────────

const INTERVAL_LABELS = { 1: 'Bulanan', 2: 'Bimulanan', 3: 'Triwulan', 4: 'Caturwulan', 6: 'Semester', 12: 'Annual' };

// Kosongkan Tanggal Mulai/Selesai semua WO — dipanggil begitu Tanggal
// Mulai/Selesai SO diubah user. Card yang sudah pernah di-expand: kosongkan
// langsung field flatpickr-nya yang kelihatan. Card yang BELUM pernah
// di-expand: field-nya belum ada di DOM, jadi objek sumbernya sendiri
// (cloneSourceData.wos[i]) yang dimutasi — supaya kalau card itu tidak
// pernah dibuka sampai submit, fallback payload (baca langsung dari objek
// wo) ikut mengirim tanggal kosong juga, bukan nilai sumber yang sudah basi.
function clearAllWoDates() {
    if (!cloneSourceData || !(cloneSourceData.wos || []).length) return;

    cloneSourceData.wos.forEach((wo) => {
        wo.tanggal_mulai = null;
        wo.tanggal_selesai = null;
    });

    $('#woAccordion .wo-card').each(function () {
        const $card = $(this);
        if (!$card.find('.accordion-collapse').data('loaded')) return;

        ['.wo-tgl-mulai', '.wo-tgl-selesai'].forEach((sel) => {
            const $input = $card.find(sel);
            const fp = $input[0] && $input[0]._fp;
            if (fp) fp.clear();
            else $input.val('');
        });
    });

    Notify.toast('Tanggal Mulai/Selesai semua WO dikosongkan karena periode SO berubah — isi ulang sesuai periode yang baru.');
}

function renderWoAccordion(wos) {
    $('#woSummary').text(
        `${wos.length} WO tersedia — ` +
        `${wos.reduce((s, w) => s + (w.boq?.length || 0), 0)} BOQ, ` +
        `${wos.reduce((s, w) => s + (w.boq_other?.length || 0), 0)} BOQ Other, ` +
        `${wos.reduce((s, w) => s + (w.boq_sampling?.length || 0), 0)} BOQ Sampling`
    );

    const $accordion = $('#woAccordion').empty();
    if (!wos.length) {
        $accordion.html('<p class="text-muted fst-italic mb-0">Tidak ada Work Order pada Sales Order ini.</p>');
        return;
    }

    wos.forEach((wo, woPos) => {
        const idx = woIndexCounter++;
        const collapseId = 'woCollapse' + idx;
        const searchText = [wo.no_wo, wo.judul_pekerjaan, wo.nama_site].filter(Boolean).join(' ').toLowerCase();
        const noUrut = woPos + 1;

        // Toggle expand/collapse ditangani manual (bukan lewat plugin Collapse
        // Bootstrap) — class accordion-button/accordion-collapse tetap
        // dipakai supaya tampilannya (chevron, background aktif) identik,
        // tapi buka/tutupnya murni toggle class `d-none`, tanpa animasi
        // height/transisi yang butuh plugin JS-nya.
        const $card = $(`
            <div class="accordion-item wo-card" data-wo-index="${idx}" data-source-id-wo="${wo.id_wo}" data-search="${escHtml(searchText)}">
                <h2 class="accordion-header d-flex align-items-center">
                    <div class="form-check ms-3 me-1" onclick="event.stopPropagation()">
                        <input type="checkbox" class="form-check-input wo-include" checked title="Sertakan WO ini">
                    </div>
                    <span class="me-2" style="color:#94a3b8;font-size:12px;font-weight:600;min-width:20px;text-align:center;flex-shrink:0;">${noUrut}</span>
                    <button class="accordion-button collapsed wo-toggle" type="button">
                        <strong class="me-2">${escHtml(wo.no_wo)}</strong> ${escHtml(wo.judul_pekerjaan || '-')}
                        <span class="pm-badge pm-badge--blue ms-2">${(wo.boq || []).length} BOQ</span>
                        ${(wo.boq_other || []).length ? `<span class="pm-badge ms-1">${wo.boq_other.length} Other</span>` : ''}
                        ${(wo.boq_sampling || []).length ? `<span class="pm-badge ms-1">${wo.boq_sampling.length} Sampling</span>` : ''}
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

            // Exclusive — sama seperti accordion FWO: cuma 1 WO yang boleh
            // terbuka sekaligus, supaya halaman tidak menumpuk banyak body WO
            // kalau 1 SO punya banyak WO (mis. 24 WO kontrak 2 tahun).
            if (opening) {
                $accordion.find('.accordion-collapse').not($collapse).addClass('d-none');
                $accordion.find('.wo-toggle').not($btn).addClass('collapsed');
                $accordion.find('.wo-card').not($card).removeClass('wo-active');
            }

            $collapse.toggleClass('d-none', !opening);
            $btn.toggleClass('collapsed', !opening);
            $card.toggleClass('wo-active', opening);

            // Build & init HARUS setelah d-none dilepas — kalau tidak, panel
            // masih tersembunyi (display:none) saat initWoBodyPlugins jalan,
            // jadi $th.outerWidth() dkk terbaca 0 dan resize kolom (yang
            // butuh lebar asli tiap kolom) gagal total.
            if (opening && !$collapse.data('loaded')) {
                $collapse.find('.accordion-body').html(buildWoBodyHtml(wo));
                initWoBodyPlugins($card, wo);
                $collapse.data('loaded', true);
            }

            if (opening) {
                // Header FWO sticky di dalam WO ini nempel PAS di bawah header
                // WO (bukan tabrakan di top:0) — butuh tinggi asli header WO,
                // makanya diukur & di-set sebagai CSS var setelah d-none
                // dilepas (harus sudah visible, sama alasan seperti guard
                // outerWidth() di atas).
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
            <div class="col-md-6 col-12">
                <label class="form-label">Judul Pekerjaan</label>
                <input type="text" class="form-control wo-judul" value="${escHtml(wo.judul_pekerjaan || '')}">
            </div>
            <div class="col-md-6 col-12">
                <label class="form-label">Site Pelanggan</label>
                <select class="form-select wo-site"></select>
                <div class="form-text">Perusahaan tetap sama seperti WO sumber — cuma bisa pilih Site lain dari Perusahaan yang sama.</div>
            </div>
            <div class="col-md-4 col-12">
                <label class="form-label">PIC Pekerjaan</label>
                <select class="form-select wo-pic"></select>
            </div>
            <div class="col-md-3 col-12">
                <label class="form-label">Frekuensi</label>
                <select class="form-select wo-interval">${intervalOptions}</select>
            </div>
            <div class="col-md-2 col-12 wo-urutan-wrap" style="${wo.interval_bulan ? '' : 'display:none;'}">
                <label class="form-label">Urutan ke-</label>
                <input type="number" min="1" class="form-control wo-urutan" value="${wo.no_urut_period ?? ''}">
            </div>
            <div class="col-md-3 col-12">
                <label class="form-label">Tanggal Mulai</label>
                <input type="text" class="form-control fp-date wo-tgl-mulai" value="${wo.tanggal_mulai || ''}" autocomplete="off">
            </div>
            <div class="col-md-3 col-12">
                <label class="form-label">Tanggal Selesai</label>
                <input type="text" class="form-control fp-date wo-tgl-selesai" value="${wo.tanggal_selesai || ''}" autocomplete="off">
            </div>
            <div class="col-md-12">
                <label class="form-label">Keterangan</label>
                <textarea class="form-control wo-keterangan" rows="2">${escHtml(wo.keterangan || '')}</textarea>
            </div>
        </div>

        <ul class="nav nav-tabs wo-subtabs mb-3">
            <li class="nav-item">
                <button type="button" class="nav-link active wo-subtab-btn" data-target="boq">
                    <i class="fa-solid fa-table-list me-1"></i> BOQ
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link wo-subtab-btn" data-target="fwo">
                    <i class="fa-solid fa-helmet-safety me-1"></i> Fieldwork Order (FWO)
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link wo-subtab-btn" data-target="budget">
                    <i class="fa-solid fa-wallet me-1"></i> Budget
                </button>
            </li>
        </ul>

        <div class="wo-subtab-pane" data-pane="boq">
            <h6 class="mb-2"><i class="fa-solid fa-table-list me-1 text-primary"></i> BOQ</h6>
            ${buildBoqTableHtml(wo.boq || [], 'boq')}

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

// ── FWO accordion (nested di dalam body WO) ────────────────────────────────
// Sama-sama pakai pola manual toggle + lazy-build seperti accordion WO di
// atas (bukan buka semua FWO), karena alasannya sama: outerWidth()/select2
// yang di-init saat elemen masih d-none akan salah baca lebar/gagal render.
function renderFwoAccordion($woBody, wo) {
    const $list = $woBody.find('.fwo-list');
    const fwos = wo.fwos || [];

    $woBody.find('.fwo-summary').text(
        fwos.length
            ? `${fwos.length} FWO — ${fwos.reduce((s, f) => s + (f.fieldwork_boq?.length || 0), 0)} Fieldwork BOQ, ` +
              `${fwos.reduce((s, f) => s + (f.personel?.length || 0), 0)} Personel`
            : ''
    );

    if (!fwos.length) {
        $list.html('<p class="text-muted fst-italic mb-0">Tidak ada FWO pada WO ini.</p>');
        return;
    }

    fwos.forEach((fwo, fwoPos) => {
        const idx = fwoIndexCounter++;
        const collapseId = 'fwoCollapse' + idx;
        const searchText = [fwo.no_fwo, fwo.judul_pekerjaan].filter(Boolean).join(' ').toLowerCase();
        const noUrut = fwoPos + 1;

        const $card = $(`
            <div class="accordion-item fwo-card" data-fwo-index="${idx}" data-source-id-fwo="${fwo.id_fwo}" data-search="${escHtml(searchText)}">
                <h2 class="accordion-header d-flex align-items-center">
                    <div class="form-check ms-3 me-1" onclick="event.stopPropagation()">
                        <input type="checkbox" class="form-check-input fwo-include" checked title="Sertakan FWO ini">
                    </div>
                    <span class="me-2" style="color:#94a3b8;font-size:12px;font-weight:600;min-width:20px;text-align:center;flex-shrink:0;">${noUrut}</span>
                    <button class="accordion-button collapsed fwo-toggle" type="button">
                        <strong class="me-2">${escHtml(fwo.no_fwo)}</strong> ${escHtml(fwo.judul_pekerjaan || '-')}
                        <span class="pm-badge pm-badge--blue ms-2">${(fwo.fieldwork_boq || []).length} Fieldwork BOQ</span>
                        <span class="pm-badge ms-1">${(fwo.personel || []).length} Personel</span>
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

            // Exclusive — cuma 1 FWO yang boleh terbuka sekaligus, supaya WO
            // card tidak menumpuk banyak body FWO kalau jumlah FWO-nya
            // banyak (mis. 10+). Tutup semua FWO lain dulu sebelum buka ini.
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
                initFwoBodyPlugins($card, fwo, wo, $woBody);
                $collapse.data('loaded', true);
            }

            // Karena exclusive (FWO lain otomatis tertutup), tanpa ini posisi
            // scroll browser tetap di tempat semula — user harus scroll manual
            // lagi buat lihat dari atas card FWO yang baru dibuka. Scroll
            // header card-nya ke atas viewport begitu terbuka.
            if (opening) {
                $card[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
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
    return `
        <div class="row g-3 mb-3">
            <div class="col-md-6 col-12">
                <label class="form-label">Judul Pekerjaan</label>
                <input type="text" class="form-control fwo-judul" value="${escHtml(fwo.judul_pekerjaan || '')}">
            </div>
            <div class="col-md-6 col-12">
                <label class="form-label">Site Pekerjaan</label>
                <select class="form-select fwo-site"></select>
            </div>
            <div class="col-md-4 col-12">
                <label class="form-label">PIC Pekerjaan</label>
                <select class="form-select fwo-pic"></select>
            </div>
            <div class="col-md-4 col-12">
                <label class="form-label">Tanggal Mulai</label>
                <input type="text" class="form-control fp-date fwo-tgl-mulai" value="${fwo.tanggal_mulai || ''}" autocomplete="off">
                <div class="form-text fwo-hint-tgl-mulai" style="display:none;"></div>
            </div>
            <div class="col-md-4 col-12">
                <label class="form-label">Tanggal Selesai</label>
                <input type="text" class="form-control fp-date fwo-tgl-selesai" value="${fwo.tanggal_selesai || ''}" autocomplete="off">
                <div class="form-text fwo-hint-tgl-selesai" style="display:none;"></div>
            </div>
            <div class="col-md-6 col-12">
                <label class="form-label">Waktu Kedatangan</label>
                <input type="text" class="form-control fp-datetime fwo-waktu-kedatangan" value="${fwo.waktu_kedatangan || ''}" autocomplete="off">
            </div>
            <div class="col-md-12">
                <label class="form-label">Keterangan</label>
                <textarea class="form-control fwo-keterangan" rows="2">${escHtml(fwo.keterangan || '')}</textarea>
            </div>
        </div>

        <h6 class="mb-2"><i class="fa-solid fa-table-list me-1 text-primary"></i> Fieldwork BOQ</h6>
        ${buildFieldworkBoqTableHtml(fwo.fieldwork_boq || [])}

        <h6 class="mb-2 mt-3"><i class="fa-solid fa-layer-group me-1 text-warning"></i> BOQ Other</h6>
        ${buildBoqTambahanTableHtml(fwo.fwo_boq_other || [], 'fwo-boq-other')}

        <h6 class="mb-2 mt-3"><i class="fa-solid fa-vial me-1 text-success"></i> BOQ Sampling</h6>
        ${buildBoqTambahanTableHtml(fwo.fwo_boq_sampling || [], 'fwo-boq-sampling')}

        <h6 class="mb-2 mt-3"><i class="fa-solid fa-users me-1 text-secondary"></i> Personel</h6>
        ${buildPersonelTableHtml(fwo.personel || [])}

        <h6 class="mb-2 mt-3"><i class="fa-solid fa-wallet me-1" style="color:#0f766e;"></i> Budget</h6>
        <div class="fwo-budget-list">${buildBudgetPlanListHtml(fwo.budgets || [], 'fwo-budget')}</div>
    `;
}

// Baris Fieldwork BOQ hasil clone: Testing Point tetap (bukan dropdown,
// mengikuti alokasi sumber). Baris baru (.fieldwork-boq-new-row): Testing
// Point WAJIB dipilih dari dropdown yang di-restrict ke BOQ milik WO ini
// sendiri saja (bukan pencarian global seperti "Tambah BOQ" di level WO) —
// disepakati user karena alokasi Fieldwork BOQ memang cuma boleh mengacu ke
// BOQ WO-nya sendiri.
function buildFieldworkBoqTableHtml(items) {
    const rows = items.map((r) => {
        const pointName = r.point_name || r.nama_testing_point || '-';
        return `
        <tr class="fieldwork-boq-row" data-source-id-fwo-boq="${r.source_id_fwo_boq}" data-id-testing-point="${r.id_testing_point}">
            <td class="text-center">
                <input type="checkbox" class="form-check-input row-include" checked title="Sertakan baris ini">
            </td>
            <td style="max-width:260px;"><span class="fw-semibold text-truncate d-inline-block" style="max-width:100%;vertical-align:bottom;" title="${escHtml(pointName)}">${escHtml(pointName)}</span></td>
            <td style="width:90px;"><input type="number" class="form-control form-control-sm row-qty" value="${r.qty ?? ''}"></td>
            <td><input type="text" class="form-control form-control-sm row-keterangan" value="${escHtml(r.keterangan || '')}"></td>
            <td style="width:50px;"></td>
        </tr>`;
    }).join('');

    return `
        <div class="boq-section">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0 fieldwork-boq-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px;"></th>
                            <th>Testing Point</th>
                            <th>Qty</th>
                            <th>Keterangan</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            ${!items.length ? '<p class="text-muted fst-italic small mt-1">Tidak ada Fieldwork BOQ.</p>' : ''}
            <button type="button" class="btn btn-outline-primary btn-sm mt-2 btn-add-fieldwork-boq">
                <i class="fa-solid fa-plus me-1"></i> Tambah Fieldwork BOQ
            </button>
        </div>`;
}

function newFieldworkBoqRowHtml() {
    return `
        <tr class="fieldwork-boq-row fieldwork-boq-new-row">
            <td class="text-center">
                <input type="checkbox" class="form-check-input row-include" checked title="Sertakan baris ini">
            </td>
            <td><select class="form-select form-select-sm new-fieldwork-boq-point"></select></td>
            <td style="width:90px;"><input type="number" class="form-control form-control-sm row-qty" value=""></td>
            <td><input type="text" class="form-control form-control-sm row-keterangan" value=""></td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-fieldwork-boq" title="Hapus baris ini">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>`;
}

function personelRoleOptions(selected) {
    return PERSONEL_ROLES.map((r) => `<option value="${r}" ${r === selected ? 'selected' : ''}>${r}</option>`).join('');
}

function buildPersonelTableHtml(items) {
    const rows = items.map((r) => `
        <tr class="personel-row" data-source-id-fwo-personel="${r.source_id_fwo_personel}">
            <td><select class="form-select form-select-sm row-personnel"></select></td>
            <td style="width:180px;"><select class="form-select form-select-sm row-role">${personelRoleOptions(r.role)}</select></td>
            <td style="width:50px;" class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-personel" title="Hapus baris ini">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>`).join('');

    return `
        <div class="boq-section">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0 personel-table">
                    <thead class="table-light">
                        <tr>
                            <th>Personnel</th>
                            <th>Role</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            ${!items.length ? '<p class="text-muted fst-italic small mt-1">Tidak ada Personel.</p>' : ''}
            <button type="button" class="btn btn-outline-primary btn-sm mt-2 btn-add-personel">
                <i class="fa-solid fa-plus me-1"></i> Tambah Personel
            </button>
        </div>`;
}

function newPersonelRowHtml() {
    return `
        <tr class="personel-row personel-new-row">
            <td><select class="form-select form-select-sm row-personnel"></select></td>
            <td style="width:180px;"><select class="form-select form-select-sm row-role">${personelRoleOptions('')}</select></td>
            <td style="width:50px;" class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-personel" title="Hapus baris ini">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>`;
}

// ── Fase 3: Budget Plan (WO & FWO, reusable — struktur & behavior identik,
// cuma beda konteks kepemilikan) ─────────────────────────────────────────
// Granularitas checklist cuma di level Plan (disepakati user): centang
// "Sertakan" per Plan, field Label/Keterangan/Tanggal bisa diedit, tapi Item
// & Actual/Realisasi di dalamnya cuma ditampilkan read-only (ikut utuh kalau
// Plan-nya disertakan, tidak dipilah/diedit satu-satu) — konsisten dengan
// keputusan user, dan backend juga TIDAK menerima item/actual dari client
// (di-re-fetch dari DB berdasarkan source_id_budget).
function formatRupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

function buildBudgetPlanListHtml(budgets, rowClass) {
    if (!budgets.length) {
        return '<p class="text-muted fst-italic mb-0">Tidak ada Budget Plan.</p>';
    }
    return budgets.map((b) => buildBudgetPlanCardHtml(b, rowClass)).join('');
}

function buildBudgetPlanCardHtml(b, rowClass) {
    const items = b.items || [];
    const totalBudget = items.reduce((s, it) => s + Number(it.nominal_budget || 0), 0);
    const totalActual = items.reduce((s, it) => s + (it.actuals || []).reduce((s2, a) => s2 + Number(a.nominal_actual || 0), 0), 0);

    const itemRows = items.map((it) => `
        <tr>
            <td>${escHtml(it.nama_account || '-')} ${it.is_cash_advance ? '<span class="pm-badge pm-badge--blue ms-1" style="font-size:9px;">CA</span>' : ''}</td>
            <td class="text-end" style="white-space:nowrap;">${formatRupiah(it.nominal_budget)}</td>
            <td class="text-end" style="white-space:nowrap;">${formatRupiah((it.actuals || []).reduce((s, a) => s + Number(a.nominal_actual || 0), 0))}</td>
        </tr>`).join('');

    return `
        <div class="boq-section ${rowClass}-row" data-source-id-budget="${b.source_id_budget}"
            style="background:#fff;border:1px solid #d6dce5;border-left:4px solid #0f766e;
                   border-radius:10px;padding:14px 16px;margin-bottom:16px;
                   box-shadow:0 1px 3px rgba(15,23,42,.06);">
            <div class="d-flex align-items-start gap-2 mb-2">
                <input type="checkbox" class="form-check-input row-include mt-1" checked title="Sertakan Plan ini">
                <div class="flex-grow-1 row g-2">
                    <div class="col-md-6 col-12">
                        <label class="form-label small mb-1">Label Plan</label>
                        <input type="text" class="form-control form-control-sm row-label" value="${escHtml(b.label || '')}">
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label small mb-1">Tanggal Mulai</label>
                        <input type="text" class="form-control form-control-sm fp-date row-tgl-mulai" value="${b.tanggal_mulai || ''}" autocomplete="off">
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label small mb-1">Tanggal Selesai</label>
                        <input type="text" class="form-control form-control-sm fp-date row-tgl-selesai" value="${b.tanggal_selesai || ''}" autocomplete="off">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small mb-1">Keterangan</label>
                        <textarea class="form-control form-control-sm row-keterangan" rows="1">${escHtml(b.keterangan || '')}</textarea>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-1">
                    <thead class="table-light">
                        <tr><th>Account</th><th class="text-end" style="width:130px;">Budget</th><th class="text-end" style="width:130px;">Realisasi</th></tr>
                    </thead>
                    <tbody>${itemRows || '<tr><td colspan="3" class="text-muted fst-italic small">Tidak ada item.</td></tr>'}</tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end gap-3 small text-muted">
                <span>Total Budget: <b>${formatRupiah(totalBudget)}</b></span>
                <span>Total Realisasi: <b>${formatRupiah(totalActual)}</b></span>
            </div>
        </div>`;
}

// Testing Point/Standard/Matriks Sample digabung jadi 1 kolom bertumpuk +
// dipotong (truncate, max 1 baris per teks + tooltip native `title` untuk
// versi lengkapnya) — sebelumnya 3 kolom lebar sendiri-sendiri, isinya bisa
// 1 paragraf penuh, bikin tinggi baris melambung & tabel berat dibaca
// (dilaporkan user via screenshot). Sekarang tinggi baris konsisten pendek,
// kolom Qty/Satuan/Harga/Keterangan yang memang perlu diedit dapat ruang
// lebih lega.
function buildBoqTableHtml(items, rowClass) {
    const rows = items.map((r) => {
        const pointName = r.point_name || r.nama_testing_point || r.item_produk_alternate || '-';
        return `
            <tr class="${rowClass}-row" data-source-id-boq="${r.source_id_boq}" data-id-testing-point="${r.id_testing_point ?? ''}">
                <td class="text-center">
                    <input type="checkbox" class="form-check-input row-include" checked title="Sertakan baris ini">
                </td>
                <td style="max-width:260px;"><span class="fw-semibold text-truncate d-inline-block" style="max-width:100%;vertical-align:bottom;" title="${escHtml(pointName)}">${escHtml(pointName)}</span></td>
                <td style="width:90px;"><input type="number" class="form-control form-control-sm row-qty" value="${r.qty ?? ''}"></td>
                <td style="width:150px;"><select class="form-select form-select-sm row-satuan"></select></td>
                <td style="width:130px;"><input type="text" class="form-control form-control-sm input-num-mask input-num-int row-harga" value="${r.harga ?? 0}"></td>
                <td><input type="text" class="form-control form-control-sm row-keterangan" value="${escHtml(r.keterangan || '')}"></td>
            </tr>`;
    }).join('');

    return `
        <div class="boq-section">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0 boq-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px;"></th>
                            <th>Testing Point</th>
                            <th>Qty</th>
                            <th>Satuan</th>
                            <th>Harga</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            ${!items.length ? '<p class="text-muted fst-italic small mt-1">Tidak ada BOQ.</p>' : ''}
            <button type="button" class="btn btn-outline-primary btn-sm mt-2 btn-add-boq">
                <i class="fa-solid fa-plus me-1"></i> Tambah BOQ
            </button>
        </div>`;
}

// newBoqRowHtml() menghasilkan SEPASANG <tr> — baris field utama + baris
// checklist Testing Item di bawahnya (spanning) — dipasangkan/dihapus
// bersamaan. Beda dari baris hasil clone: tidak punya data-source-id-boq
// sama sekali, jadi backend tahu ini harus divalidasi & di-insert dari nol
// (bukan disalin dari record sumber).
function newBoqRowHtml() {
    return `
        <tr class="boq-row boq-new-row">
            <td class="text-center">
                <input type="checkbox" class="form-check-input row-include" checked title="Sertakan baris ini">
            </td>
            <td style="max-width:260px;"><select class="form-select form-select-sm new-boq-point"></select></td>
            <td style="width:90px;"><input type="number" class="form-control form-control-sm row-qty" value=""></td>
            <td style="width:150px;"><select class="form-select form-select-sm row-satuan"></select></td>
            <td style="width:130px;"><input type="text" class="form-control form-control-sm input-num-mask input-num-int row-harga" value="0"></td>
            <td><input type="text" class="form-control form-control-sm row-keterangan" value=""></td>
        </tr>
        <tr class="boq-new-row-items">
            <td></td>
            <td colspan="5">
                <div class="new-boq-items-toggle d-none mb-1" style="cursor:pointer;">
                    <i class="fa-solid fa-chevron-down me-1 toggle-icon" style="font-size:10px;transition:transform .15s;"></i>
                    <span class="fw-semibold small">Testing Item</span>
                    <span class="text-muted small ms-1">(<span class="checked-count">0</span> dipilih)</span>
                </div>
                <div class="new-boq-items-checklist text-muted small fst-italic">Pilih Testing Point dulu untuk memilih Testing Item-nya.</div>
                <button type="button" class="btn btn-outline-danger btn-sm mt-1 btn-remove-new-boq">
                    <i class="fa-solid fa-trash me-1"></i> Hapus BOQ ini
                </button>
            </td>
        </tr>`;
}

function buildBoqTambahanTableHtml(items, rowClass) {
    const rows = items.map((r) => `
        <tr class="${rowClass}-row" data-source-id-boq-tambahan="${r.source_id_boq_tambahan}">
            <td class="text-center">
                <input type="checkbox" class="form-check-input row-include" checked title="Sertakan baris ini">
            </td>
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
                        <tr>
                            <th style="width:36px;"></th>
                            <th>Nama Item</th>
                            <th>Qty</th>
                            <th>Satuan</th>
                            <th>Harga</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            ${!items.length ? '<p class="text-muted fst-italic small mt-1">Tidak ada data.</p>' : ''}
            <button type="button" class="btn btn-outline-primary btn-sm mt-2 btn-add-boq-tambahan" data-rowclass="${rowClass}">
                <i class="fa-solid fa-plus me-1"></i> Tambah Item
            </button>
        </div>`;
}

function newBoqTambahanRowHtml(rowClass) {
    return `
        <tr class="${rowClass}-row">
            <td class="text-center">
                <input type="checkbox" class="form-check-input row-include" checked title="Sertakan baris ini">
            </td>
            <td><input type="text" class="form-control form-control-sm row-nama" placeholder="Nama item" value=""></td>
            <td style="width:90px;"><input type="number" class="form-control form-control-sm row-qty" value=""></td>
            <td style="width:150px;"><select class="form-select form-select-sm row-satuan"></select></td>
            <td style="width:130px;"><input type="text" class="form-control form-control-sm input-num-mask input-num-int row-harga" value="0"></td>
            <td><input type="text" class="form-control form-control-sm row-keterangan" value=""></td>
        </tr>`;
}

function initWoBodyPlugins($card, wo) {
    const $body = $card.find('.accordion-body');

    initFpDate($body);
    initNumericMask($body[0]);
    linkMulaiSelesai($body.find('.wo-tgl-mulai'), $body.find('.wo-tgl-selesai'));

    // Resize kolom ala Excel — kolom Testing Point/Standard/Matriks sudah
    // dipadatkan, tapi lebar tiap kolom masih bisa beda kebutuhan per user
    // (ada yang mau Keterangan lebih lebar, ada yang mau Testing Point lebih
    // lebar) — biarkan user atur sendiri dengan drag pinggir kolom.
    $body.find('table.boq-table, table.boq-other-table, table.boq-sampling-table').each(function () {
        makeTableColumnsResizable($(this));
    });

    // Site di-scope ke Perusahaan WO sumber (dikunci, tidak diedit di UI) —
    // id_br diambil dari wo.id_pelanggan_pekerjaan langsung, bukan dari
    // dropdown Perusahaan (Perusahaan WO memang tidak ditampilkan sebagai
    // field yang bisa diubah, sama seperti pola Clone WO yang sudah ada).
    const $site = $body.find('.wo-site');
    $site.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih Site', minimumInputLength: 0,
        dropdownParent: $card,
        ajax: {
            url: window.cloneRoute.select2Site, dataType: 'json', delay: 250,
            data: (p) => ({ q: p.term, id_br: wo.id_pelanggan_pekerjaan || '' }),
            processResults: (d) => ({ results: d }), cache: false,
        },
        escapeMarkup: (m) => m,
    });
    if (wo.id_site_pelanggan_pekerjaan) {
        $site.append(new Option(wo.nama_site || ('#' + wo.id_site_pelanggan_pekerjaan), wo.id_site_pelanggan_pekerjaan, true, true)).trigger('change');
    }

    const $pic = $body.find('.wo-pic');
    $pic.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih PIC Pekerjaan', minimumInputLength: 0,
        dropdownParent: $card,
        ajax: {
            url: window.cloneRoute.select2User, dataType: 'json', delay: 200,
            data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true,
        },
        escapeMarkup: (m) => m,
    });
    if (wo.id_pic_pelanggan_pekerjaan) {
        $pic.append(new Option(wo.nama_pic || ('#' + wo.id_pic_pelanggan_pekerjaan), wo.id_pic_pelanggan_pekerjaan, true, true)).trigger('change');
    }

    $body.find('.wo-interval').on('change', function () {
        $body.find('.wo-urutan-wrap').toggle(!!$(this).val());
    });

    $body.find('.boq-row, .boq-other-row, .boq-sampling-row').each(function () {
        const $row = $(this);
        const sourceRow = findSourceRow($row, wo);
        initSatuanSelectForRow($row, $card, sourceRow);
        bindRowIncludeToggle($row);
    });

    $body.find('.wo-budget-row').each(function () {
        bindRowIncludeToggle($(this));
    });

    // Uncheck "Sertakan" di 1 baris BOQ WO harus ikut meng-uncheck (+ disable,
    // supaya tidak bisa dicentang manual lagi selama BOQ-nya masih di-exclude)
    // Fieldwork BOQ manapun di FWO manapun dalam WO ini yang mengacu ke
    // Testing Point yang sama — kalau tidak, baris Fieldwork BOQ itu kelihatan
    // masih tercentang padahal bakal di-skip diam-diam oleh backend saat
    // submit (dilaporkan user: tampilan jadi tidak konsisten/"aneh").
    $body.on('change', '.boq-section .boq-row:not(.boq-new-row) .row-include', function () {
        const $row = $(this).closest('.boq-row');
        syncFieldworkBoqInclude($body, $row.data('id-testing-point'), $(this).is(':checked'));
    });

    // ── Tambah BOQ baru (bukan hasil clone) — checklist Testing Item
    // granular per Testing Point yang dipilih, sama semangatnya dengan
    // halaman "Kelola BOQ" (`/boq/create`), tapi disederhanakan jadi 1 baris
    // + panel checklist di bawahnya (bukan modal terpisah) supaya tidak
    // nambah 1 lapis UI lagi di dalam accordion yang sudah nested.
    $body.on('click', '.btn-add-boq', function () {
        const $tbody = $(this).closest('.boq-section').find('.boq-table tbody');
        const $rows = $(newBoqRowHtml());
        $tbody.append($rows);
        initNewBoqRow($rows.first(), $card, $body);
    });

    $body.on('click', '.btn-remove-new-boq', function () {
        const $itemsRow = $(this).closest('tr');
        const $mainRow = $itemsRow.prev('.boq-new-row');
        $itemsRow.remove();
        $mainRow.remove();
    });

    $body.on('click', '.btn-add-boq-tambahan', function () {
        const rowClass = $(this).data('rowclass');
        const $tbody = $(this).closest('.boq-section').find(`.${rowClass}-table tbody`);
        const $row = $(newBoqTambahanRowHtml(rowClass));
        $tbody.append($row);
        initSatuanSelectForRow($row, $card, null);
        bindRowIncludeToggle($row);
    });

    // Sub-tab BOQ vs FWO — toggle murni d-none (bukan plugin Tab Bootstrap),
    // konsisten dengan pola manual accordion di atas. Dipisah jadi tab supaya
    // WO card tidak langsung menampilkan tabel BOQ + list FWO sekaligus
    // menumpuk vertikal (bisa sangat panjang kalau FWO-nya banyak).
    $body.on('click', '.wo-subtab-btn', function () {
        const target = $(this).data('target');
        $card.find('.wo-subtab-btn').removeClass('active');
        $(this).addClass('active');
        $card.find('.wo-subtab-pane').addClass('d-none');
        $card.find(`.wo-subtab-pane[data-pane="${target}"]`).removeClass('d-none');
    });

    // FWO ditaruh & di-render di sini (bukan langsung di buildWoBodyHtml)
    // supaya konsisten dengan pola lazy-build: elemen sudah pasti visible
    // (d-none sudah dilepas) begitu initWoBodyPlugins jalan.
    renderFwoAccordion($body, wo);

    $body.on('input', '.fwo-search', function () {
        const q = $(this).val().toLowerCase().trim();
        $body.find('.fwo-card').each(function () {
            const text = ($(this).data('search') || '').toString();
            $(this).toggle(!q || text.includes(q));
        });
        $(this).closest('.pm-search').find('.fwo-search-clear').toggleClass('d-none', !q);
    });
    $body.on('click', '.fwo-search-clear', function () {
        const $wrap = $(this).closest('.pm-search');
        $wrap.find('.fwo-search').val('').trigger('input');
    });

    // Tambah Fieldwork BOQ baru — dropdown Testing Point DIBATASI ke BOQ
    // milik WO ini sendiri (bukan pencarian global), sesuai kesepakatan user.
    $body.on('click', '.btn-add-fieldwork-boq', function () {
        const $fwoCard = $(this).closest('.fwo-card');
        const $tbody = $(this).closest('.boq-section').find('.fieldwork-boq-table tbody');
        const $row = $(newFieldworkBoqRowHtml());
        $tbody.append($row);
        initNewFieldworkBoqRow($row, $fwoCard, $body);
    });

    $body.on('click', '.btn-remove-fieldwork-boq', function () {
        $(this).closest('.fieldwork-boq-row').remove();
    });

    $body.on('click', '.btn-add-personel', function () {
        const $fwoCard = $(this).closest('.fwo-card');
        const $tbody = $(this).closest('.boq-section').find('.personel-table tbody');
        const $row = $(newPersonelRowHtml());
        $tbody.append($row);
        initPersonelSelectForRow($row, $fwoCard, null);
    });

    $body.on('click', '.btn-remove-personel', function () {
        $(this).closest('.personel-row').remove();
    });
}

// Testing Point yang boleh dipilih untuk Fieldwork BOQ baru = Testing Point
// yang SAAT INI masih aktif di BOQ milik WO ini (baik hasil clone yang masih
// dicentang "Sertakan" maupun BOQ baru yang sudah dipilih Testing Point-nya)
// — dibaca langsung dari DOM $woBody, bukan snapshot data sumber, supaya
// akurat walau user baru saja meng-uncheck/menambah BOQ sebelum expand FWO.
function availableWoTestingPoints($woBody) {
    const points = [];
    const seen = new Set();

    $woBody.find('.boq-section .boq-row:not(.boq-new-row)').each(function () {
        const $row = $(this);
        if (!$row.find('.row-include').is(':checked')) return;
        const id = $row.data('id-testing-point');
        if (!id || seen.has(String(id))) return;
        seen.add(String(id));
        points.push({ id, label: $row.find('td').eq(1).find('.text-truncate').first().attr('title') || $row.find('td').eq(1).text().trim() });
    });

    $woBody.find('.new-boq-point').each(function () {
        const $select = $(this);
        const id = $select.val();
        if (!id || seen.has(String(id))) return;
        const $row = $select.closest('.boq-new-row');
        if (!$row.find('.row-include').is(':checked')) return;
        seen.add(String(id));
        const data = $select.select2('data');
        points.push({ id, label: data && data[0] ? data[0].text : ('#' + id) });
    });

    return points;
}

// Terapkan status "Sertakan" BOQ WO (per Testing Point) ke semua baris
// Fieldwork BOQ hasil clone di FWO manapun dalam WO ini yang mengacu ke
// Testing Point yang sama. Dipanggil baik saat user toggle checkbox BOQ WO
// (live sync) maupun saat body FWO baru pertama kali di-build (initial sync,
// menutup kemungkinan BOQ WO sudah di-uncheck duluan sebelum FWO-nya dibuka).
function syncFieldworkBoqInclude($woBody, testingPointId, included) {
    if (!testingPointId) return;
    $woBody.find(`.fieldwork-boq-row[data-id-testing-point="${testingPointId}"]`).each(function () {
        const $row = $(this);
        const $chk = $row.find('.row-include');
        if ($chk.is(':checked') === included) return;
        $chk.prop('checked', included).prop('disabled', !included).trigger('change');
        $row.toggleClass('opacity-50', !included);
    });
    // Testing Point yang baru di-exclude dari BOQ WO otomatis tidak boleh lagi
    // jadi pilihan untuk Fieldwork BOQ BARU yang belum disimpan.
    if (!included) {
        $woBody.find('.new-fieldwork-boq-point').each(function () {
            if (String($(this).val()) === String(testingPointId)) {
                $(this).val(null).trigger('change');
            }
        });
    }
}

function usedFieldworkBoqTestingPointIds($fwoCard, excludeSelect) {
    const ids = new Set();
    $fwoCard.find('.fieldwork-boq-row:not(.fieldwork-boq-new-row)').each(function () {
        const tp = $(this).data('id-testing-point');
        if (tp) ids.add(String(tp));
    });
    $fwoCard.find('.new-fieldwork-boq-point').each(function () {
        if (excludeSelect && this === excludeSelect[0]) return;
        const val = $(this).val();
        if (val) ids.add(String(val));
    });
    return ids;
}

function initNewFieldworkBoqRow($row, $fwoCard, $woBody) {
    const $point = $row.find('.new-fieldwork-boq-point');
    const available = availableWoTestingPoints($woBody);
    const used = usedFieldworkBoqTestingPointIds($fwoCard, $point);

    $point.append('<option value="">— Pilih Testing Point —</option>');
    available.forEach((p) => {
        if (used.has(String(p.id))) return;
        $point.append(new Option(p.label, p.id));
    });

    bindRowIncludeToggle($row);
}

function initPersonelSelectForRow($row, $fwoCard, sourceRow) {
    const $personnel = $row.find('.row-personnel');
    $personnel.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih Personnel', minimumInputLength: 0,
        dropdownParent: $fwoCard,
        ajax: {
            url: window.cloneRoute.select2Personnel, dataType: 'json', delay: 200,
            data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true,
        },
        escapeMarkup: (m) => m,
    });
    if (sourceRow && sourceRow.id_personnel) {
        $personnel.append(new Option(sourceRow.nama_personnel || ('#' + sourceRow.id_personnel), sourceRow.id_personnel, true, true)).trigger('change');
    }
}

// Tanggal Selesai tidak boleh sebelum Tanggal Mulai — field WO & FWO tidak
// pakai atribut `name` (dipakai berulang untuk banyak card sekaligus), jadi
// auto-link bawaan initFpDate (yang mendeteksi pasangan lewat substring nama
// field) tidak berlaku di sini dan harus dipasang manual (bug nyata: Tanggal
// Selesai WO sempat bisa diisi lebih kecil dari Tanggal Mulai-nya).
function linkMulaiSelesai($mulai, $selesai) {
    if (!$mulai.length || !$selesai.length) return;
    const fpMulai = $mulai[0]._fp;
    const fpSelesai = $selesai[0]._fp;
    if (!fpMulai || !fpSelesai) return;

    if ($mulai.val()) fpSelesai.set('minDate', $mulai.val());

    fpMulai.config.onChange = fpMulai.config.onChange || [];
    fpMulai.config.onChange.push(function (selectedDates, dateStr) {
        fpSelesai.set('minDate', dateStr || null);
        if (dateStr && fpSelesai.selectedDates[0] && $selesai.val() < dateStr) {
            fpSelesai.clear();
        }
    });
}

const MONTH_FULL_CLONE = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
function fmtDateFullClone(str) {
    if (!str) return null;
    const d = new Date(str);
    if (isNaN(d)) return null;
    return d.getDate() + ' ' + MONTH_FULL_CLONE[d.getMonth()] + ' ' + d.getFullYear();
}

// Batasi (disable) tanggal FWO di luar rentang tanggal WO + tampilkan hint —
// sama persis polanya dengan applyWoDateRange() di fieldworks/create.blade.php.
function applyFwoDateRange($fwoBody, woMulai, woSelesai) {
    const $mulai = $fwoBody.find('.fwo-tgl-mulai');
    const $selesai = $fwoBody.find('.fwo-tgl-selesai');
    const woMulaiFmt = fmtDateFullClone(woMulai);
    const woSelesaiFmt = fmtDateFullClone(woSelesai);

    $fwoBody.data('wo-mulai', woMulai || null);

    if ($mulai[0] && $mulai[0]._fp) {
        $mulai[0]._fp.set('minDate', woMulai || null);
        $mulai[0]._fp.set('maxDate', woSelesai || null);
    }
    if ($selesai[0] && $selesai[0]._fp) {
        $selesai[0]._fp.set('maxDate', woSelesai || null);
    }
    // minDate Tanggal Selesai FWO = yang PALING TELAT antara awal WO dan
    // Tanggal Mulai FWO sendiri (bukan cuma awal WO) — supaya Tanggal Selesai
    // FWO juga tidak bisa diisi lebih kecil dari Tanggal Mulai FWO-nya sendiri.
    recomputeFwoSelesaiMin($fwoBody);

    const $hintMulai = $fwoBody.find('.fwo-hint-tgl-mulai');
    const $hintSelesai = $fwoBody.find('.fwo-hint-tgl-selesai');
    if (woMulaiFmt || woSelesaiFmt) {
        $hintMulai.text(`Rentang WO: ${woMulaiFmt || '-'} s/d ${woSelesaiFmt || '-'}`).show();
        $hintSelesai.text(`Rentang WO: ${woMulaiFmt || '-'} s/d ${woSelesaiFmt || '-'}`).show();
    } else {
        $hintMulai.hide();
        $hintSelesai.hide();
    }
}

function recomputeFwoSelesaiMin($fwoBody) {
    const $selesai = $fwoBody.find('.fwo-tgl-selesai');
    if (!$selesai[0] || !$selesai[0]._fp) return;
    const woMulai = $fwoBody.data('wo-mulai');
    const ownMulai = $fwoBody.find('.fwo-tgl-mulai').val();
    const minDate = [woMulai, ownMulai].filter(Boolean).sort().pop() || null;
    $selesai[0]._fp.set('minDate', minDate);
    if (minDate && $selesai[0]._fp.selectedDates[0] && $selesai.val() < minDate) {
        $selesai[0]._fp.clear();
    }
}

function initFwoBodyPlugins($fwoCard, fwo, wo, $woBody) {
    const $body = $fwoCard.find('.accordion-body');

    initFpDate($body);
    initNumericMask($body[0]);

    // Tanggal Selesai FWO tidak boleh sebelum Tanggal Mulai FWO sendiri.
    const $fwoMulai = $body.find('.fwo-tgl-mulai');
    if ($fwoMulai[0] && $fwoMulai[0]._fp) {
        const fpMulai = $fwoMulai[0]._fp;
        fpMulai.config.onChange = fpMulai.config.onChange || [];
        fpMulai.config.onChange.push(function () {
            recomputeFwoSelesaiMin($body);
        });
    }

    $body.find('table.fwo-boq-other-table, table.fwo-boq-sampling-table').each(function () {
        makeTableColumnsResizable($(this));
    });

    // Rentang tanggal FWO wajib di dalam rentang tanggal WO-nya sendiri —
    // aturan sama seperti halaman "Tambah FWO" biasa (fieldworks/create).
    // Dibaca dari field tanggal WO yang LIVE di $woBody (bisa sudah diedit
    // user di tab BOQ), bukan snapshot data sumber wo.tanggal_mulai/selesai.
    applyFwoDateRange($body, $woBody.find('.wo-tgl-mulai').val(), $woBody.find('.wo-tgl-selesai').val());
    $woBody.on('change.fwoDateRange', '.wo-tgl-mulai, .wo-tgl-selesai', function () {
        applyFwoDateRange($body, $woBody.find('.wo-tgl-mulai').val(), $woBody.find('.wo-tgl-selesai').val());
    });

    // Waktu Kedatangan tidak boleh melewati Tanggal Selesai FWO (batas bawah
    // sudah ditangani initFpDate bawaan lewat field tanggal_mulai-nya).
    const $fwoSelesai = $body.find('.fwo-tgl-selesai');
    if ($fwoSelesai[0] && $fwoSelesai[0]._fp) {
        $fwoSelesai[0]._fp.set('onChange', function (selectedDates, dateStr) {
            const $tiba = $body.find('.fwo-waktu-kedatangan');
            if (!$tiba[0] || !$tiba[0]._fp) return;
            $tiba[0]._fp.set('maxDate', dateStr ? dateStr + ' 23:59' : null);
            const tibaVal = $tiba.val();
            if (dateStr && tibaVal && tibaVal.substring(0, 10) > dateStr) {
                $tiba[0]._fp.clear();
            }
        });
    }

    const $site = $body.find('.fwo-site');
    $site.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih Site', minimumInputLength: 0,
        dropdownParent: $fwoCard,
        ajax: {
            url: window.cloneRoute.select2Site, dataType: 'json', delay: 250,
            data: (p) => ({ q: p.term, id_br: wo.id_pelanggan_pekerjaan || '' }),
            processResults: (d) => ({ results: d }), cache: false,
        },
        escapeMarkup: (m) => m,
    });
    if (fwo.id_site_pelanggan_pekerjaan) {
        $site.append(new Option(fwo.nama_site || ('#' + fwo.id_site_pelanggan_pekerjaan), fwo.id_site_pelanggan_pekerjaan, true, true)).trigger('change');
    }

    const $pic = $body.find('.fwo-pic');
    $pic.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih PIC Pekerjaan', minimumInputLength: 0,
        dropdownParent: $fwoCard,
        ajax: {
            url: window.cloneRoute.select2Contact, dataType: 'json', delay: 250,
            data: (p) => ({ q: p.term, with_site: 1 }), processResults: (d) => ({ results: d }), cache: false,
        },
        escapeMarkup: (m) => m,
    });
    if (fwo.id_pic_pelanggan_pekerjaan) {
        $pic.append(new Option(fwo.nama_pic || ('#' + fwo.id_pic_pelanggan_pekerjaan), fwo.id_pic_pelanggan_pekerjaan, true, true)).trigger('change');
    }

    $body.find('.fwo-boq-other-row, .fwo-boq-sampling-row').each(function () {
        const $row = $(this);
        const sourceRow = (fwo.fwo_boq_other || []).concat(fwo.fwo_boq_sampling || [])
            .find((r) => String(r.source_id_boq_tambahan) === String($row.data('source-id-boq-tambahan')));
        initSatuanSelectForRow($row, $fwoCard, sourceRow);
        bindRowIncludeToggle($row);
    });

    $body.find('.fieldwork-boq-row:not(.fieldwork-boq-new-row)').each(function () {
        const $row = $(this);
        bindRowIncludeToggle($row);

        // Initial sync — kalau BOQ WO-nya sudah di-uncheck DUAN sebelum FWO
        // ini pertama kali dibuka (baru di-build sekarang), langsung ikutkan
        // statusnya di sini juga, jangan tunggu event 'change' berikutnya.
        const tp = $row.data('id-testing-point');
        const $srcBoq = $woBody.find(`.boq-section .boq-row[data-id-testing-point="${tp}"]`);
        if ($srcBoq.length && !$srcBoq.find('.row-include').is(':checked')) {
            $row.find('.row-include').prop('checked', false).prop('disabled', true).trigger('change');
            $row.addClass('opacity-50');
        }
    });

    $body.find('.personel-row').each(function () {
        const $row = $(this);
        const sourceRow = (fwo.personel || []).find((r) => String(r.source_id_fwo_personel) === String($row.data('source-id-fwo-personel')));
        initPersonelSelectForRow($row, $fwoCard, sourceRow);
    });

    $body.find('.fwo-budget-row').each(function () {
        bindRowIncludeToggle($(this));
    });
}

function initSatuanSelectForRow($row, $card, sourceRow) {
    const $satuan = $row.find('.row-satuan');
    $satuan.select2({
        width: '100%', allowClear: true, placeholder: 'Satuan', minimumInputLength: 0,
        dropdownParent: $card,
        ajax: {
            url: window.cloneRoute.select2Satuan, dataType: 'json', delay: 200,
            data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true,
        },
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

// Kumpulkan id_testing_point yang SUDAH dipakai di WO ini — baik dari baris
// hasil clone maupun baris baru lain — supaya tidak ada 2 BOQ dengan Testing
// Point yang sama dalam 1 WO (aturan yang sama seperti BoqController::store()).
function usedTestingPointIds($body, excludeSelect) {
    const ids = new Set();
    $body.find('.boq-row').each(function () {
        const tp = $(this).data('id-testing-point');
        if (tp) ids.add(String(tp));
    });
    $body.find('.new-boq-point').each(function () {
        if (excludeSelect && this === excludeSelect[0]) return;
        const val = $(this).val();
        if (val) ids.add(String(val));
    });
    return ids;
}

function initNewBoqRow($mainRow, $card, $body) {
    const $point = $mainRow.find('.new-boq-point');
    const $itemsRow = $mainRow.next('.boq-new-row-items');
    const $toggle = $itemsRow.find('.new-boq-items-toggle');
    const $checklist = $itemsRow.find('.new-boq-items-checklist');
    const $count = $toggle.find('.checked-count');

    initSatuanSelectForRow($mainRow, $card, null);
    bindRowIncludeToggle($mainRow);

    function updateCheckedCount() {
        $count.text($checklist.find('.new-boq-item-check:checked').length);
    }

    // Panel checklist bisa di-expand/collapse — begitu Testing Point sudah
    // punya banyak Testing Item (kadang puluhan), user tidak wajib melihat
    // semuanya terus-menerus, cukup lihat ringkasan jumlah yang dicentang.
    $toggle.on('click', function () {
        const collapsed = $checklist.hasClass('d-none');
        $checklist.toggleClass('d-none', !collapsed);
        $toggle.find('.toggle-icon').css('transform', collapsed ? 'rotate(0deg)' : 'rotate(-90deg)');
    });

    $itemsRow.on('change', '.new-boq-item-check', updateCheckedCount);

    $point.select2({
        width: '100%', allowClear: true, placeholder: 'Pilih Testing Point', minimumInputLength: 1,
        dropdownParent: $card,
        ajax: {
            url: window.cloneRoute.select2TestingPoint, dataType: 'json', delay: 250,
            data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true,
        },
        escapeMarkup: (m) => m,
    });

    $point.on('select2:select', function (e) {
        const pointId = e.params.data.id;

        if (usedTestingPointIds($body, $point).has(String(pointId))) {
            Notify.error('Testing Point ini sudah dipakai di BOQ lain pada WO yang sama.');
            $point.val(null).trigger('change');
            $toggle.addClass('d-none');
            $checklist.removeClass('d-none').html('<span class="text-muted fst-italic">Pilih Testing Point dulu untuk memilih Testing Item-nya.</span>');
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
                </div>
            `).join(''));
            $toggle.removeClass('d-none');
            $toggle.find('.toggle-icon').css('transform', 'rotate(0deg)');
            updateCheckedCount();
        }).fail(function () {
            $checklist.html('<span class="text-danger">Gagal memuat Testing Item.</span>');
        });
    });

    $point.on('select2:clear', function () {
        $toggle.addClass('d-none');
        $checklist.removeClass('d-none').html('<span class="text-muted fst-italic">Pilih Testing Point dulu untuk memilih Testing Item-nya.</span>');
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

// ── Resize kolom tabel (ala Excel) ─────────────────────────────────────────

// Tambahkan handle drag di pinggir kanan tiap kolom header — geser untuk
// mengubah lebar kolom itu. table-layout:fixed dipaksa supaya lebar kolom
// murni ditentukan lebar <th> (bukan ikut melar mengikuti konten <td>), jadi
// drag-nya konsisten & tidak "dilawan" oleh isi baris.
function makeTableColumnsResizable($table) {
    if (!$table.length || $table.data('resizableInit')) return;
    $table.data('resizableInit', true);
    $table.css('table-layout', 'fixed');

    $table.find('thead th').each(function () {
        const $th = $(this);
        $th.css({ position: 'relative', width: $th.outerWidth() + 'px' });
        $th.append('<span class="col-resize-handle"></span>');
    });

    $table.on('mousedown', '.col-resize-handle', function (e) {
        e.preventDefault();
        const $th = $(this).closest('th');
        const startX = e.pageX;
        const startWidth = $th.outerWidth();
        $(this).addClass('resizing');

        $(document).on('mousemove.colResize', function (ev) {
            const newWidth = Math.max(40, startWidth + (ev.pageX - startX));
            $th.css('width', newWidth + 'px');
        });
        $(document).on('mouseup.colResize', function () {
            $table.find('.col-resize-handle').removeClass('resizing');
            $(document).off('mousemove.colResize mouseup.colResize');
        });
    });
}

// ── Submit ──────────────────────────────────────────────────────────────

// Reusable buat WO maupun FWO — baca baris Budget Plan (rowClass beda:
// 'wo-budget-row' / 'fwo-budget-row'), granularitas cuma di level Plan jadi
// tidak perlu baca item/actual sama sekali (backend re-fetch dari DB).
function collectBudgetsPayload($body, rowClass) {
    return $body.find(`.${rowClass}-row`).map(function () {
        const $row = $(this);
        return {
            source_id_budget: $row.data('source-id-budget') || null,
            include: $row.find('.row-include').is(':checked'),
            label: $row.find('.row-label').val(),
            keterangan: $row.find('.row-keterangan').val() || null,
            tanggal_mulai: $row.find('.row-tgl-mulai').val() || null,
            tanggal_selesai: $row.find('.row-tgl-selesai').val() || null,
        };
    }).get();
}

function collectWoPayload($card, wo) {
    const $body = $card.find('.accordion-body');
    const collectRows = (rowClass, keyField, nameEditable) => {
        return $body.find(`.${rowClass}-row`).map(function () {
            const $row = $(this);
            const base = {
                include: $row.find('.row-include').is(':checked'),
                qty: rawNumVal($row.find('.row-qty')[0]) ?? null,
                id_satuan: $row.find('.row-satuan').val() || null,
                harga: rawNumVal($row.find('.row-harga')[0]) ?? 0,
                keterangan: $row.find('.row-keterangan').val() || null,
            };
            base[keyField] = $row.data(keyField.replace(/_/g, '-')) || null;
            if (nameEditable) base.nama_item = $row.find('.row-nama').val();

            // Baris BOQ baru (bukan hasil clone) — tidak punya source_id_boq,
            // butuh id_testing_point yang dipilih + Testing Item mana saja
            // yang dicentang di checklist pasangannya.
            if ($row.hasClass('boq-new-row')) {
                base.id_testing_point = $row.find('.new-boq-point').val() || null;
                base.id_testing_items = $row.next('.boq-new-row-items')
                    .find('.new-boq-item-check:checked')
                    .map(function () { return $(this).val(); })
                    .get();
            }
            return base;
        }).get();
    };

    return {
        source_id_wo: wo.id_wo,
        include: $card.find('.wo-include').is(':checked'),
        judul_pekerjaan: $body.find('.wo-judul').val() || wo.judul_pekerjaan,
        id_site_pelanggan_pekerjaan: $body.find('.wo-site').val() || null,
        id_pic_pelanggan_pekerjaan: $body.find('.wo-pic').val() || null,
        interval_bulan: $body.find('.wo-interval').val() || null,
        no_urut_period: $body.find('.wo-urutan').val() || null,
        tanggal_mulai: $body.find('.wo-tgl-mulai').val() || null,
        tanggal_selesai: $body.find('.wo-tgl-selesai').val() || null,
        keterangan: $body.find('.wo-keterangan').val() || null,
        boq: collectRows('boq', 'source_id_boq', false),
        boq_other: collectRows('boq-other', 'source_id_boq_tambahan', true),
        boq_sampling: collectRows('boq-sampling', 'source_id_boq_tambahan', true),
        budgets: collectBudgetsPayload($body, 'wo-budget'),
        fwos: collectFwosPayload($body, wo),
    };
}

// Sama seperti WO yang belum di-expand: kalau card FWO tidak pernah dibuka,
// body-nya belum ada di DOM — kirim apa adanya dari data sumber supaya tidak
// diam-diam hilang dari payload.
function collectFwosPayload($woBody, wo) {
    const result = [];
    $woBody.find('.fwo-card').each(function () {
        const $fwoCard = $(this);
        const fwo = (wo.fwos || []).find((f) => f.id_fwo === Number($fwoCard.data('source-id-fwo')));
        if (!fwo) return;

        if (!$fwoCard.find('.accordion-body').children().length) {
            result.push(fallbackFwoPayload(fwo, $fwoCard.find('.fwo-include').is(':checked')));
            return;
        }

        result.push(collectFwoPayload($fwoCard, fwo));
    });
    return result;
}

function fallbackFwoPayload(fwo, include) {
    return {
        source_id_fwo: fwo.id_fwo,
        include: include !== false,
        judul_pekerjaan: fwo.judul_pekerjaan,
        id_site_pelanggan_pekerjaan: fwo.id_site_pelanggan_pekerjaan,
        id_pic_pelanggan_pekerjaan: fwo.id_pic_pelanggan_pekerjaan,
        tanggal_mulai: fwo.tanggal_mulai,
        tanggal_selesai: fwo.tanggal_selesai,
        waktu_kedatangan: fwo.waktu_kedatangan,
        keterangan: fwo.keterangan,
        fieldwork_boq: (fwo.fieldwork_boq || []).map((r) => ({ source_id_fwo_boq: r.source_id_fwo_boq, include: true, id_testing_point: r.id_testing_point, qty: r.qty, keterangan: r.keterangan })),
        fwo_boq_other: (fwo.fwo_boq_other || []).map((r) => ({ source_id_boq_tambahan: r.source_id_boq_tambahan, include: true, nama_item: r.nama_item, qty: r.qty, id_satuan: r.id_satuan, harga: r.harga, keterangan: r.keterangan })),
        fwo_boq_sampling: (fwo.fwo_boq_sampling || []).map((r) => ({ source_id_boq_tambahan: r.source_id_boq_tambahan, include: true, nama_item: r.nama_item, qty: r.qty, id_satuan: r.id_satuan, harga: r.harga, keterangan: r.keterangan })),
        personel: (fwo.personel || []).map((r) => ({ source_id_fwo_personel: r.source_id_fwo_personel, include: true, id_personnel: r.id_personnel, role: r.role })),
        budgets: (fwo.budgets || []).map((b) => ({ source_id_budget: b.source_id_budget, include: true, label: b.label, keterangan: b.keterangan, tanggal_mulai: b.tanggal_mulai, tanggal_selesai: b.tanggal_selesai })),
    };
}

function collectFwoPayload($fwoCard, fwo) {
    const $body = $fwoCard.find('.accordion-body');

    const collectTambahanRows = (rowClass) => $body.find(`.${rowClass}-row`).map(function () {
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

    const fieldworkBoq = $body.find('.fieldwork-boq-row').map(function () {
        const $row = $(this);
        const base = {
            source_id_fwo_boq: $row.data('source-id-fwo-boq') || null,
            include: $row.find('.row-include').is(':checked'),
            qty: rawNumVal($row.find('.row-qty')[0]) ?? null,
            keterangan: $row.find('.row-keterangan').val() || null,
        };
        if ($row.hasClass('fieldwork-boq-new-row')) {
            base.id_testing_point = $row.find('.new-fieldwork-boq-point').val() || null;
        } else {
            base.id_testing_point = $row.data('id-testing-point') || null;
        }
        return base;
    }).get();

    const personel = $body.find('.personel-row').map(function () {
        const $row = $(this);
        return {
            source_id_fwo_personel: $row.data('source-id-fwo-personel') || null,
            include: true,
            id_personnel: $row.find('.row-personnel').val() || null,
            role: $row.find('.row-role').val() || null,
        };
    }).get();

    return {
        source_id_fwo: fwo.id_fwo,
        include: $fwoCard.find('.fwo-include').is(':checked'),
        judul_pekerjaan: $body.find('.fwo-judul').val() || fwo.judul_pekerjaan,
        id_site_pelanggan_pekerjaan: $body.find('.fwo-site').val() || null,
        id_pic_pelanggan_pekerjaan: $body.find('.fwo-pic').val() || null,
        tanggal_mulai: $body.find('.fwo-tgl-mulai').val() || null,
        tanggal_selesai: $body.find('.fwo-tgl-selesai').val() || null,
        waktu_kedatangan: $body.find('.fwo-waktu-kedatangan').val() || null,
        keterangan: $body.find('.fwo-keterangan').val() || null,
        fieldwork_boq: fieldworkBoq,
        fwo_boq_other: collectTambahanRows('fwo-boq-other'),
        fwo_boq_sampling: collectTambahanRows('fwo-boq-sampling'),
        personel,
        budgets: collectBudgetsPayload($body, 'fwo-budget'),
    };
}

// Validasi di frontend sebelum kirim — supaya field yang salah/kosong dapat
// pesan yang jelas ("Isi Qty untuk ...") langsung di halaman, bukan baru
// ketahuan setelah request nyangkut jadi error SQL mentah dari server
// (dilaporkan user: "Column 'qty' cannot be null" tampil apa adanya).
// Backend tetap validasi ulang semua ini juga (frontend tidak pernah jadi
// satu-satunya lapis pertahanan), tapi supaya user tidak perlu submit dulu
// baru tahu salahnya di mana, dicek juga di sini lebih dulu.
function validateWosPayload(wosPayload) {
    const errors = [];

    wosPayload.forEach((wo) => {
        if (!wo.include) return;

        const source = (cloneSourceData.wos || []).find((w) => w.id_wo === wo.source_id_wo);
        const woLabel = source ? source.no_wo : `WO #${wo.source_id_wo}`;

        if (!wo.judul_pekerjaan || !String(wo.judul_pekerjaan).trim()) {
            errors.push(`${woLabel}: Judul Pekerjaan wajib diisi.`);
        }

        if (wo.tanggal_mulai && wo.tanggal_selesai && String(wo.tanggal_selesai) < String(wo.tanggal_mulai)) {
            errors.push(`${woLabel}: Tanggal Selesai tidak boleh sebelum Tanggal Mulai.`);
        }

        (wo.budgets || []).forEach((b) => {
            if (!b.include) return;
            if (!b.label || !String(b.label).trim()) {
                errors.push(`${woLabel}: isi Label untuk salah satu Budget Plan.`);
            }
            if (b.tanggal_mulai && b.tanggal_selesai && String(b.tanggal_selesai) < String(b.tanggal_mulai)) {
                errors.push(`${woLabel}: Tanggal Selesai Budget Plan tidak boleh sebelum Tanggal Mulai.`);
            }
        });

        (wo.boq || []).forEach((r) => {
            if (!r.include) return;
            if (!r.qty || r.qty < 1) {
                errors.push(`${woLabel}: isi Qty (minimal 1) untuk salah satu baris BOQ.`);
            }
            if (!r.source_id_boq) {
                if (!r.id_testing_point) {
                    errors.push(`${woLabel}: ada baris BOQ baru yang belum memilih Testing Point.`);
                } else if (!r.id_testing_items || !r.id_testing_items.length) {
                    errors.push(`${woLabel}: pilih minimal 1 Testing Item untuk BOQ baru yang ditambahkan.`);
                }
            }
        });

        ['boq_other', 'boq_sampling'].forEach((key) => {
            const label = key === 'boq_sampling' ? 'BOQ Sampling' : 'BOQ Other';
            (wo[key] || []).forEach((r) => {
                if (!r.include) return;
                if (!r.nama_item || !String(r.nama_item).trim()) {
                    errors.push(`${woLabel}: isi Nama Item untuk salah satu baris ${label}.`);
                }
                if (!r.qty || r.qty < 1) {
                    errors.push(`${woLabel}: isi Qty (minimal 1) untuk salah satu baris ${label}.`);
                }
            });
        });

        (wo.fwos || []).forEach((fwo) => {
            const woSource = (cloneSourceData.wos || []).find((w) => w.id_wo === wo.source_id_wo);
            const fwoSource = woSource ? (woSource.fwos || []).find((f) => f.id_fwo === fwo.source_id_fwo) : null;
            const fwoLabel = fwoSource ? fwoSource.no_fwo : `FWO #${fwo.source_id_fwo}`;

            if (!fwo.judul_pekerjaan || !String(fwo.judul_pekerjaan).trim()) {
                errors.push(`${woLabel} — ${fwoLabel}: Judul Pekerjaan wajib diisi.`);
            }
            if (!fwo.id_pic_pelanggan_pekerjaan) {
                errors.push(`${woLabel} — ${fwoLabel}: PIC Pekerjaan wajib diisi.`);
            }

            // Rentang tanggal FWO wajib di dalam rentang tanggal WO-nya
            // sendiri — dicek juga di backend, ini supaya user tahu lebih
            // awal (flatpickr sudah membatasi pilihan, tapi tetap dicek
            // ulang di sini kalau ada isian lama di luar rentang baru).
            const woMulaiCmp = wo.tanggal_mulai ? String(wo.tanggal_mulai).substring(0, 10) : null;
            const woSelesaiCmp = wo.tanggal_selesai ? String(wo.tanggal_selesai).substring(0, 10) : null;
            const fwoMulaiCmp = fwo.tanggal_mulai ? String(fwo.tanggal_mulai).substring(0, 10) : null;
            const fwoSelesaiCmp = fwo.tanggal_selesai ? String(fwo.tanggal_selesai).substring(0, 10) : null;

            if (fwoMulaiCmp && woMulaiCmp && fwoMulaiCmp < woMulaiCmp) {
                errors.push(`${woLabel} — ${fwoLabel}: Tanggal Mulai FWO tidak boleh sebelum Tanggal Mulai WO (${woMulaiCmp}).`);
            }
            if (fwoMulaiCmp && woSelesaiCmp && fwoMulaiCmp > woSelesaiCmp) {
                errors.push(`${woLabel} — ${fwoLabel}: Tanggal Mulai FWO tidak boleh setelah Tanggal Selesai WO (${woSelesaiCmp}).`);
            }
            if (fwoSelesaiCmp && woMulaiCmp && fwoSelesaiCmp < woMulaiCmp) {
                errors.push(`${woLabel} — ${fwoLabel}: Tanggal Selesai FWO tidak boleh sebelum Tanggal Mulai WO (${woMulaiCmp}).`);
            }
            if (fwoSelesaiCmp && woSelesaiCmp && fwoSelesaiCmp > woSelesaiCmp) {
                errors.push(`${woLabel} — ${fwoLabel}: Tanggal Selesai FWO tidak boleh setelah Tanggal Selesai WO (${woSelesaiCmp}).`);
            }

            (fwo.budgets || []).forEach((b) => {
                if (!b.include) return;
                if (!b.label || !String(b.label).trim()) {
                    errors.push(`${woLabel} — ${fwoLabel}: isi Label untuk salah satu Budget Plan.`);
                }
                if (b.tanggal_mulai && b.tanggal_selesai && String(b.tanggal_selesai) < String(b.tanggal_mulai)) {
                    errors.push(`${woLabel} — ${fwoLabel}: Tanggal Selesai Budget Plan tidak boleh sebelum Tanggal Mulai.`);
                }
            });

            (fwo.fieldwork_boq || []).forEach((r) => {
                if (!r.include) return;
                if (!r.qty || r.qty < 1) {
                    errors.push(`${woLabel} — ${fwoLabel}: isi Qty (minimal 1) untuk salah satu baris Fieldwork BOQ.`);
                }
                if (!r.source_id_fwo_boq && !r.id_testing_point) {
                    errors.push(`${woLabel} — ${fwoLabel}: ada baris Fieldwork BOQ baru yang belum memilih Testing Point.`);
                }
            });

            ['fwo_boq_other', 'fwo_boq_sampling'].forEach((key) => {
                const label = key === 'fwo_boq_sampling' ? 'BOQ Sampling' : 'BOQ Other';
                (fwo[key] || []).forEach((r) => {
                    if (!r.include) return;
                    if (!r.nama_item || !String(r.nama_item).trim()) {
                        errors.push(`${woLabel} — ${fwoLabel}: isi Nama Item untuk salah satu baris ${label}.`);
                    }
                    if (!r.qty || r.qty < 1) {
                        errors.push(`${woLabel} — ${fwoLabel}: isi Qty (minimal 1) untuk salah satu baris ${label}.`);
                    }
                });
            });

            (fwo.personel || []).forEach((r) => {
                if (!r.id_personnel) {
                    errors.push(`${woLabel} — ${fwoLabel}: pilih Personnel untuk salah satu baris Personel.`);
                }
                if (!r.role) {
                    errors.push(`${woLabel} — ${fwoLabel}: pilih Role untuk salah satu baris Personel.`);
                }
            });
        });
    });

    // Hilangkan duplikat pesan (mis. 2 baris BOQ sama-sama kosong Qty di WO
    // yang sama) supaya tidak muncul notifikasi bertumpuk-tumpuk.
    return [...new Set(errors)];
}

function submitClone() {
    if (!cloneSourceData) return;

    const soPayload = {
        tanggal_so: $('input[name="tanggal_so"]').val(),
        judul_order: $('input[name="judul_order"]').val(),
        tanggal_mulai: $('input[name="tanggal_mulai"]').val() || null,
        tanggal_selesai: $('input[name="tanggal_selesai"]').val() || null,
        tidak_ada_po: $('#so_tidak_ada_po').is(':checked') ? 1 : 0,
        tanggal_po: $('input[name="tanggal_po"]').val() || null,
        no_po: $('input[name="no_po"]').val() || null,
        id_office: $('#so_id_office').val() || null,
        id_so_referensi: $('#so_id_so_referensi').val() || null,
        id_pelanggan: $('#so_id_pelanggan').val(),
        id_site_pelanggan: $('#so_id_site_pelanggan').val() || null,
        id_pic_pelanggan: $('#so_id_pic_pelanggan').val() || null,
        id_pelanggan_delivery: $('#so_id_pelanggan_delivery').val() || null,
        id_site_pelanggan_delivery: $('#so_id_site_pelanggan_delivery').val() || null,
        id_pic_pelanggan_delivery: $('#so_id_pic_pelanggan_delivery').val() || null,
        id_pelanggan_payment: $('#so_id_pelanggan_payment').val() || null,
        id_site_pelanggan_payment: $('#so_id_site_pelanggan_payment').val() || null,
        id_pic_pelanggan_payment: $('#so_id_pic_pelanggan_payment').val() || null,
        pic_input: $('#so_pic_input').val() || null,
        pic_order: $('#so_pic_order').val() || null,
        pic_marketing_internal: $('#so_pic_marketing_internal').val() || null,
        pic_marketing_eksternal: $('#so_pic_marketing_eksternal').val() || null,
        keterangan_status: $('textarea[name="keterangan_status"]').val() || null,
        cara_pembayaran: $('textarea[name="cara_pembayaran"]').val() || null,
        keterangan: $('textarea[name="keterangan"]').val() || null,
    };

    if (!soPayload.tanggal_so || !soPayload.id_pelanggan) {
        Notify.error('Tanggal SO dan Perusahaan (Pemesan) wajib diisi.');
        return;
    }

    if (!soPayload.id_so_referensi) {
        Notify.error('SO Reference wajib dipilih.');
        return;
    }

    if (soPayload.tanggal_mulai && soPayload.tanggal_selesai && soPayload.tanggal_selesai < soPayload.tanggal_mulai) {
        Notify.error('Tanggal Selesai SO tidak boleh sebelum Tanggal Mulai SO.');
        return;
    }

    const wosPayload = [];
    $('#woAccordion .wo-card').each(function () {
        const idx = $(this).data('wo-index');
        const wo = (cloneSourceData.wos || []).find((w) => w.id_wo === Number($(this).data('source-id-wo')));
        if (!wo) return;

        // Kalau card belum pernah di-expand, body-nya belum dirender — WO
        // tetap ikut disalin persis seperti data sumber (tidak ada yang diedit).
        if (!$(this).find('.accordion-body').children().length) {
            wosPayload.push({
                source_id_wo: wo.id_wo,
                include: $(this).find('.wo-include').is(':checked'),
                judul_pekerjaan: wo.judul_pekerjaan,
                id_site_pelanggan_pekerjaan: wo.id_site_pelanggan_pekerjaan,
                id_pic_pelanggan_pekerjaan: wo.id_pic_pelanggan_pekerjaan,
                interval_bulan: wo.interval_bulan,
                no_urut_period: wo.no_urut_period,
                tanggal_mulai: wo.tanggal_mulai,
                tanggal_selesai: wo.tanggal_selesai,
                keterangan: wo.keterangan,
                boq: (wo.boq || []).map((r) => ({ source_id_boq: r.source_id_boq, include: true, qty: r.qty, id_satuan: r.id_satuan, harga: r.harga, keterangan: r.keterangan })),
                boq_other: (wo.boq_other || []).map((r) => ({ source_id_boq_tambahan: r.source_id_boq_tambahan, include: true, nama_item: r.nama_item, qty: r.qty, id_satuan: r.id_satuan, harga: r.harga, keterangan: r.keterangan })),
                boq_sampling: (wo.boq_sampling || []).map((r) => ({ source_id_boq_tambahan: r.source_id_boq_tambahan, include: true, nama_item: r.nama_item, qty: r.qty, id_satuan: r.id_satuan, harga: r.harga, keterangan: r.keterangan })),
                budgets: (wo.budgets || []).map((b) => ({ source_id_budget: b.source_id_budget, include: true, label: b.label, keterangan: b.keterangan, tanggal_mulai: b.tanggal_mulai, tanggal_selesai: b.tanggal_selesai })),
                fwos: (wo.fwos || []).map((fwo) => fallbackFwoPayload(fwo)),
            });
            return;
        }

        wosPayload.push(collectWoPayload($(this), wo));
    });

    const validationErrors = validateWosPayload(wosPayload);
    if (validationErrors.length) {
        Notify.error(validationErrors.join('<br>'));
        return;
    }

    const includedCount = wosPayload.filter((w) => w.include).length;

    Notify.confirm(`Buat salinan Sales Order ini beserta ${includedCount} WO yang dicentang?`, function () {
        const $btn = $('#btnSubmitClone');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...');

        $.ajax({
            url: window.cloneRoute.submit,
            method: 'POST',
            data: JSON.stringify({ _token: window.cloneRoute.csrf, so: soPayload, wos: wosPayload }),
            contentType: 'application/json',
            success: function (res) {
                Notify.success(res.message || 'Sales Order berhasil disalin');
                setTimeout(function () {
                    window.location.href = '/sales-orders?open=' + res.id_so;
                }, 800);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-copy me-1"></i> Buat Salinan');
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const msg = Object.values(xhr.responseJSON.errors).map((e) => e[0]).join('<br>');
                    Notify.error(msg);
                } else {
                    Notify.error(xhr.responseJSON?.message || 'Gagal membuat salinan Sales Order. Tidak ada data yang tersimpan.');
                }
            },
        });
    });
}
