let page;
let currentWosData = null;
let currentSoId = null;

window.addEventListener('storage', function (e) {
    if (e.key === 'wo_created' && e.newValue) {
        try {
            var data = JSON.parse(e.newValue);
            var target = data.id_so || currentSoId;
            if (target) loadWoProgress(target);
        } catch (_) {}
    }
});

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
                escHtml(term) +
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
                ? `<div class="fw-semibold" style="font-size:12px;color:#166534;">${escHtml(wo.nama_period)}</div>` +
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
                    ${escHtml(wo.no_wo ?? "—")}
                </a>
            </td>
            <td ${TD} style="padding:8px 12px;color:#374151;">${escHtml(wo.nama_pelanggan ?? "—")}</td>
            <td ${TD} style="padding:8px 12px;color:#374151;">${escHtml(wo.nama_site_pelanggan ?? "—")}</td>
            <td ${TD} style="padding:8px 12px;color:#374151;">${escHtml(wo.judul_pekerjaan ?? "—")}</td>
            <td ${TD} style="padding:8px 12px;text-align:center;color:#7c3aed;font-weight:600;">${wo.fwo_count}</td>
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
                    <th ${TH_BASE} style="min-width:90px;text-align:center;">Total FWO</th>
                    <th ${TH_BASE} style="min-width:130px;">Total Harga</th>
                    <th ${TH_BASE} style="min-width:90px;">Action</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
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

    page = new CrudPageController({
        primaryKey: "id_so",
        renderForm: renderForm,
        afterLoad: function (res) {
            loadWoProgress(res.id_so);
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
