// Clone SO — Fase 1 (SO + WO + BOQ + BOQ Other/Sampling).
// Halaman penuh (bukan modal) — hierarki tetap kejaga: BOQ/BOQ Other/BOQ
// Sampling milik 1 WO dirender DI DALAM card accordion WO yang sama, bukan
// section terpisah, jadi kalau WO-nya di-skip semua turunannya ikut skip.

let cloneSourceData = null;
let woIndexCounter = 0;

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

    $('#so_id_office').select2({ width: '100%', allowClear: true, placeholder: 'Pilih Office' });
    $('#so_id_office').val(so.id_office ? String(so.id_office) : '').trigger('change');

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

    wos.forEach((wo) => {
        const idx = woIndexCounter++;
        const collapseId = 'woCollapse' + idx;
        const searchText = [wo.no_wo, wo.judul_pekerjaan, wo.nama_site].filter(Boolean).join(' ').toLowerCase();

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

            if (opening && !$collapse.data('loaded')) {
                $collapse.find('.accordion-body').html(buildWoBodyHtml(wo));
                initWoBodyPlugins($card, wo);
                $collapse.data('loaded', true);
            }

            $collapse.toggleClass('d-none', !opening);
            $btn.toggleClass('collapsed', !opening);
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

        <h6 class="mb-2"><i class="fa-solid fa-table-list me-1 text-primary"></i> BOQ</h6>
        ${buildBoqTableHtml(wo.boq || [], 'boq')}

        <h6 class="mb-2 mt-3"><i class="fa-solid fa-layer-group me-1 text-warning"></i> BOQ Other</h6>
        ${buildBoqTambahanTableHtml(wo.boq_other || [], 'boq-other')}

        <h6 class="mb-2 mt-3"><i class="fa-solid fa-vial me-1 text-success"></i> BOQ Sampling</h6>
        ${buildBoqTambahanTableHtml(wo.boq_sampling || [], 'boq-sampling')}
    `;
}

function buildBoqTableHtml(items, rowClass) {
    if (!items.length) return '<p class="text-muted fst-italic small">Tidak ada BOQ.</p>';
    const rows = items.map((r) => {
        const standard = [r.standard_nomor, r.standard_judul].filter(Boolean).join(' — ') || '-';
        const matriks = [r.matriks_kode, r.matriks_judul].filter(Boolean).join(' — ') || '-';
        return `
            <tr class="${rowClass}-row" data-source-id-boq="${r.source_id_boq}">
                <td class="text-center">
                    <input type="checkbox" class="form-check-input row-include" checked title="Sertakan baris ini">
                </td>
                <td>${escHtml(r.nama_testing_point || r.item_produk_alternate || '-')}</td>
                <td class="small text-muted">${escHtml(standard)}</td>
                <td class="small text-muted">${escHtml(matriks)}</td>
                <td style="width:90px;"><input type="number" class="form-control form-control-sm row-qty" value="${r.qty ?? ''}"></td>
                <td style="width:150px;"><select class="form-select form-select-sm row-satuan"></select></td>
                <td style="width:130px;"><input type="text" class="form-control form-control-sm input-num-mask input-num-int row-harga" value="${r.harga ?? 0}"></td>
                <td><input type="text" class="form-control form-control-sm row-keterangan" value="${escHtml(r.keterangan || '')}"></td>
            </tr>`;
    }).join('');

    return `
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0 ${rowClass}-table">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Testing Point</th>
                        <th>Standard</th>
                        <th>Matriks Sample</th>
                        <th>Qty</th>
                        <th>Satuan</th>
                        <th>Harga</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`;
}

function buildBoqTambahanTableHtml(items, rowClass) {
    if (!items.length) return '<p class="text-muted fst-italic small">Tidak ada data.</p>';
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
        </div>`;
}

function initWoBodyPlugins($card, wo) {
    const $body = $card.find('.accordion-body');

    initFpDate($body);
    initNumericMask($body[0]);

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
        const $satuan = $row.find('.row-satuan');
        const sourceRow = findSourceRow($row, wo);
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

        $row.find('.row-include').on('change', function () {
            const on = $(this).is(':checked');
            $row.find('input, select').not('.row-include').prop('disabled', !on);
            $row.toggleClass('opacity-50', !on);
        });
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

// ── Submit ──────────────────────────────────────────────────────────────

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
            base[keyField] = $row.data(keyField.replace(/_/g, '-'));
            if (nameEditable) base.nama_item = $row.find('.row-nama').val();
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
    };
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
            });
            return;
        }

        wosPayload.push(collectWoPayload($(this), wo));
    });

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
