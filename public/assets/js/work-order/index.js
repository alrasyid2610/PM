let page;

// ── Load & render BOQ progress ─────────────────────────────────────────────────
function loadBoqProgress(id_wo) {
    $('#boqProgressContent').html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>'
    );

    $.get('/work-orders/' + id_wo + '/boq-progress', function (data) {
        $('#boqProgressContent').html(renderBoqProgressContent(data, id_wo));
    }).fail(function () {
        $('#boqProgressContent').html(
            '<div class="text-center text-danger py-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data</div>'
        );
    });
}

function renderBoqProgressContent(data, id_wo) {
    const hasBoq = data.sections && data.sections.length > 0;
    const hasFwo = data.fwos && data.fwos.length > 0;

    if (!hasBoq && !hasFwo) {
        return '<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
            'Belum ada data BOQ untuk Work Order ini</div>';
    }

    // ── Summary bar ──────────────────────────────────────────────────────────
    const pctColor = data.progress_pct >= 100 ? 'bg-success' : data.progress_pct > 0 ? 'bg-primary' : 'bg-secondary';
    const pctBadgeStyle = data.progress_pct >= 100
        ? 'background:#198754;color:#fff;'
        : data.progress_pct > 0 ? 'background:#dbeafe;color:#1d4ed8;' : 'background:#e9ecef;color:#495057;';

    const summaryHtml = hasBoq ? `
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">BOQ Progress</span>
                    ${data.total_boq_amount > 0
                        ? `<span style="font-size:11px;background:#eff6ff;color:#1d4ed8;padding:2px 8px;border-radius:20px;font-weight:600;">
                            <i class="fa-solid fa-tag me-1" style="font-size:10px;"></i>Rp ${Number(data.total_boq_amount).toLocaleString('en-US')}
                           </span>`
                        : ''}
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">${data.total_fwo_qty} / ${data.total_boq_qty} qty</span>
                    <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;${pctBadgeStyle}">${data.progress_pct}%</span>
                </div>
            </div>
            <div class="progress" style="height:7px;">
                <div class="progress-bar ${pctColor}" style="width:${data.progress_pct}%;transition:width .4s;"></div>
            </div>
        </div>` : '';

    // ── BOQ Items rows ────────────────────────────────────────────────────────
    const sectionsHtml = hasBoq ? data.sections.map(function (sec) {
        const secColor   = sec.progress_pct >= 100 ? 'bg-success' : sec.progress_pct > 0 ? 'bg-primary' : 'bg-secondary';
        const satuan     = sec.satuan ? escWo(sec.satuan) : '';
        const done       = sec.progress_pct >= 100
            ? '<i class="fa-solid fa-circle-check text-success flex-shrink-0 ms-2" style="font-size:13px;"></i>' : '';
        const priceHtml  = sec.harga > 0
            ? `<div style="font-size:11px;color:#64748b;margin-top:2px;">
                <span>Rp ${Number(sec.harga).toLocaleString('en-US')}${satuan ? ' / ' + satuan : ''}</span>
                <span style="margin:0 4px;">×</span>
                <span>${sec.boq_qty}${satuan ? ' ' + satuan : ''}</span>
                <span style="margin:0 4px;">=</span>
                <strong style="color:#1d4ed8;">Rp ${Number(sec.total_amount).toLocaleString('en-US')}</strong>
               </div>`
            : '';
        return `<a href="/boq?open=${id_wo}" target="_blank"
            class="d-flex align-items-center gap-2 py-2 text-decoration-none"
            style="border-bottom:1px solid #f1f5f9;color:inherit;transition:background .15s;border-radius:4px;padding-left:4px;padding-right:4px;"
            onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
            <div class="flex-grow-1" style="min-width:0;">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <div class="small fw-semibold" style="color:#1a56db;">${escWo(sec.point_name)}</div>
                        ${priceHtml}
                    </div>
                    <span class="small text-muted flex-shrink-0 ms-2" style="padding-top:1px;">${sec.fwo_qty} / ${sec.boq_qty}${satuan ? ' ' + satuan : ''}</span>
                </div>
                <div class="progress" style="height:5px;">
                    <div class="progress-bar ${secColor}" style="width:${sec.progress_pct}%;transition:width .4s;"></div>
                </div>
            </div>${done}
        </a>`;
    }).join('') : '<div class="text-muted small py-2 text-center">Belum ada item BOQ</div>';

    // ── FWO list rows ─────────────────────────────────────────────────────────
    const fwoListHtml = hasFwo ? data.fwos.map(function (fwo) {
        const tgl = fwo.tanggal_mulai ? fwo.tanggal_mulai.substring(0, 10) : '—';
        return `<div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid #f1f5f9;">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-hard-hat" style="color:#7c3aed;font-size:12px;"></i>
                <a href="/fieldworks?open=${fwo.id_fwo}" target="_blank"
                    class="fw-semibold small text-decoration-none">${escWo(fwo.no_fwo ?? '—')}</a>
                <span class="text-muted small">${tgl}</span>
            </div>
            <span style="font-size:11px;background:#e9ecef;color:#495057;padding:3px 8px;border-radius:20px;white-space:nowrap;">
                ${fwo.boq_section_count} item &middot; ${fwo.total_qty} qty
            </span>
        </div>`;
    }).join('') : '<div class="text-muted small py-2 text-center">Belum ada FWO</div>';

    // ── 2-column layout ───────────────────────────────────────────────────────
    return `${summaryHtml}
        <div class="row g-3">
            <div class="col-md-6">
                <div class="fw-semibold small text-muted mb-2">
                    <i class="fa-solid fa-layer-group me-1"></i> BOQ Items (${data.sections.length})
                </div>
                ${sectionsHtml}
            </div>
            <div class="col-md-6" style="border-left:1px solid #e9ecef;">
                <div class="fw-semibold small text-muted mb-2">
                    <i class="fa-solid fa-hard-hat me-1"></i> Fieldwork Orders (${data.fwos.length})
                </div>
                ${fwoListHtml}
            </div>
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

    page = new CrudPageController({
        primaryKey: 'id_wo',
        renderForm: renderForm,
        afterLoad: function (res) {
            loadBoqProgress(res.id_wo);
            loadWoPeriodContent(res.id_wo, res.id_so, res.id_period);
            initWoPeriodSection(res.id_wo, res.id_so, res.id_period);
        },
    });
});
