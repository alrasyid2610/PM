let page;
let boqViewMode        = localStorage.getItem('boq_view_mode') || 'card';
let currentBoqData     = null;
let currentBoqWoId     = null;
let currentWoData      = null;
let sourceFwoId        = null;
let copyFwoPersonelIdx = 0;

// ── BOQ Summary Card ───────────────────────────────────────────────────────────
function renderBoqSummary(data) {
    const totalBoqItems  = (data.sections || []).length;
    const totalFwo       = (data.fwos || []).length;
    const totalBoqQty    = data.total_boq_qty    || 0;
    const totalFwoQty    = data.total_fwo_qty    || 0;
    const pct            = data.progress_pct     || 0;
    const totalNilai     = data.total_boq_amount || 0;

    const nilaiLabel = totalNilai >= 1e9
        ? 'Rp ' + (totalNilai / 1e9).toFixed(1) + ' M'
        : totalNilai >= 1e6
            ? 'Rp ' + (totalNilai / 1e6).toFixed(1) + ' jt'
            : totalNilai > 0 ? 'Rp ' + Number(totalNilai).toLocaleString('en-US') : '—';

    const pctBarColor = pct >= 100 ? '#16a34a' : pct > 0 ? 'var(--primary-500, #1d4ed8)' : '#94a3b8';
    const pctTextColor = pct >= 100 ? '#15803d' : pct > 0 ? 'var(--primary-700, #1e40af)' : '#64748b';

    const card = (icon, color, label, value) => `
        <div style="flex:1;min-width:110px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;display:flex;align-items:center;gap:9px;cursor:pointer;"
            onclick="document.getElementById('boq-section')?.scrollIntoView({behavior:'smooth',block:'start'})">
            <div style="width:28px;height:28px;border-radius:8px;background:${color}18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa-solid ${icon}" style="color:${color};font-size:12px;"></i>
            </div>
            <div>
                <div style="font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:1px;">${label}</div>
                <div style="font-size:14px;font-weight:700;color:#1e293b;line-height:1;">${value}</div>
            </div>
        </div>`;

    const progressCard = `
        <div style="flex:2;min-width:180px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;cursor:pointer;"
            onclick="document.getElementById('boq-section')?.scrollIntoView({behavior:'smooth',block:'start'})">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                <i class="fa-solid fa-chart-line" style="color:${pctBarColor};font-size:11px;"></i>
                <span style="font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">Progress Keseluruhan</span>
            </div>
            <div style="background:#f1f5f9;border-radius:4px;height:6px;margin-bottom:5px;overflow:hidden;">
                <div style="width:${pct}%;height:100%;background:${pctBarColor};border-radius:4px;transition:width .5s;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:10px;color:#64748b;">${totalFwoQty} / ${totalBoqQty} qty terpenuhi</span>
                <span style="font-size:15px;font-weight:700;color:${pctTextColor};">${pct}%</span>
            </div>
        </div>`;

    $('#boqSummaryCard').html(`
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
            ${card('fa-layer-group', '#16a34a',        'Total BOQ',     totalBoqItems + ' item')}
            ${card('fa-hard-hat',   '#7c3aed',         'Total FWO',     totalFwo)}
            ${card('fa-cubes',      'var(--primary-500, #1d4ed8)', 'Total Qty BOQ', totalBoqQty + ' qty')}
            ${card('fa-tag',        '#0891b2',         'Total Nilai',   nilaiLabel)}
            ${progressCard}
        </div>
    `);
}

// ── Load & render BOQ progress ─────────────────────────────────────────────────
function loadBoqProgress(id_wo) {
    currentBoqWoId = id_wo;
    $('#boqProgressContent').html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>'
    );

    $.get('/work-orders/' + id_wo + '/boq-progress', function (data) {
        currentBoqData = data;
        renderBoqSummary(data);
        renderBoqView(data, id_wo);
    }).fail(function () {
        $('#boqProgressContent').html(
            '<div class="text-center text-danger py-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data</div>'
        );
    });
}

function renderBoqView(data, id_wo) {
    if (boqViewMode === 'table') {
        $('#boqProgressContent').html(renderBoqProgressTable(data, id_wo));
    } else {
        $('#boqProgressContent').html(renderBoqProgressContent(data, id_wo));
    }
    syncBoqToggleBtn();
}

function syncBoqToggleBtn() {
    const $btn = $('#btnToggleBoqView');
    if (boqViewMode === 'table') {
        $btn.html('<i class="fa-solid fa-grip"></i>').attr('title', 'Tampilan Kartu');
    } else {
        $btn.html('<i class="fa-solid fa-table-list"></i>').attr('title', 'Tampilan Tabel');
    }
}

function renderBoqProgressTable(data, id_wo) {
    const hasBoq = data.sections && data.sections.length > 0;

    if (!hasBoq) {
        return '<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
            'Belum ada data BOQ untuk Work Order ini</div>';
    }

    const pctBadgeStyle = data.progress_pct >= 100
        ? 'background:#198754;color:#fff;'
        : data.progress_pct > 0 ? 'background:#dbeafe;color:#1d4ed8;' : 'background:#e9ecef;color:#495057;';

    const summaryHtml = `
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">BOQ Progress</span>
                    ${data.total_boq_amount > 0
                        ? `<span style="font-size:11px;background:#eff6ff;color:#1d4ed8;padding:2px 8px;border-radius:20px;font-weight:600;">
                            <i class="fa-solid fa-tag me-1" style="font-size:10px;"></i>Rp ${Number(data.total_boq_amount).toLocaleString('en-US')}
                           </span>` : ''}
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">${data.total_fwo_qty} / ${data.total_boq_qty} qty</span>
                    <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;${pctBadgeStyle}">${data.progress_pct}%</span>
                </div>
            </div>
            <div class="progress" style="height:7px;">
                <div class="progress-bar ${data.progress_pct >= 100 ? 'bg-success' : data.progress_pct > 0 ? 'bg-primary' : 'bg-secondary'}"
                    style="width:${data.progress_pct}%;transition:width .4s;"></div>
            </div>
        </div>`;

    const TH    = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD    = 'style="padding:8px 12px;vertical-align:middle;"';
    const TDsub = 'style="padding:5px 12px;vertical-align:middle;background:#f8fafc;"';

    const allWoFwos = data.fwos || [];

    const rows = data.sections.map(function (sec) {
        const satuan      = sec.satuan ? escWo(sec.satuan) : '—';
        const sisa        = sec.boq_qty - sec.fwo_qty;
        const pctColor    = sec.progress_pct >= 100 ? '#198754' : sec.progress_pct > 0 ? '#1d4ed8' : '#6c757d';
        const pctBg       = sec.progress_pct >= 100 ? '#d1fae5' : sec.progress_pct > 0 ? '#dbeafe' : '#e9ecef';

        // Hanya FWO yang terhubung ke BOQ ini via fieldwork_boq
        const boqFwos = sec.fwos || [];
        const hasFwos = boqFwos.length > 0;

        const leadIcon = hasFwos
            ? `<i class="fa-solid fa-chevron-right boq-chevron" style="font-size:10px;color:#94a3b8;transition:transform .2s;margin-right:4px;"></i><i class="fa-solid fa-layer-group" style="color:#16a34a;font-size:10px;"></i>`
            : `<i class="fa-solid fa-layer-group" style="color:#16a34a;font-size:10px;"></i>`;

        const boqRow = `<tr class="boq-data-row${hasFwos ? ' boq-expandable' : ''}" data-boq-id="${sec.id_boq}"
            style="cursor:${hasFwos ? 'pointer' : 'default'};">
            <td ${TD} style="width:40px;text-align:center;">${leadIcon}</td>
            <td ${TD}><a href="/boq?open=${id_wo}" target="_blank" class="text-decoration-none fw-semibold" style="color:#1a56db;">${escWo(sec.point_name)}</a></td>
            <td ${TD} style="color:#64748b;">${satuan}</td>
            <td ${TD} style="text-align:right;font-weight:600;">${sec.boq_qty}</td>
            <td ${TD} style="text-align:right;color:#7c3aed;font-weight:600;">${sec.fwo_qty}</td>
            <td ${TD} style="text-align:right;font-weight:600;color:${sisa > 0 ? '#dc2626' : '#16a34a'};">${sisa}</td>
            <td ${TD} style="text-align:center;min-width:120px;">
                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;background:${pctBg};color:${pctColor};white-space:nowrap;">${sec.progress_pct}%</span>
                <div class="progress mt-1" style="height:4px;border-radius:2px;">
                    <div style="width:${sec.progress_pct}%;height:100%;background:${pctColor};border-radius:2px;transition:width .4s;"></div>
                </div>
            </td>
        </tr>`;

        let fwoRows = '';
        if (hasFwos) {
            fwoRows = boqFwos.map(function (fwo) {
                const fwoPct = sec.boq_qty > 0 ? Math.round(fwo.qty / sec.boq_qty * 100) : 0;
                const pctCell = `<span style="font-size:11px;font-weight:600;padding:2px 7px;border-radius:20px;background:#ede9fe;color:#7c3aed;">${fwoPct}%</span>`;

                return `<tr class="fwo-sub-row fwo-sub-${sec.id_boq}" style="display:none;">
                    <td ${TDsub} style="width:40px;"></td>
                    <td ${TDsub} style="padding-left:28px;">
                        <i class="fa-solid fa-hard-hat" style="color:#7c3aed;font-size:10px;margin-right:5px;"></i>
                        <a href="/fieldworks?open=${fwo.id_fwo}" target="_blank"
                            class="text-decoration-none fw-semibold" style="font-size:12px;color:#7c3aed;"
                            onclick="event.stopPropagation();">${escWo(fwo.no_fwo)}</a>
                    </td>
                    <td ${TDsub} style="font-size:12px;color:#64748b;">${fwo.tanggal_mulai ? escWo(fwo.tanggal_mulai) : '—'}</td>
                    <td ${TDsub}></td>
                    <td ${TDsub} style="text-align:right;font-size:12px;font-weight:700;color:#7c3aed;">${fwo.qty}</td>
                    <td ${TDsub}></td>
                    <td ${TDsub} style="text-align:center;">
                        ${pctCell}
                        <button type="button" class="btn-copy-fwo ms-1" data-fwo-id="${fwo.id_fwo}"
                            style="border:none;background:none;padding:1px 3px;color:#94a3b8;cursor:pointer;line-height:1;font-size:10px;"
                            title="Salin FWO ini">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
        }

        return boqRow + fwoRows;
    }).join('');

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

    return summaryHtml + searchBar + `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;min-width:700px;">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th ${TH} style="width:40px;"></th>
                    <th ${TH} style="min-width:200px;">Item BOQ</th>
                    <th ${TH} style="min-width:80px;">Satuan</th>
                    <th ${TH} style="min-width:80px;text-align:right;">BOQ Qty</th>
                    <th ${TH} style="min-width:80px;text-align:right;">FWO Qty</th>
                    <th ${TH} style="min-width:70px;text-align:right;">Sisa</th>
                    <th ${TH} style="min-width:110px;text-align:center;">Progress</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
}


function renderBoqProgressContent(data, id_wo) {
    const hasBoq = data.sections && data.sections.length > 0;

    if (!hasBoq) {
        return '<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
            'Belum ada data BOQ untuk Work Order ini</div>';
    }

    const pctBadgeStyle = data.progress_pct >= 100
        ? 'background:#198754;color:#fff;'
        : data.progress_pct > 0 ? 'background:#dbeafe;color:#1d4ed8;' : 'background:#e9ecef;color:#495057;';

    const summaryHtml = `
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">BOQ Progress</span>
                    ${data.total_boq_amount > 0
                        ? `<span style="font-size:11px;background:#eff6ff;color:#1d4ed8;padding:2px 8px;border-radius:20px;font-weight:600;">
                            <i class="fa-solid fa-tag me-1" style="font-size:10px;"></i>Rp ${Number(data.total_boq_amount).toLocaleString('en-US')}
                           </span>` : ''}
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">${data.total_fwo_qty} / ${data.total_boq_qty} qty</span>
                    <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;${pctBadgeStyle}">${data.progress_pct}%</span>
                </div>
            </div>
            <div class="progress" style="height:7px;">
                <div class="progress-bar ${data.progress_pct >= 100 ? 'bg-success' : data.progress_pct > 0 ? 'bg-primary' : 'bg-secondary'}"
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
    const rowsHtml = data.sections.map(function (sec) {
        const satuan   = sec.satuan ? escWo(sec.satuan) : '';
        const secColor = sec.progress_pct >= 100 ? '#198754' : sec.progress_pct > 0 ? '#1d4ed8' : '#94a3b8';
        const secBg    = sec.progress_pct >= 100 ? '#dcfce7' : sec.progress_pct > 0 ? '#dbeafe' : '#f1f5f9';
        const doneIcon = sec.progress_pct >= 100
            ? '<i class="fa-solid fa-circle-check" style="color:#198754;font-size:12px;margin-left:6px;flex-shrink:0;"></i>' : '';
        const priceHtml = sec.harga > 0
            ? `<div style="font-size:11px;color:#64748b;margin-top:2px;">
                Rp ${Number(sec.harga).toLocaleString('en-US')}${satuan ? ' / ' + satuan : ''}
                <span style="margin:0 3px;color:#cbd5e1;">×</span>
                ${sec.boq_qty}${satuan ? ' ' + satuan : ''}
                <span style="margin:0 3px;color:#cbd5e1;">=</span>
                <strong style="color:#1d4ed8;">Rp ${Number(sec.total_amount).toLocaleString('en-US')}</strong>
               </div>` : '';

        // FWO kolom kanan
        const boqFwos = sec.fwos || [];
        const fwoColHtml = boqFwos.length > 0
            ? boqFwos.map(function (fwo) {
                const fwoPct   = sec.boq_qty > 0 ? Math.round(fwo.qty / sec.boq_qty * 100) : 0;
                const fPctColor = fwoPct >= 100 ? '#198754' : fwoPct > 0 ? '#7c3aed' : '#94a3b8';
                const fPctBg   = fwoPct >= 100 ? '#d1fae5' : fwoPct > 0 ? '#ede9fe' : '#f1f5f9';
                return `<div style="display:flex;align-items:center;gap:8px;padding:4px 0;border-bottom:1px solid #f8fafc;">
                    <i class="fa-solid fa-hard-hat" style="color:#7c3aed;font-size:10px;flex-shrink:0;"></i>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;">
                            <a href="/fieldworks?open=${fwo.id_fwo}" target="_blank"
                                class="text-decoration-none fw-semibold" style="font-size:12px;color:#7c3aed;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escWo(fwo.no_fwo)}</a>
                            <div style="display:flex;align-items:center;gap:5px;flex-shrink:0;">
                                <span style="font-size:11px;font-weight:700;color:#7c3aed;">${fwo.qty} ${satuan || 'qty'}</span>
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
            }).join('')
            : `<div style="font-size:11px;color:#94a3b8;padding:4px 0;">
                <i class="fa-solid fa-circle-minus me-1" style="font-size:9px;"></i>Belum ada FWO dipetakan
               </div>`;

        return `<div style="display:flex;border-bottom:1px solid #e2e8f0;gap:0;">
            <!-- Kolom BOQ -->
            <div style="flex:1;min-width:0;padding:12px 14px;border-right:1px solid #e2e8f0;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px;">
                    <div style="min-width:0;">
                        <a href="/boq?open=${id_wo}" target="_blank"
                            class="text-decoration-none"
                            style="font-size:13px;font-weight:600;color:#1a56db;">${escWo(sec.point_name)}</a>
                        ${priceHtml}
                    </div>
                    <div style="display:flex;align-items:center;flex-shrink:0;">
                        <span style="font-size:11px;color:#64748b;white-space:nowrap;">${sec.fwo_qty} / ${sec.boq_qty}${satuan ? ' ' + satuan : ''}</span>
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
    }).join('');

    return summaryHtml + `
        <div style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
            ${headerRow}
            ${rowsHtml}
        </div>`;
}

// ── Event handlers ─────────────────────────────────────────────────────────────
$(document).ready(function () {

    $(document).on('click', '#btnRefreshBoqProgress', function () {
        const woId  = $(this).data('wo-id');
        const $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        loadBoqProgress(woId);
        setTimeout(function () { $icon.removeClass('fa-spin'); }, 600);
    });

    $(document).on('input', '#boqSearchInput', function () {
        const q     = $(this).val().toLowerCase().trim();
        let visible = 0;
        $('#btnClearBoqSearch').toggleClass('d-none', !q);
        $('.boq-data-row').each(function () {
            const match = !q || $(this).find('td:nth-child(2)').text().toLowerCase().includes(q);
            $(this).toggle(match);
            const boqId = $(this).data('boq-id');
            if (boqId && !match) {
                $('.fwo-sub-' + boqId).hide();
            }
            if (match) visible++;
        });
        const total = $('.boq-data-row').length;
        $('#boqSearchCount').text(q ? visible + ' dari ' + total + ' item' : '');
    });

$(document).on('click', '.boq-expandable', function () {
        const boqId   = $(this).data('boq-id');
        const $subs   = $('.fwo-sub-' + boqId);
        const $chev   = $(this).find('.boq-chevron');
        const isOpen  = $subs.first().is(':visible');
        if (isOpen) {
            $subs.hide();
            $chev.css('transform', '');
        } else {
            $subs.show();
            $chev.css('transform', 'rotate(90deg)');
        }
    });

    $(document).on('click', '#btnClearBoqSearch', function () {
        $('#boqSearchInput').val('').trigger('input');
    });

    $(document).on('click', '#btnToggleBoqView', function () {
        boqViewMode = boqViewMode === 'card' ? 'table' : 'card';
        localStorage.setItem('boq_view_mode', boqViewMode);
        if (currentBoqData && currentBoqWoId) {
            renderBoqView(currentBoqData, currentBoqWoId);
        }
    });

    // ── Copy FWO ─────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-copy-fwo', function () {
        sourceFwoId = $(this).data('fwo-id');
        $('#modalCopyFwoBody').html(
            '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data FWO...</div>'
        );
        $('#btnConfirmCopyFwo').prop('disabled', true);
        new bootstrap.Modal($('#modalCopyFwo')[0]).show();

        $.when(
            $.get(window.route.fwoDetail + sourceFwoId),
            $.get(window.route.fwoBoqDetail + sourceFwoId)
        ).done(function (fwoRes, boqRes) {
            fillCopyFwoModal(fwoRes[0], boqRes[0]);
            $('#btnConfirmCopyFwo').prop('disabled', false);
        }).fail(function () {
            $('#modalCopyFwoBody').html(
                '<div class="text-center text-danger py-4"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data FWO</div>'
            );
        });
    });

    $(document).on('click', '#btnAddCopyPersonel', function () {
        const row = $(renderCopyPersonelRow(null, null, ''));
        $('#copyFwoPersonelContainer').append(row);
        initCopyPersonelSelect2(row.find('.copy-personel-user-select'));
    });

    $(document).on('click', '.btn-remove-copy-personel', function () {
        $(this).closest('.copy-personel-row').remove();
    });

    $(document).on('click', '#btnConfirmCopyFwo', function () {
        const judul = $('#copyFwoJudul').val().trim();
        if (!judul) { Notify.warning('Judul pekerjaan wajib diisi'); return; }

        const personels = [];
        let personelValid = true;
        $('#copyFwoPersonelContainer .copy-personel-row').each(function () {
            const userId = $(this).find('.copy-personel-user-select').val();
            const role   = $(this).find('.copy-personel-role-select').val();
            if (!userId) { personelValid = false; return false; }
            personels.push({ id_user: parseInt(userId), role: role || null });
        });
        if (!personelValid) { Notify.warning('Pilih personel untuk semua baris'); return; }

        const sections = [];
        $('.copy-boq-qty').each(function () {
            const boqId = $(this).data('boq-id');
            const qty   = parseInt($(this).val());
            if (boqId && !isNaN(qty) && qty > 0) {
                sections.push({ id_boq: parseInt(boqId), qty: qty });
            }
        });

        const payload = {
            judul_pekerjaan: judul,
            tanggal_mulai:   $('#copyFwoTglMulai').val()   || null,
            tanggal_selesai: $('#copyFwoTglSelesai').val() || null,
            keterangan:      $('#copyFwoKeterangan').val() || null,
            personels,
            sections,
        };

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url:         window.route.fwoDuplicate + sourceFwoId + '/duplicate',
            method:      'POST',
            contentType: 'application/json',
            headers:     { 'X-CSRF-TOKEN': window.route.csrf },
            data:        JSON.stringify(payload),
            success: function (res) {
                Notify.success('FWO berhasil disalin: ' + res.no_fwo);
                bootstrap.Modal.getInstance('#modalCopyFwo').hide();
                if (currentBoqWoId) loadBoqProgress(currentBoqWoId);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal menyalin FWO');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-copy me-1"></i> Buat Salinan');
            },
        });
    });

    $('#modalCopyFwo').on('hidden.bs.modal', function () {
        sourceFwoId = null;
        $('#btnConfirmCopyFwo').prop('disabled', false)
            .html('<i class="fa-solid fa-copy me-1"></i> Buat Salinan');
    });

    page = new CrudPageController({
        primaryKey: 'id_wo',
        renderForm: renderForm,
        afterLoad: function (res) {
            currentWoData = res;
            loadBoqProgress(res.id_wo);
        },
    });
});

// ── Copy FWO helpers ───────────────────────────────────────────────────────────
function renderCopyPersonelRow(userId, userName, role) {
    const idx = copyFwoPersonelIdx++;
    const roleOptions = ['Leader', 'Driver', 'Anggota'].map(function (r) {
        return `<option value="${r}" ${role === r ? 'selected' : ''}>${r}</option>`;
    }).join('');
    return `<div class="copy-personel-row d-flex align-items-start gap-2" data-idx="${idx}">
        <div style="flex:1;min-width:0;">
            <select class="form-select form-select-sm copy-personel-user-select"
                data-user-id="${userId || ''}" data-user-name="${escWo(userName || '')}"></select>
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
    const userId   = $select.data('user-id');
    const userName = $select.data('user-name');
    $select.select2({
        width: '100%',
        placeholder: 'Ketik nama personel...',
        allowClear: true,
        minimumInputLength: 0,
        dropdownParent: $('#modalCopyFwo'),
        ajax: {
            url:  window.route.usersSelect2,
            dataType: 'json',
            delay: 200,
            data: function (p) { return { q: p.term }; },
            processResults: function (d) { return { results: d }; },
            cache: true,
        },
    });
    if (userId) {
        const opt = new Option(userName, userId, true, true);
        $select.append(opt).trigger('change');
    }
}

function fillCopyFwoModal(fwo, boqs) {
    copyFwoPersonelIdx = 0;
    const dateMulai   = (fwo.tanggal_mulai   || '').substring(0, 10);
    const dateSelesai = (fwo.tanggal_selesai || '').substring(0, 10);

    let personelHtml = (fwo.personels || []).map(function (p) {
        return renderCopyPersonelRow(p.id_user, p.user_name, p.role);
    }).join('');
    if (!personelHtml) {
        personelHtml = renderCopyPersonelRow(null, null, '');
    }

    let boqHtml = '';
    if (boqs && boqs.length > 0) {
        const secRows = boqs.map(function (sec) {
            return `<div class="d-flex align-items-center gap-3 p-2"
                style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                <div style="flex:1;min-width:0;">
                    <div class="fw-semibold small">${escWo(sec.point_name)}</div>
                    <div class="text-muted" style="font-size:11px;">BOQ Qty: ${sec.boq_qty}${sec.satuan ? ' ' + escWo(sec.satuan) : ''}</div>
                </div>
                <div style="width:90px;">
                    <input type="number" class="form-control form-control-sm text-end copy-boq-qty"
                        data-boq-id="${sec.id_boq}" value="${sec.qty || ''}"
                        min="1" max="${sec.boq_qty}" placeholder="qty">
                </div>
            </div>`;
        }).join('');
        boqHtml = `<div class="mb-3">
            <label class="form-label fw-semibold">Qty per BOQ Section</label>
            <div class="d-flex flex-column gap-2">${secRows}</div>
        </div>`;
    }

    $('#modalCopyFwoBody').html(`
        <div class="alert alert-light border mb-3 py-2" style="font-size:12px;">
            <i class="fa-solid fa-copy me-1 text-primary"></i>
            Menyalin dari: <strong>${escWo(fwo.no_fwo)}</strong>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Judul Pekerjaan <span class="text-danger">*</span></label>
            <input type="text" id="copyFwoJudul" class="form-control"
                value="${escWo(fwo.judul_pekerjaan || '')}" maxlength="500">
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tanggal Mulai</label>
                <input type="date" id="copyFwoTglMulai" class="form-control" value="${dateMulai}">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tanggal Selesai</label>
                <input type="date" id="copyFwoTglSelesai" class="form-control" value="${dateSelesai}">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Keterangan</label>
            <textarea id="copyFwoKeterangan" class="form-control" rows="2">${escWo(fwo.keterangan || '')}</textarea>
        </div>
        ${boqHtml}
        <label class="form-label fw-semibold">Personel</label>
        <div id="copyFwoPersonelContainer" class="d-flex flex-column gap-2 mb-2">${personelHtml}</div>
        <button type="button" id="btnAddCopyPersonel" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Tambah Personel
        </button>
    `);

    $('#copyFwoPersonelContainer .copy-personel-user-select').each(function () {
        initCopyPersonelSelect2($(this));
    });
}
