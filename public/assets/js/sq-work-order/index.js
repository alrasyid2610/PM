let currentSqWoId = null;
let currentSqWoData = null;

window.datatableHeaderLabels = Object.assign({}, window.datatableHeaderLabels, {
    no_sq_wo: 'No WO',
    no_sq: 'No SQ',
    judul_pekerjaan: 'Judul Pekerjaan',
    hari_mulai: 'Hari Mulai',
    durasi_hari: 'Durasi',
});

// PIC Pekerjaan di-scope oleh Site Pekerjaan yang dipilih (PIC milik Site itu
// atau PIC level Perusahaan) — pola sama initWoSiteField() di work-order/index.js.
function initSqWoPicField() {
    const $pic = $('#detail_id_pic_pelanggan_pekerjaan');
    const $site = $('#detail_id_site_pelanggan_pekerjaan');
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
                return { q: params.term || '', with_site: 1, id_site: $site.val() || '' };
            },
            processResults: function (data) { return { results: data }; },
            cache: false,
        },
        escapeMarkup: function (m) { return m; },
    });

    $site.off('change.sqWoPicClear').on('change.sqWoPicClear', function () {
        $pic.val(null).trigger('change');
    });
}

$(document).ready(function () {
    if ($('#sq-work-orders-table').length === 0) return;

    new CrudPageController({
        primaryKey: 'id_sq_wo',
        renderForm: renderForm,
        detailTitle: function (res) { return res.no_sq_wo || res.judul_pekerjaan || 'SQ Work Order'; },
        initSelect: function () {
            initNumericMask(document.getElementById('detailContent'));
        },
        afterLoad: function (res) {
            currentSqWoId = res.id_sq_wo;
            currentSqWoData = res;
            initFpDate('#detailContent');
            initSqWoPicField();
        },
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus SQ Work Order ini beserta seluruh BOQ-nya?', function () {
            $.ajax({
                url: window.route.update + id,
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' },
                success: function (res) {
                    Notify.success(res.message || 'Data berhasil dihapus');
                    setTimeout(function () { window.location.href = window.location.pathname; }, 1000);
                },
                error: function (xhr) { Notify.error(xhr.responseJSON?.message || 'Terjadi kesalahan'); },
            });
        });
    });

    $(document).on('shown.bs.tab', '#sqWoDetailTabs button[data-bs-toggle="tab"]', function (e) {
        const target = $(e.target).data('bs-target');
        $('#sqWoTabActionsInfo, #sqWoTabActionsBoq, #sqWoTabActionsBoqOther, #sqWoTabActionsBoqSampling, #sqWoTabActionsBudget')
            .addClass('d-none').removeClass('d-flex');
        if (target === '#tabInfo') $('#sqWoTabActionsInfo').removeClass('d-none');
        if (target === '#tabBoq') { $('#sqWoTabActionsBoq').removeClass('d-none').addClass('d-flex'); loadSqWoBoq(currentSqWoId); }
        if (target === '#tabBoqOther') { $('#sqWoTabActionsBoqOther').removeClass('d-none').addClass('d-flex'); loadSqWoTambahan(currentSqWoId); }
        if (target === '#tabBoqSampling') { $('#sqWoTabActionsBoqSampling').removeClass('d-none').addClass('d-flex'); loadSqWoTambahan(currentSqWoId); }
        if (target === '#tabBudget') { $('#sqWoTabActionsBudget').removeClass('d-none').addClass('d-flex'); loadSqWoBudget(currentSqWoId); }
    });

    $(document).on('click', '#btnRefreshSqBoq', function () {
        loadSqWoBoq(currentSqWoId);
    });
});

// ── Tab BOQ ──────────────────────────────────────────────────────────────────
// Tab-nya sendiri cuma ringkasan read-only (pola sama tab BOQ di WO asli,
// yang menampilkan progress table, bukan editor langsung). Kelola BOQ
// (tambah/edit/hapus) dilakukan lewat modal #sqBoqManageModal — replikasi
// penuh halaman /boq (boq/create.blade.php), termasuk behavior dua-fase-nya:
// section BARU langsung punya field Qty/Satuan/Harga yang terlihat & bisa
// diisi (belum tersimpan ke server sampai "Simpan BOQ" diklik), sedangkan
// section yang SUDAH ADA di DB tampil read-only dengan tombol Edit sendiri
// (langsung tersimpan begitu "Simpan" di section itu diklik).
let sqWoBoqCache = [];
let sqBoqManageNewSections = [];
let sqBoqManageSeq = 1;

function loadSqWoBoq(idSqWo, afterCb) {
    $.get(window.sqWoRoute.boqShow + idSqWo, function (sections) {
        sqWoBoqCache = sections || [];
        renderSqWoBoqSummary(sqWoBoqCache);
        if (afterCb) afterCb();
    }).fail(function () {
        $('#sqWoBoqSummary').html('<div class="text-center text-danger py-3">Gagal memuat BOQ.</div>');
    });
}

function renderSqWoBoqSummary(sections) {
    if (!sections.length) {
        $('#sqWoBoqSummary').html('<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
            'Belum ada data BOQ untuk Work Order ini</div>');
        return;
    }

    // Markup & style disalin dari renderBoqProgressTable() (work-order/index.js),
    // tanpa kolom FWO Qty/Sisa (SQ tidak punya FWO) dan nama item bukan link.
    const TH = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';
    const totalAmount = sections.reduce((sum, s) => sum + Math.max(0, (Number(s.qty) || 0) * (Number(s.harga) || 0) - (Number(s.discount) || 0)), 0);

    const rows = sections.map(function (sec, idx) {
        const gross = (Number(sec.qty) || 0) * (Number(sec.harga) || 0);
        const total = Math.max(0, gross - (Number(sec.discount) || 0));
        const discHtml = Number(sec.discount) > 0
            ? `<span style="color:#dc2626;">− Rp ${Number(sec.discount).toLocaleString('en-US')}</span>` +
              (gross > 0 ? `<div style="font-size:10px;color:#94a3b8;">${(sec.discount / gross * 100).toFixed(2).replace(/\.?0+$/, '')}%</div>` : '')
            : '<span style="color:#cbd5e1;">—</span>';
        return `<tr class="sq-boq-summary-row" data-search="${escHtml((sec.point_name || '').toLowerCase())}">
            <td ${TD} style="width:40px;text-align:center;color:#94a3b8;">${idx + 1}</td>
            <td ${TD}><span class="fw-semibold" style="color:#1a56db;">${escHtml(sec.point_name || '—')}</span></td>
            <td ${TD} style="color:#64748b;">${sec.satuan ? escHtml(sec.satuan) : '—'}</td>
            <td ${TD} style="text-align:right;font-weight:600;">${sec.qty ?? 0}</td>
            <td ${TD} style="text-align:right;">${sec.harga > 0 ? 'Rp ' + Number(sec.harga).toLocaleString('en-US') : '<span style="color:#cbd5e1;">—</span>'}</td>
            <td ${TD} style="text-align:right;">${discHtml}</td>
            <td ${TD} style="text-align:right;font-weight:600;color:#1d4ed8;">${sec.harga > 0 ? 'Rp ' + total.toLocaleString('en-US') : '<span style="color:#cbd5e1;">—</span>'}</td>
        </tr>`;
    }).join('');

    const summary = totalAmount > 0
        ? `<div class="mb-3 d-flex align-items-center gap-2">
               <span class="small text-muted">Total BOQ</span>
               <span style="font-size:11px;background:#eff6ff;color:#1d4ed8;padding:2px 8px;border-radius:20px;font-weight:600;">
                   <i class="fa-solid fa-tag me-1" style="font-size:10px;"></i>Rp ${Number(totalAmount).toLocaleString('en-US')}
               </span>
           </div>` : '';

    const searchBar = `<div class="mb-2 d-flex align-items-center gap-2">
        <div class="input-group input-group-sm" style="max-width:280px;">
            <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0;">
                <i class="fa-solid fa-magnifying-glass text-muted" style="font-size:11px;"></i>
            </span>
            <input type="text" id="sqBoqSearchInput" class="form-control" placeholder="Cari item BOQ..."
                style="border-color:#e2e8f0;font-size:12px;" data-no-disable>
            <button type="button" id="btnClearSqBoqSearch" class="btn btn-outline-secondary d-none"
                style="border-color:#e2e8f0;font-size:11px;" title="Hapus pencarian">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    </div>`;

    $('#sqWoBoqSummary').html(summary + searchBar + `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;min-width:800px;white-space:nowrap;">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th ${TH} style="width:40px;">No</th>
                    <th ${TH} style="min-width:200px;">Item BOQ</th>
                    <th ${TH} style="min-width:80px;">Satuan</th>
                    <th ${TH} style="min-width:80px;text-align:right;">BOQ Qty</th>
                    <th ${TH} style="min-width:110px;text-align:right;">Harga</th>
                    <th ${TH} style="min-width:110px;text-align:right;">Discount</th>
                    <th ${TH} style="min-width:120px;text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`);
}

$(document).on('input', '#sqBoqSearchInput', function () {
    const q = $(this).val().toLowerCase().trim();
    $('#sqWoBoqSummary .sq-boq-summary-row').each(function () {
        $(this).toggle(!q || ($(this).data('search') || '').toString().includes(q));
    });
    $('#btnClearSqBoqSearch').toggleClass('d-none', !q);
});
$(document).on('click', '#btnClearSqBoqSearch', function () {
    $('#sqBoqSearchInput').val('').trigger('input');
});

function initSatuanSelectSqWo($select, idVal, labelVal) {
    if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
    $select.empty();
    if (idVal && labelVal) $select.append(new Option(labelVal, idVal, true, true));
    $select.select2({
        width: '100%', placeholder: '— Pilih —', allowClear: true, minimumInputLength: 0,
        dropdownParent: $select.closest('.modal'),
        ajax: {
            url: window.sqWoRoute.select2Satuan, dataType: 'json', delay: 200,
            data: (p) => ({ q: p.term || '' }), processResults: (d) => ({ results: d }), cache: true,
        },
        language: {
            noResults: () => `<span>Tidak ditemukan. <a href="/satuan/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`,
        },
        escapeMarkup: (m) => m,
    });
}

function renderSqBoqItemsList(items) {
    if (!items.length) return '<div class="text-muted small py-2">Tidak ada Testing Item</div>';
    return items.map((it, j) => `
        <div class="d-flex align-items-center flex-wrap gap-2" style="padding:5px 0;border-bottom:1px solid #f1f5f9;">
            <span class="text-muted small fw-semibold">${j + 1}.</span>
            <span class="fw-semibold small">${escHtml(it.judul_indonesia || '—')}</span>
            <span class="text-muted small">/ ${escHtml(it.judul_inggris || '—')}</span>
            <span class="item-meta-badge">${escHtml(it.kode_unit || '—')} · ${escHtml(String(it.nilai ?? '—'))}</span>
        </div>`).join('');
}

// Baris total: qty × harga − discount = subtotal (pola sama boqTotalHtml() di /boq)
function sqBoqTotalHtml(qty, harga, discount) {
    if (!(qty && harga)) return '';
    const gross = qty * harga;
    const disc = Math.min(Number(discount) || 0, gross);
    let html = Number(qty).toLocaleString('en-US') + ' qty &times; Rp ' + Number(harga).toLocaleString('en-US');
    if (disc > 0) html += ' &minus; Rp ' + disc.toLocaleString('en-US') + ' (discount)';
    return html + ' = <strong style="color:#1d4ed8;">Rp ' + (gross - disc).toLocaleString('en-US') + '</strong>';
}

// ══ Modal "Kelola BOQ" — replikasi penuh /boq ═══════════════════════════════

$(document).on('click', '.btn-kelola-sq-boq', function () {
    openSqBoqManageModal();
});

function openSqBoqManageModal() {
    sqBoqManageNewSections = [];
    $('#sqBoqManageWoLabel').text(currentSqWoData.no_sq_wo || '—');
    $('#sqBoqManageJudul').text(currentSqWoData.judul_pekerjaan || '—');

    const banner = [];
    if (currentSqWoData.nama_site_pelanggan_pekerjaan) {
        banner.push(`<div class="d-flex align-items-center gap-2"><i class="fa-solid fa-location-dot" style="color:#0891b2;"></i><span style="color:#0e7490;font-weight:600;">${escHtml(currentSqWoData.nama_site_pelanggan_pekerjaan)}</span></div>`);
    }
    banner.push(`<div class="d-flex align-items-center gap-2"><i class="fa-solid fa-briefcase" style="color:#1a56db;"></i><span style="font-weight:700;color:#1a56db;">${escHtml(currentSqWoData.no_sq_wo || '—')}</span></div>`);
    banner.push(`<div class="d-flex align-items-center gap-2"><i class="fa-solid fa-file-lines" style="color:#374151;"></i><span>${escHtml(currentSqWoData.judul_pekerjaan || '—')}</span></div>`);
    $('#sqBoqManageBanner').html(banner.join('<div style="width:1px;height:16px;background:#e2e8f0;"></div>'));

    $.get(window.sqWoRoute.boqShow + currentSqWoId, function (sections) {
        sqWoBoqCache = sections || [];
        renderSqBoqManageAll();
    });

    new bootstrap.Modal(document.getElementById('sqBoqManageModal')).show();
}

function sqBoqManageSectionHtml(sec, isNew) {
    const items = sec.items || [];
    const key = isNew ? sec.tmpId : sec.id_sq_boq;
    const attr = isNew ? `data-tmp-id="${key}"` : `data-id-sq-boq="${key}"`;
    const badge = isNew ? '' : `<span class="badge" style="font-size:10px;background:#e0e7ef;color:#475569;">Sudah ada</span>`;
    const editBtn = isNew
        ? `<button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 btn-sq-boq-manage-edit-new" style="font-size:12px;"><i class="fa-solid fa-pen me-1"></i> Edit</button>`
        : `<button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 btn-sq-boq-manage-edit-existing" style="font-size:12px;"><i class="fa-solid fa-pen me-1"></i> Edit</button>`;
    const deleteBtn = isNew
        ? `<button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 btn-sq-boq-manage-remove-new" style="font-size:12px;"><i class="fa-solid fa-trash me-1"></i> Hapus</button>`
        : `<button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 btn-sq-boq-manage-delete-existing" style="font-size:12px;"><i class="fa-solid fa-trash me-1"></i> Hapus</button>`;

    return `
    <div class="card mb-4 sq-boq-manage-section" ${attr} data-point-id="${sec.id_testing_point}">
        <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <i class="fa-solid fa-chevron-right btn-sq-boq-manage-toggle"></i>
                <i class="fa-solid fa-layer-group" style="color:#2563eb;"></i>
                <span class="fw-semibold">${escHtml(sec.point_name)}</span>
                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary" style="font-size:11px;">${items.length} item</span>
                ${badge}
            </div>
            <div class="d-flex gap-2">${editBtn}${deleteBtn}</div>
        </div>
        <div class="card-body px-3 py-3" style="display:${isNew ? 'block' : 'none'};">
            ${isNew ? renderSqBoqNewFieldsBody(sec) : renderSqBoqViewBody(sec)}
        </div>
    </div>`;
}

// Section BARU (belum tersimpan) — field selalu terlihat & bisa diisi
// langsung, sama seperti .section-fields di addSection() /boq.
function renderSqBoqNewFieldsBody(sec) {
    return `
        <div class="section-fields" style="background:#fafbfc;border:1px solid #e9ecef;border-radius:6px;padding:12px 14px;margin-bottom:14px;">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label form-label-sm text-muted mb-1">Item Produk Alternatif</label>
                    <input type="text" class="form-control form-control-sm sq-boq-new-item-produk" placeholder="opsional" value="${escHtml(sec.item_produk_alternate || '')}">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Qty</label>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm input-num-mask input-num-int sq-boq-new-qty" placeholder="0" value="${sec.qty || ''}">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Satuan</label>
                    <select class="form-select form-select-sm sq-boq-new-satuan"></select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Harga (Rp)</label>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm input-num-mask sq-boq-new-harga" placeholder="0" value="${sec.harga || ''}">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Discount (Rp)</label>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm input-num-mask input-num-int sq-boq-new-discount" placeholder="0" value="${sec.discount || 0}">
                </div>
                <div class="col-md-12">
                    <div class="sq-boq-new-total-line text-end" style="font-size:12px;color:#64748b;min-height:18px;margin-bottom:2px;">${sqBoqTotalHtml(sec.qty, sec.harga, sec.discount)}</div>
                </div>
                <div class="col-md-12">
                    <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                    <input type="text" class="form-control form-control-sm sq-boq-new-ket" placeholder="opsional" value="${escHtml(sec.keterangan || '')}">
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-1 boq-items-toggle" style="cursor:pointer;user-select:none;">
            <span class="text-muted small fw-semibold">
                <i class="fa-solid fa-list-check me-1"></i> Items
            </span>
            <i class="fa-solid fa-chevron-up text-muted boq-items-chevron" style="font-size:11px;transition:transform .2s;"></i>
        </div>
        <div class="boq-items">${renderSqBoqItemsList(sec.items || [])}</div>`;
}

// View (readonly) — field sama persis dengan renderExistingViewBody() di /boq.
function renderSqBoqViewBody(sec) {
    return `
        <div style="background:#f8fafc;border:1px solid #e9ecef;border-radius:6px;padding:10px 14px;margin-bottom:12px;">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label form-label-sm text-muted mb-1">Item Produk Alternatif</label>
                    <p class="form-control form-control-sm mb-0">${escHtml(sec.item_produk_alternate ?? '—')}</p>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Qty</label>
                    <p class="form-control form-control-sm mb-0">${escHtml(String(sec.qty ?? '—'))}</p>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Satuan</label>
                    <p class="form-control form-control-sm mb-0">${escHtml(sec.satuan ?? '—')}</p>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Harga (Rp)</label>
                    <p class="form-control form-control-sm mb-0">${sec.harga ? Number(sec.harga).toLocaleString('en-US') : '—'}</p>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Discount (Rp)</label>
                    <p class="form-control form-control-sm mb-0">${Number(sec.discount) > 0 ? Number(sec.discount).toLocaleString('en-US') : '—'}</p>
                </div>
                <div class="col-md-12">
                    <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                    <p class="form-control form-control-sm mb-0">${escHtml(sec.keterangan ?? '—')}</p>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-1">
            <div class="d-flex align-items-center gap-2 boq-items-toggle" style="cursor:pointer;user-select:none;flex:1;">
                <span class="text-muted small fw-semibold">
                    <i class="fa-solid fa-list-check me-1"></i> Items
                </span>
                <i class="fa-solid fa-chevron-up text-muted boq-items-chevron" style="font-size:11px;transition:transform .2s;"></i>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-sq-boq-manage-edit-items py-0 px-2" style="font-size:11px;">
                <i class="fa-solid fa-pen me-1"></i> Ubah Item
            </button>
        </div>
        <div class="boq-items">${renderSqBoqItemsList(sec.items || [])}</div>`;
}

// Edit (section yang SUDAH ADA di DB) — semua field bisa diubah, simpan
// langsung ke server — sama persis renderExistingEditBody() di /boq.
function renderSqBoqEditBody(sec) {
    return `
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:10px 14px;margin-bottom:12px;">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label form-label-sm text-muted mb-1">Item Produk Alternatif</label>
                    <input type="text" class="form-control form-control-sm sq-boq-edit-item-produk" value="${escHtml(sec.item_produk_alternate ?? '')}">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Qty</label>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm input-num-mask input-num-int sq-boq-edit-qty" value="${sec.qty ?? ''}">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Satuan</label>
                    <select class="form-select form-select-sm sq-boq-edit-satuan"></select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Harga (Rp)</label>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm input-num-mask sq-boq-edit-harga" value="${sec.harga ?? ''}">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Discount (Rp)</label>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm input-num-mask input-num-int sq-boq-edit-discount" value="${sec.discount ?? 0}">
                </div>
                <div class="col-md-12">
                    <div class="sq-boq-edit-total-line text-end" style="font-size:12px;color:#64748b;min-height:18px;margin-bottom:2px;">${sqBoqTotalHtml(sec.qty, sec.harga, sec.discount)}</div>
                </div>
                <div class="col-md-12">
                    <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                    <input type="text" class="form-control form-control-sm sq-boq-edit-ket" value="${escHtml(sec.keterangan ?? '')}">
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 btn-sq-boq-manage-cancel-existing" style="font-size:12px;">Batal</button>
                <button type="button" class="btn btn-sm btn-primary py-1 px-2 btn-sq-boq-manage-save-existing" style="font-size:12px;">
                    <i class="fa-solid fa-check me-1"></i> Simpan
                </button>
            </div>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-1">
            <div class="d-flex align-items-center gap-2 boq-items-toggle" style="cursor:pointer;user-select:none;flex:1;">
                <span class="text-muted small fw-semibold">
                    <i class="fa-solid fa-list-check me-1"></i> Items
                </span>
                <i class="fa-solid fa-chevron-up text-muted boq-items-chevron" style="font-size:11px;transition:transform .2s;"></i>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-sq-boq-manage-edit-items py-0 px-2" style="font-size:11px;">
                <i class="fa-solid fa-pen me-1"></i> Ubah Item
            </button>
        </div>
        <div class="boq-items">${renderSqBoqItemsList(sec.items || [])}</div>`;
}

function renderSqBoqManageAll() {
    const hasAny = sqWoBoqCache.length || sqBoqManageNewSections.length;
    $('#sqBoqManageEmpty').toggleClass('d-none', !!hasAny);
    $('#sqBoqManageSections').html(
        sqWoBoqCache.map((sec) => sqBoqManageSectionHtml(sec, false)).join('') +
        sqBoqManageNewSections.map((sec) => sqBoqManageSectionHtml(sec, true)).join('')
    );

    sqBoqManageNewSections.forEach(function (sec) {
        const $card = $(`.sq-boq-manage-section[data-tmp-id="${sec.tmpId}"]`);
        initSatuanSelectSqWo($card.find('.sq-boq-new-satuan'), sec.id_satuan || null, sec.satuan || null);
        initNumericMask($card[0]);
    });

    $('#btnSqBoqManageSave').prop('disabled', sqBoqManageNewSections.length === 0);
}

// Expand/collapse daftar Items (sama seperti .boq-items-toggle di /boq).
$(document).on('click', '.boq-items-toggle', function () {
    let $items = $(this).next('.boq-items');
    if (!$items.length) $items = $(this).parent().next('.boq-items');
    $items.slideToggle(180);
    $(this).find('.boq-items-chevron').toggleClass('collapsed');
});

// Expand/collapse per section (klik ikon chevron saja, sama seperti /boq).
$(document).on('click', '.btn-sq-boq-manage-toggle', function () {
    $(this).closest('.sq-boq-manage-section').find('.card-body').slideToggle(150);
    $(this).toggleClass('rotated');
});

// Live update baris Total saat Qty/Harga/Discount diketik (baru maupun edit).
$(document).on('input', '.sq-boq-new-qty, .sq-boq-new-harga, .sq-boq-new-discount', function () {
    const $card = $(this).closest('.sq-boq-manage-section');
    const qty = rawNumVal($card.find('.sq-boq-new-qty')[0]) || 0;
    const harga = rawNumVal($card.find('.sq-boq-new-harga')[0]) || 0;
    const discount = rawNumVal($card.find('.sq-boq-new-discount')[0]) || 0;
    $card.find('.sq-boq-new-total-line').html(sqBoqTotalHtml(qty, harga, discount));
});
$(document).on('input', '.sq-boq-edit-qty, .sq-boq-edit-harga, .sq-boq-edit-discount', function () {
    const $card = $(this).closest('.sq-boq-manage-section');
    const qty = rawNumVal($card.find('.sq-boq-edit-qty')[0]) || 0;
    const harga = rawNumVal($card.find('.sq-boq-edit-harga')[0]) || 0;
    const discount = rawNumVal($card.find('.sq-boq-edit-discount')[0]) || 0;
    $card.find('.sq-boq-edit-total-line').html(sqBoqTotalHtml(qty, harga, discount));
});

// ── Section BARU (belum tersimpan): Edit checklist / Hapus dari daftar ──────
$(document).on('click', '.btn-sq-boq-manage-edit-new', function () {
    const tmpId = $(this).closest('.sq-boq-manage-section').data('tmpId');
    openSqBoqSectionModal({ mode: 'edit-new', tmpId, sec: sqBoqManageNewSections.find((s) => String(s.tmpId) === String(tmpId)) });
});

$(document).on('click', '.btn-sq-boq-manage-remove-new', function () {
    const tmpId = $(this).closest('.sq-boq-manage-section').data('tmpId');
    sqBoqManageNewSections = sqBoqManageNewSections.filter((s) => String(s.tmpId) !== String(tmpId));
    renderSqBoqManageAll();
});

// ── Section SUDAH ADA: toggle Edit Qty/Satuan/Harga inline, simpan langsung ─
$(document).on('click', '.btn-sq-boq-manage-edit-existing', function () {
    const $card = $(this).closest('.sq-boq-manage-section');
    const sec = sqWoBoqCache.find((b) => String(b.id_sq_boq) === String($card.data('idSqBoq')));
    if (!sec) return;
    $card.find('.card-body').show();
    $card.find('.card-body').html(renderSqBoqEditBody(sec));
    initSatuanSelectSqWo($card.find('.sq-boq-edit-satuan'), sec.id_satuan || null, sec.satuan || null);
    initNumericMask($card[0]);
});

$(document).on('click', '.btn-sq-boq-manage-cancel-existing', function () {
    const $card = $(this).closest('.sq-boq-manage-section');
    const sec = sqWoBoqCache.find((b) => String(b.id_sq_boq) === String($card.data('idSqBoq')));
    if (!sec) return;
    $card.find('.card-body').html(renderSqBoqViewBody(sec));
});

$(document).on('click', '.btn-sq-boq-manage-save-existing', function () {
    const $card = $(this).closest('.sq-boq-manage-section');
    const $body = $card.find('.card-body');
    const idSqBoq = $card.data('idSqBoq');
    const sec = sqWoBoqCache.find((b) => String(b.id_sq_boq) === String(idSqBoq));
    if (!sec) return;

    const qty = rawNumVal($body.find('.sq-boq-edit-qty')[0]);
    const idSatuan = $body.find('.sq-boq-edit-satuan').val() || null;
    if (!qty) { Notify.warning('Qty harus diisi.'); return; }
    if (!idSatuan) { Notify.warning('Satuan harus diisi.'); return; }

    const updated = {
        id_testing_point: sec.id_testing_point,
        item_produk_alternate: $body.find('.sq-boq-edit-item-produk').val() || null,
        qty: qty, id_satuan: idSatuan,
        harga: rawNumVal($body.find('.sq-boq-edit-harga')[0]) || 0,
        discount: rawNumVal($body.find('.sq-boq-edit-discount')[0]) || 0,
        keterangan: $body.find('.sq-boq-edit-ket').val() || null,
        items: (sec.items || []).map((it) => it.id_testing_item),
    };
    saveExistingSqBoqSections(updated, idSqBoq, 'Item BOQ tersimpan');
});

// Hapus section yang sudah ada di DB — langsung ke server, minimal 1 item BOQ
// harus tersisa (pola sama btn-existing-delete di /boq).
$(document).on('click', '.btn-sq-boq-manage-delete-existing', function () {
    const $card = $(this).closest('.sq-boq-manage-section');
    const idSqBoq = $card.data('idSqBoq');
    const sec = sqWoBoqCache.find((b) => String(b.id_sq_boq) === String(idSqBoq));
    if (!sec) return;

    Notify.confirmDelete(`Hapus item BOQ "${sec.point_name}"?`, function () {
        const remaining = sqWoBoqCache.filter((b) => String(b.id_sq_boq) !== String(idSqBoq));
        if (!remaining.length && !sqBoqManageNewSections.length) {
            Notify.error('Tidak dapat menghapus item BOQ terakhir. Minimal harus ada 1 item BOQ pada Work Order ini.');
            return;
        }
        $.ajax({
            url: window.sqWoRoute.boqSave + currentSqWoId,
            method: 'POST',
            data: { _token: window.sqWoRoute.csrf, sections: remaining.map(sqBoqToPayload) },
            success: function () {
                Notify.success('Item BOQ berhasil dihapus');
                sqWoBoqCache = remaining;
                renderSqBoqManageAll();
                renderSqWoBoqSummary(sqWoBoqCache);
            },
            error: function (xhr) { Notify.error(xhr.responseJSON?.message || 'Gagal menghapus item BOQ'); },
        });
    });
});

function sqBoqToPayload(b) {
    return {
        id_testing_point: b.id_testing_point, item_produk_alternate: b.item_produk_alternate,
        qty: b.qty, id_satuan: b.id_satuan, harga: b.harga, discount: b.discount,
        keterangan: b.keterangan, items: (b.items || []).map((it) => it.id_testing_item),
    };
}

// Simpan 1 section yang sudah ada di DB (immediate PUT-style, tetap kirim
// seluruh daftar existing lain karena SqBoqController::save() whole-replace).
function saveExistingSqBoqSections(updatedSection, idSqBoq, successMsg) {
    const others = sqWoBoqCache.filter((b) => String(b.id_sq_boq) !== String(idSqBoq)).map(sqBoqToPayload);
    $.ajax({
        url: window.sqWoRoute.boqSave + currentSqWoId,
        method: 'POST',
        data: { _token: window.sqWoRoute.csrf, sections: others.concat([updatedSection]) },
        success: function () {
            Notify.success(successMsg || 'BOQ tersimpan');
            $.get(window.sqWoRoute.boqShow + currentSqWoId, function (sections) {
                sqWoBoqCache = sections || [];
                renderSqBoqManageAll();
                renderSqWoBoqSummary(sqWoBoqCache);
            });
        },
        error: function (xhr) { Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan BOQ'); },
    });
}

// ── "+ Tambah Item" (footer modal) — Testing Point + checklist, section
// hasilnya BELUM tersimpan sampai "Simpan BOQ" diklik ─────────────────────
$(document).on('click', '#btnSqBoqManageAdd', function () {
    openSqBoqSectionModal({ mode: 'add' });
});

// "Ubah Item" pada section yang sudah ada di DB — checklist diedit lewat
// modal yang sama, TP terkunci, simpan langsung ke server.
$(document).on('click', '.btn-sq-boq-manage-edit-items', function (e) {
    e.preventDefault();
    const idSqBoq = $(this).closest('.sq-boq-manage-section').data('idSqBoq');
    openSqBoqSectionModal({ mode: 'edit-existing', sec: sqWoBoqCache.find((b) => String(b.id_sq_boq) === String(idSqBoq)) });
});

function usedSqBoqPointIds(excludePtId) {
    const ids = new Set();
    sqWoBoqCache.forEach((b) => { if (String(b.id_testing_point) !== String(excludePtId)) ids.add(String(b.id_testing_point)); });
    sqBoqManageNewSections.forEach((b) => { if (String(b.id_testing_point) !== String(excludePtId)) ids.add(String(b.id_testing_point)); });
    return ids;
}

let sqBoqSectionModalCtx = { mode: 'add' };

function openSqBoqSectionModal(ctx) {
    sqBoqSectionModalCtx = ctx;
    const sec = ctx.sec || null;
    const $modal = $('#sqBoqSectionModal');
    $modal.find('#sqBoqSectionModalLabel').html(sec
        ? '<i class="fa-solid fa-pen me-2" style="color:#b45309;"></i>Ubah Item'
        : '<i class="fa-solid fa-layer-group me-2" style="color:#1a56db;"></i>Tambah Item');
    $modal.find('#sqBoqSectionModal-btn-save-text').text(sec ? 'Simpan Perubahan' : 'Tambah Item');

    const excludePtId = sec ? sec.id_testing_point : null;
    const $point = $modal.find('#sqBoqSectionModal-point');
    if ($point.hasClass('select2-hidden-accessible')) $point.select2('destroy');
    $point.empty();
    $point.select2({
        width: '100%', dropdownParent: $modal, placeholder: 'Ketik nama item...', allowClear: true, minimumInputLength: 0,
        ajax: {
            url: window.sqWoRoute.select2TestingPoint, dataType: 'json', delay: 250,
            data: (p) => ({ q: p.term }),
            // Testing Point yang sudah dipakai di section lain ditandai centang
            // hijau & tidak bisa dipilih lagi — bukan boleh dipilih lalu baru
            // ditolak sesudahnya — pola sama persis select2 di /boq.
            processResults: function (d) {
                const used = usedSqBoqPointIds(excludePtId);
                return {
                    results: d.map(function (item) {
                        const isUsed = used.has(String(item.id));
                        return Object.assign({}, item, { disabled: isUsed, _added: isUsed });
                    }),
                };
            },
            cache: false,
        },
        templateResult: function (item) {
            if (item.loading) return item.text;
            if (item._added) {
                return $('<span style="color:#16a34a;"><i class="fa-solid fa-circle-check me-1"></i>' + escHtml(item.text) + '</span>');
            }
            return item.text;
        },
        escapeMarkup: function (m) { return m; },
    });

    $point.off('select2:select').on('select2:select', function (e) {
        loadSqBoqSectionItems(e.params.data.id, null);
    });
    $point.off('select2:clear').on('select2:clear', resetSqBoqSectionItems);

    if (sec) {
        $point.append(new Option(sec.point_name || ('#' + sec.id_testing_point), sec.id_testing_point, true, true)).trigger('change.select2');
        $point.prop('disabled', true);
        loadSqBoqSectionItems(sec.id_testing_point, (sec.items || []).map((it) => String(it.id_testing_item)));
    } else {
        $point.prop('disabled', false);
        resetSqBoqSectionItems();
    }

    showStackedModal($modal[0]);
    initNumericMask($modal[0]);
}

function resetSqBoqSectionItems() {
    $('#sqBoqSectionModal-search').val('');
    $('#sqBoqSectionModal-search-empty').addClass('d-none');
    $('#sqBoqSectionModal-items-wrap').addClass('d-none');
    $('#sqBoqSectionModal-empty').removeClass('d-none');
    $('#sqBoqSectionModal-btn-save').prop('disabled', true);
}

function loadSqBoqSectionItems(ptId, preCheckedIds) {
    const $wrap = $('#sqBoqSectionModal-items');
    $('#sqBoqSectionModal-empty').addClass('d-none');
    $('#sqBoqSectionModal-items-wrap').removeClass('d-none');
    $wrap.html('<div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat item...</div>');
    $('#sqBoqSectionModal-btn-save').prop('disabled', true);

    $.get(window.sqWoRoute.itemsByPoint + ptId, function (res) {
        const items = res.data || [];
        if (!items.length) {
            $wrap.html('<div class="text-center text-muted py-3"><i class="fa-solid fa-inbox me-1 opacity-50"></i> Tidak ada item pada Testing Point ini</div>');
            return;
        }
        $wrap.html(items.map((it) => `
            <div class="modal-item-row d-flex align-items-center gap-3">
                <input type="checkbox" class="form-check-input sq-boq-modal-item-check flex-shrink-0 mt-0"
                    id="sqmitem_${it.id_testing_item}" value="${it.id_testing_item}"
                    ${!preCheckedIds || preCheckedIds.includes(String(it.id_testing_item)) ? 'checked' : ''}
                    data-item='${JSON.stringify(it).replace(/'/g, '&#39;')}'>
                <label class="d-flex justify-content-between align-items-center w-100 gap-2" for="sqmitem_${it.id_testing_item}" style="cursor:pointer;margin:0;">
                    <div>
                        <span class="fw-semibold">${escHtml(it.judul_indonesia || '—')}</span>
                        <span class="text-muted ms-1 small">/ ${escHtml(it.judul_inggris || '—')}</span>
                    </div>
                    <span class="item-meta-badge flex-shrink-0">${escHtml(it.kode_unit || '—')} · ${escHtml(String(it.nilai ?? '—'))}</span>
                </label>
            </div>`).join(''));
        $('#sqBoqSectionModal-btn-save').prop('disabled', $wrap.find('.sq-boq-modal-item-check:checked').length === 0);
    }).fail(function () {
        $wrap.html('<div class="text-center text-danger py-3">Gagal memuat Testing Item.</div>');
    });
}

$(document).on('change', '.sq-boq-modal-item-check', function () {
    $('#sqBoqSectionModal-btn-save').prop('disabled', $('#sqBoqSectionModal-items .sq-boq-modal-item-check:checked').length === 0);
});
// Pilih / Hapus semua — hanya item yang terlihat (sama seperti /boq)
$(document).on('click', '#sqBoqSectionModal-check-all', function (e) { e.preventDefault(); $('#sqBoqSectionModal-items .modal-item-row:not(.d-none) .sq-boq-modal-item-check').prop('checked', true).trigger('change'); });
$(document).on('click', '#sqBoqSectionModal-uncheck-all', function (e) { e.preventDefault(); $('#sqBoqSectionModal-items .modal-item-row:not(.d-none) .sq-boq-modal-item-check').prop('checked', false).trigger('change'); });
$(document).on('input', '#sqBoqSectionModal-search', function () {
    const term = $(this).val().toLowerCase();
    let visible = 0;
    $('#sqBoqSectionModal-items .modal-item-row').each(function () {
        const item = $(this).find('.sq-boq-modal-item-check').data('item') || {};
        const text = ((item.judul_indonesia || '') + ' ' + (item.judul_inggris || '')).toLowerCase();
        const match = !term || text.includes(term);
        $(this).toggleClass('d-none', !match);
        if (match) visible++;
    });
    $('#sqBoqSectionModal-search-empty').toggleClass('d-none', visible > 0);
});
$(document).on('hidden.bs.modal', '#sqBoqSectionModal', function () { $('#sqBoqSectionModal-point').prop('disabled', false).val(null); });

$(document).on('click', '#sqBoqSectionModal-btn-save', function () {
    const $modal = $('#sqBoqSectionModal');
    const ptId = $modal.find('#sqBoqSectionModal-point').val();
    if (!ptId) { Notify.warning('Testing Point wajib dipilih.'); return; }

    const checkedItems = [];
    $modal.find('.sq-boq-modal-item-check:checked').each(function () { checkedItems.push($(this).data('item')); });
    if (!checkedItems.length) { Notify.warning('Pilih minimal 1 Testing Item.'); return; }
    const itemIds = checkedItems.map((it) => it.id_testing_item);

    const ctx = sqBoqSectionModalCtx;

    if (ctx.mode === 'edit-existing') {
        const sec = ctx.sec;
        const updated = {
            id_testing_point: sec.id_testing_point, item_produk_alternate: sec.item_produk_alternate,
            qty: sec.qty, id_satuan: sec.id_satuan, harga: sec.harga, discount: sec.discount,
            keterangan: sec.keterangan, items: itemIds,
        };
        bootstrap.Modal.getInstance($modal[0])?.hide();
        saveExistingSqBoqSections(updated, sec.id_sq_boq, 'Item berhasil diperbarui');
        return;
    }

    if (ctx.mode === 'edit-new') {
        const idx = sqBoqManageNewSections.findIndex((s) => String(s.tmpId) === String(ctx.tmpId));
        if (idx > -1) sqBoqManageNewSections[idx].items = checkedItems;
        bootstrap.Modal.getInstance($modal[0])?.hide();
        renderSqBoqManageAll();
        return;
    }

    // mode 'add' — section baru, BELUM tersimpan sampai "Simpan BOQ" diklik.
    const ptText = $modal.find('#sqBoqSectionModal-point').select2('data')[0].text;
    sqBoqManageNewSections.push({
        tmpId: 'new_' + (sqBoqManageSeq++),
        id_testing_point: parseInt(ptId),
        point_name: ptText,
        item_produk_alternate: null, qty: null, id_satuan: null, satuan: null,
        harga: null, discount: 0, keterangan: null,
        items: checkedItems,
    });
    bootstrap.Modal.getInstance($modal[0])?.hide();
    renderSqBoqManageAll();
});

// ── "Simpan BOQ" (footer) — persis btnSave di /boq: validasi Qty & Satuan
// wajib utk tiap section baru, lalu simpan SEMUA (existing tak berubah +
// section baru) sekaligus dalam 1 whole-replace call. ─────────────────────
$(document).on('click', '#btnSqBoqManageSave', function () {
    if (!sqBoqManageNewSections.length) return;

    let valid = true;
    const newPayload = [];
    sqBoqManageNewSections.forEach(function (sec) {
        const $card = $(`.sq-boq-manage-section[data-tmp-id="${sec.tmpId}"]`);
        const qty = rawNumVal($card.find('.sq-boq-new-qty')[0]);
        const idSatuan = $card.find('.sq-boq-new-satuan').val() || null;
        if (!qty || !idSatuan) { valid = false; }
        newPayload.push({
            id_testing_point: sec.id_testing_point,
            item_produk_alternate: $card.find('.sq-boq-new-item-produk').val() || null,
            qty: qty || 0, id_satuan: idSatuan,
            harga: rawNumVal($card.find('.sq-boq-new-harga')[0]) || 0,
            discount: rawNumVal($card.find('.sq-boq-new-discount')[0]) || 0,
            keterangan: $card.find('.sq-boq-new-ket').val() || null,
            items: sec.items.map((it) => it.id_testing_item),
        });
    });

    if (!valid) { Notify.warning('Qty dan Satuan wajib diisi untuk setiap item baru.'); return; }

    const existingPayload = sqWoBoqCache.map(sqBoqToPayload);
    const $btn = $(this);
    Notify.confirm('Simpan BOQ?', function () {
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');
        $.ajax({
            url: window.sqWoRoute.boqSave + currentSqWoId,
            method: 'POST',
            data: { _token: window.sqWoRoute.csrf, sections: existingPayload.concat(newPayload) },
            success: function () {
                Notify.success('BOQ berhasil disimpan');
                sqBoqManageNewSections = [];
                $.get(window.sqWoRoute.boqShow + currentSqWoId, function (sections) {
                    sqWoBoqCache = sections || [];
                    renderSqBoqManageAll();
                    renderSqWoBoqSummary(sqWoBoqCache);
                });
            },
            error: function (xhr) { Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan BOQ'); },
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan BOQ');
        });
    });
});

// ── Tab BOQ Other / BOQ Sampling ─────────────────────────────────────────────
let sqWoTambahanCache = { lainnya: [], sampling: [] };

const SQ_TAMBAHAN_LABEL = { lainnya: 'BOQ Other', sampling: 'BOQ Sampling' };
const SQ_TAMBAHAN_ICON  = { lainnya: 'fa-file-invoice', sampling: 'fa-vial-virus' };

function loadSqWoTambahan(idSqWo) {
    const spinner = '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>';
    $('#sqWoBoqOtherTable, #sqWoBoqSamplingTable').html(spinner);
    $.get(window.sqWoRoute.tambahanList + idSqWo + '/list', function (res) {
        sqWoTambahanCache = res;
        $('#sqWoBoqOtherTable').html(renderSqWoTambahanTable(res.lainnya || [], 'lainnya'));
        $('#sqWoBoqSamplingTable').html(renderSqWoTambahanTable(res.sampling || [], 'sampling'));
    }).fail(function () {
        $('#sqWoBoqOtherTable, #sqWoBoqSamplingTable').html('<div class="text-center text-danger py-4">Gagal memuat data.</div>');
    });
}

// Markup disalin dari renderWoBoqTambahanList() (work-order/index.js).
function renderSqWoTambahanTable(rows, jenis) {
    if (!rows.length) {
        return `<div class="text-center text-muted py-4">
            <i class="fa-solid ${SQ_TAMBAHAN_ICON[jenis]} fa-2x d-block mb-2 opacity-25"></i>
            Belum ada item ${SQ_TAMBAHAN_LABEL[jenis]}
        </div>`;
    }

    const total = rows.reduce((sum, r) => sum + (r.qty || 0) * (r.harga || 0), 0);
    const rowsHtml = rows.map(function (r, i) {
        const subtotal = (r.qty || 0) * (r.harga || 0);
        return `<tr data-id="${r.id_sq_boq_tambahan}" data-jenis="${jenis}">
            <td style="font-size:12px;color:#94a3b8;width:36px;">${i + 1}</td>
            <td style="font-size:12px;">${escHtml(r.nama_item)}</td>
            <td style="font-size:12px;text-align:right;white-space:nowrap;">${r.qty ?? 0} ${escHtml(r.satuan || '')}</td>
            <td style="font-size:12px;text-align:right;white-space:nowrap;">Rp ${Number(r.harga || 0).toLocaleString('id-ID')}</td>
            <td style="font-size:12px;text-align:right;white-space:nowrap;font-weight:600;">Rp ${Number(subtotal).toLocaleString('id-ID')}</td>
            <td style="font-size:12px;">${escHtml(r.keterangan || '—')}</td>
            <td class="text-center" style="width:72px;white-space:nowrap;">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-edit-sq-tambahan"
                    title="Edit" style="font-size:11px;">
                    <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-remove-sq-tambahan"
                    data-nama="${escHtml(r.nama_item)}" title="Hapus" style="font-size:11px;">
                    <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
                </button>
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

$(document).on('click', '.btn-sq-tambahan-add', function () {
    openSqTambahanModal($(this).data('jenis'), null);
});

$(document).on('click', '.btn-edit-sq-tambahan', function () {
    const $tr = $(this).closest('tr');
    const jenis = $tr.data('jenis');
    const id = $tr.data('id');
    const row = (sqWoTambahanCache[jenis] || []).find((r) => String(r.id_sq_boq_tambahan) === String(id));
    openSqTambahanModal(jenis, row);
});

$(document).on('click', '.btn-remove-sq-tambahan', function () {
    const id = $(this).closest('tr').data('id');
    const nama = $(this).data('nama');
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
            url: window.sqWoRoute.tambahanDelete + id,
            method: 'DELETE',
            data: { _token: window.sqWoRoute.csrf },
            success: function () { Notify.success('Item berhasil dihapus.'); loadSqWoTambahan(currentSqWoId); },
            error: function (xhr) { Notify.error(xhr.responseJSON?.message || 'Gagal menghapus item.'); },
        });
    });
});

function openSqTambahanModal(jenis, row) {
    const $modal = $('#sqBoqTambahanModal');
    const label = jenis === 'lainnya' ? 'BOQ Other' : 'BOQ Sampling';
    $modal.find('#sqBoqTambahanModalLabel').html(`<i class="fa-solid fa-file-invoice me-2" style="color:#b45309;"></i>${row ? 'Edit' : 'Tambah'} Item ${label}`);
    $modal.find('#sqBoqTambahanModal-id').val(row ? row.id_sq_boq_tambahan : '');
    $modal.find('#sqBoqTambahanModal-jenis').val(jenis);
    $modal.find('#sqBoqTambahanModal-id-sq-wo').val(currentSqWoId);
    $modal.find('#sqBoqTambahanModal-nama').val(row ? row.nama_item : '');
    $modal.find('#sqBoqTambahanModal-qty').val(row ? row.qty : '');
    $modal.find('#sqBoqTambahanModal-harga').val(row ? row.harga : '');
    $modal.find('#sqBoqTambahanModal-keterangan').val(row ? (row.keterangan || '') : '');
    initSatuanSelectSqWo($modal.find('#sqBoqTambahanModal-satuan'), row?.id_satuan || null, row?.satuan || null);

    new bootstrap.Modal($modal[0]).show();
    initNumericMask($modal[0]);
}

$(document).on('click', '#sqBoqTambahanModal-btn-save', function () {
    const $modal = $('#sqBoqTambahanModal');
    const id = $modal.find('#sqBoqTambahanModal-id').val();
    const payload = {
        _token: window.sqWoRoute.csrf,
        id_sq_wo: $modal.find('#sqBoqTambahanModal-id-sq-wo').val(),
        jenis: $modal.find('#sqBoqTambahanModal-jenis').val(),
        nama_item: $modal.find('#sqBoqTambahanModal-nama').val().trim(),
        qty: rawNumVal($modal.find('#sqBoqTambahanModal-qty')[0]),
        id_satuan: $modal.find('#sqBoqTambahanModal-satuan').val() || null,
        harga: rawNumVal($modal.find('#sqBoqTambahanModal-harga')[0]),
        keterangan: $modal.find('#sqBoqTambahanModal-keterangan').val().trim() || null,
    };
    if (!payload.nama_item) { Notify.warning('Nama item wajib diisi.'); return; }
    if (!payload.qty) { Notify.warning('Qty wajib diisi.'); return; }

    const isEdit = !!id;
    if (isEdit) payload._method = 'PUT';
    const $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');
    $.post(isEdit ? window.sqWoRoute.tambahanUpdate + id : window.sqWoRoute.tambahanStore, payload)
        .done(function () {
            bootstrap.Modal.getInstance($modal[0])?.hide();
            Notify.success('Item berhasil disimpan.');
            loadSqWoTambahan(currentSqWoId);
        })
        .fail(function (xhr) { Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan item.'); })
        .always(function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan'); });
});

// ── Tab Budget ───────────────────────────────────────────────────────────────
function loadSqWoBudget(idSqWo) {
    $('#sqWoBudgetContent').html('<div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');
    $.get(window.sqWoRoute.budgetList + idSqWo + '/list', function (plans) {
        renderSqWoBudgetList(plans || []);
    }).fail(function () {
        $('#sqWoBudgetContent').html('<div class="text-center text-danger py-3">Gagal memuat Budget.</div>');
    });
}

function renderSqWoBudgetList(plans) {
    if (!plans.length) {
        $('#sqWoBudgetContent').html(`<div class="text-center text-muted py-4">
            <i class="fa-solid fa-wallet fa-2x d-block mb-2 opacity-25"></i> Belum ada Budget Plan.
        </div>`);
        return;
    }

    const cards = plans.map(function (p) {
        const itemRows = p.items.map(function (item, idx) {
            const caBadge = item.is_cash_advance
                ? `<span class="badge ms-1" style="background:#eff6ff;color:#1d4ed8;font-size:10px;font-weight:500;border:1px solid #bfdbfe;">CA</span>` : '';
            const categoryCell = item.nama_category
                ? `<span style="font-size:11px;color:#6b7280;background:#f1f5f9;padding:1px 6px;border-radius:4px;">${escHtml(item.nama_category)}</span>`
                : `<span class="text-muted" style="font-size:11px;">—</span>`;
            return `<tr>
                <td style="width:36px;text-align:center;color:#94a3b8;font-size:11px;">${idx + 1}</td>
                <td>${categoryCell}</td>
                <td><span class="fw-semibold">${escHtml(item.nama_account)}</span>${caBadge}</td>
                <td style="color:#1d4ed8;font-weight:600;">${formatRupiahSqWo(item.nominal_budget)}</td>
                <td>${escHtml(item.keterangan || '—')}</td>
            </tr>`;
        }).join('');

        return `<div class="mb-3 border rounded" data-id-sq-budget="${p.id_sq_budget}">
            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                style="background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:calc(0.375rem - 1px) calc(0.375rem - 1px) 0 0;">
                <div class="d-flex align-items-center flex-wrap gap-1">
                    <span class="fw-bold" style="font-size:13px;">${escHtml(p.label)}</span>
                    ${(p.hari_mulai || p.hari_selesai) ? `<span class="text-muted ms-1" style="font-size:11px;">
                        <i class="fa-regular fa-calendar me-1"></i>Hari ke-${p.hari_mulai ?? '?'}${p.hari_selesai ? ' – ke-' + p.hari_selesai : ''}
                    </span>` : ''}
                    ${p.keterangan ? `<span class="text-muted ms-1" style="font-size:11px;">· ${escHtml(p.keterangan)}</span>` : ''}
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span style="font-size:12px;">Total: <b style="color:#1d4ed8;">${formatRupiahSqWo(p.total_budget)}</b></span>
                    <button type="button" class="pm-btn-icon btn-edit-sq-wo-budget" title="Edit" data-no-disable>
                        <i class="fa-solid fa-pen" style="font-size:11px;"></i>
                    </button>
                    <button type="button" class="pm-btn-icon btn-remove-sq-wo-budget" title="Hapus" data-no-disable style="color:#dc2626;">
                        <i class="fa-solid fa-trash" style="font-size:11px;"></i>
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead style="background:#fff;"><tr>
                        <th style="width:36px;"></th><th>Kategori</th><th>Account</th><th>Nominal</th><th>Keterangan</th>
                    </tr></thead>
                    <tbody>${itemRows}</tbody>
                </table>
            </div>
        </div>`;
    }).join('');

    $('#sqWoBudgetContent').html(cards);
}

$(document).on('click', '.btn-sq-wo-budget-add', function () {
    openSqWoBudgetModal(null);
});

$(document).on('click', '.btn-edit-sq-wo-budget', function () {
    const id = $(this).closest('[data-id-sq-budget]').data('idSqBudget');
    $.get(window.sqWoRoute.budgetShow + id, function (data) { openSqWoBudgetModal(data); });
});

$(document).on('click', '.btn-remove-sq-wo-budget', function () {
    const id = $(this).closest('[data-id-sq-budget]').data('idSqBudget');
    Notify.confirmDelete('Hapus Budget Plan ini?', function () {
        $.ajax({
            url: window.sqWoRoute.budgetDelete + id,
            method: 'DELETE',
            data: { _token: window.sqWoRoute.csrf },
            success: function () { Notify.success('Budget Plan dihapus'); loadSqWoBudget(currentSqWoId); },
            error: function () { Notify.error('Gagal menghapus Budget Plan'); },
        });
    });
});

function initSqWoBudgetAccountSelect2() {
    $('#sqWoBudgetItemsBody .bi-account').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) return;
        $(this).select2({
            dropdownParent: $('#sqWoBudgetPlanModal'),
            placeholder: 'Pilih Account', allowClear: false, width: '100%', minimumInputLength: 0,
            ajax: {
                url: window.sqWoRoute.select2Account, dataType: 'json', delay: 200,
                data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true,
            },
        });
    });
}

function buildSqWoBudgetItemRow(item) {
    return `<tr class="sq-wo-budget-item-row">
        <td>
            <input type="hidden" class="bi-id" value="${item ? item.id_sq_budget_item : ''}">
            <select class="form-select form-select-sm bi-account" data-no-disable style="min-width:160px;">
                ${item ? `<option value="${item.id_account}" selected>${escHtml(item.nama_account)}</option>` : '<option value="">Pilih Account</option>'}
            </select>
        </td>
        <td>
            <input type="text" inputmode="numeric" class="form-control form-control-sm input-num-mask input-num-int bi-nominal"
                value="${item ? Number(item.nominal_budget).toLocaleString('en-US') : ''}" placeholder="0" data-no-disable>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm bi-keterangan" value="${item ? escHtml(item.keterangan || '') : ''}" placeholder="Opsional" data-no-disable>
        </td>
        <td class="text-center">
            <div class="form-check d-flex justify-content-center mb-0">
                <input type="checkbox" class="form-check-input bi-ca" data-no-disable ${item && item.is_cash_advance ? 'checked' : ''} title="Cash Advance" style="width:18px;height:18px;cursor:pointer;">
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-sq-wo-budget-remove-row" data-no-disable><i class="fa-solid fa-trash"></i></button>
        </td>
    </tr>`;
}

function recalcSqWoBudgetTotal() {
    let total = 0;
    $('#sqWoBudgetItemsBody .bi-nominal').each(function () {
        total += parseInt(($(this).val() || '').replace(/,/g, ''), 10) || 0;
    });
    $('#sqWoBudgetModalTotal').text(formatRupiahSqWo(total));
}

function openSqWoBudgetModal(data) {
    const isEdit = !!data;
    $('#sqWoBudgetPlanModalLabel').html(`<i class="fa-solid fa-wallet me-2" style="color:#0f766e;"></i>${isEdit ? 'Edit' : 'Tambah'} Budget Plan`);
    $('#sqWoBudgetModal-id').val(isEdit ? data.id_sq_budget : '');
    $('#sqWoBudgetModal-id-sq-wo').val(currentSqWoId);
    $('#sqWoBudgetModal-label').val(isEdit ? data.label : '');
    $('#sqWoBudgetModal-keterangan').val(isEdit ? (data.keterangan || '') : '');
    $('#sqWoBudgetModal-hari-mulai').val(isEdit ? (data.hari_mulai || '') : '');
    $('#sqWoBudgetModal-hari-selesai').val(isEdit ? (data.hari_selesai || '') : '');

    const $body = $('#sqWoBudgetItemsBody').empty();
    const items = isEdit ? (data.items || []) : [];
    if (items.length) items.forEach((item) => $body.append(buildSqWoBudgetItemRow(item)));
    else $body.append(buildSqWoBudgetItemRow(null));

    initSqWoBudgetAccountSelect2();
    initNumericMask(document.getElementById('sqWoBudgetPlanModal'));
    recalcSqWoBudgetTotal();

    new bootstrap.Modal(document.getElementById('sqWoBudgetPlanModal')).show();
}

$(document).on('click', '#btnSqWoBudgetAddRow', function () {
    $('#sqWoBudgetItemsBody').append(buildSqWoBudgetItemRow(null));
    initSqWoBudgetAccountSelect2();
    initNumericMask(document.getElementById('sqWoBudgetPlanModal'));
});

$(document).on('click', '.btn-sq-wo-budget-remove-row', function () {
    $(this).closest('tr').remove();
    recalcSqWoBudgetTotal();
});

$(document).on('input', '#sqWoBudgetItemsBody .bi-nominal', function () {
    recalcSqWoBudgetTotal();
});

$(document).on('click', '#sqWoBudgetModal-btn-save', function () {
    const id = $('#sqWoBudgetModal-id').val();
    const idSqWo = $('#sqWoBudgetModal-id-sq-wo').val();
    const label = $('#sqWoBudgetModal-label').val().trim();
    if (!label) { Notify.warning('Label wajib diisi.'); return; }

    const items = [];
    let valid = true;
    $('#sqWoBudgetItemsBody .sq-wo-budget-item-row').each(function () {
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
        _token: window.sqWoRoute.csrf,
        id_sq_wo: idSqWo,
        label: label,
        keterangan: $('#sqWoBudgetModal-keterangan').val(),
        hari_mulai: $('#sqWoBudgetModal-hari-mulai').val() || null,
        hari_selesai: $('#sqWoBudgetModal-hari-selesai').val() || null,
        items: items,
    };
    const isEdit = !!id;
    if (isEdit) payload._method = 'PUT';
    const $btn = $(this);
    $btn.prop('disabled', true);
    $.post(isEdit ? window.sqWoRoute.budgetUpdate + id : window.sqWoRoute.budgetStore, payload)
        .done(function () {
            bootstrap.Modal.getInstance(document.getElementById('sqWoBudgetPlanModal'))?.hide();
            Notify.success('Budget Plan tersimpan');
            loadSqWoBudget(currentSqWoId);
        })
        .fail(function (xhr) { Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan Budget Plan'); })
        .always(function () { $btn.prop('disabled', false); });
});

// ── Modal bertumpuk (Tambah/Ubah Item BOQ di atas modal Kelola BOQ) ─────────
// Bootstrap 5 tidak menumpuk backdrop/z-index otomatis untuk modal kedua yang
// dibuka selagi modal pertama masih terbuka — dinaikkan manual di sini.
function showStackedModal(modalEl) {
    const openCount = $('.modal.show').length;
    new bootstrap.Modal(modalEl).show();
    if (openCount > 0) {
        setTimeout(function () {
            const z = 1060 + openCount * 20;
            $(modalEl).css('z-index', z);
            $('.modal-backdrop').last().css('z-index', z - 10);
        }, 0);
    }
}
