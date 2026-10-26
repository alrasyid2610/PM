let page;
let currentWosData = null;
let currentSoId = null;

// PIC yang muncul = union dari semua Perusahaan yang sudah dipilih di ketiga
// kategori (Pemesan, Pengiriman, Pembayaran) — dibaca ulang tiap select2
// dibuka/diketik, jadi otomatis update tanpa perlu reinit manual.
function _soSelectedCompanyIds() {
    return [
        $('#detail_id_pelanggan').val(),
        $('#detail_id_pelanggan_delivery').val(),
        $('#detail_id_pelanggan_payment').val(),
    ].filter(function (v) { return !!v; });
}

// PIC yang muncul = gabungan PIC dari Site yang dipilih di ketiga kategori
function _soSelectedSiteIds() {
    return [
        $('#detail_id_site_pelanggan').val(),
        $('#detail_id_site_pelanggan_delivery').val(),
        $('#detail_id_site_pelanggan_payment').val(),
    ].filter(function (v) { return !!v; });
}

// Ambil {id, text} dari select2 yang sedang terpilih. null kalau belum diisi.
function _soGetSelect2Data(selector) {
    const $el = $(selector);
    if (!$el.length || !$el.val()) return null;
    const data = $el.select2('data');
    if (!data || !data.length) return null;
    return { id: $el.val(), text: data[0].text };
}

// Begitu Perusahaan & Site Pemesan sudah lengkap, salin ke Pengiriman &
// Pembayaran yang masih kosong (Perusahaan, Site, dan PIC kalau sudah ada).
function _syncSoPemesanToOthers() {
    const company = _soGetSelect2Data('#detail_id_pelanggan');
    const site    = _soGetSelect2Data('#detail_id_site_pelanggan');
    if (!company || !site) return;

    const pic = _soGetSelect2Data('#detail_id_pic_pelanggan');

    const targets = [
        { company: '#detail_id_pelanggan_delivery', site: '#detail_id_site_pelanggan_delivery', pic: '#detail_id_pic_pelanggan_delivery' },
        { company: '#detail_id_pelanggan_payment',  site: '#detail_id_site_pelanggan_payment',  pic: '#detail_id_pic_pelanggan_payment' },
    ];

    // Pemesan selalu menimpa Pengiriman & Pembayaran (disamakan), termasuk PIC.
    // PIC yang belum dipilih di Pemesan dikosongkan di tujuan.
    targets.forEach(function (t) {
        const $c = $(t.company);
        if ($c.length) {
            if (!$c.find('option[value="' + company.id + '"]').length) $c.append(new Option(company.text, company.id));
            $c.val(company.id).trigger('change');
        }
        // Site disalin dari Pemesan: opsi lama dibuang, lalu Site Pemesan dipilih
        const $s = $(t.site);
        if ($s.length) {
            $s.empty().append(new Option(site.text, site.id, true, true)).trigger('change');
        }
        const $p = $(t.pic);
        if ($p.length) {
            if (pic) $p.append(new Option(pic.text, pic.id, true, true)).trigger('change');
            else $p.val(null).trigger('change');
        }
    });
}

// Site di tiap kategori (Pemesan/Pengiriman/Pembayaran) di-scope oleh
// Perusahaan yang dipilih di kategori yang sama — reinit select2 dengan ajax
// yang menyertakan id_br dari field company pasangannya.
function initSoSiteFields() {
    const pairs = [
        { company: '#detail_id_pelanggan', site: '#detail_id_site_pelanggan' },
        { company: '#detail_id_pelanggan_delivery', site: '#detail_id_site_pelanggan_delivery' },
        { company: '#detail_id_pelanggan_payment', site: '#detail_id_site_pelanggan_payment' },
    ];

    pairs.forEach(function (pair) {
        const $el = $(pair.site);
        if (!$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

        $el.select2({
            width: '100%',
            dropdownParent: $('#detailContent'),
            placeholder: 'Pilih Data',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: 'business-relations/sites/select2',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    // Belum pilih Perusahaan → tidak ada Site yang tampil
                    return { q: params.term || '', id_br: $(pair.company).val() || '-1' };
                },
                processResults: function (data) { return { results: data }; },
                cache: false,
            },
            language: {
                noResults: function () {
                    return '<span>Tidak ditemukan. <a href="/business-relations/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>';
                },
            },
            escapeMarkup: function (m) { return m; },
        });
    });

    // Ganti Perusahaan di suatu kategori → kosongkan Site kategori itu
    // (Site lama sudah tentu tidak valid lagi untuk Perusahaan yang baru)
    pairs.forEach(function (pair) {
        $(pair.company)
            .off('select2:select.soCompanySiteClear select2:clear.soCompanySiteClear')
            .on('select2:select.soCompanySiteClear select2:clear.soCompanySiteClear', function () {
                $(pair.site).val(null).trigger('change');
            });
    });
}

function initSoPicFields() {
    const selectors = [
        '#detail_id_pic_pelanggan',
        '#detail_id_pic_pelanggan_delivery',
        '#detail_id_pic_pelanggan_payment',
    ];

    selectors.forEach(function (sel) {
        const $el = $(sel);
        if (!$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

        $el.select2({
            width: '100%',
            dropdownParent: $('#detailContent'),
            placeholder: 'Pilih Data',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: 'business-relation-contacts/select2',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term || '', id_br: _soSelectedCompanyIds(), id_sites: _soSelectedSiteIds(), with_site: 1 };
                },
                processResults: function (data) { return { results: data }; },
                cache: false,
            },
            language: {
                noResults: function () {
                    return '<span>Tidak ditemukan. <a href="/business-relation-contacts/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>';
                },
            },
            escapeMarkup: function (m) { return m; },
        });
    });

    // Begitu Perusahaan & Site Pemesan sudah lengkap → salin ke Pengiriman &
    // Pembayaran (PIC ikut kalau sudah dipilih juga). Hanya mengisi field yang
    // MASIH KOSONG di kategori tujuan, tidak menimpa yang sudah diisi manual.
    // Hanya Site & PIC Pemesan yang memicu auto-fill (bukan Perusahaan, karena
    // ganti Perusahaan juga mengosongkan Site → auto-fill jalan dengan Site lama)
    ['#detail_id_site_pelanggan', '#detail_id_pic_pelanggan'].forEach(function (sel) {
        $(sel)
            .off('select2:select.soPemesanSync')
            .on('select2:select.soPemesanSync', _syncSoPemesanToOthers);
    });

    // Ganti Perusahaan di suatu kategori → kosongkan PIC kategori itu
    // (PIC lama sudah tentu tidak valid lagi untuk Perusahaan yang baru)
    const companyPicPairs = [
        ['#detail_id_pelanggan', '#detail_id_pic_pelanggan'],
        ['#detail_id_pelanggan_delivery', '#detail_id_pic_pelanggan_delivery'],
        ['#detail_id_pelanggan_payment', '#detail_id_pic_pelanggan_payment'],
    ];
    companyPicPairs.forEach(function (pair) {
        $(pair[0])
            .off('select2:select.soCompanyClear select2:clear.soCompanyClear')
            .on('select2:select.soCompanyClear select2:clear.soCompanyClear', function () {
                $(pair[1]).val(null).trigger('change');
            });
    });
}

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

window.datatableColumnRenderers = {
    status: function (data) {
        var map = {
            'on-progress': { bg: '#eff6ff', color: '#1d4ed8', border: '#bfdbfe', icon: 'fa-spinner',      label: 'On Progress' },
            completed:     { bg: '#f0fdf4', color: '#15803d', border: '#bbf7d0', icon: 'fa-circle-check', label: 'Completed' },
            cancel:        { bg: '#fff7ed', color: '#c2410c', border: '#fed7aa', icon: 'fa-ban',          label: 'Cancel' },
            deleted:       { bg: '#fef2f2', color: '#b91c1c', border: '#fecaca', icon: 'fa-trash',        label: 'Deleted' },
        };
        var s = map[data] || { bg: '#f1f5f9', color: '#475569', border: '#e2e8f0', icon: 'fa-circle', label: data || '-' };
        return '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:6px;background:' + s.bg + ';color:' + s.color + ';font-size:11px;font-weight:600;border:1px solid ' + s.border + ';white-space:nowrap;">'
            + '<i class="fa-solid ' + s.icon + '" style="font-size:10px;"></i> ' + s.label + '</span>';
    },
};

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
    openIframeModal(
        "#modalCreateWo",
        "iframeCreateWo",
        "loaderCreateWo",
        "/work-orders/create?id_so=" + soId + "&embed=1",
    );
});

$(document).on("click", ".btn-add-termin-modal", function () {
    var soId = $(this).data("so-id");
    openIframeModal(
        "#modalCreateTermin",
        "iframeCreateTermin",
        "loaderCreateTermin",
        "/termin/create?id_so=" + soId + "&embed=1",
    );
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
        return s + (w.total_nilai_wo || 0);
    }, 0);
    const totalBoqOnly = wos.reduce((s, w) => s + (w.total_boq_amount || 0), 0);
    const totalOtherOnly = wos.reduce((s, w) => s + (w.total_boq_other_amount || 0), 0);
    const totalSamplingOnly = wos.reduce((s, w) => s + (w.total_boq_sampling_amount || 0), 0);

    const pct =
        totalBoqQty > 0 ? Math.round((totalFwoQty / totalBoqQty) * 100) : 0;
    const barColor = pct >= 100 ? "#16a34a" : pct > 0 ? "#d97706" : "#94a3b8";
    const pctColor = pct >= 100 ? "#16a34a" : pct > 0 ? "#d97706" : "#94a3b8";

    const fmtRp = (n) => "Rp " + Number(n || 0).toLocaleString("en-US");
    const categories = [
        { label: "BOQ",          value: totalBoqOnly,      bg: "#eff6ff", color: "#1d4ed8", border: "#bfdbfe" },
        { label: "BOQ Other",    value: totalOtherOnly,    bg: "#fff7ed", color: "#c2410c", border: "#fed7aa" },
        { label: "BOQ Sampling", value: totalSamplingOnly, bg: "#f0fdf4", color: "#15803d", border: "#bbf7d0" },
    ];
    const badgesHtml = categories
        .filter((c) => c.value > 0)
        .map((c) => `<span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:${c.bg};color:${c.color};border:1px solid ${c.border};white-space:nowrap;">${c.label}: ${fmtRp(c.value)}</span>`)
        .join("");
    const totalNilaiCard = `<div class="pm-kpi-card" style="flex-direction:column;align-items:flex-start;gap:8px;min-width:220px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <div class="pm-kpi-icon" style="background:#7c3aed;flex-shrink:0;">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div>
                <div class="pm-kpi-label">Total Nilai</div>
                <div class="pm-kpi-value">${totalHarga > 0 ? fmtRp(totalHarga) : "—"}</div>
            </div>
        </div>
        ${badgesHtml ? `<div style="display:flex;gap:6px;flex-wrap:wrap;">${badgesHtml}</div>` : ""}
    </div>`;

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
            totalNilaiCard +
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
                <a href="/work-orders?open=${wo.id_wo}" class="pm-link-record" style="white-space:nowrap;font-weight:normal;">
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
                ${can('work-orders', 'can_create') ? `
                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 ms-1 btn-copy-wo"
                    style="font-size:11px;" title="Salin WO ini" data-wo-id="${wo.id_wo}">
                    <i class="fa-solid fa-copy"></i>
                </button>` : ''}
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
        $("#soTabActionsInfo, #soTabActionsWo, #soTabActionsTermin, #soTabActionsOutputOther").addClass(
            "d-none",
        );
        if (target === "#tabInfoSo")
            $("#soTabActionsInfo").removeClass("d-none");
        if (target === "#tabWo") $("#soTabActionsWo").removeClass("d-none");
        if (target === "#tabTermin")
            $("#soTabActionsTermin").removeClass("d-none");
        if (target === "#tabSoOutputOther")
            $("#soTabActionsOutputOther").removeClass("d-none");
    },
);

// ── OUTPUT OTHER level SO (tab Output Other; item Siap bisa ditarik saat tambah Termin) ──
// Pola sama dengan Output Other di WO (work-order/index.js), endpoint /so-output-other (izin sales-orders).
let soOutputOtherFilePond = null;
const SO_OO_STATUS_LABEL = { belum_siap: 'Belum Siap', siap: 'Siap', terkirim: 'Terkirim' };
const SO_OO_STATUS_COLOR = {
    belum_siap: { bg: '#fee2e2', color: '#dc2626' },
    siap:       { bg: '#dbeafe', color: '#1d4ed8' },
    terkirim:   { bg: '#dcfce7', color: '#15803d' },
};

function loadSoOutputOther(idSo) {
    const $wrap = $('#soOutputOtherContent');
    $wrap.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');
    $.get('/so-output-other/' + idSo + '/list')
        .done(function (res) { $wrap.html(renderSoOutputOtherList(res.data || [], !!res.locked)); })
        .fail(function () { $wrap.html('<div class="text-center text-danger py-4">Gagal memuat data.</div>'); });
}

function renderSoOutputOtherList(rows, isLocked) {
    if (!rows.length) {
        return '<div class="text-center text-muted py-4"><i class="fa-solid fa-file-circle-plus fa-2x d-block mb-2 opacity-25"></i>Belum ada item Output Other</div>';
    }
    const body = rows.map(function (r, i) {
        const st = SO_OO_STATUS_COLOR[r.status] || SO_OO_STATUS_COLOR.belum_siap;
        const periode = r.tanggal_mulai || r.tanggal_selesai
            ? (r.tanggal_mulai ? r.tanggal_mulai.substring(0, 10) : '—') + ' s/d ' + (r.tanggal_selesai ? r.tanggal_selesai.substring(0, 10) : '—')
            : '—';
        const files = renderFilePreviewButton(r.attachments || [], { title: (r.nama_item || 'Output') + ' — Lampiran' });
        const drive = r.link_drive
            ? '<a href="' + escHtml(r.link_drive) + '" target="_blank" style="font-size:11px;color:#1a56db;"><i class="fa-brands fa-google-drive me-1"></i>Drive</a>'
            : '';
        const siap = !isLocked && r.status === 'belum_siap' && can('output-pekerjaan-siap', 'can_update')
            ? '<button type="button" class="btn btn-sm btn-success py-0 px-2 me-1 btn-so-output-other-siap" data-id="' + r.id_output_tambahan + '" title="Tandai Siap" style="font-size:11px;" data-no-disable><i class="fa-solid fa-check me-1"></i>Siap</button>'
            : '';
        const edit = !isLocked && can('sales-orders', 'can_create')
            ? '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-so-output-other-edit" data-id="' + r.id_output_tambahan + '" title="Edit" style="font-size:11px;"><i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i></button>'
            : '';
        const del = !isLocked && can('sales-orders', 'can_delete')
            ? '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-so-output-other-delete" data-id="' + r.id_output_tambahan + '" data-nama="' + escHtml(r.nama_item) + '" title="Hapus" style="font-size:11px;"><i class="fa-solid fa-trash" style="color:#dc2626;"></i></button>'
            : '';
        return '<tr>'
            + '<td style="font-size:12px;color:#94a3b8;width:36px;">' + (i + 1) + '</td>'
            + '<td style="font-size:12px;">' + escHtml(r.nama_item) + '</td>'
            + '<td style="font-size:12px;text-align:right;white-space:nowrap;">' + (r.qty ?? 0) + ' ' + escHtml(r.satuan || '') + '</td>'
            + '<td style="text-align:center;white-space:nowrap;"><span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:' + st.bg + ';color:' + st.color + ';">' + (SO_OO_STATUS_LABEL[r.status] || r.status) + '</span></td>'
            + '<td style="font-size:11px;white-space:nowrap;color:#64748b;">' + periode + '</td>'
            + '<td style="font-size:11px;">' + files + drive + '</td>'
            + '<td class="text-center" style="white-space:nowrap;">' + siap + edit + del + '</td>'
            + '</tr>';
    }).join('');

    return '<div class="table-responsive"><table class="pm-table"><thead><tr>'
        + '<th style="width:36px;">No</th><th>Nama Item</th><th style="width:100px;text-align:right;">Qty</th>'
        + '<th style="width:90px;text-align:center;">Status</th><th style="width:170px;">Periode</th>'
        + '<th style="width:140px;">Lampiran</th><th style="width:72px;"></th>'
        + '</tr></thead><tbody>' + body + '</tbody></table></div>';
}

function initSoOutputOtherSatuan(idVal, labelVal) {
    const $sel = $('#soOutputOtherModal-satuan');
    if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
    $sel.empty();
    if (idVal && labelVal) $sel.append(new Option(labelVal, idVal, true, true));
    $sel.select2({
        width: '100%', placeholder: '— Pilih —', allowClear: true, minimumInputLength: 0,
        dropdownParent: $('#soOutputOtherModal'),
        ajax: { url: '/satuan/select2', delay: 200, dataType: 'json', data: (p) => ({ q: p.term ?? '' }), processResults: (d) => ({ results: d }) },
        language: { noResults: () => '<span>Tidak ditemukan. <a href="/satuan/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>' },
        escapeMarkup: (m) => m,
    });
}

$(document).on('click', '#btnRefreshSoOutputOther', function () {
    loadSoOutputOther($(this).data('so-id'));
});

// Opsi "Siap" di dropdown Status hanya untuk user ber-izin aksi khusus (atau bila item memang sudah Siap)
function toggleSoOutputOtherSiapOption($modal, statusSaatIni) {
    const boleh = can('output-pekerjaan-siap', 'can_update') || statusSaatIni === 'siap';
    $modal.find('#soOutputOtherModal-status option[value="siap"]').prop('disabled', !boleh);
}

// Tombol aksi "Siap" di daftar (sama dengan tombol Siap di Output Pekerjaan WO)
$(document).on('click', '.btn-so-output-other-siap', function () {
    const $btn = $(this);
    const id = $btn.data('id');
    const idSo = $('#tab-so-output-other-btn').data('so-id');
    Notify.confirm('Ubah status menjadi <b>Siap</b>?', function () {
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
        $.ajax({
            url: '/so-output-other/' + id + '/status',
            method: 'POST',
            data: { _token: window.route.csrf, status: 'siap' },
            success: function () { Notify.success('Status diperbarui menjadi Siap'); loadSoOutputOther(idSo); },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal mengubah status');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i>Siap');
            },
        });
    });
});

$(document).on('click', '#btnAddSoOutputOther', function () {
    const idSo = $(this).data('so-id');
    const $modal = $('#soOutputOtherModal');
    toggleSoOutputOtherSiapOption($modal, '');
    $modal.find('#soOutputOtherModalLabel').html('<i class="fa-solid fa-file-circle-plus me-2" style="color:#0f766e;"></i>Tambah Output Other');
    $modal.find('#soOutputOtherModal-id').val('');
    $modal.find('#soOutputOtherModal-id-so').val(idSo);
    $modal.find('#soOutputOtherModal-qty').val('');
    $modal.find('#soOutputOtherModal-status').val('belum_siap');
    $modal.find('#soOutputOtherModal-tgl-mulai').val('');
    $modal.find('#soOutputOtherModal-tgl-selesai').val('');
    $modal.find('#soOutputOtherModal-link-drive').val('');
    $modal.find('#soOutputOtherModal-keterangan').val('');
    $modal.find('#soOutputOtherModal-existing-files').empty();
    initSoOutputOtherSatuan();
    initJudulOutputSelect($modal.find('#soOutputOtherModal-nama'), 'other', $modal, '');

    if (soOutputOtherFilePond) { soOutputOtherFilePond.destroy(); soOutputOtherFilePond = null; }
    soOutputOtherFilePond = createFileUploader('#soOutputOtherModal-attachments');

    new bootstrap.Modal($modal[0]).show();
    initNumericMask($modal[0]);
    initFpDate($modal[0]);
});

$(document).on('click', '.btn-so-output-other-edit', function (e) {
    e.stopPropagation();
    const id = $(this).data('id');
    const $modal = $('#soOutputOtherModal');
    $modal.find('#soOutputOtherModalLabel').html('<i class="fa-solid fa-pen me-2" style="color:#0f766e;"></i>Edit Output Other');
    $modal.find('#soOutputOtherModal-id').val(id);

    $.get('/so-output-other/' + id)
        .done(function (r) {
            $modal.find('#soOutputOtherModal-id-so').val(r.id_so);
            initJudulOutputSelect($modal.find('#soOutputOtherModal-nama'), 'other', $modal, r.nama_item || '');
            const qtyEl = $modal.find('#soOutputOtherModal-qty')[0];
            if (qtyEl && qtyEl._cleave) qtyEl._cleave.setRawValue(r.qty || ''); else $modal.find('#soOutputOtherModal-qty').val(r.qty || '');
            toggleSoOutputOtherSiapOption($modal, r.status);
            $modal.find('#soOutputOtherModal-status').val(r.status || 'belum_siap');
            $modal.find('#soOutputOtherModal-tgl-mulai').val(r.tanggal_mulai ? r.tanggal_mulai.substring(0, 10) : '');
            $modal.find('#soOutputOtherModal-tgl-selesai').val(r.tanggal_selesai ? r.tanggal_selesai.substring(0, 10) : '');
            $modal.find('#soOutputOtherModal-link-drive').val(r.link_drive || '');
            $modal.find('#soOutputOtherModal-keterangan').val(r.keterangan || '');
            initSoOutputOtherSatuan(r.id_satuan, r.satuan);

            const filesHtml = (r.attachments || []).map(function (f) {
                return '<div class="d-flex align-items-center gap-2 mb-1" style="font-size:12px;">'
                    + '<i class="fa-solid fa-paperclip"></i>'
                    + '<span class="text-truncate" style="max-width:260px;" title="' + escHtml(filePreviewName(f)) + '">' + escHtml(filePreviewName(f)) + '</span>'
                    + renderFilePreviewButton([f])
                    + '<a href="#" class="btn-remove-existing-so-output-file" style="font-size:11px;color:#dc2626;cursor:pointer;">Hapus</a>'
                    + '<input type="hidden" class="existing-so-output-file-path" value="' + escHtml(f) + '">'
                    + '</div>';
            }).join('');
            $modal.find('#soOutputOtherModal-existing-files').html(filesHtml);
        })
        .always(function () {
            if (soOutputOtherFilePond) { soOutputOtherFilePond.destroy(); soOutputOtherFilePond = null; }
            soOutputOtherFilePond = createFileUploader('#soOutputOtherModal-attachments');
            new bootstrap.Modal($modal[0]).show();
            initNumericMask($modal[0]);
            initFpDate($modal[0]);
        });
});

$(document).on('click', '.btn-remove-existing-so-output-file', function (e) {
    e.preventDefault();
    $(this).closest('div').remove();
});

$(document).on('hidden.bs.modal', '#soOutputOtherModal', function () {
    if (soOutputOtherFilePond) { soOutputOtherFilePond.destroy(); soOutputOtherFilePond = null; }
});

$(document).on('click', '#soOutputOtherModal-btn-save', function () {
    const $modal = $('#soOutputOtherModal');
    const id   = $modal.find('#soOutputOtherModal-id').val();
    const idSo = $modal.find('#soOutputOtherModal-id-so').val();
    const $btn = $(this);

    const namaItem = ($modal.find('#soOutputOtherModal-nama').val() || '').trim();
    const qty = rawNumVal($modal.find('#soOutputOtherModal-qty')[0]);
    if (!namaItem) { Notify.warning('Nama item wajib diisi.'); return; }
    if (!qty)      { Notify.warning('Qty wajib diisi.'); return; }

    const fd = new FormData();
    fd.append('_token', window.route.csrf);
    fd.append('id_so', idSo);
    fd.append('nama_item', namaItem);
    fd.append('qty', qty);
    fd.append('id_satuan', $modal.find('#soOutputOtherModal-satuan').val() || '');
    fd.append('status', $modal.find('#soOutputOtherModal-status').val());
    fd.append('tanggal_mulai', $modal.find('#soOutputOtherModal-tgl-mulai').val() || '');
    fd.append('tanggal_selesai', $modal.find('#soOutputOtherModal-tgl-selesai').val() || '');
    fd.append('link_drive', $modal.find('#soOutputOtherModal-link-drive').val().trim());
    fd.append('keterangan', $modal.find('#soOutputOtherModal-keterangan').val().trim());
    $modal.find('.existing-so-output-file-path').each(function () { fd.append('existing_attachments[]', $(this).val()); });
    if (soOutputOtherFilePond) soOutputOtherFilePond.getFiles().forEach(function (f) { fd.append('attachments[]', f.file); });

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');
    $.ajax({ url: id ? '/so-output-other/' + id : '/so-output-other', method: 'POST', data: fd, processData: false, contentType: false })
        .done(function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance($modal[0])?.hide();
                Notify.success('Item berhasil disimpan.');
                loadSoOutputOther(idSo);
            } else {
                Notify.error(res.message || 'Gagal menyimpan item.');
            }
        })
        .fail(function (xhr) { Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan item.'); })
        .always(function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan'); });
});

$(document).on('click', '.btn-so-output-other-delete', function () {
    const id = $(this).data('id');
    const nama = $(this).data('nama');
    const idSo = $('#tab-so-output-other-btn').data('so-id');
    Swal.fire({
        title: 'Hapus Item?',
        html: 'Item <strong>' + escHtml(nama) + '</strong> akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        reverseButtons: true,
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: '/so-output-other/' + id,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.route.csrf },
            success: function () { Notify.success('Item berhasil dihapus.'); loadSoOutputOther(idSo); },
            error: function (xhr) { Notify.error(xhr.responseJSON?.message || 'Gagal menghapus item.'); },
        });
    });
});

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

    // Salin WO — membuka halaman Clone WO (bukan modal), memakai ulang UI Clone SO dalam mode 1 WO
    $(document).on("click", ".btn-copy-wo", function () {
        window.location.href = "/work-orders/" + $(this).data("wo-id") + "/clone";
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
        detailTitle: function (res) { return res.no_so; },
        useAttachment: true,
        initSelect: function () {
            initSoPicFields();
            initNumericMask(document.getElementById('detailContent'));
        },
        afterLoad: function (res) {
            // Site pakai class .form-select-dynamic (mode ajax generik) yang
            // di-init ulang oleh initDynamicSelect() setelah initSelect() —
            // kalau initSoSiteFields() dipanggil di initSelect, hasilnya akan
            // ditimpa lagi. Jadi harus dipanggil di sini (afterLoad), yang
            // jalan setelah initDynamicSelect().
            initSoSiteFields();
            // Akses Tab (grup "Akses Tab"): sembunyikan tab yang tidak diizinkan — logika umum di core/permissionEngine.js
            applyTabAccess({
                nav: '#soDetailTabs',
                map: {
                    '#tabInfoSo':        'so-tab-info',
                    '#tabWo':            'so-tab-wo',
                    '#tabTermin':        'so-tab-termin',
                    '#tabSoOutputOther': 'so-tab-output-other',
                },
                infoTarget: '#tabInfoSo',
                label: 'SO',
            });
            loadWoProgress(res.id_so);
            loadTerminList(res.id_so);
            loadSoOutputOther(res.id_so);
            initFpDate('#detailContent');
        },
    });

    $(document).on("click", ".btn-delete-record", function () {
        const id = $(this).data("id");
        Notify.confirmDelete("Hapus Sales Order?", function () {
            $.ajax({
                url: window.route.update + id,
                method: "POST",
                data: { _token: window.route.csrf, _method: "DELETE" },
                success: function (res) {
                    Notify.success(res.message || "Data berhasil dihapus");
                    setTimeout(function () { window.location.href = window.location.pathname; }, 1000);
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
