let page;
let currentBoqData = null;
let currentBoqWoId = null;
let currentWoData = null;
let sourceFwoId = null;
let copyFwoPersonelIdx = 0;
let currentOutputWoId = null;
let outputDataMap = {};
let outputFilePond = null;

window.addEventListener("storage", function (e) {
    if (e.key === "fwo_created" && e.newValue) {
        try {
            var data = JSON.parse(e.newValue);
            var target = data.id_wo || currentBoqWoId;
            var fwoModal = bootstrap.Modal.getInstance(
                document.getElementById("modalCreateFwo"),
            );
            if (fwoModal) {
                fwoModal.hide();
                document.getElementById("iframeCreateFwo").src = "";
            }
            if (target && String(target) === String(currentBoqWoId)) {
                loadBoqProgress(target);
            }
        } catch (_) {}
    }

    if (e.key === "boq_created" && e.newValue) {
        try {
            var data = JSON.parse(e.newValue);
            var target = data.id_wo || currentBoqWoId;
            var modal = bootstrap.Modal.getInstance(
                document.getElementById("modalCreateBoq"),
            );
            if (modal) {
                modal.hide();
                document.getElementById("iframeCreateBoq").src = "";
            }
            if (target && String(target) === String(currentBoqWoId)) {
                loadBoqProgress(target);
            }
        } catch (_) {}
    }
});

// ── Tab switch: show/hide action buttons ──────────────────────────────────────
$(document).on(
    "shown.bs.tab",
    '#woDetailTabs button[data-bs-toggle="tab"]',
    function (e) {
        const target = $(e.target).data("bs-target");
        $("#woTabActionsInfo, #woTabActionsBoq, #woTabActionsFwo, #woTabActionsOutput").addClass("d-none");
        if (target === "#tabInfo")   $("#woTabActionsInfo").removeClass("d-none");
        if (target === "#tabBoq")    $("#woTabActionsBoq").removeClass("d-none");
        if (target === "#tabFwo")    $("#woTabActionsFwo").removeClass("d-none");
        if (target === "#tabOutput") $("#woTabActionsOutput").removeClass("d-none");
    },
);

$(document).on("click", ".btn-add-fwo-modal", function () {
    var woId = $(this).data("wo-id");
    document.getElementById("iframeCreateFwo").src =
        "/fieldworks/create?id_wo=" + woId + "&embed=1";
    new bootstrap.Modal(document.getElementById("modalCreateFwo")).show();
});

$(document).on("click", ".btn-add-boq-modal", function () {
    var woId = $(this).data("wo-id");
    document.getElementById("iframeCreateBoq").src =
        "/boq/create?id_wo=" + woId + "&embed=1";
    new bootstrap.Modal(document.getElementById("modalCreateBoq")).show();
});

// ── BOQ Summary Card ───────────────────────────────────────────────────────────
function renderBoqSummary(data) {
    const totalBoqItems = (data.sections || []).length;
    const totalFwo      = data.total_fwo ?? 0;
    const fwoCompleted  = data.fwo_completed ?? 0;
    const outBelumSiap  = data.output_belum_siap ?? 0;
    const outSiap       = data.output_siap ?? 0;
    const outTerkirim   = data.output_terkirim ?? 0;
    const totalOutput   = outBelumSiap + outSiap + outTerkirim;

    const fwoColor = fwoCompleted >= totalFwo && totalFwo > 0 ? "#16a34a"
                   : fwoCompleted > 0 ? "#d97706" : "#94a3b8";

    const kpiCard = (icon, iconBg, label, value, sub = '') => `
        <div class="pm-kpi-card">
            <div class="pm-kpi-icon" style="background:${iconBg};">
                <i class="fa-solid ${icon}"></i>
            </div>
            <div>
                <div class="pm-kpi-label">${label}</div>
                <div class="pm-kpi-value">${value}</div>
                ${sub ? `<div class="pm-kpi-sub">${sub}</div>` : ''}
            </div>
        </div>`;

    const outputCard = totalOutput === 0 ? kpiCard("fa-file-circle-check", "#64748b", "Output", "—", "Belum ada output") :
        `<div class="pm-kpi-card" style="flex-direction:column;align-items:flex-start;gap:8px;min-width:220px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div class="pm-kpi-icon" style="background:#0f766e;flex-shrink:0;">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>
                <div>
                    <div class="pm-kpi-label">Output Pekerjaan</div>
                    <div class="pm-kpi-value">${totalOutput} <span style="font-size:12px;font-weight:400;color:#94a3b8;">dokumen</span></div>
                </div>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">
                    <i class="fa-solid fa-clock me-1" style="font-size:9px;"></i>${outBelumSiap} Belum Siap
                </span>
                <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;">
                    <i class="fa-solid fa-check me-1" style="font-size:9px;"></i>${outSiap} Siap
                </span>
                <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">
                    <i class="fa-solid fa-paper-plane me-1" style="font-size:9px;"></i>${outTerkirim} Terkirim
                </span>
            </div>
        </div>`;

    $("#boqSummaryCard").html(
        kpiCard("fa-layer-group", "#0891b2", "Total BOQ", totalBoqItems + " item") +
        `<div class="pm-kpi-card">
            <div class="pm-kpi-icon" style="background:${fwoColor};">
                <i class="fa-solid fa-hard-hat"></i>
            </div>
            <div>
                <div class="pm-kpi-label">Total FWO</div>
                <div class="pm-kpi-value" style="color:${fwoColor};">${fwoCompleted}<span style="font-size:12px;font-weight:500;color:#94a3b8;">/${totalFwo}</span></div>
                <div class="pm-kpi-sub">FWO selesai</div>
            </div>
        </div>` +
        outputCard
    );
}

// Load FWO

// ── Load & render BOQ progress ─────────────────────────────────────────────────
function loadBoqProgress(id_wo) {
    currentBoqWoId = id_wo;
    $("#boqProgressContent").html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>',
    );

    $.get("/work-orders/" + id_wo + "/boq-progress", function (data) {
        currentBoqData = data;
        renderBoqSummary(data);
        renderBoqView(data, id_wo);
    }).fail(function () {
        $("#boqProgressContent").html(
            '<div class="text-center text-danger py-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data</div>',
        );
    });
}

function renderBoqView(data, id_wo) {
    $("#boqProgressContent").html(renderBoqProgressTable(data, id_wo));
    $("#fwoProgressContent").html(renderFwoProgressTable(data, id_wo));
}

function renderBoqProgressTable(data, id_wo) {
    const hasBoq = data.sections && data.sections.length > 0;

    if (!hasBoq) {
        return (
            '<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
            "Belum ada data BOQ untuk Work Order ini</div>"
        );
    }

    const pctBadgeStyle =
        data.progress_pct >= 100
            ? "background:#198754;color:#fff;"
            : data.progress_pct > 0
              ? "background:#dbeafe;color:#1d4ed8;"
              : "background:#e9ecef;color:#495057;";

    const summaryHtml = `
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">BOQ Progress</span>
                    ${
                        data.total_boq_amount > 0
                            ? `<span style="font-size:11px;background:#eff6ff;color:#1d4ed8;padding:2px 8px;border-radius:20px;font-weight:600;">
                            <i class="fa-solid fa-tag me-1" style="font-size:10px;"></i>Rp ${Number(data.total_boq_amount).toLocaleString("en-US")}
                           </span>`
                            : ""
                    }
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">${data.total_fwo_qty} / ${data.total_boq_qty} qty</span>
                    <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;${pctBadgeStyle}">${data.progress_pct}%</span>
                </div>
            </div>
            <div class="progress" style="height:7px;">
                <div class="progress-bar ${data.progress_pct >= 100 ? "bg-success" : data.progress_pct > 0 ? "bg-primary" : "bg-secondary"}"
                    style="width:${data.progress_pct}%;transition:width .4s;"></div>
            </div>
        </div>`;

    const TH =
        'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';
    const TDsub =
        'style="padding:5px 12px;vertical-align:middle;background:#f8fafc;"';

    const allWoFwos = data.fwos || [];

    const rows = data.sections
        .map(function (sec) {
            const satuan = sec.satuan ? escHtml(sec.satuan) : "—";
            const sisa = sec.boq_qty - sec.fwo_qty;
            const pctColor =
                sec.progress_pct >= 100
                    ? "#198754"
                    : sec.progress_pct > 0
                      ? "#1d4ed8"
                      : "#6c757d";
            const pctBg =
                sec.progress_pct >= 100
                    ? "#d1fae5"
                    : sec.progress_pct > 0
                      ? "#dbeafe"
                      : "#e9ecef";

            // Hanya FWO yang terhubung ke BOQ ini via fieldwork_boq
            const boqFwos = sec.fwos || [];
            const hasFwos = boqFwos.length > 0;

            const leadIcon = hasFwos
                ? `<i class="fa-solid fa-chevron-right boq-chevron" style="font-size:10px;color:#94a3b8;transition:transform .2s;margin-right:4px;"></i><i class="fa-solid fa-layer-group" style="color:#16a34a;font-size:10px;"></i>`
                : `<i class="fa-solid fa-layer-group" style="color:#16a34a;font-size:10px;"></i>`;

            const boqRow = `<tr class="boq-data-row${hasFwos ? " boq-expandable" : ""}" data-boq-id="${sec.id_boq}"
            style="cursor:${hasFwos ? "pointer" : "default"};">
            <td ${TD} style="width:40px;text-align:center;">${leadIcon}</td>
            <td ${TD}><a href="/boq?open=${id_wo}" class="text-decoration-none fw-semibold" style="color:#1a56db;">${escHtml(sec.point_name)}</a></td>
            <td ${TD} style="color:#64748b;">${satuan}</td>
            <td ${TD} style="text-align:right;font-weight:600;">${sec.boq_qty}</td>
            <td ${TD} style="text-align:right;color:#7c3aed;font-weight:600;">${sec.fwo_qty}</td>
            <td ${TD} style="text-align:right;font-weight:600;color:${sisa > 0 ? "#dc2626" : "#16a34a"};">${sisa}</td>
        </tr>`;

            let fwoRows = "";
            return boqRow;
        })
        .join("");

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

    return (
        summaryHtml +
        searchBar +
        `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;min-width:700px;">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th ${TH} style="width:40px;"></th>
                    <th ${TH} style="min-width:200px;">Item BOQ</th>
                    <th ${TH} style="min-width:80px;">Satuan</th>
                    <th ${TH} style="min-width:80px;text-align:right;">BOQ Qty</th>
                    <th ${TH} style="min-width:80px;text-align:right;">FWO Qty</th>
                    <th ${TH} style="min-width:70px;text-align:right;">Sisa</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`
    );
}

function renderBoqProgressContent(data, id_wo) {
    const hasBoq = data.sections && data.sections.length > 0;

    if (!hasBoq) {
        return (
            '<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
            "Belum ada data BOQ untuk Work Order ini</div>"
        );
    }

    const pctBadgeStyle =
        data.progress_pct >= 100
            ? "background:#198754;color:#fff;"
            : data.progress_pct > 0
              ? "background:#dbeafe;color:#1d4ed8;"
              : "background:#e9ecef;color:#495057;";

    const summaryHtml = `
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">BOQ Progress</span>
                    ${
                        data.total_boq_amount > 0
                            ? `<span style="font-size:11px;background:#eff6ff;color:#1d4ed8;padding:2px 8px;border-radius:20px;font-weight:600;">
                            <i class="fa-solid fa-tag me-1" style="font-size:10px;"></i>Rp ${Number(data.total_boq_amount).toLocaleString("en-US")}
                           </span>`
                            : ""
                    }
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">${data.total_fwo_qty} / ${data.total_boq_qty} qty</span>
                    <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;${pctBadgeStyle}">${data.progress_pct}%</span>
                </div>
            </div>
            <div class="progress" style="height:7px;">
                <div class="progress-bar ${data.progress_pct >= 100 ? "bg-success" : data.progress_pct > 0 ? "bg-primary" : "bg-secondary"}"
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
    const rowsHtml = data.sections
        .map(function (sec) {
            const satuan = sec.satuan ? escHtml(sec.satuan) : "";
            const secColor =
                sec.progress_pct >= 100
                    ? "#198754"
                    : sec.progress_pct > 0
                      ? "#1d4ed8"
                      : "#94a3b8";
            const secBg =
                sec.progress_pct >= 100
                    ? "#dcfce7"
                    : sec.progress_pct > 0
                      ? "#dbeafe"
                      : "#f1f5f9";
            const doneIcon =
                sec.progress_pct >= 100
                    ? '<i class="fa-solid fa-circle-check" style="color:#198754;font-size:12px;margin-left:6px;flex-shrink:0;"></i>'
                    : "";
            const priceHtml =
                sec.harga > 0
                    ? `<div style="font-size:11px;color:#64748b;margin-top:2px;">
                Rp ${Number(sec.harga).toLocaleString("en-US")}${satuan ? " / " + satuan : ""}
                <span style="margin:0 3px;color:#cbd5e1;">×</span>
                ${sec.boq_qty}${satuan ? " " + satuan : ""}
                <span style="margin:0 3px;color:#cbd5e1;">=</span>
                <strong style="color:#1d4ed8;">Rp ${Number(sec.total_amount).toLocaleString("en-US")}</strong>
               </div>`
                    : "";

            // FWO kolom kanan
            const boqFwos = sec.fwos || [];
            const fwoColHtml =
                boqFwos.length > 0
                    ? boqFwos
                          .map(function (fwo) {
                              const fwoPct =
                                  sec.boq_qty > 0
                                      ? Math.round(
                                            (fwo.qty / sec.boq_qty) * 100,
                                        )
                                      : 0;
                              const fPctColor =
                                  fwoPct >= 100
                                      ? "#198754"
                                      : fwoPct > 0
                                        ? "#7c3aed"
                                        : "#94a3b8";
                              const fPctBg =
                                  fwoPct >= 100
                                      ? "#d1fae5"
                                      : fwoPct > 0
                                        ? "#ede9fe"
                                        : "#f1f5f9";
                              return `<div style="display:flex;align-items:center;gap:8px;padding:4px 0;border-bottom:1px solid #f8fafc;">
                    <i class="fa-solid fa-hard-hat" style="color:#7c3aed;font-size:10px;flex-shrink:0;"></i>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;">
                            <a href="/fieldworks?open=${fwo.id_fwo}"
                                class="text-decoration-none fw-semibold" style="font-size:12px;color:#7c3aed;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(fwo.no_fwo)}</a>
                            <div style="display:flex;align-items:center;gap:5px;flex-shrink:0;">
                                <span style="font-size:11px;font-weight:700;color:#7c3aed;">${fwo.qty} ${satuan || "qty"}</span>
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
                          })
                          .join("")
                    : `<div style="font-size:11px;color:#94a3b8;padding:4px 0;">
                <i class="fa-solid fa-circle-minus me-1" style="font-size:9px;"></i>Belum ada FWO dipetakan
               </div>`;

            return `<div style="display:flex;border-bottom:1px solid #e2e8f0;gap:0;">
            <!-- Kolom BOQ -->
            <div style="flex:1;min-width:0;padding:12px 14px;border-right:1px solid #e2e8f0;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px;">
                    <div style="min-width:0;">
                        <a href="/boq?open=${id_wo}"
                            class="text-decoration-none"
                            style="font-size:13px;font-weight:600;color:#1a56db;">${escHtml(sec.point_name)}</a>
                        ${priceHtml}
                    </div>
                    <div style="display:flex;align-items:center;flex-shrink:0;">
                        <span style="font-size:11px;color:#64748b;white-space:nowrap;">${sec.fwo_qty} / ${sec.boq_qty}${satuan ? " " + satuan : ""}</span>
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
        })
        .join("");

    return (
        summaryHtml +
        `
        <div style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
            ${headerRow}
            ${rowsHtml}
        </div>`
    );
}

function renderFwoProgressTable(data, id_wo) {
    const fwos = data.fwos || [];

    if (!fwos.length) {
        return (
            '<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
            "Belum ada FWO untuk Work Order ini</div>"
        );
    }

    const TH =
        'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';

    const rows = fwos
        .map(function (f) {
            const tglMulai = f.tanggal_mulai
                ? f.tanggal_mulai.substring(0, 10)
                : "—";
            const tglSelesai = f.tanggal_selesai
                ? f.tanggal_selesai.substring(0, 10)
                : "—";
            const isCompleted = f.status === 'completed';
            const statusBadge = isCompleted
                ? `<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:20px;background:#f0fdf4;color:#15803d;font-size:11px;font-weight:600;border:1px solid #bbf7d0;white-space:nowrap;">
                       <i class="fa-solid fa-circle-check" style="font-size:10px;"></i> Completed
                   </span>`
                : `<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:20px;background:#fffbeb;color:#b45309;font-size:11px;font-weight:600;border:1px solid #fde68a;white-space:nowrap;">
                       <i class="fa-solid fa-hourglass-half" style="font-size:10px;"></i> Planned
                   </span>`;
            const search = [f.no_fwo, f.judul_pekerjaan, f.keterangan, f.status]
                .join(" ")
                .toLowerCase();

            return `<tr class="fwo-data-row" data-search="${escHtml(search)}">
            <td ${TD}>
                <a href="/fieldworks?open=${f.id_fwo}"
                    class="fw-semibold text-decoration-none" style="color:#1a56db;white-space:nowrap;">
                    ${escHtml(f.no_fwo ?? "—")}
                </a>
            </td>
            <td ${TD} style="color:#374151;">${escHtml(f.judul_pekerjaan ?? "—")}</td>
            <td ${TD} style="color:#64748b;">${escHtml(f.keterangan ?? "—")}</td>
            <td ${TD}>${statusBadge}</td>
            <td ${TD} style="color:#64748b;white-space:nowrap;">${tglMulai}</td>
            <td ${TD} style="color:#64748b;white-space:nowrap;">${tglSelesai}</td>
            <td ${TD} style="text-align:center;white-space:nowrap;">
                <a href="/fieldworks?open=${f.id_fwo}"
                    class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" title="Buka detail FWO">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 ms-1 btn-copy-fwo"
                    style="font-size:11px;" title="Salin FWO ini" data-fwo-id="${f.id_fwo}">
                    <i class="fa-solid fa-copy"></i>
                </button>
            </td>
        </tr>`;
        })
        .join("");

    const searchBar = `<div class="mb-2 d-flex align-items-center gap-2">
        <div class="input-group input-group-sm" style="max-width:280px;">
            <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0;">
                <i class="fa-solid fa-magnifying-glass text-muted" style="font-size:11px;"></i>
            </span>
            <input type="text" id="fwoSearchInput" class="form-control" placeholder="Cari No FWO atau judul..."
                style="border-color:#e2e8f0;font-size:12px;" data-no-disable>
            <button type="button" id="btnClearFwoSearch" class="btn btn-outline-secondary d-none"
                style="border-color:#e2e8f0;font-size:11px;" title="Hapus pencarian">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <span id="fwoSearchCount" class="text-muted" style="font-size:11px;"></span>
    </div>`;

    return (
        searchBar +
        `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;min-width:600px;">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th ${TH} style="min-width:140px;">No FWO</th>
                    <th ${TH} style="min-width:200px;">Judul Pekerjaan</th>
                    <th ${TH} style="min-width:180px;">Keterangan</th>
                    <th ${TH} style="min-width:110px;">Status</th>
                    <th ${TH} style="min-width:110px;">Tgl Mulai</th>
                    <th ${TH} style="min-width:110px;">Tgl Selesai</th>
                    <th ${TH} style="min-width:60px;"></th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`
    );
}

// ── Output Pekerjaan ───────────────────────────────────────────────────────────
function loadOutputProgress(id_wo) {
    currentOutputWoId = id_wo;
    $("#outputContent").html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>',
    );
    $.get(window.route.update + id_wo + "/outputs", function (data) {
        outputDataMap = {};
        (data || []).forEach(function (item) {
            outputDataMap[item.id_output] = item;
        });
        $("#outputContent").html(renderOutputTable(data));
    }).fail(function () {
        $("#outputContent").html(
            '<div class="text-center text-danger py-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data</div>',
        );
    });
}

function outputFileName(path) {
    var base = path.split("/").pop();
    var parts = base.split("_");
    // "output_pekerjaan_TIMESTAMP_originalname" → skip first 3 segments
    return parts.length > 3 ? parts.slice(3).join("_") : base;
}

function renderOutputTable(outputs) {
    const TH =
        'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';

    var bodyHtml;
    if (!outputs || !outputs.length) {
        bodyHtml =
            '<tr><td colspan="9" class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>Belum ada output pekerjaan</td></tr>';
    } else {
        bodyHtml = outputs
            .map(function (item, idx) {
                var attachHtml = "—";
                try {
                    var files =
                        typeof item.attachments === "string"
                            ? JSON.parse(item.attachments)
                            : item.attachments || [];
                    if (files && files.length) {
                        attachHtml = files
                            .map(function (p) {
                                return (
                                    '<a href="/storage/' +
                                    p +
                                    '" target="_blank" ' +
                                    'class="d-inline-flex align-items-center gap-1 me-1" ' +
                                    'style="font-size:11px;color:#1a56db;text-decoration:none;">' +
                                    '<i class="fa-solid fa-paperclip" style="font-size:10px;"></i>' +
                                    escHtml(outputFileName(p)) +
                                    "</a>"
                                );
                            })
                            .join("");
                    }
                } catch (e) {}
                // Status badge
                var statusMap = {
                    belum_siap: { label: 'Belum Siap', bg: '#fef2f2', color: '#dc2626', border: '#fecaca' },
                    siap:       { label: 'Siap',       bg: '#f0fdf4', color: '#16a34a', border: '#bbf7d0' },
                    terkirim:   { label: 'Terkirim',   bg: '#eff6ff', color: '#1d4ed8', border: '#bfdbfe' },
                };
                var st = statusMap[item.status] || statusMap['belum_siap'];
                var statusBadge = '<span style="font-size:11px;padding:2px 9px;border-radius:20px;background:' + st.bg + ';color:' + st.color + ';border:1px solid ' + st.border + ';white-space:nowrap;">'
                    + escHtml(st.label)
                    + '</span>';

                // Jenis dokumen & qty
                var jenisBadge = '—';
                if (item.jenis_dokumen) {
                    var jLabel = {copy:'Copy', asli:'Asli', asli_dan_copy:'Asli & Copy'}[item.jenis_dokumen] || item.jenis_dokumen;
                    jenisBadge = '<span style="font-size:11px;padding:2px 8px;border-radius:20px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;white-space:nowrap;">' + escHtml(jLabel) + '</span>';
                }
                var qtyCopyHtml = (item.qty_copy != null && (item.jenis_dokumen === 'copy' || item.jenis_dokumen === 'asli_dan_copy'))
                    ? '<span style="font-weight:600;color:#1d4ed8;">' + item.qty_copy + '</span>'
                    : '<span style="color:#94a3b8;">—</span>';
                var qtyAsliHtml = (item.qty_asli != null && (item.jenis_dokumen === 'asli' || item.jenis_dokumen === 'asli_dan_copy'))
                    ? '<span style="font-weight:600;color:#16a34a;">' + item.qty_asli + '</span>'
                    : '<span style="color:#94a3b8;">—</span>';
                // Link drive
                var driveHtml = item.link_drive
                    ? '<a href="' + escHtml(item.link_drive) + '" target="_blank" style="font-size:11px;color:#1a56db;"><i class="fa-brands fa-google-drive me-1"></i>Drive</a>'
                    : '—';

                return (
                    '<tr class="output-data-row" data-id="' +
                    item.id_output +
                    '">' +
                    "<td " + TD + ' style="width:40px;text-align:center;color:#94a3b8;">' + (idx + 1) + "</td>" +
                    "<td " + TD + ' style="font-weight:500;">' + escHtml(item.judul_output) + "</td>" +
                    "<td " + TD + ' style="color:#64748b;">' + (item.judul_dokumen ? escHtml(item.judul_dokumen) : "—") + "</td>" +
                    "<td " + TD + ">" + statusBadge + "</td>" +
                    "<td " + TD + ">" + jenisBadge + "</td>" +
                    "<td " + TD + ' style="text-align:center;">' + qtyCopyHtml + "</td>" +
                    "<td " + TD + ' style="text-align:center;">' + qtyAsliHtml + "</td>" +
                    "<td " + TD + ">" + driveHtml + "</td>" +
                    "<td " + TD + ">" + attachHtml + "</td>" +
                    "<td " + TD + ' style="white-space:nowrap;text-align:right;">' +
                    (item.status === 'belum_siap'
                        ? '<button type="button" class="btn btn-sm btn-success py-0 px-2 me-1 btn-output-status" data-id="' + item.id_output + '" data-status="siap" data-no-disable style="font-size:11px;"><i class="fa-solid fa-check me-1"></i>Siap</button>'
                        : '') +
                    (item.status === 'siap'
                        ? '<button type="button" class="btn btn-sm btn-primary py-0 px-2 me-1 btn-output-status" data-id="' + item.id_output + '" data-status="terkirim" data-no-disable style="font-size:11px;"><i class="fa-solid fa-paper-plane me-1"></i>Kirim</button>'
                        : '') +
                    '<button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 me-1 btn-edit-output" ' +
                    'data-id="' + item.id_output + '" data-no-disable style="font-size:11px;"><i class="fa-solid fa-pen"></i></button>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-delete-output" ' +
                    'data-id="' + item.id_output + '" data-no-disable style="font-size:11px;"><i class="fa-solid fa-trash"></i></button>' +
                    "</td></tr>"
                );
            })
            .join("");
    }

    return (
        '<div class="table-responsive">' +
        '<table class="table table-sm table-hover mb-0" style="font-size:13px;">' +
        '<thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;"><tr>' +
        "<th " + TH + ' style="width:40px;">#</th>' +
        "<th " + TH + ' style="min-width:200px;">Judul Output</th>' +
        "<th " + TH + ' style="min-width:160px;">Nomor Dokumen</th>' +
        "<th " + TH + ' style="min-width:120px;">Status</th>' +
        "<th " + TH + ' style="min-width:120px;">Jenis Dok.</th>' +
        "<th " + TH + ' style="min-width:80px;text-align:center;">Qty Copy</th>' +
        "<th " + TH + ' style="min-width:80px;text-align:center;">Qty Asli</th>' +
        "<th " + TH + ' style="min-width:80px;">Drive</th>' +
        "<th " + TH + ' style="min-width:150px;">Lampiran</th>' +
        "<th " + TH + ' style="min-width:80px;"></th>' +
        "</tr></thead>" +
        "<tbody>" +
        bodyHtml +
        "</tbody>" +
        "</table></div>"
    );
}

function showOutputForm(data) {
    var isEdit = data && data.id_output;
    var existingFilesHtml = "";
    if (isEdit && data.attachments) {
        try {
            var files =
                typeof data.attachments === "string"
                    ? JSON.parse(data.attachments)
                    : data.attachments;
            if (files && files.length) {
                existingFilesHtml = files
                    .map(function (p, i) {
                        return (
                            '<div class="d-inline-flex align-items-center gap-1 me-2 mb-1 existing-file-item" ' +
                            'style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:4px;padding:2px 8px;">' +
                            '<a href="/storage/' +
                            p +
                            '" target="_blank" style="font-size:11px;color:#166534;text-decoration:none;">' +
                            '<i class="fa-solid fa-paperclip me-1" style="font-size:10px;"></i>' +
                            escHtml(outputFileName(p)) +
                            "</a>" +
                            '<button type="button" class="btn-remove-existing-file" data-index="' +
                            i +
                            '" ' +
                            'style="border:none;background:none;color:#dc2626;padding:0 2px;cursor:pointer;font-size:11px;line-height:1;">' +
                            '<i class="fa-solid fa-times"></i></button>' +
                            '<input type="hidden" class="existing-file-path" value="' +
                            escHtml(p) +
                            '">' +
                            "</div>"
                        );
                    })
                    .join("");
            }
        } catch (e) {}
    }

    var html =
        (isEdit ? '<input type="hidden" id="outputEditId" value="' + data.id_output + '">' : '') +
        '<div class="row g-3">' +
        '<div class="col-md-6">' +
        '<label class="form-label form-label-sm text-muted mb-1">Judul Output <span class="text-danger">*</span></label>' +
        '<input type="text" id="outputJudulOutput" class="form-control form-control-sm" placeholder="Judul Output" maxlength="255" value="' + (isEdit ? escHtml(data.judul_output || '') : '') + '">' +
        '</div>' +
        '<div class="col-md-6">' +
        '<label class="form-label form-label-sm text-muted mb-1">Nomor Dokumen</label>' +
        '<input type="text" id="outputJudulDokumen" class="form-control form-control-sm" placeholder="Nomor Dokumen" maxlength="255" value="' + (isEdit ? escHtml(data.judul_dokumen || '') : '') + '">' +
        '</div>' +
        '<div class="col-md-3">' +
        '<label class="form-label form-label-sm text-muted mb-1">Status</label>' +
        '<select id="outputStatus" class="form-select form-select-sm">' +
        '<option value="belum_siap"' + (isEdit && data.status === 'belum_siap' ? ' selected' : (!isEdit ? ' selected' : '')) + '>Belum Siap</option>' +
        '<option value="siap"'      + (isEdit && data.status === 'siap'       ? ' selected' : '') + '>Siap</option>' +
        '<option value="terkirim"'  + (isEdit && data.status === 'terkirim'   ? ' selected' : '') + '>Terkirim</option>' +
        '</select>' +
        '</div>' +
        '<div class="col-md-3">' +
        '<label class="form-label form-label-sm text-muted mb-1">Jenis Dokumen</label>' +
        '<select id="outputJenisDokumen" class="form-select form-select-sm">' +
        '<option value="">— Pilih —</option>' +
        '<option value="copy"' + (isEdit && data.jenis_dokumen === 'copy' ? ' selected' : '') + '>Copy</option>' +
        '<option value="asli"' + (isEdit && data.jenis_dokumen === 'asli' ? ' selected' : '') + '>Asli</option>' +
        '<option value="asli_dan_copy"' + (isEdit && data.jenis_dokumen === 'asli_dan_copy' ? ' selected' : '') + '>Asli dan Copy</option>' +
        '</select>' +
        '</div>' +
        '<div id="outputQtyCopyWrap" class="col-md-3" style="display:none;">' +
        '<label class="form-label form-label-sm text-muted mb-1">Qty Copy</label>' +
        '<input type="number" id="outputQtyCopy" class="form-control form-control-sm" placeholder="Jumlah copy" min="1" value="' + (isEdit && data.qty_copy ? data.qty_copy : '') + '">' +
        '</div>' +
        '<div id="outputQtyAsliWrap" class="col-md-3" style="display:none;">' +
        '<label class="form-label form-label-sm text-muted mb-1">Qty Asli</label>' +
        '<input type="number" id="outputQtyAsli" class="form-control form-control-sm" placeholder="Jumlah asli" min="1" value="' + (isEdit && data.qty_asli ? data.qty_asli : '') + '">' +
        '</div>' +
        '<div class="col-md-6">' +
        '<label class="form-label form-label-sm text-muted mb-1"><i class="fa-brands fa-google-drive me-1" style="color:#1a73e8;"></i>Link Drive</label>' +
        '<input type="url" id="outputLinkDrive" class="form-control form-control-sm" placeholder="https://drive.google.com/..." value="' + (isEdit && data.link_drive ? escHtml(data.link_drive) : '') + '">' +
        '</div>' +
        '<div class="col-md-12">' +
        '<label class="form-label form-label-sm text-muted mb-1">Lampiran</label>' +
        (existingFilesHtml ? '<div id="existingFilesWrap" class="mb-2">' + existingFilesHtml + '</div>' : '') +
        '<input type="file" id="outputAttachments" multiple>' +
        '</div></div>';

    if (outputFilePond) {
        outputFilePond.destroy();
        outputFilePond = null;
    }

    $('#modalOutputFormTitle').html(
        '<i class="fa-solid fa-' + (isEdit ? 'pen' : 'plus') + ' me-2" style="color:#0f766e;"></i>' +
        (isEdit ? 'Edit Output' : 'Tambah Output')
    );
    $('#modalOutputFormBody').html(html);
    outputFilePond = createFileUploader('#outputAttachments');
    triggerOutputQtyVisibility(isEdit ? (data.jenis_dokumen || '') : '');

    var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalOutputForm'));
    modal.show();
    setTimeout(function () { $('#outputJudulOutput').focus(); }, 400);
}

function triggerOutputQtyVisibility(jenis) {
    $("#outputQtyCopyWrap").toggle(jenis === 'copy' || jenis === 'asli_dan_copy');
    $("#outputQtyAsliWrap").toggle(jenis === 'asli' || jenis === 'asli_dan_copy');
}

// ── Event handlers ─────────────────────────────────────────────────────────────
$(document).ready(function () {
    $(document).on("click", "#btnRefreshBoqProgress", function () {
        const woId = $(this).data("wo-id");
        const $icon = $(this).find("i");
        $icon.addClass("fa-spin");
        loadBoqProgress(woId);
        setTimeout(function () {
            $icon.removeClass("fa-spin");
        }, 600);
    });

    $(document).on("input", "#boqSearchInput", function () {
        const q = $(this).val().toLowerCase().trim();
        let visible = 0;
        $("#btnClearBoqSearch").toggleClass("d-none", !q);
        $(".boq-data-row").each(function () {
            const match =
                !q ||
                $(this)
                    .find("td:nth-child(2)")
                    .text()
                    .toLowerCase()
                    .includes(q);
            $(this).toggle(match);
            const boqId = $(this).data("boq-id");
            if (boqId && !match) {
                $(".fwo-sub-" + boqId).hide();
            }
            if (match) visible++;
        });
        const total = $(".boq-data-row").length;
        $("#boqSearchCount").text(
            q ? visible + " dari " + total + " item" : "",
        );
    });

    $(document).on("click", ".boq-expandable", function () {
        const boqId = $(this).data("boq-id");
        const $subs = $(".fwo-sub-" + boqId);
        const $chev = $(this).find(".boq-chevron");
        const isOpen = $subs.first().is(":visible");
        if (isOpen) {
            $subs.hide();
            $chev.css("transform", "");
        } else {
            $subs.show();
            $chev.css("transform", "rotate(90deg)");
        }
    });

    $(document).on("click", "#btnClearBoqSearch", function () {
        $("#boqSearchInput").val("").trigger("input");
    });

    // FWO Search
    $(document).on("input", "#fwoSearchInput", function () {
        const q = $(this).val().toLowerCase().trim();
        let visible = 0;
        $("#btnClearFwoSearch").toggleClass("d-none", !q);
        $(".fwo-data-row").each(function () {
            const match = !q || ($(this).data("search") || "").includes(q);
            $(this).toggle(match);
            if (match) visible++;
        });
        const total = $(".fwo-data-row").length;
        $("#fwoSearchCount").text(q ? visible + " dari " + total + " FWO" : "");
    });

    $(document).on("click", "#btnClearFwoSearch", function () {
        $("#fwoSearchInput").val("").trigger("input");
    });

    // ── Output Pekerjaan ─────────────────────────────────────────────────────
    $(document).on("click", "#btnAddOutput", function () {
        showOutputForm(null);
    });

    $(document).on("change", "#outputJenisDokumen", function () {
        triggerOutputQtyVisibility($(this).val());
    });

    // Tombol aksi status
    $(document).on("click", ".btn-output-status", function () {
        var $btn = $(this);
        var id = $btn.data('id');
        var newStatus = $btn.data('status');
        var label = newStatus === 'siap' ? 'Siap' : 'Terkirim';

        Notify.confirm('Ubah status menjadi <b>' + label + '</b>?', function () {
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
            $.ajax({
                url: window.route.outputBase + id + '/status',
                method: 'POST',
                data: { _token: window.route.csrf, status: newStatus },
                success: function () {
                    Notify.success('Status diperbarui menjadi ' + label);
                    loadOutputProgress(currentOutputWoId);
                    loadBoqProgress(currentOutputWoId);
                },
                error: function () {
                    Notify.error('Gagal mengubah status');
                    $btn.prop('disabled', false);
                }
            });
        });
    });


    $('#modalOutputForm').on('hidden.bs.modal', function () {
        if (outputFilePond) {
            outputFilePond.destroy();
            outputFilePond = null;
        }
        $('#modalOutputFormBody').html('');
    });

    $(document).on("click", ".btn-edit-output", function () {
        var id = $(this).data("id");
        var item = outputDataMap[id];
        if (item) {
            showOutputForm(item);
        }
    });

    $(document).on("click", ".btn-remove-existing-file", function () {
        $(this).closest(".existing-file-item").remove();
    });

    $(document).on("click", "#btnSaveOutput", function () {
        var judulOutput = $("#outputJudulOutput").val().trim();
        if (!judulOutput) {
            Swal.fire({
                icon: "warning",
                title: "Field wajib diisi",
                text: "Judul Output harus diisi.",
            });
            return;
        }
        var isEdit = $("#outputEditId").length > 0;
        var editId = isEdit ? $("#outputEditId").val() : null;

        var fd = new FormData();
        fd.append("_token", window.route.csrf);
        fd.append("judul_output", judulOutput);
        fd.append("judul_dokumen", $("#outputJudulDokumen").val().trim());
        fd.append("status", $("#outputStatus").val());
        fd.append("jenis_dokumen", $("#outputJenisDokumen").val());
        fd.append("qty_copy", $("#outputQtyCopy").val() || '');
        fd.append("qty_asli", $("#outputQtyAsli").val() || '');
        fd.append("link_drive", $("#outputLinkDrive").val().trim());
        if (!isEdit) {
            fd.append("id_wo", currentOutputWoId);
        }

        $("#existingFilesWrap .existing-file-path").each(function () {
            fd.append("existing_attachments[]", $(this).val());
        });

        if (outputFilePond) {
            outputFilePond.getFiles().forEach(function (f) {
                fd.append("attachments[]", f.file);
            });
        }

        var url = isEdit
            ? window.route.outputBase + editId
            : window.route.outputBase;
        var $btn = $("#btnSaveOutput");
        $btn.prop("disabled", true).html(
            '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...',
        );

        $.ajax({
            url: url,
            method: "POST",
            data: fd,
            processData: false,
            contentType: false,
            success: function () {
                Notify.success(isEdit ? "Output berhasil diperbarui" : "Output berhasil ditambahkan");
                $btn.prop("disabled", false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan');
                var modal = bootstrap.Modal.getInstance(document.getElementById('modalOutputForm'));
                if (modal) modal.hide();
                loadOutputProgress(currentOutputWoId);
                loadBoqProgress(currentOutputWoId);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || "Terjadi kesalahan");
                $btn.prop("disabled", false).html(
                    '<i class="fa-solid fa-floppy-disk me-1"></i> Simpan',
                );
            },
        });
    });

    $(document).on("click", ".btn-delete-output", function () {
        var id = $(this).data("id");
        Notify.confirm("Hapus output pekerjaan ini?", function () {
            $.ajax({
                url: window.route.outputBase + id,
                method: "POST",
                data: { _token: window.route.csrf, _method: "DELETE" },
                success: function () {
                    Notify.success("Output berhasil dihapus");
                    loadOutputProgress(currentOutputWoId);
                },
                error: function (xhr) {
                    Notify.error(
                        xhr.responseJSON?.message || "Terjadi kesalahan",
                    );
                },
            });
        });
    });

    // FWO Refresh
    $(document).on("click", "#btnRefreshFwoProgress", function () {
        const woId = $(this).data("wo-id");
        const $icon = $(this).find("i");
        $icon.addClass("fa-spin");
        loadBoqProgress(woId);
        setTimeout(function () {
            $icon.removeClass("fa-spin");
        }, 600);
    });

    // ── Copy FWO ─────────────────────────────────────────────────────────────
    $(document).on("click", ".btn-copy-fwo", function () {
        sourceFwoId = $(this).data("fwo-id");
        $("#modalCopyFwoBody").html(
            '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data FWO...</div>',
        );
        $("#btnConfirmCopyFwo").prop("disabled", true);
        new bootstrap.Modal($("#modalCopyFwo")[0]).show();

        $.when(
            $.get(window.route.fwoDetail + sourceFwoId),
            $.get(window.route.fwoBoqForCopy + sourceFwoId),
        )
            .done(function (fwoRes, boqRes) {
                const allFull = fillCopyFwoModal(fwoRes[0], boqRes[0]);
                $("#btnConfirmCopyFwo").prop("disabled", allFull);
            })
            .fail(function () {
                $("#modalCopyFwoBody").html(
                    '<div class="text-center text-danger py-4"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data FWO</div>',
                );
            });
    });

    $(document).on("click", "#btnAddCopyPersonel", function () {
        const row = $(renderCopyPersonelRow(null, null, ""));
        $("#copyFwoPersonelContainer").append(row);
        initCopyPersonelSelect2(row.find(".copy-personel-user-select"));
    });

    $(document).on("click", ".btn-remove-copy-personel", function () {
        $(this).closest(".copy-personel-row").remove();
    });

    $(document).on("click", "#btnConfirmCopyFwo", function () {
        const judul = $("#copyFwoJudul").val().trim();
        if (!judul) {
            Notify.warning("Judul pekerjaan wajib diisi");
            return;
        }

        const personels = [];
        let personelValid = true;
        $("#copyFwoPersonelContainer .copy-personel-row").each(function () {
            const userId = $(this).find(".copy-personel-user-select").val();
            const role = $(this).find(".copy-personel-role-select").val();
            if (!userId) {
                personelValid = false;
                return false;
            }
            personels.push({ id_user: parseInt(userId), role: role || null });
        });
        if (!personelValid) {
            Notify.warning("Pilih personel untuk semua baris");
            return;
        }

        const sections = [];
        $(".copy-boq-qty").each(function () {
            const boqId = $(this).data("boq-id");
            const qty = parseInt($(this).val());
            if (boqId && !isNaN(qty) && qty > 0) {
                sections.push({ id_boq: parseInt(boqId), qty: qty });
            }
        });

        const payload = {
            judul_pekerjaan: judul,
            tanggal_mulai: $("#copyFwoTglMulai").val() || null,
            tanggal_selesai: $("#copyFwoTglSelesai").val() || null,
            keterangan: $("#copyFwoKeterangan").val() || null,
            personels,
            sections,
        };

        const $btn = $(this);
        $btn.prop("disabled", true).html(
            '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...',
        );

        $.ajax({
            url: window.route.fwoDuplicate + sourceFwoId + "/duplicate",
            method: "POST",
            contentType: "application/json",
            headers: { "X-CSRF-TOKEN": window.route.csrf },
            data: JSON.stringify(payload),
            success: function (res) {
                Notify.success("FWO berhasil disalin: " + res.no_fwo);
                bootstrap.Modal.getInstance("#modalCopyFwo").hide();
                if (currentBoqWoId) loadBoqProgress(currentBoqWoId);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || "Gagal menyalin FWO");
                $btn.prop("disabled", false).html(
                    '<i class="fa-solid fa-copy me-1"></i> Buat Salinan',
                );
            },
        });
    });

    $("#modalCopyFwo").on("hidden.bs.modal", function () {
        sourceFwoId = null;
        $("#btnConfirmCopyFwo")
            .prop("disabled", false)
            .html('<i class="fa-solid fa-copy me-1"></i> Buat Salinan');
    });

    page = new CrudPageController({
        primaryKey: "id_wo",
        renderForm: renderForm,
        afterLoad: function (res) {
            currentWoData = res;
            loadBoqProgress(res.id_wo);
            loadOutputProgress(res.id_wo);
        },
    });

    $(document).on("click", ".btn-delete-record", function () {
        const id = $(this).data("id");
        Notify.confirm("Hapus Work Order?", function () {
            $.ajax({
                url: window.route.update + id,
                method: "POST",
                data: { _token: window.route.csrf, _method: "DELETE" },
                success: function (res) {
                    Notify.success(res.message || "Data berhasil dihapus");
                    $("#detailContent").html("");
                    page.selectedRow.id = null;
                    if ($.fn.DataTable.isDataTable("#masterTable")) {
                        $("#masterTable").DataTable().ajax.reload(null, false);
                    }
                },
                error: function (xhr) {
                    Notify.error(
                        xhr.responseJSON?.message || "Terjadi kesalahan",
                    );
                },
            });
        });
    });
});

// ── Copy FWO helpers ───────────────────────────────────────────────────────────
function renderCopyPersonelRow(userId, userName, role) {
    const idx = copyFwoPersonelIdx++;
    const roleOptions = ["Leader", "Driver", "Anggota"]
        .map(function (r) {
            return `<option value="${r}" ${role === r ? "selected" : ""}>${r}</option>`;
        })
        .join("");
    return `<div class="copy-personel-row d-flex align-items-start gap-2" data-idx="${idx}">
        <div style="flex:1;min-width:0;">
            <select class="form-select form-select-sm copy-personel-user-select"
                data-user-id="${userId || ""}" data-user-name="${escHtml(userName || "")}"></select>
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
    const userId = $select.data("user-id");
    const userName = $select.data("user-name");
    $select.select2({
        width: "100%",
        placeholder: "Ketik nama personel...",
        allowClear: true,
        minimumInputLength: 0,
        dropdownParent: $("#modalCopyFwo"),
        ajax: {
            url: window.route.usersSelect2,
            dataType: "json",
            delay: 200,
            data: function (p) {
                return { q: p.term };
            },
            processResults: function (d) {
                return { results: d };
            },
            cache: true,
        },
    });
    if (userId) {
        const opt = new Option(userName, userId, true, true);
        $select.append(opt).trigger("change");
    }
}

function fillCopyFwoModal(fwo, boqs) {
    copyFwoPersonelIdx = 0;
    const dateMulai = (fwo.tanggal_mulai || "").substring(0, 10);
    const dateSelesai = (fwo.tanggal_selesai || "").substring(0, 10);

    let personelHtml = (fwo.personels || [])
        .map(function (p) {
            return renderCopyPersonelRow(p.id_user, p.user_name, p.role);
        })
        .join("");
    if (!personelHtml) {
        personelHtml = renderCopyPersonelRow(null, null, "");
    }

    let boqHtml = "";
    if (boqs && boqs.length > 0) {
        function renderBoqCard(sec) {
            const unallocated = sec.unallocated_qty ?? 0;
            const isFull = unallocated <= 0;
            const satuan = sec.satuan ? " " + escHtml(sec.satuan) : "";
            const defaultQty = isFull
                ? ""
                : Math.min(sec.qty || 0, unallocated);

            const sisaHtml = isFull
                ? `<span style="font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px;background:#dcfce7;color:#166534;">Terpenuhi ✓</span>`
                : `<span style="font-size:10px;color:#64748b;">Sisa: <strong style="color:${unallocated < (sec.qty || 0) ? "#dc2626" : "#1d4ed8"};">${unallocated}${satuan}</strong></span>`;

            const input = isFull
                ? `<div style="width:90px;text-align:center;font-size:11px;color:#94a3b8;">—</div>`
                : `<input type="number" class="form-control form-control-sm text-end copy-boq-qty"
                    data-boq-id="${sec.id_boq}" value="${defaultQty}"
                    min="1" max="${unallocated}" placeholder="qty"
                    oninput="this.classList.toggle('is-invalid', this.value > ${unallocated})">`;

            const fullNotice = isFull
                ? `<div style="font-size:11px;color:#dc2626;margin-top:4px;">
                       <i class="fa-solid fa-circle-xmark me-1"></i>Qty sudah terpenuhi, tidak bisa ditambahkan
                   </div>`
                : "";

            return `<div class="d-flex align-items-center gap-3 p-2"
                style="background:${isFull ? "#f0fdf4" : "#f8fafc"};border:1px solid ${isFull ? "#bbf7d0" : "#e2e8f0"};border-radius:8px;">
                <div style="flex:1;min-width:0;">
                    <div class="fw-semibold small">${escHtml(sec.point_name)}</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span style="font-size:11px;color:#64748b;">Total BOQ: ${sec.boq_qty}${satuan}</span>
                        ${sisaHtml}
                    </div>
                    ${fullNotice}
                </div>
                <div style="width:90px;">${input}</div>
            </div>`;
        }

        // Pisahkan: item dari source FWO (qty !== null) vs item tambahan dari WO
        const sourceItems = boqs.filter(function (s) {
            return s.qty !== null;
        });
        const extraItems = boqs.filter(function (s) {
            return s.qty === null;
        });

        const sourceRows = sourceItems.map(renderBoqCard).join("");
        const extraRows = extraItems.map(renderBoqCard).join("");

        const divider =
            extraItems.length > 0
                ? `<div style="display:flex;align-items:center;gap:10px;margin:4px 0;">
                   <hr style="flex:1;border-color:#e2e8f0;margin:0;">
                   <span style="font-size:11px;color:#94a3b8;white-space:nowrap;font-weight:600;letter-spacing:.3px;">
                       <i class="fa-solid fa-plus me-1" style="font-size:10px;"></i>Tambahkan item lainnya
                   </span>
                   <hr style="flex:1;border-color:#e2e8f0;margin:0;">
               </div>
               <div class="d-flex flex-column gap-2">${extraRows}</div>`
                : "";

        const allFull = boqs.every(function (sec) {
            return (sec.unallocated_qty ?? 0) <= 0;
        });
        const allFullBanner = allFull
            ? `<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:8px;font-size:12px;color:#dc2626;display:flex;align-items:center;gap:8px;">
                   <i class="fa-solid fa-triangle-exclamation"></i>
                   <span>Semua BOQ item sudah terpenuhi. FWO baru tidak dapat menyertakan BOQ pada WO ini.</span>
               </div>`
            : "";

        boqHtml = `<div class="mb-3">
            <label class="form-label fw-semibold">Qty per BOQ Item</label>
            ${allFullBanner}
            <div class="d-flex flex-column gap-2">
                ${sourceRows}
            </div>
            ${divider}
        </div>`;
    }

    $("#modalCopyFwoBody").html(`
        <div class="alert alert-light border mb-3 py-2" style="font-size:12px;">
            <i class="fa-solid fa-copy me-1 text-primary"></i>
            Menyalin dari: <strong>${escHtml(fwo.no_fwo)}</strong>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Judul Pekerjaan <span class="text-danger">*</span></label>
            <input type="text" id="copyFwoJudul" class="form-control"
                value="${escHtml(fwo.judul_pekerjaan || "")}" maxlength="500">
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
            <textarea id="copyFwoKeterangan" class="form-control" rows="2">${escHtml(fwo.keterangan || "")}</textarea>
        </div>
        ${boqHtml}
        <label class="form-label fw-semibold">Personel</label>
        <div id="copyFwoPersonelContainer" class="d-flex flex-column gap-2 mb-2">${personelHtml}</div>
        <button type="button" id="btnAddCopyPersonel" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Tambah Personel
        </button>
        <div style="height:220px;"></div>
    `);

    $("#copyFwoPersonelContainer .copy-personel-user-select").each(function () {
        initCopyPersonelSelect2($(this));
    });

    const allFull =
        boqs &&
        boqs.length > 0 &&
        boqs.every(function (sec) {
            return (sec.unallocated_qty ?? 0) <= 0;
        });
    return allFull;
}
