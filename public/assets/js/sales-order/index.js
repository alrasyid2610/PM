let page;
let currentWosData = null;
let currentSoId = null;

window.addEventListener('storage', function (e) {
    if (e.key === 'wo_created' && e.newValue) {
        try {
            var data = JSON.parse(e.newValue);
            var target = data.id_so || currentSoId;
            if (target) loadWoProgress(target);
            var modal = bootstrap.Modal.getInstance(document.getElementById('modalCreateWo'));
            if (modal) {
                modal.hide();
                document.getElementById('iframeCreateWo').src = '';
            }
        } catch (_) {}
    }
    if (e.key === 'termin_created' && e.newValue) {
        try {
            var data = JSON.parse(e.newValue);
            var target = data.id_so || currentSoId;
            if (target) loadTerminList(target);
            var modal = bootstrap.Modal.getInstance(document.getElementById('modalCreateTermin'));
            if (modal) {
                modal.hide();
                document.getElementById('iframeCreateTermin').src = '';
            }
        } catch (_) {}
    }
});

$(document).on('click', '.btn-add-wo-modal', function () {
    var soId = $(this).data('so-id');
    document.getElementById('iframeCreateWo').src = '/work-orders/create?id_so=' + soId + '&embed=1';
    var modal = new bootstrap.Modal(document.getElementById('modalCreateWo'));
    modal.show();
});

$(document).on('click', '.btn-add-termin-modal', function () {
    var soId = $(this).data('so-id');
    document.getElementById('iframeCreateTermin').src = '/termin/create?id_so=' + soId + '&embed=1';
    var modal = new bootstrap.Modal(document.getElementById('modalCreateTermin'));
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
    const totalWo    = wos.length;
    const totalFwo   = wos.reduce(function (s, w) { return s + (w.fwo_count || 0); }, 0);
    const totalBoqQty = wos.reduce(function (s, w) { return s + (w.total_boq_qty || 0); }, 0);
    const totalFwoQty = wos.reduce(function (s, w) { return s + (w.total_fwo_qty || 0); }, 0);
    const totalHarga  = wos.reduce(function (s, w) { return s + (w.total_boq_amount || 0); }, 0);

    const pct = totalBoqQty > 0 ? Math.round((totalFwoQty / totalBoqQty) * 100) : 0;
    const barColor  = pct >= 100 ? "#16a34a" : pct > 0 ? "#d97706" : "#94a3b8";
    const pctColor  = pct >= 100 ? "#16a34a" : pct > 0 ? "#d97706" : "#94a3b8";

    const harga = totalHarga >= 1e9
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
        kpiCard("fa-briefcase",  "var(--primary-500,#1a5fbe)", "Total WO",    totalWo + " WO") +
        kpiCard("fa-hard-hat",   "var(--primary-700,#18386b)", "Total FWO",   totalFwo + " FWO") +
        kpiCard("fa-layer-group","#0891b2",                    "Total QTY",   totalBoqQty + " qty") +
        kpiCard("fa-tag",        "#0f766e",                    "Total Nilai",  harga) +
        progressCard
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

const SO_INTERVAL_LABELS = {1:'Bulanan',2:'Bimulanan',3:'Triwulan',4:'Caturwulan',6:'Semester',12:'Annual'};

function renderWoProgressTable(wos) {
    // Level 1: kelompokkan per Site Pelanggan
    const siteGroups = new Map();
    wos.forEach(function (wo) {
        const key = wo.nama_site_pelanggan || "—";
        if (!siteGroups.has(key)) siteGroups.set(key, []);
        siteGroups.get(key).push(wo);
    });

    const TH = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
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
            ? (SO_INTERVAL_LABELS[interval] || interval + ' bln')
            : 'Tidak Ada Periode';
        const badgeColor  = interval ? '#0d9488' : '#64748b';
        const badgeBg     = interval ? '#ccfbf1'  : '#f1f5f9';
        const badgeBorder = interval ? '#5eead4' : '#e2e8f0';

        const rows = list.map(function(wo, i) { return buildRow(wo, i + 1); }).join('');

        return `<div style="border-top:1px solid #e2e8f0;">
            <div class="wo-period-header" style="display:flex;align-items:center;gap:8px;padding:8px 14px 8px 32px;background:#fafbfc;cursor:pointer;user-select:none;">
                <i class="fa-solid fa-chevron-down" style="color:${badgeColor};font-size:10px;transition:transform .2s;"></i>
                <i class="fa-solid fa-calendar-days" style="color:${badgeColor};font-size:11px;"></i>
                <span style="font-size:12px;font-weight:700;color:${badgeColor};text-transform:uppercase;letter-spacing:.5px;">${escHtml(label)}</span>
                <span style="font-size:11px;font-weight:700;padding:1px 8px;border-radius:20px;background:${badgeBg};color:${badgeColor};border:1px solid ${badgeBorder};">${list.length} WO</span>
            </div>
            <div class="wo-period-body">
                <div class="table-responsive" style="padding-left:32px;">
                    <table class="table table-sm table-hover table-striped mb-0" style="font-size:13px;min-width:700px;">
                        <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                            <tr>
                                <th ${TH} style="width:40px;text-align:center;">No</th>
                                <th ${TH} style="min-width:120px;">No WO</th>
                                <th ${TH} style="min-width:200px;">Judul Pekerjaan</th>
                                <th ${TH} style="min-width:180px;">Keterangan</th>
                                <th ${TH} style="min-width:90px;text-align:center;">Urutan ke-</th>
                                <th ${TH} style="min-width:90px;text-align:center;">Total FWO</th>
                                <th ${TH} style="min-width:90px;text-align:center;">Action</th>
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
        const sortedPeriods = Array.from(periodGroups.entries()).sort(function (a, b) {
            if (a[0] === null) return 1;
            if (b[0] === null) return -1;
            return Number(a[0]) - Number(b[0]);
        });

        const periodSections = sortedPeriods.map(function (entry) {
            return buildPeriodSection(entry[1], entry[0]);
        }).join('');

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
$(document).on("click", "#woProgressContent .pm-accordion-header", function (e) {
    if ($(e.target).closest("a, button").length) return;
    const $header = $(this);
    const $body = $header.next(".pm-accordion-collapse");
    const isOpen = $header.attr("aria-expanded") === "true";
    $header.attr("aria-expanded", !isOpen);
    $body.slideToggle(150);
});

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
$(document).on('shown.bs.tab', '#soDetailTabs button[data-bs-toggle="tab"]', function (e) {
    const target = $(e.target).data('bs-target');
    $('#soTabActionsInfo, #soTabActionsWo, #soTabActionsTermin').addClass('d-none');
    if (target === '#tabInfoSo') $('#soTabActionsInfo').removeClass('d-none');
    if (target === '#tabWo')     $('#soTabActionsWo').removeClass('d-none');
    if (target === '#tabTermin') $('#soTabActionsTermin').removeClass('d-none');
});

// ── Load Termin ────────────────────────────────────────────────────────────────
function loadTerminList(id_so, onDone) {
    $('#terminContent').html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>'
    );
    $.get(window.route.terminBySo + id_so, function (data) {
        $('#terminContent').html(renderTerminTable(data));
        if (onDone) onDone();
    }).fail(function () {
        $('#terminContent').html(
            '<div class="text-center text-danger py-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data</div>'
        );
        if (onDone) onDone();
    });
}

function renderTerminTable(rows) {
    if (!rows || !rows.length) {
        return '<div class="text-center text-muted py-4">'
            + '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>'
            + 'Belum ada Termin untuk Sales Order ini</div>';
    }

    const statusClass = { pending: 'pm-badge--pending', proses: 'pm-badge--proses', selesai: 'pm-badge--selesai' };
    const statusLabel = { pending: 'Pending', proses: 'Proses', selesai: 'Selesai' };

    const rows_html = rows.map(function (t) {
        const cls   = statusClass[t.status] || 'pm-badge--pending';
        const lbl   = statusLabel[t.status] || t.status;
        const badge = `<span class="pm-badge ${cls}">${lbl}</span>`;
        const nilai = t.nilai ? 'Rp ' + Number(t.nilai).toLocaleString('id-ID') : '—';
        const pct   = t.persentase ? t.persentase + '%' : '—';
        const tgl   = t.tanggal ? t.tanggal.substring(0, 10) : '—';

        return `<tr>
            <td><a href="/termin?open=${t.id_termin}" class="pm-link-record">${escHtml(t.no_termin ?? '—')}</a></td>
            <td>${escHtml(t.nama ?? '—')}</td>
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
    }).join('');

    return `<div class="table-responsive">
        <table class="pm-table">
            <thead>
                <tr>
                    <th style="min-width:120px;">No Termin</th>
                    <th style="min-width:200px;">Nama</th>
                    <th style="min-width:90px;text-align:center;">%</th>
                    <th style="min-width:130px;">Nilai</th>
                    <th style="min-width:110px;">Tanggal</th>
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
    $(document).on("click", ".btn-copy-wo", function () {
        const woId = $(this).data("wo-id");
        const $btn = $(this);

        Notify.confirm("Buat salinan Work Order ini?", function () {
            $btn.prop("disabled", true).html(
                '<i class="fa-solid fa-spinner fa-spin"></i>',
            );

            $.ajax({
                url: window.route.woDuplicate + woId + "/duplicate",
                method: "POST",
                data: { _token: window.route.csrf },
                success: function (res) {
                    Notify.success("WO berhasil disalin: " + res.no_wo);
                    loadWoProgress(currentSoId);
                },
                error: function (xhr) {
                    Notify.error(
                        xhr.responseJSON?.message || "Gagal menyalin WO",
                    );
                    $btn.prop("disabled", false).html(
                        '<i class="fa-solid fa-copy"></i>',
                    );
                },
            });
        });
    });

    // Refresh Termin
    $(document).on('click', '#btnRefreshTermin', function () {
        const soId = $(this).data('so-id');
        const $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        loadTerminList(soId, function () { $icon.removeClass('fa-spin'); });
    });

    page = new CrudPageController({
        primaryKey: "id_so",
        renderForm: renderForm,
        afterLoad: function (res) {
            loadWoProgress(res.id_so);
            loadTerminList(res.id_so);
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
