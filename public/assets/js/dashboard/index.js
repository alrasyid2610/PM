// Dashboard berbasis widget: tiap kartu/grafik memuat datanya sendiri-sendiri
// (/dashboard/widget/{slug}); widget yang tidak diizinkan tidak dirender sama sekali
// (dan endpoint-nya 403). Tabel = 1 kartu dengan tab sesuai widget tabel yang diizinkan.

$(document).ready(function () {
    loadKpis();
    loadCharts();

    if (window.dashboardFirstTab) switchTab(window.dashboardFirstTab);

    $(document).on('click', '.dw-card-clickable', function () {
        var type = $(this).data('tab');
        $('.dw-tab-btn[data-type]').removeClass('active');
        $('.dw-tab-btn[data-type="' + type + '"]').addClass('active');
        switchTab(type);
        $('html, body').animate({ scrollTop: $('.card').last().offset().top - 20 }, 300);
    });

    $(document).on('click', '.dw-tab-btn[data-type]', function () {
        $('.dw-tab-btn[data-type]').removeClass('active');
        $(this).addClass('active');
        switchTab($(this).data('type'));
    });
});

function switchTab(type) {
    window.route.data    = window.route.list;
    window._dtParams     = { type: type };

    window.datatableHeaderLabels = {
        no:         'No.',
        judul:      'Judul',
        pelanggan:  'Pelanggan',
        pic:        'PIC',
        deadline:   type === 'sq' ? 'Berlaku Sampai' : 'Deadline',
        status:     'Status',
    };

    window.datatableColumnRenderers = {
        no: function (data, t, row) {
            var base = window.route[type];
            return '<a href="' + base + '?open=' + row.id_rec + '" class="fw-semibold text-decoration-none" style="color:#1a56db">' + (data ?? '-') + '</a>';
        },
        status: function (data) {
            var map = {
                'on-progress': '<span class="badge bg-primary">On Progress</span>',
                'onprogress':  '<span class="badge bg-primary">On Progress</span>',
                completed:     '<span class="badge bg-success">Completed</span>',
                cancel:        '<span class="badge bg-danger">Cancel</span>',
                planned:       '<span class="badge bg-info text-dark">Planned</span>',
                draft:         '<span class="badge bg-secondary">Draft</span>',
                final:         '<span class="badge" style="background:#1d4ed8;">Final</span>',
            };
            return map[data] ?? ('<span class="badge bg-secondary">' + (data ?? '-') + '</span>');
        },
        deadline: function (data) {
            return data ? data : '<span class="text-muted">-</span>';
        },
    };

    $('#global-loader').fadeIn(150);
    initDataTable('#dashboard-list-table', function () {
        $('#global-loader').fadeOut(300);
    });
}

// ── Kartu angka ──
function loadKpis() {
    $('.dw-card[data-widget]').each(function () {
        var slug = $(this).data('widget');
        $.get(window.route.widget + '/' + slug, function (res) {
            $('#' + slug + '-value').text(res.value ?? 0);
            $('#' + slug + '-sub').text(res.sub ?? '');
        }).fail(function () {
            $('#' + slug + '-value').text('—');
        });
    });
}

// ── Grafik ──
var _dashCharts = {};

function loadCharts() {
    $('[data-chart-widget]').each(function () {
        loadChart($(this).data('chart-widget'));
    });

    // Ganti tahun atau mode (Kotor/Bersih) → muat ulang grafik dengan kedua nilai filter
    $(document).on('change', '.dw-chart-year, .dw-chart-mode', function () {
        var $card = $(this).closest('[data-chart-widget]');
        loadChart($card.data('chart-widget'), $card.find('.dw-chart-year').val(), $card.find('.dw-chart-mode').val());
    });
}

function formatRupiahDash(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

// Angka ringkas untuk label batang & sumbu: 1,2 jt / 3,4 M
function ringkasRupiah(n) {
    n = Number(n || 0);
    if (n >= 1e9)  return (n / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
    if (n >= 1e6)  return (n / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
    if (n >= 1e3)  return (n / 1e3).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' rb';
    return String(Math.round(n));
}

var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];

function renderDonut(slug, $box, res) {
    if (_dashCharts[slug]) { _dashCharts[slug].destroy(); }
    if (!res.total) {
        $box.html('<div class="text-center text-muted py-5">Belum ada data</div>');
        return;
    }
    $box.empty();
    _dashCharts[slug] = new ApexCharts($box[0], {
        chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
        series: res.data,
        labels: res.labels,
        colors: res.colors,
        legend: { position: 'right' },
        dataLabels: { enabled: true, formatter: function (v, o) { return o.w.config.series[o.seriesIndex]; } },
        plotOptions: { pie: { donut: { size: '62%', labels: { show: true, total: { show: true, label: 'Total SQ', formatter: function () { return res.total; } } } } } },
    });
    _dashCharts[slug].render();
}

// Nilai SO per bulan: batang + garis (tren) per seri. Mode "semua" = Kotor & Bersih berdampingan.
// Label angka di atas batang (horizontal untuk 1 seri, tegak untuk 2 seri), lihat komentar di renderNilaiBulan.
// Legenda & total dirender sendiri di atas grafik (apex 3.22 tidak punya legenda kustom).
var NILAI_WARNA = { kotor: '#7c3aed', bersih: '#0d9488' };

function renderNilaiBulan(slug, $card, $box, res) {
    var keys = res.mode === 'semua' ? ['kotor', 'bersih'] : [res.mode];
    var nama = { kotor: 'Kotor (sebelum discount)', bersih: 'Bersih (setelah discount)' };

    // Ringkasan + legenda
    $card.find('.dw-chart-summary').html(keys.map(function (k) {
        var total = res['total_' + k];
        return '<span class="d-inline-flex align-items-center gap-1">'
            + '<span style="width:10px;height:10px;border-radius:2px;background:' + NILAI_WARNA[k] + ';display:inline-block;"></span>'
            + nama[k] + ': <b style="color:#1e293b;">' + formatRupiahDash(total) + '</b></span>';
    }).join('') + '<span class="ms-auto">Tahun ' + res.year + '</span>');

    // Seri: semua batang dulu, lalu semua garis (indeks batang = 0..n-1 untuk label)
    var series = keys.map(function (k) { return { name: nama[k], type: 'column', data: res[k] }; })
        .concat(keys.map(function (k) { return { name: nama[k] + ' (tren)', type: 'line', data: res[k] }; }));
    var colors = keys.map(function (k) { return NILAI_WARNA[k]; }).concat(keys.map(function (k) { return NILAI_WARNA[k]; }));
    var barIdx = keys.map(function (k, i) { return i; });
    var single = keys.length === 1;

    // Label angka SELALU di atas batang (bukan di dalam), jadi terbaca berapa pun tinggi batangnya.
    // Sumbu Y diberi ruang ekstra di atas (headroom) supaya label batang tertinggi tidak terpotong.
    // Mode "semua" (2 batang berdampingan): label diputar tegak agar tidak saling menimpa.
    var nilaiMax = Math.max.apply(null, keys.reduce(function (a, k) { return a.concat(res[k]); }, [0]));
    var yMax = nilaiMax > 0 ? nilaiMax * (single ? 1.18 : 1.4) : undefined;

    function putarLabel() {
        if (single) return;
        $box.find('.apexcharts-datalabels text').each(function () {
            var x = parseFloat(this.getAttribute('x')) + 4, y = parseFloat(this.getAttribute('y'));
            this.setAttribute('x', x);
            this.setAttribute('text-anchor', 'start');
            this.setAttribute('transform', 'rotate(-90 ' + x + ' ' + y + ')');
        });
    }

    if (_dashCharts[slug]) { _dashCharts[slug].destroy(); }
    _dashCharts[slug] = new ApexCharts($box[0], {
        chart: {
            type: 'line', height: 340, toolbar: { show: false }, fontFamily: 'inherit',
            events: { mounted: putarLabel, updated: putarLabel },
        },
        series: series,
        colors: colors,
        stroke: { width: series.map(function (s) { return s.type === 'line' ? 3 : 0; }), curve: 'smooth' },
        markers: { size: series.map(function (s) { return s.type === 'line' ? 4 : 0; }), strokeWidth: 2, strokeColors: '#fff' },
        plotOptions: { bar: { borderRadius: 3, columnWidth: single ? '45%' : '65%', dataLabels: { position: 'top' } } },
        dataLabels: {
            enabled: true,
            enabledOnSeries: barIdx,
            offsetY: single ? -22 : -6,
            formatter: function (v) { return v ? ringkasRupiah(v) : ''; },
            style: { colors: ['#334155'], fontSize: single ? '11px' : '10px', fontWeight: 600 },
            background: { enabled: false },
        },
        xaxis: { categories: BULAN },
        yaxis: { labels: { formatter: function (v) { return ringkasRupiah(v); } }, min: 0, max: yMax, forceNiceScale: true },
        grid: { borderColor: '#e2e8f0', padding: { top: 10, right: 10 } },
        legend: { show: false },
        // Garis tren memakai data yang sama dengan batang → tooltip cukup 1 baris per kondisi (hanya seri batang)
        tooltip: {
            shared: true, intersect: false,
            custom: function (o) {
                var rows = keys.map(function (k, i) {
                    return '<div style="display:flex;align-items:center;gap:6px;padding:2px 10px;font-size:12px;">'
                        + '<span style="width:9px;height:9px;border-radius:50%;background:' + NILAI_WARNA[k] + ';display:inline-block;"></span>'
                        + nama[k] + ': <b>' + formatRupiahDash(o.series[i][o.dataPointIndex]) + '</b></div>';
                }).join('');
                return '<div style="padding:4px 0;"><div style="padding:4px 10px;background:#eef2f6;font-size:12px;">'
                    + BULAN[o.dataPointIndex] + ' ' + res.year + '</div>' + rows + '</div>';
            },
        },
        noData: { text: 'Belum ada data' },
    });
    _dashCharts[slug].render();
}

// ── Peta Customer per Provinsi (Leaflet, tanpa peta dasar) ───────────────
// Metrik: customer | site | nilai SO. Warna = nilai metrik per provinsi dengan KELAS (bukan skala linear)
// agar provinsi kecil tidak "tertelan" Jakarta/Jawa Barat. Label angka di tiap provinsi (disembunyikan bila
// provinsi terlalu kecil di zoom saat ini). Klik provinsi → panel kota + customer. Filter lewat toolbar.
var _dashMaps = {};
var PETA_WARNA = ['#dbeafe', '#93c5fd', '#3b82f6', '#1d4ed8', '#1e3a8a'];
var PETA_KOSONG = '#f1f5f9';

function escD(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

// Kelas warna: jumlah (customer/site) pakai batas tetap; nilai Rp pakai pecahan dari nilai tertinggi.
function kelasPeta(metrik, max) {
    var batas;
    if (!max) return []; // tidak ada data → legenda hanya "0"
    if (metrik === 'nilai') {
        batas = [0.02, 0.1, 0.3, 0.6, 1].map(function (f) { return Math.max(1, Math.round(max * f)); });
    } else {
        batas = [1, 5, 15, 30, Infinity];
    }
    var out = [], awal = 1;
    batas.forEach(function (b, i) {
        if (awal > Math.max(max, 1)) return;
        var akhir = b;
        if (akhir < awal) return; // batas berimpit (nilai tertinggi kecil) → lewati kelas kosong
        var label;
        if (metrik === 'nilai') {
            label = (i === 0 ? '≤ ' : '> ' + ringkasRupiah(awal - 1) + ' – ') + ringkasRupiah(akhir);
        } else {
            label = akhir === Infinity ? (awal + '+') : (awal === akhir ? String(awal) : awal + '–' + akhir);
        }
        out.push({ min: awal, max: akhir, warna: PETA_WARNA[i], label: label });
        awal = akhir + 1;
    });
    return out;
}

function warnaPeta(n, kelas) {
    if (!n) return PETA_KOSONG;
    for (var i = 0; i < kelas.length; i++) if (n >= kelas[i].min && n <= kelas[i].max) return kelas[i].warna;
    return kelas.length ? kelas[kelas.length - 1].warna : PETA_KOSONG;
}

// Nilai metrik untuk ditampilkan: angka biasa atau Rupiah ringkas
function fmtMetrik(metrik, n) { return metrik === 'nilai' ? ringkasRupiah(n) : String(n); }

// Pilihan toolbar → parameter ke backend
function petaParams($card) {
    var metrik = $card.find('.dw-peta-metrik').val() || 'customer';
    var p = { metrik: metrik };
    if (metrik !== 'nilai' && $card.find('.dw-peta-cakupan').val() === 'so') p.cakupan = 'so';
    if (metrik === 'nilai' || p.cakupan === 'so') {
        var th = $card.find('.dw-peta-tahun').val();
        if (th) p.tahun = th;
    }
    var kat = $card.find('.dw-peta-kategori').val();
    if (kat) p.kategori = kat;
    if (metrik === 'nilai') p.mode = $card.find('.dw-peta-mode').val() || 'bersih';
    return p;
}

// Aktif/nonaktif & tampil/sembunyi kontrol sesuai metrik yang dipilih
function petaAturToolbar($card) {
    var metrik = $card.find('.dw-peta-metrik').val();
    var cakupanSo = $card.find('.dw-peta-cakupan').val() === 'so';
    $card.find('.dw-peta-cakupan').prop('disabled', metrik === 'nilai');
    $card.find('.dw-peta-tahun').prop('disabled', !(metrik === 'nilai' || cakupanSo));
    $card.find('.dw-peta-mode').toggleClass('d-none', metrik !== 'nilai');
}

function loadPeta(slug) {
    var $card = $('[data-chart-widget="' + slug + '"]');
    petaAturToolbar($card);
    $.get(window.route.widget + '/' + slug, petaParams($card), function (res) { renderPeta(slug, $card, res); })
        .fail(function () { $card.find('.dw-peta-map').html('<div class="text-center text-danger py-5">Gagal memuat peta.</div>'); });
}

$(document).on('change', '.dw-peta-metrik, .dw-peta-cakupan, .dw-peta-tahun, .dw-peta-kategori, .dw-peta-mode', function () {
    loadPeta($(this).closest('[data-chart-widget]').data('chart-widget'));
});

function renderPeta(slug, $card, res) {
    var $mapEl = $card.find('.dw-peta-map');
    var $panel = $card.find('.dw-peta-panel');
    var metrik = res.metrik;
    var kelas = kelasPeta(metrik, res.max);
    var byKode = {};
    res.provinsi.forEach(function (p) { byKode[p.kode] = p; });
    var nilaiMode = res.mode === 'kotor' ? 'kotor (sebelum discount)' : 'bersih (setelah discount)';

    // Isi pilihan tahun & kategori (sekali, lalu pertahankan pilihan user)
    var $th = $card.find('.dw-peta-tahun'), $kat = $card.find('.dw-peta-kategori');
    if (!$th.data('terisi')) {
        $th.html('<option value="">Semua tahun SO</option>' + (res.years || []).map(function (y) { return '<option value="' + y + '">' + y + '</option>'; }).join('')).data('terisi', 1);
        $kat.html('<option value="">Semua kategori bisnis</option>' + (res.kategori_opsi || []).map(function (k) { return '<option value="' + k.id + '">' + escD(k.nama) + '</option>'; }).join('')).data('terisi', 1);
    }
    petaAturToolbar($card);

    // Ringkasan di atas peta
    var ringkas = '<span>Customer: <b style="color:#1e293b;">' + res.total_customer + '</b></span>'
        + '<span>Site: <b style="color:#1e293b;">' + res.total_site + '</b></span>';
    if (metrik === 'nilai') ringkas += '<span>Nilai SO ' + nilaiMode + ': <b style="color:#1e293b;">' + formatRupiahDash(res.total_nilai) + '</b></span>';
    if (res.belum_site) {
        ringkas += '<a href="#" class="dw-peta-belum text-decoration-none" style="color:#b45309;"><i class="fa-solid fa-triangle-exclamation me-1"></i>'
            + res.belum_site + ' lokasi (' + res.belum_customer + ' customer) belum terpetakan — lihat daftar</a>';
    }
    $card.find('.dw-chart-summary').html(ringkas);

    // Parameter filter yang sama dipakai untuk rincian provinsi & daftar belum terpetakan
    function filterParams(extra) { return $.extend(petaParams($card), extra || {}); }

    // Panel awal: peringkat provinsi menurut metrik
    function panelAwal() {
        var top = res.provinsi.filter(function (p) { return p[metrik] > 0; }).sort(function (a, b) { return b[metrik] - a[metrik]; });
        var maxN = top.length ? top[0][metrik] : 1;
        var html = '<div class="text-muted mb-2" style="font-size:12px;"><i class="fa-regular fa-hand-pointer me-1"></i>Klik provinsi di peta untuk melihat kota dan customer-nya.</div>'
            + '<div class="fw-semibold mb-2">Peringkat provinsi</div>';
        html += top.map(function (p) {
            return '<div class="dw-peta-row mb-2" data-kode="' + p.kode + '" style="cursor:pointer;">'
                + '<div class="d-flex justify-content-between" style="font-size:12px;"><span>' + escD(p.provinsi) + '</span><b>' + fmtMetrik(metrik, p[metrik]) + '</b></div>'
                + '<div style="height:6px;background:#e2e8f0;border-radius:3px;"><div style="height:6px;border-radius:3px;background:' + warnaPeta(p[metrik], kelas) + ';width:' + Math.max(4, Math.round(p[metrik] / maxN * 100)) + '%;"></div></div></div>';
        }).join('');
        if (!top.length) html += '<div class="text-muted">Tidak ada data untuk filter ini.</div>';
        $panel.html(html);
    }

    // Panel rincian 1 provinsi (kota + customer)
    function panelProvinsi(kode) {
        var p = byKode[kode] || { provinsi: kode, customer: 0, site: 0, nilai: 0 };
        $panel.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');
        $.get(window.route.widget + '/' + slug, filterParams({ kode: kode }), function (d) {
            var sub = p.customer + ' customer · ' + p.site + ' site' + (metrik === 'nilai' ? ' · ' + formatRupiahDash(p.nilai) : '');
            var html = '<div class="d-flex justify-content-between align-items-start mb-2">'
                + '<div><div class="fw-semibold" style="font-size:14px;">' + escD(d.provinsi) + '</div>'
                + '<div class="text-muted" style="font-size:12px;">' + sub + '</div></div>'
                + '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 dw-peta-back" data-no-disable><i class="fa-solid fa-arrow-left me-1"></i>Kembali</button></div>';
            if (!d.kota.length) {
                html += '<div class="text-muted">Tidak ada customer di provinsi ini untuk filter yang dipilih.</div>';
            } else {
                html += d.kota.map(function (k, i) {
                    var id = 'petaKota_' + kode + '_' + i;
                    var nilaiKota = metrik === 'nilai' ? ringkasRupiah(k.nilai) : (metrik === 'site' ? k.site : k.customer);
                    return '<div class="mb-2" style="border:1px solid #e2e8f0;border-radius:6px;">'
                        + '<div class="d-flex justify-content-between align-items-center px-2 py-1 dw-peta-kota" data-target="#' + id + '" style="cursor:pointer;background:#f8fafc;">'
                        + '<span style="font-size:12px;"><i class="fa-solid fa-chevron-right me-1" style="font-size:9px;"></i>' + escD(k.kota) + '</span>'
                        + '<span class="pm-badge pm-badge--blue">' + nilaiKota + '</span></div>'
                        + '<ul id="' + id + '" class="list-unstyled mb-0 px-2 py-1" style="display:none;font-size:12px;">'
                        + k.daftar.map(function (c) {
                            return '<li class="py-1 d-flex justify-content-between gap-2"><a href="' + window.route.customerDetail + '?open=' + c.id_br + '" class="text-decoration-none" style="color:#1a56db;">' + escD(c.nama) + '</a>'
                                + (metrik === 'nilai' ? '<span class="text-muted text-nowrap">' + ringkasRupiah(c.nilai) + '</span>' : '') + '</li>';
                        }).join('') + '</ul></div>';
                }).join('');
            }
            $panel.html(html);
        }).fail(function () { $panel.html('<div class="text-danger">Gagal memuat rincian provinsi.</div>'); });
    }

    if (_dashMaps[slug]) { _dashMaps[slug].map.remove(); delete _dashMaps[slug]; }

    $.getJSON(window.route.petaGeo, function (geo) {
        var map = L.map($mapEl[0], {
            zoomSnap: 0.25, minZoom: 3, maxZoom: 9, scrollWheelZoom: false, attributionControl: false,
        });

        var selected = null;
        var layerByKode = {};
        var labelByKode = {};
        var geoLayer = L.geoJSON(geo, {
            style: function (f) {
                var p = byKode[f.properties.kode_wilayah];
                return { fillColor: warnaPeta(p ? p[metrik] : 0, kelas), fillOpacity: 0.95, color: '#ffffff', weight: 1 };
            },
            onEachFeature: function (f, layer) {
                var kode = f.properties.kode_wilayah;
                var p = byKode[kode] || { customer: 0, site: 0, nilai: 0 };
                layerByKode[kode] = layer;
                layer.bindTooltip('<b>' + escD(f.properties.nama) + '</b><br>Customer: ' + p.customer + '<br>Site: ' + p.site
                    + (metrik === 'nilai' ? '<br>Nilai SO: ' + formatRupiahDash(p.nilai) : ''), { sticky: true });
                layer.on({
                    mouseover: function () { if (selected !== layer) layer.setStyle({ weight: 2, color: '#334155' }); layer.bringToFront(); if (selected) selected.bringToFront(); },
                    mouseout: function () { if (selected !== layer) geoLayer.resetStyle(layer); },
                    click: function () { pilih(kode); },
                });

                // Label angka di tengah provinsi (tidak menangkap klik)
                if (p[metrik] > 0) {
                    labelByKode[kode] = L.marker(layer.getBounds().getCenter(), {
                        interactive: false, keyboard: false,
                        icon: L.divIcon({ className: 'dw-peta-lbl-wrap', html: '<span class="dw-peta-lbl">' + fmtMetrik(metrik, p[metrik]) + '</span>', iconSize: [0, 0] }),
                    }).addTo(map);
                }
            },
        }).addTo(map);

        // Sembunyikan label provinsi yang terlalu kecil di zoom sekarang (tampil saat di-zoom)
        function aturLabel() {
            Object.keys(labelByKode).forEach(function (kode) {
                var b = layerByKode[kode].getBounds();
                var w = Math.abs(map.latLngToLayerPoint(b.getNorthEast()).x - map.latLngToLayerPoint(b.getSouthWest()).x);
                var el = labelByKode[kode].getElement();
                if (el) el.style.display = (w >= 30 || map.getZoom() >= 7 || (selected && layerByKode[kode] === selected)) ? '' : 'none';
            });
        }
        map.on('zoomend moveend', aturLabel);

        function pilih(kode) {
            if (selected) geoLayer.resetStyle(selected);
            selected = layerByKode[kode] || null;
            if (selected) {
                selected.setStyle({ weight: 3, color: '#f59e0b' });
                selected.bringToFront();
                map.fitBounds(selected.getBounds(), { maxZoom: 7, padding: [30, 30] });
            }
            panelProvinsi(kode);
        }
        function reset() {
            if (selected) geoLayer.resetStyle(selected);
            selected = null;
            map.fitBounds(geoLayer.getBounds());
            panelAwal();
        }
        // Legenda (sesuai metrik)
        var judulLegenda = metrik === 'nilai' ? 'Nilai SO' : (metrik === 'site' ? 'Site' : 'Customer');
        var legend = L.control({ position: 'bottomright' });
        legend.onAdd = function () {
            var div = L.DomUtil.create('div');
            div.style.cssText = 'background:#fff;padding:6px 8px;border-radius:6px;font-size:11px;box-shadow:0 1px 4px rgba(0,0,0,.2);line-height:1.5;';
            var items = [{ warna: PETA_KOSONG, label: '0' }].concat(kelas);
            div.innerHTML = '<div style="font-weight:600;margin-bottom:2px;">' + judulLegenda + '</div>' + items.map(function (k) {
                return '<div><span style="display:inline-block;width:12px;height:12px;background:' + k.warna + ';border:1px solid #cbd5e1;margin-right:5px;vertical-align:middle;"></span>' + k.label + '</div>';
            }).join('');
            return div;
        };
        legend.addTo(map);

        map.fitBounds(geoLayer.getBounds());
        _dashMaps[slug] = { map: map, reset: reset, pilih: pilih };
        panelAwal();
        aturLabel();

        // Interaksi (didelegasikan sekali per render peta)
        $panel.off('.peta')
            .on('click.peta', '.dw-peta-row', function () { pilih(String($(this).data('kode'))); })
            .on('click.peta', '.dw-peta-back', reset)
            .on('click.peta', '.dw-peta-kota', function () {
                $($(this).data('target')).slideToggle(120);
                $(this).find('.fa-chevron-right').toggleClass('fa-rotate-90');
            });
        $card.find('.dw-peta-reset').off('click.peta').on('click.peta', reset);
        $card.find('.dw-chart-summary').off('click.peta').on('click.peta', '.dw-peta-belum', function (e) {
            e.preventDefault();
            tampilBelumTerpetakan(slug, filterParams({ belum: 1 }));
        });
        setTimeout(function () { map.invalidateSize(); map.fitBounds(geoLayer.getBounds()); aturLabel(); }, 150);
    }).fail(function () {
        $mapEl.html('<div class="text-center text-danger py-5">Gagal memuat peta.</div>');
    });
}

// Daftar lokasi yang provinsinya kosong / tidak dikenali → supaya bisa dilengkapi di data Site
function tampilBelumTerpetakan(slug, params) {
    $.get(window.route.widget + '/' + slug, params, function (d) {
        var rows = (d.belum || []).map(function (r) {
            var url = window.route.customerDetail + '?open=' + r.id_br + (r.id_site ? '&tab=tabBrsSite&site=' + r.id_site : '');
            return '<tr><td><a href="' + url + '" target="_blank" class="text-decoration-none">' + escD(r.customer) + '</a></td>'
                + '<td>' + escD(r.site || '—') + '</td><td>' + escD(r.provinsi || '(kosong)') + '</td><td>' + escD(r.kota || '—') + '</td></tr>';
        }).join('');
        Swal.fire({
            title: 'Belum terpetakan',
            html: '<div class="text-start" style="font-size:12px;color:#64748b;margin-bottom:8px;">Provinsi pada Site kosong atau tidak dikenali. Buka customer lalu lengkapi provinsi di tab Site.</div>'
                + '<div class="table-responsive text-start" style="max-height:340px;overflow:auto;"><table class="table table-sm mb-0" style="font-size:12px;">'
                + '<thead><tr><th>Customer</th><th>Site</th><th>Provinsi tertulis</th><th>Kota</th></tr></thead><tbody>' + rows + '</tbody></table></div>',
            width: 720,
            confirmButtonText: 'Tutup',
        });
    });
}

function loadChart(slug, year, mode) {
    var $card = $('[data-chart-widget="' + slug + '"]');
    var $box  = $card.find('.dw-chart-box');

    var params = {};
    if (year) params.year = year;
    if (mode) params.mode = mode;

    $.get(window.route.widget + '/' + slug, params, function (res) {
        if (res.kind === 'donut') return renderDonut(slug, $box, res);
        if (res.kind === 'peta') return renderPeta(slug, $card, res);

        var $sel = $card.find('.dw-chart-year');
        $sel.html((res.years || [res.year]).map(function (y) {
            return '<option value="' + y + '"' + (Number(y) === Number(res.year) ? ' selected' : '') + '>' + y + '</option>';
        }).join(''));

        if (res.kind === 'bar_tahun_nilai') return renderNilaiBulan(slug, $card, $box, res);

        // Jumlah SO per bulan (batang). Ringkasan di atas grafik, bukan subtitle apex (menimpa sumbu).
        $card.find('.dw-chart-summary').html('<span>Total <b style="color:#1e293b;">' + res.total + ' SO</b> pada ' + res.year + '</span>');
        if (_dashCharts[slug]) { _dashCharts[slug].destroy(); }
        _dashCharts[slug] = new ApexCharts($box[0], {
            chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
            series: [{ name: 'Jumlah SO', data: res.data }],
            xaxis: { categories: BULAN },
            yaxis: { labels: { formatter: function (v) { return Math.round(v); } }, min: 0, forceNiceScale: true },
            dataLabels: { enabled: true, offsetY: -20, style: { colors: ['#334155'], fontSize: '11px' } },
            tooltip: { y: { formatter: function (v) { return v + ' SO'; } } },
            colors: ['#1a56db'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '50%', dataLabels: { position: 'top' } } },
            noData: { text: 'Belum ada data' },
        });
        _dashCharts[slug].render();
    }).fail(function () {
        $box.html('<div class="text-center text-danger py-4">Gagal memuat grafik.</div>');
    });
}
