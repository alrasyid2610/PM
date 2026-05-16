let page;
let currentWosData = null;
let currentSoId    = null;

function loadWoProgress(id_so, onDone) {
    currentSoId = id_so;
    $("#woProgressContent").html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>',
    );

    $.get(window.route.woProgress + id_so + "/wo-progress", function (wos) {
        currentWosData = wos;
        $("#woBadgeCount").text(wos ? wos.length : 0);
        if (wos && wos.length) renderWoSummary(wos);
        renderWoProgressView(wos);
        if (onDone) onDone();
    }).fail(function () {
        currentWosData = null;
        $("#woBadgeCount").text("!");
        $("#woProgressContent").html(
            '<div class="text-center text-danger py-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data</div>',
        );
        if (onDone) onDone();
    });
}

function filterWos(wos, term) {
    if (!term) return wos;
    const lower = term.toLowerCase();
    return (wos || []).filter(function (wo) {
        if ((wo.no_wo ?? "").toLowerCase().includes(lower)) return true;
        if ((wo.judul_pekerjaan ?? "").toLowerCase().includes(lower))
            return true;
        if ((wo.nama_period ?? "").toLowerCase().includes(lower)) return true;
        return false;
    });
}

function renderWoSummary(wos) {
    const totalWo = wos.length;
    const totalFwo = wos.reduce(function (s, w) {
        return s + (w.fwo_count || 0);
    }, 0);
    const totalBoqQty = wos.reduce(function (s, w) {
        return s + (w.total_boq_qty || 0);
    }, 0);
    const totalFwoQty = wos.reduce(function (s, w) {
        return s + (w.total_fwo_qty || 0);
    }, 0);
    const totalHarga = wos.reduce(function (s, w) {
        return s + (w.total_boq_amount || 0);
    }, 0);
    const pct =
        totalBoqQty > 0 ? Math.round((totalFwoQty / totalBoqQty) * 100) : 0;
    const pctBarColor =
        pct >= 100
            ? "#22c55e"
            : pct > 0
              ? "var(--primary-400,#4a9eff)"
              : "#cbd5e1";
    const pctTextColor =
        pct >= 100
            ? "#16a34a"
            : pct > 0
              ? "var(--primary-500,#1a5fbe)"
              : "#94a3b8";

    const harga =
        totalHarga > 0
            ? totalHarga >= 1e9
                ? "Rp " + (totalHarga / 1e9).toFixed(1) + " M"
                : "Rp " + (totalHarga / 1e6).toFixed(1) + " jt"
            : "—";

    const card = function (icon, label, value, iconBg) {
        return `<div style="flex:1;min-width:120px;background:#fff;border-radius:8px;
                     border:1px solid var(--primary-200,#bcd0f8);
                     box-shadow:0 1px 3px rgba(26,95,190,.06);
                     display:flex;align-items:center;gap:10px;padding:10px 12px;">
            <div style="width:30px;height:30px;border-radius:7px;background:${iconBg};
                         display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="${icon}" style="font-size:13px;color:#fff;"></i>
            </div>
            <div>
                <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;line-height:1;">${label}</div>
                <div style="font-size:15px;font-weight:700;color:var(--primary-700,#18386b);margin-top:2px;line-height:1.2;">${value}</div>
            </div>
        </div>`;
    };

    $("#woSummaryCard").html(
        `<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:4px;cursor:pointer;" title="Lihat daftar Work Order"
              onclick="document.getElementById('wo-section')?.scrollIntoView({behavior:'smooth',block:'start'})">
            ${card("fa-solid fa-briefcase", "Total WO", totalWo, "var(--primary-500,#1a5fbe)")}
            ${card("fa-solid fa-hard-hat", "Total FWO", totalFwo, "var(--primary-700,#18386b)")}
            ${card("fa-solid fa-layer-group", "Total BOQ", totalBoqQty + " qty", "#0891b2")}
            ${card("fa-solid fa-tag", "Total Nilai", harga, "#0f766e")}
            <div style="flex:2;min-width:200px;background:#fff;border-radius:8px;
                         border:1px solid var(--primary-200,#bcd0f8);
                         box-shadow:0 1px 3px rgba(26,95,190,.06);padding:10px 14px;">
                <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                    <i class="fa-solid fa-chart-line me-1"></i>Progress Keseluruhan
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="flex:1;">
                        <div style="height:6px;background:var(--primary-100,#e8f0fe);border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:${pct}%;background:${pctBarColor};border-radius:3px;transition:width .5s;"></div>
                        </div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:4px;">${totalFwoQty} / ${totalBoqQty} qty terpenuhi</div>
                    </div>
                    <div style="font-size:18px;font-weight:800;color:${pctTextColor};white-space:nowrap;min-width:44px;text-align:right;">${pct}%</div>
                </div>
            </div>
        </div>`,
    );
}

function renderWoProgressView(wos) {
    const term = ($("#woProgressSearch").val() || "").trim();
    const filtered = filterWos(wos, term);

    $("#btnClearWoSearch").toggleClass("d-none", !term);

    if (!wos || !wos.length) {
        $("#woProgressContent").html(
            '<div class="text-center text-muted py-4">' +
                '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
                "Belum ada Work Order untuk Sales Order ini</div>",
        );
    } else if (!filtered.length) {
        $("#woProgressContent").html(
            '<div class="text-center text-muted py-4">' +
                '<i class="fa-solid fa-magnifying-glass fa-2x d-block mb-2 opacity-25"></i>' +
                "Tidak ditemukan hasil untuk <strong>&ldquo;" +
                escSo(term) +
                "&rdquo;</strong></div>",
        );
    } else {
        $("#woProgressContent").html(renderWoProgressTable(filtered));
    }
}

function renderWoProgressTable(wos) {
    const rows = wos
        .map(function (wo) {
            const INTERVAL_LABELS = {
                1: "Bulanan",
                2: "Bimulanan",
                3: "Triwulan",
                4: "Caturwulan",
                6: "Semester",
                12: "Annual",
            };
            const amount =
                wo.total_boq_amount > 0
                    ? "Rp " +
                      Number(wo.total_boq_amount).toLocaleString("en-US")
                    : "—";
            const period = wo.nama_period
                ? `<div class="fw-semibold" style="font-size:12px;color:#166534;">${escSo(wo.nama_period)}</div>` +
                  (wo.tanggal_mulai || wo.tanggal_selesai
                      ? `<div style="font-size:11px;color:#64748b;margin-top:1px;">
                       <i class="fa-solid fa-calendar-days me-1" style="font-size:10px;"></i>${(wo.tanggal_mulai || "").substring(0, 7)} s/d ${(wo.tanggal_selesai || "").substring(0, 7)}
                   </div>`
                      : "") +
                  (wo.interval_bulan
                      ? `<div style="font-size:11px;color:#64748b;">
                       <i class="fa-solid fa-rotate me-1" style="font-size:10px;"></i>${INTERVAL_LABELS[wo.interval_bulan] || wo.interval_bulan + " bln"}
                   </div>`
                      : "")
                : `<span class="text-muted">—</span>`;

            const pctColor =
                wo.progress_pct >= 100
                    ? "#198754"
                    : wo.progress_pct > 0
                      ? "#1d4ed8"
                      : "#6c757d";
            const pctBg =
                wo.progress_pct >= 100
                    ? "#d1fae5"
                    : wo.progress_pct > 0
                      ? "#dbeafe"
                      : "#e9ecef";

            const TD = 'class="align-middle" style="padding:8px 12px;"';
            return `<tr>
            <td ${TD}>
                <a href="/work-orders?open=${wo.id_wo}" target="_blank"
                    class="fw-semibold text-decoration-none" style="color:#1d4ed8;font-size:13px;white-space:nowrap;">
                    ${escSo(wo.no_wo ?? "—")}
                </a>
            </td>
            <td ${TD} style="padding:8px 12px;color:#374151;">${escSo(wo.nama_pelanggan ?? "—")}</td>
            <td ${TD} style="padding:8px 12px;color:#374151;">${escSo(wo.nama_site_pelanggan ?? "—")}</td>
            <td ${TD} style="padding:8px 12px;color:#374151;">${escSo(wo.judul_pekerjaan ?? "—")}</td>
            <td ${TD} style="padding:8px 12px;">${period}</td>
            <td ${TD} style="padding:8px 12px;text-align:center;color:#6d28d9;font-weight:600;">${wo.total_boq_qty}</td>
            <td ${TD} style="padding:8px 12px;text-align:center;color:#7c3aed;font-weight:600;">${wo.fwo_count}</td>
            <td ${TD} style="padding:8px 12px;text-align:center;">
                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;white-space:nowrap;background:${pctBg};color:${pctColor};">
                    ${wo.progress_pct}%
                </span>
            </td>
            <td ${TD} style="padding:8px 12px;color:#1d4ed8;font-weight:600;white-space:nowrap;">${amount}</td>
            <td ${TD} style="padding:8px 12px;text-align:center;white-space:nowrap;">
                <a href="/work-orders?open=${wo.id_wo}" target="_blank"
                    class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" title="Buka detail WO">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 ms-1 btn-copy-wo"
                    style="font-size:11px;" title="Salin WO ini" data-wo-id="${wo.id_wo}">
                    <i class="fa-solid fa-copy"></i>
                </button>
            </td>
        </tr>`;
        })
        .join("");

    const TH_BASE =
        'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;"';
    return `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;min-width:1400px;">
            <thead class="text-muted fw-semibold" style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th ${TH_BASE} style="min-width:120px;">No WO</th>
                    <th ${TH_BASE} style="min-width:220px;">Pelanggan</th>
                    <th ${TH_BASE} style="min-width:200px;">Site Pelanggan</th>
                    <th ${TH_BASE} style="min-width:240px;">Judul Pekerjaan</th>
                    <th ${TH_BASE} style="min-width:220px;">Period</th>
                    <th ${TH_BASE} style="min-width:90px;text-align:center;">Total BOQ</th>
                    <th ${TH_BASE} style="min-width:90px;text-align:center;">Total FWO</th>
                    <th ${TH_BASE} style="min-width:90px;text-align:center;">Progress</th>
                    <th ${TH_BASE} style="min-width:130px;">Total Harga</th>
                    <th ${TH_BASE} style="min-width:90px;"></th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
}

function escSo(str) {
    return String(str ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

// ── SO Period Section ─────────────────────────────────────────────────────────

function loadSoPeriods(id_so) {
    var $wrap = $("#soPeriodContent");
    $wrap.html(
        '<div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>',
    );

    $.get("/wo-periods/by-so/" + id_so, function (periods) {
        window._soPeriods = periods;
        $wrap.html(renderSoPeriodList(periods, id_so));
    }).fail(function () {
        $wrap.html(
            '<div class="text-muted small text-danger text-center py-2">Gagal memuat data period</div>',
        );
    });
}

function renderSoPeriodList(periods, id_so) {
    if (!periods || !periods.length) {
        return (
            '<div class="text-center text-muted py-3">' +
            '<i class="fa-solid fa-calendar-xmark fa-lg d-block mb-2 opacity-25"></i>' +
            '<span class="small">Belum ada period jadwal untuk SO ini</span></div>'
        );
    }

    return periods
        .map(function (p) {
            return renderSoPeriodCard(p, id_so);
        })
        .join("");
}

function renderSoPeriodCard(p, id_so) {
    var tglMulai = p.tanggal_mulai ? p.tanggal_mulai.substring(0, 7) : "—";
    var tglSelesai = p.tanggal_selesai
        ? p.tanggal_selesai.substring(0, 7)
        : "—";
    var INTERVAL_LABELS_SO = {
        1: "Bulanan",
        2: "Bimulanan",
        3: "Triwulan",
        4: "Caturwulan",
        6: "Semester",
        12: "Annual",
    };
    var interval = p.interval_bulan
        ? INTERVAL_LABELS_SO[p.interval_bulan] || p.interval_bulan + " bln"
        : "—";

    var expectedWo =
        p.tanggal_mulai && p.tanggal_selesai && p.interval_bulan
            ? Math.floor(
                  (new Date(p.tanggal_selesai) - new Date(p.tanggal_mulai)) /
                      (1000 * 60 * 60 * 24 * 30) /
                      p.interval_bulan,
              )
            : null;

    var collapseId = "period-wo-" + p.id_period;

    var woTableHtml = "";
    if (p.wos && p.wos.length) {
        var groups = {};
        var groupOrder = [];
        p.wos.forEach(function (wo) {
            var key = wo.nama_site_wo || "__none__";
            if (!groups[key]) {
                groups[key] = [];
                groupOrder.push(key);
            }
            groups[key].push(wo);
        });

        var allRows = groupOrder
            .map(function (key, siteIdx) {
                var siteLabel = key === "__none__" ? "Lokasi tidak diset" : key;
                var siteGroupId = "pg-" + p.id_period + "-" + siteIdx;
                var woCount = groups[key].length;

                var groupHeader =
                    '<tr class="btn-site-group-toggle" data-target="' +
                    siteGroupId +
                    '" ' +
                    'style="background:#f1f5f9;cursor:pointer;user-select:none;">' +
                    '<td colspan="3" style="padding:6px 10px;font-size:11px;font-weight:700;color:#475569;' +
                    'text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #e2e8f0;">' +
                    '<div class="d-flex align-items-center justify-content-between">' +
                    "<span>" +
                    '<i class="fa-solid fa-location-dot me-1" style="color:var(--primary-500,#1a5fbe);"></i>' +
                    escSo(siteLabel) +
                    "</span>" +
                    '<span class="d-flex align-items-center gap-2">' +
                    '<span style="font-size:10px;background:#e2e8f0;color:#64748b;padding:1px 7px;border-radius:20px;font-weight:600;">' +
                    woCount +
                    " WO" +
                    "</span>" +
                    '<i class="fa-solid fa-chevron-down site-chevron" style="font-size:10px;transition:transform .2s;"></i>' +
                    "</span>" +
                    "</div>" +
                    "</td>" +
                    "</tr>";

                var dataRows = groups[key]
                    .map(function (wo) {
                        return (
                            '<tr class="site-group-rows ' +
                            siteGroupId +
                            '" style="border-bottom:1px solid #f1f5f9;">' +
                            '<td style="padding:6px 10px;white-space:nowrap;width:130px;">' +
                            '<a href="/work-orders?open=' +
                            wo.id_wo +
                            '" target="_blank" ' +
                            'class="fw-semibold text-decoration-none" style="color:var(--primary-700);font-size:12px;">' +
                            '<i class="fa-solid fa-briefcase me-1" style="font-size:10px;"></i>' +
                            escSo(wo.no_wo) +
                            "</a>" +
                            "</td>" +
                            '<td style="padding:6px 10px;font-size:12px;color:#475569;">' +
                            escSo(wo.judul_pekerjaan || "—") +
                            "</td>" +
                            '<td style="padding:6px 10px;text-align:right;width:40px;">' +
                            '<a href="/work-orders?open=' +
                            wo.id_wo +
                            '" target="_blank" ' +
                            'class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:10px;" title="Buka WO">' +
                            '<i class="fa-solid fa-arrow-up-right-from-square"></i>' +
                            "</a>" +
                            "</td>" +
                            "</tr>"
                        );
                    })
                    .join("");

                return groupHeader + dataRows;
            })
            .join("");

        woTableHtml =
            '<div class="table-responsive" style="border-radius:0 0 6px 6px;overflow:hidden;">' +
            '<table class="table table-sm mb-0" style="font-size:13px;">' +
            '<thead style="background:#e8f0fe;">' +
            "<tr>" +
            '<th style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#64748b;padding:5px 10px;border-color:#e2e8f0;">No WO</th>' +
            '<th style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#64748b;padding:5px 10px;border-color:#e2e8f0;">Judul Pekerjaan</th>' +
            '<th style="border-color:#e2e8f0;"></th>' +
            "</tr>" +
            "</thead>" +
            "<tbody>" +
            allRows +
            "</tbody>" +
            "</table>" +
            "</div>";
    } else {
        woTableHtml =
            '<div class="text-center text-muted py-3" style="font-size:12px;">' +
            '<i class="fa-solid fa-inbox fa-lg d-block mb-1 opacity-25"></i>Belum ada WO ter-assign' +
            "</div>";
    }

    var assignedCount = p.wos ? p.wos.length : 0;
    var isFull = expectedWo !== null && assignedCount >= expectedWo;
    var countBadge =
        expectedWo !== null
            ? '<span class="badge ' +
              (isFull ? "bg-success" : "bg-warning text-dark") +
              ' bg-opacity-10" style="font-size:11px;">' +
              assignedCount +
              " / " +
              expectedWo +
              " WO" +
              (isFull ? " ✓" : "") +
              "</span>"
            : '<span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:11px;">' +
              assignedCount +
              " WO</span>";

    return (
        '<div class="card mb-2 so-period-card" data-period-id="' +
        p.id_period +
        '" style="border:0.5px solid #e2e8f0;">' +
        // ── Header ──────────────────────────────────────────────────
        '<div class="card-header py-2 px-3" style="background:#f8fafc;">' +
        '<div class="d-flex justify-content-between align-items-center">' +
        '<div class="d-flex align-items-center gap-2 flex-wrap">' +
        '<i class="fa-solid fa-calendar-days" style="color:var(--primary-500);font-size:13px;"></i>' +
        '<span class="fw-semibold small">' +
        escSo(p.nama_period ?? "—") +
        "</span>" +
        (p.nama_site
            ? '<span class="small text-muted"><i class="fa-solid fa-location-dot me-1" style="font-size:10px;"></i>' +
              escSo(p.nama_site) +
              "</span>"
            : "") +
        countBadge +
        "</div>" +
        '<div class="d-flex gap-1">' +
        '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-edit-period" ' +
        'data-period-id="' +
        p.id_period +
        '" data-no-disable style="font-size:11px;" title="Edit period">' +
        '<i class="fa-solid fa-pen"></i>' +
        "</button>" +
        '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-delete-period" ' +
        'data-period-id="' +
        p.id_period +
        '" data-no-disable style="font-size:11px;" title="Hapus period">' +
        '<i class="fa-solid fa-trash"></i>' +
        "</button>" +
        '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-period-toggle" ' +
        'data-target="' +
        collapseId +
        '" data-no-disable style="font-size:11px;" title="Lihat WO">' +
        '<i class="fa-solid fa-chevron-down"></i>' +
        "</button>" +
        "</div>" +
        "</div>" +
        // Meta info — selalu terlihat
        '<div class="d-flex flex-wrap gap-3 mt-2">' +
        "<div>" +
        '<div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;">Jadwal</div>' +
        '<div class="small fw-semibold">' +
        tglMulai +
        " s/d " +
        tglSelesai +
        "</div>" +
        "</div>" +
        "<div>" +
        '<div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;">Frekuensi</div>' +
        '<div class="small fw-semibold">' +
        escSo(interval) +
        "</div>" +
        "</div>" +
        (p.keterangan
            ? "<div>" +
              '<div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;">Keterangan</div>' +
              '<div class="small">' +
              escSo(p.keterangan) +
              "</div>" +
              "</div>"
            : "") +
        "</div>" +
        "</div>" +
        // ── Collapsible WO table ─────────────────────────────────────
        '<div id="' +
        collapseId +
        '" style="display:none;">' +
        woTableHtml +
        "</div>" +
        "</div>"
    );
}

function renderSoPeriodForm(id_so, p) {
    var isEdit = !!p;
    var action = isEdit ? "Edit" : "Tambah";
    var siteId = p ? (p.id_site ?? "") : "";
    var siteName = p ? (p.nama_site ?? "") : "";

    return (
        '<div id="soPeriodFormWrap" class="p-3 mb-3" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">' +
        '<div class="small fw-semibold text-muted mb-3">' +
        '<i class="fa-solid fa-calendar-plus me-1"></i>' +
        action +
        " Period" +
        "</div>" +
        '<div class="row g-2">' +
        '<div class="col-md-6">' +
        '<label class="form-label form-label-sm text-muted mb-1">Nama Period <span class="text-danger">*</span></label>' +
        '<input type="text" id="soPeriodNama" class="form-control form-control-sm" placeholder="cth: Maintenance Semester 1" value="' +
        (p ? escSo(p.nama_period ?? "") : "") +
        '">' +
        "</div>" +
        '<div class="col-md-6">' +
        '<label class="form-label form-label-sm text-muted mb-1">Lokasi (Site) <span class="text-muted fst-italic" style="font-size:10px;">opsional</span></label>' +
        '<select id="soPeriodSite" style="width:100%"></select>' +
        "</div>" +
        '<div class="col-md-5">' +
        '<label class="form-label form-label-sm text-muted mb-1">Tanggal Mulai</label>' +
        '<input type="date" id="soPeriodMulai" class="form-control form-control-sm" value="' +
        (p ? (p.tanggal_mulai ?? "") : "") +
        '">' +
        "</div>" +
        '<div class="col-md-5">' +
        '<label class="form-label form-label-sm text-muted mb-1">Tanggal Selesai</label>' +
        '<input type="date" id="soPeriodSelesai" class="form-control form-control-sm" value="' +
        (p ? (p.tanggal_selesai ?? "") : "") +
        '">' +
        "</div>" +
        '<div class="col-md-2">' +
        '<label class="form-label form-label-sm text-muted mb-1">Frekuensi</label>' +
        '<select id="soPeriodInterval" class="form-select form-select-sm">' +
        '<option value="">Pilih...</option>' +
        '<option value="1"' +
        (p && p.interval_bulan == 1 ? " selected" : "") +
        ">Bulanan</option>" +
        '<option value="2"' +
        (p && p.interval_bulan == 2 ? " selected" : "") +
        ">Bimulanan</option>" +
        '<option value="3"' +
        (p && p.interval_bulan == 3 ? " selected" : "") +
        ">Triwulan</option>" +
        '<option value="4"' +
        (p && p.interval_bulan == 4 ? " selected" : "") +
        ">Caturwulan</option>" +
        '<option value="6"' +
        (p && p.interval_bulan == 6 ? " selected" : "") +
        ">Semester</option>" +
        '<option value="12"' +
        (p && p.interval_bulan == 12 ? " selected" : "") +
        ">Annual</option>" +
        "</select>" +
        "</div>" +
        '<div class="col-md-12">' +
        '<label class="form-label form-label-sm text-muted mb-1">Keterangan</label>' +
        '<input type="text" id="soPeriodKet" class="form-control form-control-sm" placeholder="opsional" value="' +
        (p ? escSo(p.keterangan ?? "") : "") +
        '">' +
        "</div>" +
        "</div>" +
        '<div class="d-flex justify-content-end gap-2 mt-3">' +
        '<button type="button" id="btnCancelSoPeriodForm" class="btn btn-outline-secondary btn-sm">Batal</button>' +
        '<button type="button" id="btnSaveSoPeriod" class="btn btn-primary btn-sm" ' +
        'data-period-id="' +
        (p ? p.id_period : "") +
        '" data-id-so="' +
        id_so +
        '">' +
        '<i class="fa-solid fa-check me-1"></i> Simpan' +
        "</button>" +
        "</div>" +
        "</div>"
    );
}

function initSoPeriodSiteSelect2(siteId, siteName) {
    var $el = $("#soPeriodSite");
    $el.select2({
        dropdownParent: $("#soPeriodFormWrap"),
        placeholder: "Pilih lokasi...",
        allowClear: true,
        ajax: {
            url:
                window.route.siteSelect2 || "/business-relations/sites/select2",
            dataType: "json",
            delay: 200,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data };
            },
        },
    });
    if (siteId) {
        var opt = new Option(siteName || siteId, siteId, true, true);
        $el.append(opt).trigger("change");
    }
}

function initSoPeriodSection(id_so) {
    // Tambah Period
    $(document).on("click", "#btnTambahPeriodSo", function () {
        if ($("#soPeriodFormWrap").length) return;
        $("#soPeriodContent").prepend(renderSoPeriodForm(id_so, null));
        initSoPeriodSiteSelect2("", "");
    });

    // Edit Period
    $(document).on("click", ".btn-edit-period", function () {
        if ($("#soPeriodFormWrap").length) return;
        var periodId = $(this).data("period-id");
        var $card = $(this).closest(".so-period-card");
        var periods = window._soPeriods || [];
        var p = periods.find(function (x) {
            return x.id_period == periodId;
        });
        if (!p) return;
        $card.before(renderSoPeriodForm(id_so, p));
        $card.hide();
        initSoPeriodSiteSelect2(p.id_site, p.nama_site);
        $("#btnCancelSoPeriodForm").data("restore-card", periodId);
    });

    // Batal form
    $(document).on("click", "#btnCancelSoPeriodForm", function () {
        var restoreId = $(this).data("restore-card");
        if (restoreId) {
            $('.so-period-card[data-period-id="' + restoreId + '"]').show();
        }
        $("#soPeriodFormWrap").remove();
    });

    // Simpan (create or update)
    $(document).on("click", "#btnSaveSoPeriod", function () {
        var $btn = $(this);
        var periodId = $btn.data("period-id");
        var idSo = $btn.data("id-so");
        var siteId = $("#soPeriodSite").val();
        var namaPeriod = $("#soPeriodNama").val().trim();
        if (!namaPeriod) {
            Notify.error("Nama period wajib diisi");
            return;
        }

        var payload = {
            _token: window.route.csrf,
            id_so: idSo,
            nama_period: namaPeriod,
            id_site: siteId || null,
            tanggal_mulai: $("#soPeriodMulai").val() || null,
            tanggal_selesai: $("#soPeriodSelesai").val() || null,
            interval_bulan: $("#soPeriodInterval").val() || null,
            keterangan: $("#soPeriodKet").val() || null,
        };

        var url = periodId ? "/wo-periods/" + periodId : "/wo-periods";
        var method = periodId ? "PUT" : "POST";
        if (method === "PUT") payload._method = "PUT";

        Notify.confirm("Simpan Data?", function () {
            $btn.prop("disabled", true).html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i>',
            );
            $.ajax({
                url: url,
                method: "POST",
                data: payload,
                success: function () {
                    $("#soPeriodFormWrap").remove();
                    $(".so-period-card").show();
                    loadSoPeriods(id_so);
                    Notify.success("Period berhasil disimpan");
                },
                error: function () {
                    Notify.error("Gagal menyimpan period");
                },
                complete: function () {
                    $btn.prop("disabled", false).html(
                        '<i class="fa-solid fa-check me-1"></i> Simpan',
                    );
                },
            });
        });
    });

    // Hapus Period
    $(document).on("click", ".btn-delete-period", function () {
        var periodId = $(this).data("period-id");
        Notify.confirm("Hapus Period?", function () {
            $.ajax({
                url: "/wo-periods/" + periodId,
                method: "POST",
                data: { _token: window.route.csrf, _method: "DELETE" },
                success: function () {
                    loadSoPeriods(id_so);
                    Notify.success("Period berhasil dihapus");
                },
                error: function () {
                    Notify.error("Gagal menghapus period");
                },
            });
        });
    });
}

$(document).ready(function () {
    // Search WO / FWO
    $(document).on("input", "#woProgressSearch", function () {
        renderWoProgressView(currentWosData);
    });

    // Clear search
    $(document).on("click", "#btnClearWoSearch", function () {
        $("#woProgressSearch").val("");
        renderWoProgressView(currentWosData);
    });

    // Refresh WO Progress
    $(document).on("click", "#btnRefreshWoProgress", function () {
        const soId = $(this).data("so-id");
        const $icon = $(this).find("i");
        $icon.addClass("fa-spin");
        $("#woProgressSearch").val("");
        $("#btnClearWoSearch").addClass("d-none");
        loadWoProgress(soId, function () {
            $icon.removeClass("fa-spin");
        });
    });

    // Salin WO
    $(document).on('click', '.btn-copy-wo', function () {
        const woId = $(this).data('wo-id');
        const $btn = $(this);

        Notify.confirm('Buat salinan Work Order ini?', function () {
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

            $.ajax({
                url:    window.route.woDuplicate + woId + '/duplicate',
                method: 'POST',
                data:   { _token: window.route.csrf },
                success: function (res) {
                    Notify.success('WO berhasil disalin: ' + res.no_wo);
                    loadWoProgress(currentSoId);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Gagal menyalin WO');
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-copy"></i>');
                },
            });
        });
    });

    // Toggle expand/collapse WO table di period card
    $(document).on("click", ".btn-period-toggle", function () {
        const targetId = $(this).data("target");
        const $target = $("#" + targetId);
        const $icon = $(this).find("i");
        $target.slideToggle(200);
        $icon.toggleClass("fa-chevron-down fa-chevron-up");
    });

    // Toggle lokasi group di dalam tabel period
    $(document).on("click", ".btn-site-group-toggle", function () {
        const groupId = $(this).data("target");
        const $rows = $("tr." + groupId);
        const $chevron = $(this).find(".site-chevron");
        const isOpen = $rows.first().is(":visible");
        $rows.each(function () {
            $(this).toggle(!isOpen);
        });
        $chevron.css("transform", isOpen ? "rotate(-90deg)" : "rotate(0deg)");
    });

    page = new CrudPageController({
        primaryKey: "id_so",
        renderForm: renderForm,
        afterLoad: function (res) {
            loadWoProgress(res.id_so);
            // Load period section
            $.get("/wo-periods/by-so/" + res.id_so, function (periods) {
                window._soPeriods = periods;
                $("#soPeriodContent").html(
                    renderSoPeriodList(periods, res.id_so),
                );
            }).fail(function () {
                $("#soPeriodContent").html(
                    '<div class="text-muted small text-danger text-center py-2">Gagal memuat data period</div>',
                );
            });
            initSoPeriodSection(res.id_so);
        },
    });
});
