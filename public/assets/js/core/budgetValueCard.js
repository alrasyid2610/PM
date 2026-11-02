// Kartu "Nilai Budget" di baris KPI detail SO dan detail SQ (gaya sama dengan kartu di detail WO / SQ WO):
// tabel Kategori (Budget FWO, Budget WO) × Estimasi, [Realisasi — hanya SO], Persentase terhadap Total Nilai dokumen.
// Data dari GET /sales-orders/{id}/budget-summary dan /sales-quotations/{id}/budget-summary (App\Support\BudgetSummary).
window.BudgetValueCard = (function () {
    var fmt = function (n) { return 'Rp ' + Number(n || 0).toLocaleString('en-US'); };

    // Kartu kosong (dipasang bersama kartu KPI lain, diisi setelah data datang)
    function shell(id) {
        return '<div class="pm-kpi-card" id="' + id + '" style="flex:2 1 420px;flex-direction:column;align-items:stretch;gap:6px;min-width:380px;">' +
            '<div style="display:flex;align-items:center;gap:8px;">' +
            '<div class="pm-kpi-icon" style="background:#0f766e;flex-shrink:0;"><i class="fa-solid fa-wallet"></i></div>' +
            '<div class="pm-kpi-label">Nilai Budget</div></div>' +
            '<div class="text-muted" style="font-size:12px;"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div></div>';
    }

    // opts: { dokumen: 'SO'|'SQ', realisasi: bool } — realisasi hanya untuk SO
    function fill(id, r, opts) {
        var $card = $('#' + id);
        if (!$card.length) return; // detail sudah berganti sebelum data selesai dimuat

        var withReal = !!opts.realisasi;
        var tanpa = (r.fwo.tanpa_realisasi || 0) + (r.wo.tanpa_realisasi || 0);
        var pct = function (n) {
            if (!r.nilai) return '—';
            return (n / r.nilai * 100).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + '%';
        };
        var cell = 'padding:4px 10px;font-size:12px;white-space:nowrap;border-bottom:1px solid #e5e7eb;';
        var head = 'padding:4px 10px;font-size:10.5px;font-weight:600;color:#64748b;text-transform:uppercase;background:#f1f5f9;border-bottom:1px solid #e2e8f0;white-space:nowrap;text-align:left;';
        var row = function (label, v, bg) {
            return '<tr style="background:' + bg + ';">' +
                '<td style="' + cell + '">' + label + '</td>' +
                '<td style="' + cell + '">' + fmt(v.estimasi) + '</td>' +
                (withReal ? '<td style="' + cell + '">' + fmt(v.realisasi) + '</td>' : '') +
                '<td style="' + cell + '"><b>' + pct(v.estimasi) + '</b>' +
                (withReal ? '<span class="text-muted" style="font-size:10.5px;"> · real. ' + pct(v.realisasi) + '</span>' : '') + '</td></tr>';
        };

        $card.html(
            '<div style="display:flex;align-items:center;gap:8px;">' +
                '<div class="pm-kpi-icon" style="background:#0f766e;flex-shrink:0;"><i class="fa-solid fa-wallet"></i></div>' +
                '<div><div class="pm-kpi-label">Nilai Budget</div>' +
                '<div class="pm-kpi-value">' + fmt(r.fwo.estimasi + r.wo.estimasi) + '</div></div>' +
                '<div class="text-muted ms-auto" style="font-size:10.5px;">% terhadap Total Nilai ' + opts.dokumen + ' ' + fmt(r.nilai) + '</div>' +
            '</div>' +
            '<div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">' +
                '<table style="width:100%;border-collapse:collapse;"><thead><tr>' +
                    '<th style="' + head + '">Kategori</th><th style="' + head + '">Estimasi</th>' +
                    (withReal ? '<th style="' + head + '">Realisasi</th>' : '') +
                    '<th style="' + head + '">Persentase</th></tr></thead>' +
                '<tbody>' + row('Budget FWO', r.fwo, '#ffffff') + row('Budget WO', r.wo, '#f8fafc') + '</tbody></table>' +
            '</div>' +
            // Item bertanda "Tanpa realisasi" (hanya terisi di SO): tidak dihitung ke Estimasi
            (tanpa > 0 ? '<div class="text-muted" style="font-size:10.5px;"><i class="fa-solid fa-ban me-1" style="color:#b45309;"></i>Tanpa realisasi ' + fmt(tanpa) + ' — tidak dihitung ke Estimasi</div>' : '')
        );
    }

    // Muat data lalu isi kartu; kegagalan hanya mengosongkan kartu ini, tidak mengganggu KPI lain
    function load(id, url, opts) {
        $.get(url).done(function (r) { fill(id, r, opts); }).fail(function () {
            $('#' + id).find('.text-muted').first().html('<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>Gagal memuat nilai budget</span>');
        });
    }

    return { shell: shell, fill: fill, load: load };
})();
