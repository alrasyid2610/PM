let page;
let currentWosData = null;
let currentSoId = null;

const MONTH_SHORT = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "Mei",
    "Jun",
    "Jul",
    "Agt",
    "Sep",
    "Okt",
    "Nov",
    "Des",
];
function fmtDate(str) {
    if (!str) return "—";
    var d = new Date(str);
    if (isNaN(d)) return str;
    return (
        d.getDate() + "-" + MONTH_SHORT[d.getMonth()] + "-" + d.getFullYear()
    );
}

window.addEventListener("storage", function (e) {
    if (e.key === "wo_created" && e.newValue) {
        try {
            var data = JSON.parse(e.newValue);
            var target = data.id_so || currentSoId;
            if (target) loadWoProgress(target);
            var modal = bootstrap.Modal.getInstance(
                document.getElementById("modalCreateWo"),
            );
            if (modal) {
                modal.hide();
                document.getElementById("iframeCreateWo").src = "";
            }
        } catch (_) {}
    }
    if (e.key === "termin_created" && e.newValue) {
        try {
            var data = JSON.parse(e.newValue);
            var target = data.id_so || currentSoId;
            if (target) loadTerminList(target);
            var modal = bootstrap.Modal.getInstance(
                document.getElementById("modalCreateTermin"),
            );
            if (modal) {
                modal.hide();
                document.getElementById("iframeCreateTermin").src = "";
            }
        } catch (_) {}
    }
});

$(document).on("click", ".btn-add-wo-modal", function () {
    var soId = $(this).data("so-id");
    document.getElementById("iframeCreateWo").src =
        "/work-orders/create?id_so=" + soId + "&embed=1";
    var modal = new bootstrap.Modal(document.getElementById("modalCreateWo"));
    modal.show();
});

$(document).on("click", ".btn-add-termin-modal", function () {
    var soId = $(this).data("so-id");
    document.getElementById("iframeCreateTermin").src =
        "/termin/create?id_so=" + soId + "&embed=1";
    var modal = new bootstrap.Modal(
        document.getElementById("modalCreateTermin"),
    );
    modal.show();
});

function loadWoProgress(id_so, onDone) {
    currentSoId = id_so;
    $("#woProgressContent").html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>',
    );

    $.get(window.route.woProgress + id_so + "/wo-progress", function (wos) {
        currentWosData = wos;
        $("#woBadgeCount").text(wos ? wos.length : 0);
        renderSoSummary(wos);
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
        return false;
    });
}

function renderSoSummary(wos) {
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
    const barColor = pct >= 100 ? "#16a34a" : pct > 0 ? "#d97706" : "#94a3b8";
    const pctColor = pct >= 100 ? "#16a34a" : pct > 0 ? "#d97706" : "#94a3b8";

    const harga =
        totalHarga >= 1e9
            ? "Rp " + (totalHarga / 1e9).toFixed(1) + " M"
            : totalHarga >= 1e6
              ? "Rp " + (totalHarga / 1e6).toFixed(1) + " jt"
              : totalHarga > 0
                ? "Rp " + Number(totalHarga).toLocaleString("en-US")
                : "—";

    const kpiCard = function (icon, iconBg, label, value) {
        return `<div class="pm-kpi-card">
            <div class="pm-kpi-icon" style="background:${iconBg};">
                <i class="fa-solid ${icon}"></i>
            </div>
            <div>
                <div class="pm-kpi-label">${label}</div>
                <div class="pm-kpi-value">${value}</div>
            </div>
        </div>`;
    };

    const progressCard = `<div class="pm-kpi-card pm-kpi-card--progress">
        <div class="pm-kpi-progress-header">
            <span class="pm-kpi-label"><i class="fa-solid fa-chart-line me-1"></i>Progress Keseluruhan</span>
            <span class="pm-kpi-pct" style="color:${pctColor};">${pct}%</span>
        </div>
        <div class="pm-kpi-bar-wrap">
            <div class="pm-kpi-bar-fill" style="width:${pct}%;background:${barColor};"></div>
        </div>
        <div class="pm-kpi-progress-sub">${totalFwoQty} / ${totalBoqQty} qty terpenuhi</div>
    </div>`;

    $("#soSummaryCard").html(
        kpiCard(
            "fa-briefcase",
            "var(--primary-500,#1a5fbe)",
            "Total WO",
            totalWo + " WO",
        ) +
            kpiCard(
                "fa-hard-hat",
                "var(--primary-700,#18386b)",
                "Total FWO",
                totalFwo + " FWO",
            ) +
            kpiCard(
                "fa-layer-group",
                "#0891b2",
                "Total QTY",
                totalBoqQty + " qty",
            ) +
            kpiCard("fa-tag", "#0f766e", "Total Nilai", harga) +
            progressCard,
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
                escHtml(term) +
                "&rdquo;</strong></div>",
        );
    } else {
        $("#woProgressContent").html(renderWoProgressTable(filtered));
    }
}

const SO_INTERVAL_LABELS = {
    1: "Bulanan",
    2: "Bimulanan",
    3: "Triwulan",
    4: "Caturwulan",
    6: "Semester",
    12: "Annual",
};

function renderWoProgressTable(wos) {
    // Level 1: kelompokkan per Site Pelanggan
    const siteGroups = new Map();
    wos.forEach(function (wo) {
        const key = wo.nama_site_pelanggan || "—";
        if (!siteGroups.has(key)) siteGroups.set(key, []);
        siteGroups.get(key).push(wo);
    });

    const TH =
        'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 12px;vertical-align:middle;"';

    function buildRow(wo, idx) {
        const periodeKe = wo.no_urut_period
            ? wo.no_urut_period
            : '<span style="color:#94a3b8;">—</span>';
        return `<tr>
            <td ${TD} style="text-align:center;color:#94a3b8;font-size:12px;">${idx}</td>
            <td ${TD}>
                <a href="/work-orders?open=${wo.id_wo}" class="pm-link-record" style="white-space:nowrap;">
                    ${escHtml(wo.no_wo ?? "—")}
                </a>
            </td>
            <td ${TD} style="color:#374151;">${escHtml(wo.judul_pekerjaan ?? "—")}</td>
            <td ${TD} style="color:#64748b;">${wo.keterangan ? escHtml(wo.keterangan) : '<span style="color:#94a3b8;">—</span>'}</td>
            <td ${TD} style="text-align:center;">${periodeKe}</td>
            <td ${TD} style="text-align:center;color:#7c3aed;font-weight:600;">${wo.fwo_count}</td>
            <td ${TD} style="text-align:center;white-space:nowrap;color:#374151;">${wo.tanggal_mulai ? fmtDate(wo.tanggal_mulai) : '<span style="color:#94a3b8;">—</span>'}</td>
            <td ${TD} style="text-align:center;white-space:nowrap;color:#374151;">${wo.tanggal_selesai ? fmtDate(wo.tanggal_selesai) : '<span style="color:#94a3b8;">—</span>'}</td>
            <td ${TD} style="text-align:center;white-space:nowrap;">
                <a href="/work-orders?open=${wo.id_wo}"
                    class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" title="Buka detail WO">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 ms-1 btn-copy-wo"
                    style="font-size:11px;" title="Salin WO ini" data-wo-id="${wo.id_wo}">
                    <i class="fa-solid fa-copy"></i>
                </button>
            </td>
        </tr>`;
    }

    function buildPeriodSection(list, interval) {
        const label = interval
            ? SO_INTERVAL_LABELS[interval] || interval + " bln"
            : "Tidak Ada Periode";
        const badgeColor = interval ? "#0d9488" : "#64748b";
        const badgeBg = interval ? "#ccfbf1" : "#f1f5f9";
        const badgeBorder = interval ? "#5eead4" : "#e2e8f0";

        const rows = list
            .map(function (wo, i) {
                return buildRow(wo, i + 1);
            })
            .join("");

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
                                <th ${TH} style="width:130px;white-space:nowrap;">No WO</th>
                                <th ${TH} style="white-space:nowrap;">Judul Pekerjaan</th>
                                <th ${TH} style="width:160px;white-space:nowrap;">Keterangan</th>
                                <th ${TH} style="width:90px;text-align:center;white-space:nowrap;">Urutan ke-</th>
                                <th ${TH} style="width:90px;text-align:center;white-space:nowrap;">Total FWO</th>
                                <th ${TH} style="width:115px;text-align:center;white-space:nowrap;">Tgl Mulai</th>
                                <th ${TH} style="width:115px;text-align:center;white-space:nowrap;">Tgl Selesai</th>
                                <th ${TH} style="width:80px;text-align:center;white-space:nowrap;">Action</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
    }

    let items = "";
    siteGroups.forEach(function (list, site) {
        // Level 2: kelompokkan per Period (interval_bulan)
        const periodGroups = new Map();
        list.forEach(function (wo) {
            const key = wo.interval_bulan || null;
            if (!periodGroups.has(key)) periodGroups.set(key, []);
            periodGroups.get(key).push(wo);
        });

        // Urutkan: interval_bulan bernilai dulu (kecil ke besar), null terakhir
        const sortedPeriods = Array.from(periodGroups.entries()).sort(
            function (a, b) {
                if (a[0] === null) return 1;
                if (b[0] === null) return -1;
                return Number(a[0]) - Number(b[0]);
            },
        );

        const periodSections = sortedPeriods
            .map(function (entry) {
                return buildPeriodSection(entry[1], entry[0]);
            })
            .join("");

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
                <div class="pm-accordion-body" style="padding:0;">
                    ${periodSections}
                </div>
            </div>
        </div>`;
    });

    return `<div class="pm-accordion" style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">${items}</div>`;
}

// Accordion toggle (grup Site Pelanggan pada tab Work Orders)
$(document).on(
    "click",
    "#woProgressContent .pm-accordion-header",
    function (e) {
        if ($(e.target).closest("a, button").length) return;
        const $header = $(this);
        const $body = $header.next(".pm-accordion-collapse");
        const isOpen = $header.attr("aria-expanded") === "true";
        $header.attr("aria-expanded", !isOpen);
        $body.slideToggle(150);
    },
);

// Toggle period (sub-grup Period dalam site accordion)
$(document).on("click", "#woProgressContent .wo-period-header", function (e) {
    if ($(e.target).closest("a, button").length) return;
    const $header = $(this);
    const $body = $header.next(".wo-period-body");
    const $chevron = $header.find(".fa-chevron-down");
    const isOpen = $body.is(":visible");
    $body.slideToggle(150);
    $chevron.css("transform", isOpen ? "rotate(-90deg)" : "rotate(0deg)");
});

// ── Tab switch — show/hide action buttons ─────────────────────────────────────
$(document).on(
    "shown.bs.tab",
    '#soDetailTabs button[data-bs-toggle="tab"]',
    function (e) {
        const target = $(e.target).data("bs-target");
        $("#soTabActionsInfo, #soTabActionsWo, #soTabActionsTermin").addClass(
            "d-none",
        );
        if (target === "#tabInfoSo")
            $("#soTabActionsInfo").removeClass("d-none");
        if (target === "#tabWo") $("#soTabActionsWo").removeClass("d-none");
        if (target === "#tabTermin")
            $("#soTabActionsTermin").removeClass("d-none");
    },
);

// ── Load Termin ────────────────────────────────────────────────────────────────
function loadTerminList(id_so, onDone) {
    $("#terminContent").html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>',
    );
    $.get(window.route.terminBySo + id_so, function (data) {
        $("#terminContent").html(renderTerminTable(data));
        if (onDone) onDone();
    }).fail(function () {
        $("#terminContent").html(
            '<div class="text-center text-danger py-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data</div>',
        );
        if (onDone) onDone();
    });
}

function renderTerminTable(rows) {
    if (!rows || !rows.length) {
        return (
            '<div class="text-center text-muted py-4">' +
            '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
            "Belum ada Termin untuk Sales Order ini</div>"
        );
    }

    const statusClass = {
        pending: "pm-badge--pending",
        proses: "pm-badge--proses",
        selesai: "pm-badge--selesai",
        siap_kirim: "pm-badge--selesai",
    };
    const statusLabel = {
        pending: "Pending",
        proses: "Proses",
        selesai: "Selesai",
        siap_kirim: "Siap Kirim",
    };

    const rows_html = rows
        .map(function (t, idx) {
            const cls = statusClass[t.status] || "pm-badge--pending";
            const lbl = statusLabel[t.status] || t.status;
            const badge = `<span class="pm-badge ${cls}">${lbl}</span>`;
            const nilai = t.nilai
                ? "Rp " + Number(t.nilai).toLocaleString("id-ID")
                : "—";
            const pct = t.persentase ? t.persentase + "%" : "—";
            const tgl = t.tanggal ? t.tanggal.substring(0, 10) : "—";

            return `<tr>
            <td style="color:#94a3b8;text-align:center;">${idx + 1}</td>
            <td><a href="/termin?open=${t.id_termin}" class="pm-link-record">${escHtml(t.no_termin ?? "—")}</a></td>
            <td>${escHtml(t.nama ?? "—")}</td>
            <td class="text-center">${pct}</td>
            <td style="color:#1d4ed8;font-weight:600;">${nilai}</td>
            <td>${tgl}</td>
            <td>${badge}</td>
            <td class="text-center">
                <a href="/termin?open=${t.id_termin}"
                    class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" title="Buka detail Termin">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
            </td>
        </tr>`;
        })
        .join("");

    return `<div class="table-responsive">
        <table class="pm-table">
            <thead>
                <tr>
                    <th style="width:40px;text-align:center;">No</th>
                    <th style="min-width:120px;">No Termin</th>
                    <th style="min-width:200px;">Nama</th>
                    <th style="min-width:90px;text-align:center;">%</th>
                    <th style="min-width:130px;">Nilai</th>
                    <th style="min-width:110px;">Tanggal Selesai</th>
                    <th style="min-width:100px;">Status</th>
                    <th style="min-width:70px;">Aksi</th>
                </tr>
            </thead>
            <tbody>${rows_html}</tbody>
        </table>
    </div>`;
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
    let sourceWoId = null;

    $(document).on("click", ".btn-copy-wo", function () {
        sourceWoId = $(this).data("wo-id");
        $("#modalCopyWoBody").html(
            '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data WO...</div>',
        );
        $("#btnConfirmCopyWo").prop("disabled", true);
        new bootstrap.Modal($("#modalCopyWo")[0]).show();

        $.get(window.route.woDuplicate + sourceWoId + "/detail")
            .done(function (wo) {
                fillCopyWoModal(wo);
                $("#btnConfirmCopyWo").prop("disabled", false);
            })
            .fail(function () {
                $("#modalCopyWoBody").html(
                    '<div class="text-center text-danger py-4"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data WO</div>',
                );
            });
    });

    $(document).on("click", "#btnAddCopyWoBoq", function () {
        addCopyWoBoqRow();
    });

    $(document).on("click", ".btn-remove-copy-wo-boq", function () {
        $(this).closest(".copy-wo-boq-row").remove();
    });

    $(document).on("click", ".btn-edit-copy-wo-boq", function () {
        const $row = $(this).closest(".copy-wo-boq-row");
        const $panel = $row.find(".copy-wo-boq-edit-panel");
        const $btn = $(this);

        if ($panel.is(":visible")) {
            $panel.slideUp(150);
            $btn.html('<i class="fa-solid fa-pen me-1"></i> Edit');
            return;
        }

        if (!$row.attr("data-edit-loaded")) {
            $row.attr("data-edit-loaded", "1");
            const satuan = $row.attr("data-satuan") || "";
            const harga = $row.attr("data-harga") || "";
            const ket = $row.attr("data-keterangan") || "";
            const itemProduk = $row.attr("data-item-produk-alternate") || "";
            const tpId = $row.attr("data-id-testing-point");
            const opts = ["PCS", "Titik", "Set"]
                .map(
                    (v) =>
                        `<option value="${v}" ${satuan === v ? "selected" : ""}>${v}</option>`,
                )
                .join("");

            $panel.html(`
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label form-label-sm text-muted mb-1">Item Produk Alternatif</label>
                        <input type="text" class="form-control form-control-sm copy-wo-boq-item-produk"
                            value="${escHtml(itemProduk)}" placeholder="opsional">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm text-muted mb-1">Satuan</label>
                        <select class="form-select form-select-sm copy-wo-boq-satuan">
                            <option value="">— Pilih —</option>${opts}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label form-label-sm text-muted mb-1">Harga (Rp)</label>
                        <input type="text" inputmode="numeric" class="form-control form-control-sm copy-wo-boq-harga input-num-mask input-num-int"
                            value="${escHtml(harga)}" placeholder="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                        <input type="text" class="form-control form-control-sm copy-wo-boq-ket"
                            value="${escHtml(ket)}" placeholder="opsional">
                    </div>
                </div>
                <div class="copy-wo-boq-items-wrap">
                    <div class="text-center text-muted py-2" style="font-size:12px;">
                        <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat item...
                    </div>
                </div>`);

            let preCheckedIds;
            try {
                preCheckedIds = new Set(
                    JSON.parse($row.attr("data-testing-item-ids") || "[]").map(
                        String,
                    ),
                );
            } catch (e) {
                preCheckedIds = new Set();
            }
            loadCopyWoBoqItems($row, tpId, preCheckedIds);
        }

        initNumericMask($panel[0]);
        $panel.slideDown(150);
        $btn.html('<i class="fa-solid fa-chevron-up me-1"></i> Selesai');
    });

    $(document).on("click", "#btnConfirmCopyWo", function () {
        const judul = $("#copyWoJudul").val().trim();
        if (!judul) {
            Notify.warning("Judul pekerjaan wajib diisi");
            return;
        }

        const tglMulai = $("#copyWoTglMulai").val();
        const tglSelesai = $("#copyWoTglSelesai").val();
        $("#copyWoTglSelesaiError").remove();
        if (tglMulai && tglSelesai && tglSelesai < tglMulai) {
            $("#copyWoTglSelesai").after(
                '<div id="copyWoTglSelesaiError" class="text-danger mt-1" style="font-size:12px;"><i class="fa-solid fa-circle-exclamation me-1"></i>Tanggal selesai tidak boleh lebih kecil dari tanggal mulai</div>',
            );
            return;
        }

        // Collect BOQ rows
        const boq = [];
        $(".copy-wo-boq-row").each(function () {
            const $row = $(this);
            const isNew = $row.attr("data-is-new") === "1";
            const isEdited = $row.attr("data-edit-loaded") === "1";
            const useInputs = isNew || isEdited;

            const tpSelect = $row.find(".copy-wo-boq-tp-select");
            const idTp = tpSelect.length
                ? tpSelect.val()
                : $row.attr("data-id-testing-point");
            if (!idTp) return;

            const qty = $row.find(".copy-wo-boq-qty").val();

            let testingItemIds = [];
            if (useInputs) {
                $row.find(".copy-wo-item-check:checked").each(function () {
                    testingItemIds.push(parseInt($(this).data("item-id")));
                });
            } else {
                try {
                    testingItemIds = JSON.parse(
                        $row.attr("data-testing-item-ids") || "[]",
                    );
                } catch (e) {}
            }

            boq.push({
                id_testing_point: parseInt(idTp),
                qty: qty ? parseInt(qty) : null,
                satuan: useInputs
                    ? $row.find(".copy-wo-boq-satuan").val() || ""
                    : $row.attr("data-satuan") || "",
                harga: useInputs
                    ? (rawNumVal($row.find(".copy-wo-boq-harga")[0]) ?? null)
                    : $row.attr("data-harga") || null,
                keterangan: useInputs
                    ? $row.find(".copy-wo-boq-ket").val() || null
                    : $row.attr("data-keterangan") || null,
                item_produk_alternate: useInputs
                    ? $row.find(".copy-wo-boq-item-produk").val() || null
                    : $row.attr("data-item-produk-alternate") || null,
                testing_item_ids: testingItemIds,
            });
        });

        const $btn = $(this);
        $btn.prop("disabled", true).html(
            '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...',
        );

        $.ajax({
            url: window.route.woDuplicate + sourceWoId + "/duplicate",
            method: "POST",
            contentType: "application/json",
            headers: { "X-CSRF-TOKEN": window.route.csrf },
            data: JSON.stringify({
                judul_pekerjaan: judul,
                tanggal_mulai: tglMulai || null,
                tanggal_selesai: tglSelesai || null,
                keterangan: $("#copyWoKeterangan").val() || null,
                id_pic_pelanggan_pekerjaan: $("#copyWoPic").val() || null,
                no_urut_period: $("#copyWoUrutan").val() || null,
                boq: boq,
            }),
            success: function (res) {
                Notify.success("WO berhasil disalin: " + res.no_wo);
                bootstrap.Modal.getInstance($("#modalCopyWo")[0]).hide();
                loadWoProgress(currentSoId);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || "Gagal menyalin WO");
                $btn.prop("disabled", false).html(
                    '<i class="fa-solid fa-copy me-1"></i> Buat Salinan',
                );
            },
        });
    });

    $(document).on("change", "#copyWoTglMulai, #copyWoTglSelesai", function () {
        $("#copyWoTglSelesaiError").remove();
    });

    $("#modalCopyWo").on("hidden.bs.modal", function () {
        sourceWoId = null;
        $("#btnConfirmCopyWo")
            .prop("disabled", false)
            .html('<i class="fa-solid fa-copy me-1"></i> Buat Salinan');
    });

    // Refresh Termin
    $(document).on("click", "#btnRefreshTermin", function () {
        const soId = $(this).data("so-id");
        const $icon = $(this).find("i");
        $icon.addClass("fa-spin");
        loadTerminList(soId, function () {
            $icon.removeClass("fa-spin");
        });
    });

    page = new CrudPageController({
        primaryKey: "id_so",
        renderForm: renderForm,
        afterLoad: function (res) {
            loadWoProgress(res.id_so);
            loadTerminList(res.id_so);
            initFpDate('#detailContent');
        },
    });

    $(document).on("click", ".btn-delete-record", function () {
        const id = $(this).data("id");
        Notify.confirm("Hapus Sales Order?", function () {
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

function fillCopyWoModal(wo) {
    const dateMulai = (wo.tanggal_mulai || "").substring(0, 10);
    const dateSelesai = (wo.tanggal_selesai || "").substring(0, 10);
    const INTERVAL_LABELS = {
        1: "Bulanan",
        2: "Bimulanan",
        3: "Triwulan",
        4: "Caturwulan",
        6: "Semester",
        12: "Annual",
    };
    const intervalLabel = wo.interval_bulan
        ? INTERVAL_LABELS[wo.interval_bulan] || wo.interval_bulan + " bln"
        : "— Tidak ada —";

    const siteName = wo["Site Pelanggan"] ?? "";
    const siteHtml = siteName
        ? `<div style="display:flex;align-items:center;gap:6px;min-width:0;">
               <i class="fa-solid fa-location-dot" style="color:#0891b2;font-size:11px;flex-shrink:0;"></i>
               <span style="color:#0e7490;font-weight:600;white-space:nowrap;">${escHtml(siteName)}</span>
           </div>
           <div style="width:1px;height:16px;background:#e2e8f0;flex-shrink:0;"></div>`
        : "";

    // Hitung next urutan dari site_wos
    const siteWos = wo.site_wos || [];
    const maxUrut = siteWos.reduce(function (m, w) {
        return Math.max(m, w.no_urut_period || 0);
    }, 0);
    const nextUrut = maxUrut + 1;

    // Build WO list section
    let woListHtml = "";
    if (siteWos.length > 0) {
        const IL2 = {
            1: "Bulanan",
            2: "Bimulanan",
            3: "Triwulan",
            4: "Caturwulan",
            6: "Semester",
            12: "Annual",
        };
        const rows = siteWos
            .map(function (w) {
                const tglM = w.tanggal_mulai
                    ? w.tanggal_mulai.substring(0, 10)
                    : "—";
                const tglS = w.tanggal_selesai
                    ? w.tanggal_selesai.substring(0, 10)
                    : "—";
                const isSelf = w.id_wo == wo.id_wo;
                const periodeLabel =
                    w.interval_bulan && w.no_urut_period
                        ? (IL2[w.interval_bulan] || w.interval_bulan + " bln") +
                          " ke-" +
                          w.no_urut_period
                        : "—";
                return `<tr style="font-size:12px;${isSelf ? "background:#eff6ff;" : ""}">
                <td style="padding:5px 10px;font-weight:600;color:#1a56db;">${escHtml(w.no_wo)}${isSelf ? ' <span style="font-size:10px;padding:1px 5px;border-radius:8px;background:#dbeafe;color:#1e40af;">sumber</span>' : ""}</td>
                <td style="padding:5px 10px;color:#374151;">${escHtml(w.judul_pekerjaan || "—")}</td>
                <td style="padding:5px 10px;color:#64748b;white-space:nowrap;">${tglM}</td>
                <td style="padding:5px 10px;color:#64748b;white-space:nowrap;">${tglS}</td>
                <td style="padding:5px 10px;white-space:nowrap;">${periodeLabel !== "—" ? `<span style="font-size:11px;padding:2px 8px;border-radius:20px;background:#eff6ff;color:#1a56db;border:1px solid #bfdbfe;"><i class="fa-solid fa-calendar-days me-1" style="font-size:10px;"></i>${escHtml(periodeLabel)}</span>` : '<span style="color:#94a3b8;">—</span>'}</td>
            </tr>`;
            })
            .join("");
        woListHtml = `<div class="col-12">
            <div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                <div style="background:#f8fafc;padding:7px 12px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;cursor:pointer;"
                    onclick="var b=document.getElementById('copyWoSiteList');b.style.display=b.style.display==='none'?'block':'none';">
                    <span style="font-size:12px;font-weight:600;color:#1a56db;">
                        <i class="fa-solid fa-briefcase me-1"></i>WO yang sudah ada di lokasi ini
                        <span style="font-size:11px;font-weight:500;background:#dbeafe;color:#1e40af;padding:1px 7px;border-radius:20px;margin-left:4px;">${siteWos.length}</span>
                    </span>
                    <i class="fa-solid fa-chevron-down" style="font-size:10px;color:#94a3b8;"></i>
                </div>
                <div id="copyWoSiteList" style="display:none;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead style="background:#f8fafc;">
                            <tr style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">
                                <th style="padding:5px 10px;">No WO</th>
                                <th style="padding:5px 10px;">Judul</th>
                                <th style="padding:5px 10px;">Tgl Mulai</th>
                                <th style="padding:5px 10px;">Tgl Selesai</th>
                                <th style="padding:5px 10px;">Periode</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
    }

    const urutanRow = wo.interval_bulan
        ? `
        <div class="col-md-2">
            <label class="form-label">Urutan ke-</label>
            <input type="number" id="copyWoUrutan" class="form-control form-control-sm" value="${nextUrut}" min="1" style="width:80px;">
        </div>`
        : "";

    const picColClass = wo.interval_bulan ? "col-md-4" : "col-md-5";

    $("#modalCopyWoBody").html(`
        <div style="position:sticky;top:0;z-index:10;background:#fff;border-bottom:2px solid #e2e8f0;padding:10px 16px;margin:-16px -16px 16px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
            <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:13px;">
                ${siteHtml}
                <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                    <i class="fa-solid fa-file-contract" style="color:#1a56db;font-size:11px;flex-shrink:0;"></i>
                    <span style="font-weight:700;color:#1a56db;white-space:nowrap;">${escHtml(wo.no_so ?? "—")}</span>
                </div>
                <div style="width:1px;height:16px;background:#e2e8f0;flex-shrink:0;"></div>
                <div style="display:flex;align-items:center;gap:6px;min-width:0;flex:1;">
                    <i class="fa-solid fa-file-lines" style="color:#374151;font-size:11px;flex-shrink:0;"></i>
                    <span style="color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(wo.judul_pekerjaan ?? "—")}</span>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Sales Order</label>
                <input type="text" class="form-control form-control-sm" value="${escHtml(wo.no_so ?? "—")}" disabled>
            </div>
            <div class="col-12">
                <label class="form-label">Judul Order <span class="text-danger">*</span></label>
                <input type="text" id="copyWoJudul" class="form-control form-control-sm"
                    value="${escHtml(wo.judul_pekerjaan ?? "")}" placeholder="Judul pekerjaan">
            </div>
            <div class="col-md-5">
                <label class="form-label">Pelanggan</label>
                <input type="text" class="form-control form-control-sm" value="${escHtml(wo.Pelanggan ?? "—")}" disabled>
            </div>
            <div class="col-md-5">
                <label class="form-label">Pelanggan Site</label>
                <input type="text" class="form-control form-control-sm" value="${escHtml(wo["Site Pelanggan"] ?? "—")}" disabled>
            </div>
            <div class="col-md-2">
                <label class="form-label">Frekuensi</label>
                <input type="text" class="form-control form-control-sm" value="${escHtml(intervalLabel)}" disabled>
            </div>
            ${urutanRow}
            <div class="${picColClass}">
                <label class="form-label">PIC Pekerjaan</label>
                <select id="copyWoPic" class="form-select form-select-sm"></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" id="copyWoTglMulai" class="form-control form-control-sm" value="${dateMulai}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Selesai</label>
                <input type="date" id="copyWoTglSelesai" class="form-control form-control-sm" value="${dateSelesai}">
            </div>
            ${woListHtml}
            <div class="col-12">
                <label class="form-label">Keterangan</label>
                <textarea id="copyWoKeterangan" class="form-control form-control-sm" rows="2">${escHtml(wo.keterangan ?? "")}</textarea>
            </div>
        </div>
    `);

    $("#copyWoPic").select2({
        width: "100%",
        placeholder: "Pilih PIC",
        allowClear: true,
        dropdownParent: $("#modalCopyWo"),
        ajax: {
            url: "/business-relation-contacts/select2",
            dataType: "json",
            delay: 250,
            data: (params) => ({ q: params.term }),
            processResults: (data) => ({ results: data }),
            cache: true,
        },
        escapeMarkup: (m) => m,
    });

    if (wo.id_pic_pelanggan_pekerjaan) {
        const opt = new Option(
            wo.nama_pic_pelanggan_pekerjaan || wo.id_pic_pelanggan_pekerjaan,
            wo.id_pic_pelanggan_pekerjaan,
            true,
            true,
        );
        $("#copyWoPic").append(opt).trigger("change");
    }

    renderCopyWoBoq(wo.boq_items || []);
}

let copyWoBoqIdx = 0;

function renderCopyWoBoq(sourceItems) {
    const hasSource = sourceItems.length > 0;
    let sourceHtml = "";
    if (hasSource) {
        sourceHtml = sourceItems
            .map(function (item) {
                const satuan = item.satuan ? escHtml(item.satuan) : "";
                return `<div class="copy-wo-boq-row" data-is-new="0"
                data-id-testing-point="${item.id_testing_point}"
                data-testing-item-ids="${escHtml(JSON.stringify(item.testing_item_ids || []))}"
                data-satuan="${escHtml(item.satuan || "")}"
                data-harga="${item.harga || ""}"
                data-keterangan="${escHtml(item.keterangan || "")}"
                data-item-produk-alternate="${escHtml(item.item_produk_alternate || "")}"
                style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                <div class="copy-wo-boq-compact d-flex align-items-center gap-2 p-2" style="background:#f8fafc;">
                    <div style="flex:1;min-width:0;">
                        <div class="fw-semibold" style="font-size:12px;color:#1a56db;">${escHtml(item.point_name)}</div>
                        ${satuan ? `<div style="font-size:11px;color:#64748b;">Satuan: ${satuan}</div>` : ""}
                    </div>
                    <input type="number" class="form-control form-control-sm text-end copy-wo-boq-qty"
                        value="${item.qty || ""}" min="1" placeholder="qty"
                        style="font-size:12px;width:90px;flex-shrink:0;">
                    <button type="button" class="btn btn-outline-primary btn-sm btn-edit-copy-wo-boq py-0 px-2 flex-shrink-0"
                        style="font-size:11px;white-space:nowrap;" tabindex="-1">
                        <i class="fa-solid fa-pen me-1"></i> Edit
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-copy-wo-boq py-0 px-2 flex-shrink-0" title="Hapus" tabindex="-1">
                        <i class="fa-solid fa-times" style="font-size:11px;"></i>
                    </button>
                </div>
                <div class="copy-wo-boq-edit-panel"
                    style="display:none;padding:14px;border-top:1px solid #e2e8f0;background:#fff;"></div>
            </div>`;
            })
            .join("");
    }

    const boqSectionHtml = `<div class="col-12" id="copyWoBoqSection">
        <label class="form-label fw-semibold">
            <i class="fa-solid fa-layer-group me-1 text-success"></i>BOQ
            ${hasSource ? `<span style="font-size:11px;font-weight:400;color:#64748b;margin-left:4px;">(dari WO sumber — edit qty sesuai kebutuhan)</span>` : ""}
        </label>
        <div id="copyWoBoqContainer" class="d-flex flex-column gap-2">
            ${sourceHtml || '<div style="font-size:12px;color:#94a3b8;padding:4px 0;"><i class="fa-solid fa-circle-minus me-1"></i>Belum ada BOQ di WO ini</div>'}
        </div>
        <div id="btnAddCopyWoBoq" style="display:flex;align-items:center;gap:10px;margin-top:10px;cursor:pointer;">
            <div style="flex:1;height:1px;background:#e2e8f0;"></div>
            <span style="font-size:12px;color:#64748b;white-space:nowrap;font-weight:500;">
                <i class="fa-solid fa-plus me-1"></i> Tambah Item BOQ
            </span>
            <div style="flex:1;height:1px;background:#e2e8f0;"></div>
        </div>
    </div>`;

    $("#copyWoKeterangan").closest(".col-12").before(boqSectionHtml);
}

function addCopyWoBoqRow() {
    copyWoBoqIdx++;
    const idx = copyWoBoqIdx;
    const row =
        $(`<div class="copy-wo-boq-row card mb-3" data-id-testing-point="" data-is-new="1">
        <div class="card-header d-flex align-items-center justify-content-between py-2 px-3" style="background:#f0fdf4;border-bottom:1px solid #bbf7d0;">
            <div style="flex:1;min-width:0;margin-right:8px;">
                <select class="form-select form-select-sm copy-wo-boq-tp-select" style="font-size:12px;"></select>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-copy-wo-boq py-0 px-2 flex-shrink-0" title="Hapus">
                <i class="fa-solid fa-times" style="font-size:11px;"></i>
            </button>
        </div>
        <div class="card-body px-3 py-3">
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label form-label-sm text-muted mb-1">Item Produk Alternatif</label>
                    <input type="text" class="form-control form-control-sm copy-wo-boq-item-produk" placeholder="opsional">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Qty</label>
                    <input type="number" class="form-control form-control-sm copy-wo-boq-qty" min="1" placeholder="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Satuan</label>
                    <select class="form-select form-select-sm copy-wo-boq-satuan">
                        <option value="">— Pilih —</option>
                        <option value="PCS">PCS</option>
                        <option value="Titik">Titik</option>
                        <option value="Set">Set</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm text-muted mb-1">Harga (Rp)</label>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm copy-wo-boq-harga input-num-mask input-num-int" placeholder="0">
                </div>
                <div class="col-12">
                    <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                    <input type="text" class="form-control form-control-sm copy-wo-boq-ket" placeholder="opsional">
                </div>
            </div>
            <div class="copy-wo-boq-items-wrap">
                <div class="text-muted small fst-italic" style="padding:4px 0;">
                    <i class="fa-solid fa-circle-info me-1 opacity-50"></i> Pilih testing point untuk memuat daftar item
                </div>
            </div>
        </div>
    </div>`);

    $("#copyWoBoqContainer").append(row);
    initNumericMask(row[0]);

    const $sel = row.find(".copy-wo-boq-tp-select");
    $sel.select2({
        width: "100%",
        placeholder: "Pilih testing point...",
        allowClear: true,
        minimumInputLength: 0,
        dropdownParent: $("#modalCopyWo"),
        ajax: {
            url: window.route.tpSelect2,
            dataType: "json",
            delay: 250,
            data: (p) => ({ q: p.term }),
            processResults: function (d) {
                const usedIds = new Set(
                    $("#copyWoBoqContainer .copy-wo-boq-row")
                        .map(function () {
                            return $(this).attr("data-id-testing-point");
                        })
                        .get()
                        .filter(Boolean),
                );
                return {
                    results: d.filter(function (item) {
                        return !usedIds.has(String(item.id));
                    }),
                };
            },
            cache: false,
        },
        escapeMarkup: (m) => m,
    });

    $sel.on("select2:select", function (e) {
        const selectedId = String(e.params.data.id || "");
        const usedIds = $("#copyWoBoqContainer .copy-wo-boq-row")
            .not(row[0])
            .map(function () {
                return $(this).attr("data-id-testing-point");
            })
            .get()
            .filter(Boolean);
        if (usedIds.includes(selectedId)) {
            $sel.val(null).trigger("change");
            row.attr("data-id-testing-point", "");
            Notify.warning("Testing point ini sudah ada di daftar BOQ");
            return;
        }
        row.attr("data-id-testing-point", selectedId);
        loadCopyWoBoqItems(row, selectedId);
    });

    $sel.on("select2:clear", function () {
        row.attr("data-id-testing-point", "");
        row.find(".copy-wo-boq-items-wrap").html(
            '<div class="text-muted small fst-italic" style="padding:4px 0;"><i class="fa-solid fa-circle-info me-1 opacity-50"></i> Pilih testing point untuk memuat daftar item</div>',
        );
    });
}

// preCheckedIds: null = semua dicentang (new row), Set<string> = hanya yg ada di set (source row edit)
function loadCopyWoBoqItems(row, pointId, preCheckedIds = null) {
    const $wrap = row.find(".copy-wo-boq-items-wrap");
    $wrap.html(
        '<div class="text-center text-muted py-2" style="font-size:12px;"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat item...</div>',
    );

    $.get(window.route.testingItemsByPoint + pointId, function (res) {
        const items = res.data ?? [];
        if (!items.length) {
            $wrap.html(
                '<div class="text-muted small fst-italic" style="padding:4px 0;"><i class="fa-solid fa-inbox me-1 opacity-50"></i> Tidak ada item pada testing point ini</div>',
            );
            return;
        }

        let html = `<div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-semibold small text-muted"><i class="fa-solid fa-list-check me-1"></i> Pilih item:</span>
            <div class="d-flex gap-3">
                <a href="#" class="copy-wo-boq-check-all small text-decoration-none">Pilih Semua</a>
                <a href="#" class="copy-wo-boq-uncheck-all small text-decoration-none text-secondary">Hapus Semua</a>
            </div>
        </div><div class="copy-wo-boq-items-list">`;

        items.forEach(function (item) {
            const unit = item.kode_unit || "—";
            const nilai = item.nilai ?? "—";
            const checked =
                preCheckedIds === null
                    ? true
                    : preCheckedIds.has(String(item.id_testing_item));
            html += `<div class="modal-item-row d-flex align-items-center gap-3">
                <input type="checkbox" class="form-check-input copy-wo-item-check flex-shrink-0 mt-0"
                    id="cwitem_${pointId}_${item.id_testing_item}" ${checked ? "checked" : ""}
                    data-item-id="${item.id_testing_item}">
                <label class="d-flex justify-content-between align-items-center w-100 gap-2"
                    for="cwitem_${pointId}_${item.id_testing_item}" style="cursor:pointer;margin:0;">
                    <div>
                        <span class="fw-semibold" style="font-size:13px;">${escHtml(item.judul_indonesia ?? "—")}</span>
                        <span class="text-muted ms-1 small">/ ${escHtml(item.judul_inggris ?? "—")}</span>
                    </div>
                    <span class="item-meta-badge flex-shrink-0">${escHtml(unit)} · ${escHtml(String(nilai))}</span>
                </label>
            </div>`;
        });

        html += "</div>";
        $wrap.html(html);

        $wrap.on("click", ".copy-wo-boq-check-all", function (e) {
            e.preventDefault();
            $wrap.find(".copy-wo-item-check").prop("checked", true);
        });
        $wrap.on("click", ".copy-wo-boq-uncheck-all", function (e) {
            e.preventDefault();
            $wrap.find(".copy-wo-item-check").prop("checked", false);
        });
    }).fail(function () {
        $wrap.html(
            '<div class="text-danger small" style="padding:4px 0;"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat item</div>',
        );
    });
}
