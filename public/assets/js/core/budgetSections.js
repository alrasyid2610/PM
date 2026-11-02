// Item Anggaran per kategori di modal Budget Plan (WO, FWO, SQ WO, SQ FWO).
// Kategori = parent di Budget Account. Tiap kategori jadi satu section
// (baris header + baris item) yang bisa dilipat. User menambah kategori sendiri lewat "+ Kategori" (kategori yang
// sudah ditambahkan tidak bisa dipilih lagi), lalu item lewat tombol "+ Item" di section-nya.
// Baris item tetap `<tr>` buatan masing-masing modul (class baris & kolomnya tidak berubah), hanya diberi
// atribut data-cat, jadi logika simpan/total di tiap modul tidak perlu berubah.
//
// cfg per modul: { body: '#xxxItemsBody', modal: '#xxxModal', rowSel: '.xxx-item-row', buildRow: fn(item|null) }
window.BudgetSections = (function () {
    var cache = null;
    var OTHER = 'x';

    function esc(s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fmtRp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }

    // Harga satuan (.bi-nominal), Qty (.bi-qty, kosong = 1) → Total baris = qty × harga satuan
    function hargaOf(tr) {
        return parseInt(String($(tr).find('.bi-nominal').val() || '').replace(/,/g, ''), 10) || 0;
    }
    function qtyOf(tr) {
        var raw = String($(tr).find('.bi-qty').val() || '').replace(',', '.');
        var q = parseFloat(raw);
        return isFinite(q) && q > 0 ? q : 1;
    }
    function hariOf(tr) {
        var h = parseFloat(String($(tr).find('.bi-hari').val() || '').replace(',', '.'));
        return isFinite(h) && h > 0 ? h : 1;
    }
    // Total baris = harga satuan × qty × hari
    function nominalOf(tr) {
        return Math.round(qtyOf(tr) * hariOf(tr) * hargaOf(tr));
    }

    // Jumlah hari plan (tanggal mulai–selesai, inklusif); tanpa/invalid → 1
    function daysFromDates(a, b) {
        if (!a || !b) return 1;
        var d1 = new Date(a + 'T00:00:00'), d2 = new Date(b + 'T00:00:00');
        var n = Math.round((d2 - d1) / 86400000) + 1;
        return isFinite(n) && n >= 1 ? n : 1;
    }
    // Versi SQ: hari ke-N mulai s/d hari ke-M selesai
    function daysFromNumbers(a, b) {
        var x = parseInt(a, 10), y = parseInt(b, 10);
        if (!isFinite(x) || !isFinite(y)) return 1;
        var n = y - x + 1;
        return n >= 1 ? n : 1;
    }
    function defaultHari(cfg) {
        var n = cfg.days ? cfg.days() : 1;
        return n >= 1 ? n : 1;
    }
    // Baris baru: Hari mengikuti rentang tanggal plan sampai user mengubahnya sendiri (data-auto=1)
    function markAutoHari(cfg, $tr) {
        $tr.find('.bi-hari').val(defaultHari(cfg)).attr('data-auto', '1');
    }

    // Persentase terhadap nilai acuan (nilai bersih SO / SQ). Basis 0 → tidak ditampilkan.
    function fmtPct(n, basis) {
        if (!basis || basis <= 0) return '';
        var v = n / basis * 100;
        if (v > 0 && v < 0.01) return '<0,01%';
        return v.toLocaleString('id-ID', { maximumFractionDigits: 2 }) + '%';
    }

    function fetchBasis(url) {
        return $.getJSON(url).then(function (r) { return r || { nilai: 0 }; }, function () { return $.Deferred().resolve({ nilai: 0 }).promise(); });
    }

    function basisOf(cfg) { return $(cfg.modal).data('bsBasis') || { nilai: 0 }; }

    // Ambil nilai acuan untuk modal ini, lalu hitung ulang persentase
    function loadBasis(cfg, url) {
        $(cfg.modal).data('bsBasis', { nilai: 0 });
        return fetchBasis(url).then(function (b) {
            $(cfg.modal).data('bsBasis', b);
            refresh(cfg);
        });
    }

    function letter(i) { return i < 26 ? String.fromCharCode(65 + i) : String(i + 1); }

    // Daftar kategori + sub kategori (di-cache per halaman)
    function load() {
        if (cache) return $.Deferred().resolve(cache).promise();
        return $.getJSON('/budget-accounts/select2', { mode: 'sections' }).then(function (cats) {
            cache = cats || [];
            return cache;
        });
    }

    function cfgOf(el) { return $(el).closest('tbody').data('bsCfg'); }
    function catsOf(cfg) { return $(cfg.body).data('bsCats') || []; }
    function rowsOf(cfg, key) {
        return $(cfg.body).children(cfg.rowSel).filter(function () { return String($(this).attr('data-cat')) === String(key); });
    }

    function headerHtml(cat, idx) {
        // 11 kolom tabel: 10 pertama (Account … Cash Advance) dipakai judul + "+ Item"; kolom ke-11 (kolom hapus)
        // berisi tombol hapus kategori, jadi sejajar tegak lurus dengan tombol hapus baris item di bawahnya.
        return '<tr class="bi-cat-header" data-cat="' + esc(cat.id_kategori) + '">' +
            '<td colspan="10" style="background:#f0fdf4;border-top:2px solid #bbf7d0;cursor:pointer;padding:6px 10px;">' +
            '<div class="d-flex align-items-center gap-2 flex-wrap">' +
            '<i class="fa-solid fa-chevron-down bi-cat-chevron" style="font-size:10px;color:#0f766e;transition:transform .2s;"></i>' +
            '<span class="fw-semibold bi-cat-title" style="color:#0f766e;font-size:13px;" data-nama="' + esc(cat.nama) + '">' + letter(idx) + '. ' + esc(cat.nama) + '</span>' +
            '<span class="bi-cat-count" style="font-size:10px;background:#dcfce7;color:#166534;border-radius:10px;padding:1px 8px;"></span>' +
            '<span class="bi-cat-empty" style="font-size:11px;color:#b45309;"><i class="fa-solid fa-circle-exclamation me-1"></i>Belum diisi</span>' +
            '<span class="ms-auto fw-semibold bi-cat-sub" style="font-size:12px;color:#0f766e;"></span>' +
            '<button type="button" class="pm-btn-pill pm-btn-pill--teal btn-bi-cat-add" data-no-disable>' +
            '<i class="fa-solid fa-plus" style="font-size:10px;"></i> Item</button>' +
            '</div></td>' +
            '<td class="text-center" style="background:#f0fdf4;border-top:2px solid #bbf7d0;cursor:pointer;padding:6px 10px;">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 btn-bi-cat-remove" data-no-disable title="Hapus kategori ini" style="font-size:11px;">' +
            '<i class="fa-solid fa-trash" style="color:#dc2626;"></i></button>' +
            '</td></tr>';
    }

    // Isi ulang section. items = item budget yang sudah ada (edit) atau [] (tambah).
    function fill(cfg, items) {
        return load().then(function (loaded) {
            var cats = JSON.parse(JSON.stringify(loaded));
            var catOf = {};   // id_account -> key kategori
            cats.forEach(function (c) {
                catOf[c.id_kategori] = c.id_kategori;
                c.children.forEach(function (ch) { catOf[ch.id] = c.id_kategori; });
            });

            var grouped = {};
            var needOther = false;
            (items || []).forEach(function (it) {
                var key = catOf[it.id_account];
                if (key === undefined) { key = OTHER; needOther = true; }
                (grouped[key] = grouped[key] || []).push(it);
            });
            if (needOther) cats.push({ id_kategori: OTHER, nama: 'Lainnya (tanpa kategori)', children: [] });

            var $body = $(cfg.body).empty().data('bsCfg', cfg).data('bsCats', cats);
            // urutan section = urutan kemunculan item (urutan user saat menyusun plan)
            var seen = [];
            (items || []).forEach(function (it) {
                var k = catOf[it.id_account] === undefined ? OTHER : catOf[it.id_account];
                if (seen.indexOf(k) < 0) seen.push(k);
            });
            seen.forEach(function (k) {
                var cat = cats.filter(function (c) { return String(c.id_kategori) === String(k); })[0];
                var its = grouped[k];
                if (!cat || !its) return;
                $body.append(headerHtml(cat, 0));
                its.forEach(function (it) {
                    $body.append(cfg.buildRow(it)).children().last().attr('data-cat', cat.id_kategori);
                });
            });

            ensureToolbar(cfg);
            $(cfg.modal).data('bsCfgModal', cfg);
            return cfg;
        });
    }

    // Select2 Account tiap baris dibatasi ke sub kategori dari kategori barisnya.
    function initSelects(cfg) {
        var cats = catsOf(cfg);
        initUnits(cfg);
        $(cfg.body).find('.bi-account').each(function () {
            var $sel = $(this);
            if ($sel.hasClass('select2-hidden-accessible')) return;

            var key = $sel.closest('tr').attr('data-cat');
            var cat = cats.filter(function (c) { return String(c.id_kategori) === String(key); })[0];
            var curId = $sel.val() || '';
            var curText = $sel.find('option:selected').text();

            var data = [{ id: '', text: '' }];
            (cat ? cat.children : []).forEach(function (ch) {
                if (ch.is_aktif || String(ch.id) === String(curId)) {
                    data.push({ id: ch.id, text: ch.text, nilai_default: ch.nilai_default });
                }
            });
            if (curId && !data.some(function (d) { return String(d.id) === String(curId); })) {
                data.push({ id: curId, text: curText });
            }

            $sel.empty().select2({
                dropdownParent: $(cfg.modal),
                placeholder: 'Pilih Account',
                allowClear: false,
                width: '100%',
                data: data,
            });
            $sel.val(curId || null).trigger('change.select2');
        });
    }

    // Select2 Unit (master Satuan) tiap baris
    function initUnits(cfg) {
        $(cfg.body).find('.bi-unit').each(function () {
            var $sel = $(this);
            if ($sel.hasClass('select2-hidden-accessible')) return;
            $sel.select2({
                dropdownParent: $(cfg.modal),
                placeholder: 'Unit',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '/satuan/select2', dataType: 'json', delay: 200,
                    data: function (p) { return { q: p.term }; },
                    processResults: function (d) { return { results: d }; },
                    cache: true,
                },
            });
        });
    }

    // Hitung ulang jumlah item, subtotal, penanda kategori kosong, dan ringkasan di modal.
    function refresh(cfg) {
        if (!cfg || !$(cfg.body).length) return;
        var empties = [];
        var thH = $(cfg.body).closest('table').find('thead').outerHeight();
        if (thH) $(cfg.body).closest('table').get(0).style.setProperty('--bi-th-h', thH + 'px');
        var barH = $(cfg.modal).find('.bi-sec-bar').outerHeight();
        if (barH) $(cfg.body).closest('table').get(0).style.setProperty('--bi-bar-h', barH + 'px');
        var dh = defaultHari(cfg);
        $(cfg.body).find('.bi-hari[data-auto="1"]').each(function () {
            if (String($(this).val()) !== String(dh)) $(this).val(dh);
        });
        applyTanpaState(cfg);
        var basis = basisOf(cfg).nilai;
        var $headers = $(cfg.body).children('.bi-cat-header');
        var grand = 0;
        $(cfg.body).children(cfg.rowSel).each(function () {
            var n = nominalOf(this);
            grand += n;
            $(this).find('.bi-total').val(n ? n.toLocaleString('en-US') : '');
            var $td = $(this).find('.bi-total').closest('td');
            var $p = $td.find('.bi-pct');
            if (!$p.length) $p = $('<small class="bi-pct d-block text-muted" style="font-size:10.5px;"></small>').appendTo($td);
            $p.text(n > 0 ? fmtPct(n, basis) : '');
        });
        $(cfg.body).children('.bi-cat-placeholder').remove();
        if (!$headers.length) {
            $(cfg.body).append('<tr class="bi-cat-placeholder"><td colspan="11" class="text-center text-muted py-4" style="font-size:12px;">' +
                'Belum ada item. Klik <b>+ Kategori</b> untuk menambahkan kategori anggaran, lalu <b>+ Item</b> di dalamnya.</td></tr>');
        }
        $headers.each(function (idx) {
            var $h = $(this);
            var $t = $h.find('.bi-cat-title');
            $t.text(letter(idx) + '. ' + $t.attr('data-nama'));
            var $rows = rowsOf(cfg, $h.attr('data-cat'));
            var sum = 0;
            $rows.each(function () { sum += nominalOf(this); });
            $h.find('.bi-cat-count').text($rows.length + ' item').toggle($rows.length > 0);
            $h.find('.bi-cat-empty').toggle($rows.length === 0);
            var sp = $rows.length ? fmtPct(sum, basis) : '';
            $h.find('.bi-cat-sub').text($rows.length ? fmtRp(sum) + (sp ? ' · ' + sp : '') : '');
            if (!$rows.length) empties.push(letter(idx) + '. ' + $t.attr('data-nama'));
        });

        if (cfg.total) {
            var $t = $(cfg.total);
            var $tp = $t.parent().find('.bi-total-pct');
            if (!$tp.length) $tp = $('<span class="bi-total-pct ms-2 fw-normal" style="font-size:12px;"></span>').insertAfter($t);
            var b = basisOf(cfg);
            $tp.text(b.nilai > 0
                ? '(' + fmtPct(grand, b.nilai) + ' dari nilai ' + (b.jenis || 'SO') + (b.no ? ' ' + b.no : '') + ' ' + fmtRp(b.nilai) + ')'
                : '(nilai ' + (b.jenis || 'SO') + ' belum ada)');
        }

        var $sum = $(cfg.modal).find('.bi-cat-summary');
        if (empties.length) {
            $sum.html('<i class="fa-solid fa-circle-exclamation me-1"></i>' + empties.length + ' kategori belum berisi item: ' + esc(empties.join(', '))).show();
        } else {
            $sum.hide().empty();
        }
    }

    function addedKeys(cfg) {
        return $(cfg.body).children('.bi-cat-header').map(function () { return String($(this).attr('data-cat')); }).get();
    }

    // Tombol "+ Kategori" (dropdown berisi kategori yang belum ditambahkan), disisipkan sebelum ringkasan
    function ensureToolbar(cfg) {
        var $modal = $(cfg.modal);
        $(cfg.body).closest('table').addClass('bi-sec-table').parent().addClass('bi-sec-wrap');
        $modal.find('.bi-cat-summary').parent().addClass('bi-sec-bar');
        // Tanggal plan berubah → Hari baris otomatis ikut (refresh); juga setelah modal tampil (tanggal default dipasang flatpickr)
        if (cfg.dateSel) {
            $(cfg.dateSel).off('change.biHari input.biHari').on('change.biHari input.biHari', function () { if (cfg.onChange) cfg.onChange(); });
        }
        $modal.off('shown.bs.modal.biHari').on('shown.bs.modal.biHari', function () { setTimeout(function () { if (cfg.onChange) cfg.onChange(); }, 80); });
        if ($modal.find('.bi-cat-toolbar').length) return;
        $modal.find('.bi-cat-summary').before(
            '<div class="dropdown bi-cat-toolbar ms-auto">' +
            '<button type="button" class="pm-btn-pill pm-btn-pill--teal" data-bs-toggle="dropdown" data-no-disable>' +
            '<i class="fa-solid fa-plus" style="font-size:10px;"></i> Kategori</button>' +
            '<ul class="dropdown-menu dropdown-menu-end bi-cat-menu" style="max-height:280px;overflow:auto;font-size:13px;"></ul></div>');
    }

    function fillMenu($toolbar) {
        var cfg = $toolbar.closest('.modal').data('bsCfgModal');
        if (!cfg) return;
        var added = addedKeys(cfg);
        var left = catsOf(cfg).filter(function (c) { return c.id_kategori !== OTHER && added.indexOf(String(c.id_kategori)) < 0; });
        $toolbar.find('.bi-cat-menu').html(left.length
            ? left.map(function (c) {
                return '<li><a class="dropdown-item bi-cat-pick" href="#" data-cat="' + esc(c.id_kategori) + '">' + esc(c.nama) + '</a></li>';
            }).join('')
            : '<li><span class="dropdown-item-text text-muted">Semua kategori sudah ditambahkan</span></li>');
    }

    function addCategory(cfg, key) {
        var cats = catsOf(cfg);
        var idx = -1;
        cats.forEach(function (c, i) { if (String(c.id_kategori) === String(key)) idx = i; });
        if (idx < 0 || addedKeys(cfg).indexOf(String(key)) >= 0) return;

        // section baru selalu di paling bawah (urutan = urutan user menambahkan)
        $(cfg.body).append(headerHtml(cats[idx], 0));
        refresh(cfg);
        addRow(cfg, key);
    }

    function toggle($h, collapse) {
        var cfg = cfgOf($h);
        if (!cfg) return;
        var $rows = rowsOf(cfg, $h.attr('data-cat'));
        if (collapse === undefined) collapse = !$h.data('collapsed');
        $h.data('collapsed', collapse);
        $rows.toggle(!collapse);
        $h.find('.bi-cat-chevron').css('transform', collapse ? 'rotate(-90deg)' : 'rotate(0deg)');
    }

    function addRow(cfg, key) {
        var $h = $(cfg.body).children('.bi-cat-header').filter(function () { return String($(this).attr('data-cat')) === String(key); });
        if (!$h.length) return;
        if ($h.data('collapsed')) toggle($h, false);

        var $rows = rowsOf(cfg, key);
        var $anchor = $rows.length ? $rows.last() : $h;
        var $tr = $(cfg.buildRow(null)).attr('data-cat', key);
        markAutoHari(cfg, $tr);
        $anchor.after($tr);

        initSelects(cfg);
        initNumericMask(document.querySelector(cfg.modal));
        refresh(cfg);
        $tr.find('.bi-account').select2('open');
    }

    $(document)
        .off('input.biSec', '.bi-qty, .bi-nominal, .bi-hari').on('input.biSec', '.bi-qty, .bi-nominal, .bi-hari', function () {
            if ($(this).hasClass('bi-hari')) $(this).attr('data-auto', '0'); // diubah manual → tidak ikut tanggal lagi
            var cfg = cfgOf(this);
            if (cfg && cfg.onChange) cfg.onChange();
        })
        .off('input.biTanpa', '.bi-qty-tanpa').on('input.biTanpa', '.bi-qty-tanpa', function () { var cfg = cfgOf(this); if (cfg) applyTanpaState(cfg); })
        .off('click.biSec', '.btn-bi-cat-add').on('click.biSec', '.btn-bi-cat-add', function (e) {
            e.stopPropagation();
            var cfg = cfgOf(this);
            if (cfg) addRow(cfg, $(this).closest('tr').attr('data-cat'));
        })
        .off('click.biSec', '.btn-bi-cat-remove').on('click.biSec', '.btn-bi-cat-remove', function (e) {
            e.stopPropagation();
            var cfg = cfgOf(this);
            if (!cfg) return;
            var $h = $(this).closest('tr');
            var $rows = rowsOf(cfg, $h.attr('data-cat'));
            var done = function () {
                $rows.remove(); $h.remove();
                refresh(cfg);
                if (cfg.onChange) cfg.onChange();
            };
            if (!$rows.length) return done();
            Swal.fire({
                title: 'Hapus kategori?',
                html: '<b>' + esc($h.find('.bi-cat-title').attr('data-nama')) + '</b> beserta ' + $rows.length + ' item di dalamnya akan dihapus dari plan ini.',
                icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#ef4444',
            }).then(function (r) { if (r.isConfirmed) done(); });
        })
        .off('show.bs.dropdown.biSec', '.bi-cat-toolbar').on('show.bs.dropdown.biSec', '.bi-cat-toolbar', function () { fillMenu($(this)); })
        .off('click.biSec', '.bi-cat-pick').on('click.biSec', '.bi-cat-pick', function (e) {
            e.preventDefault();
            var cfg = $(this).closest('.modal').data('bsCfgModal');
            if (cfg) addCategory(cfg, $(this).attr('data-cat'));
        })
        .off('click.biSec', '.bi-cat-header').on('click.biSec', '.bi-cat-header', function (e) {
            if ($(e.target).closest('.btn-bi-cat-add').length) return;
            toggle($(this));
        });

    // Kartu plan (daftar): isi persentase di setiap elemen [data-pct-val] di dalam $scope.
    // <td> diisi di dalam sel; elemen lain diberi catatan tepat di sampingnya.
    function applyPct($scope, url) {
        return fetchBasis(url).then(function (b) {
            $scope.find('.bi-pct-note').remove();
            if (!b.nilai || b.nilai <= 0) return;
            $scope.find('[data-pct-val]').each(function () {
                var txt = fmtPct(Number($(this).attr('data-pct-val')) || 0, b.nilai);
                if (!txt) return;
                var note = $('<small class="bi-pct-note text-muted" style="font-size:10.5px;"></small>');
                if (this.tagName === 'TD') note.addClass('d-block').text(txt).appendTo(this);
                else note.addClass('ms-1').text('(' + txt + ' dari nilai ' + (b.jenis || 'SO') + ')').insertAfter(this);
            });
        });
    }

    // Teks kecil di kartu plan: "2 Unit × Rp 700.000" (kosong bila qty 1 tanpa unit)
    function detail(item) {
        var q = Number(item.qty || 1), h = Number(item.hari || 1);
        if (q === 1 && h === 1 && !item.satuan) return '';
        var qs = q.toLocaleString('id-ID', { maximumFractionDigits: 2 });
        var hs = h !== 1 ? h.toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' hari × ' : '';
        return '<small class="d-block text-muted" style="font-size:10.5px;font-weight:400;">' + hs + qs +
            (item.satuan ? ' ' + esc(item.satuan) : '') + ' × Rp ' + Number(item.harga_satuan || item.nominal_budget || 0).toLocaleString('id-ID') + '</small>';
    }

    // ── "Tanpa realisasi" per-qty pada item Budget Plan (WO, FWO, SQ WO, SQ FWO) ──
    // Item tetap 1 baris (mis. qty 4); `qty_tanpa_realisasi` (mis. 2 unit milik sendiri) tidak perlu realisasi,
    // sisanya yang direalisasikan. Diisi di modal Plan (2 kolom: Qty Tanpa Realisasi + Alasan) — hanya PIC Order SQ/SO
    // terkait (atau Aksi Khusus pengganti) yang bisa mengisi; server menentukan lewat `bisa_tandai` di respons /basis,
    // dan tetap memvalidasi saat simpan (BudgetTanpaRealisasi::kolom).
    function qtyFmt(n) { return Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }

    // Total nilai bagian "tanpa realisasi" di satu plan (dikeluarkan dari pembanding realisasi)
    function tanpaTotal(items) {
        return (items || []).reduce(function (s, i) { return s + Number(i.nominal_tanpa_realisasi || 0); }, 0);
    }

    // Seluruh qty item tanpa realisasi → item tidak bisa diberi realisasi
    function tanpaPenuh(item) {
        var qt = Number(item.qty_tanpa_realisasi || 0);
        return qt > 0 && qt >= Number(item.qty || 1);
    }

    // Badge di kartu plan: "Tanpa realisasi 2 dari 4" + alasan
    function tanpaBadge(item) {
        var qt = Number(item.qty_tanpa_realisasi || 0);
        if (!(qt > 0)) return '';
        var tip = (item.alasan_tanpa_realisasi || '') +
            (item.ditandai_oleh_nama ? ' — ' + item.ditandai_oleh_nama : '') +
            (item.ditandai_at ? ' (' + String(item.ditandai_at).substring(0, 16).replace('T', ' ') + ')' : '');
        return '<span class="badge ms-1" title="' + esc(tip) + '" style="background:#fef3c7;color:#92400e;font-size:10px;font-weight:500;border:1px solid #fde68a;">' +
            '<i class="fa-solid fa-ban me-1"></i>Tanpa realisasi ' + qtyFmt(qt) + ' dari ' + qtyFmt(item.qty || 1) + '</span>' +
            (item.alasan_tanpa_realisasi ? '<small class="d-block text-muted" style="font-size:10.5px;">' + esc(item.alasan_tanpa_realisasi) + '</small>' : '');
    }

    // Dua <td> kolom modal (disisipkan setelah kolom Keterangan) + dua <th> headernya
    function tanpaCells(item) {
        var qt = item && Number(item.qty_tanpa_realisasi || 0) > 0 ? Number(item.qty_tanpa_realisasi) : '';
        return '<td style="width:100px;"><input type="number" step="any" min="0" class="form-control form-control-sm bi-qty-tanpa" ' +
            'value="' + qt + '" placeholder="0" data-no-disable title="Jumlah unit yang tidak perlu realisasi (mis. milik sendiri)"></td>' +
            '<td style="min-width:160px;"><input type="text" maxlength="255" class="form-control form-control-sm bi-alasan-tanpa" ' +
            'value="' + esc(item ? item.alasan_tanpa_realisasi || '' : '') + '" placeholder="Alasan (wajib bila diisi)" data-no-disable></td>';
    }
    var tanpaTh = '<th style="width:100px;">Qty Tanpa Realisasi</th><th style="min-width:160px;">Alasan Tanpa Realisasi</th>';

    // Nilai kolom dari satu baris modal → dikirim ke server bersama item
    function tanpaCollect($tr) {
        var q = parseFloat(String($tr.find('.bi-qty-tanpa').val() || '').replace(',', '.'));
        return {
            qty_tanpa_realisasi: isFinite(q) && q > 0 ? q : 0,
            alasan_tanpa_realisasi: ($tr.find('.bi-alasan-tanpa').val() || '').trim(),
        };
    }

    // Kolom hanya bisa diisi PIC Order; Alasan aktif hanya bila Qty Tanpa Realisasi > 0
    function applyTanpaState(cfg) {
        var bisa = !!basisOf(cfg).bisa_tandai;
        $(cfg.body).children(cfg.rowSel).each(function () {
            var $q = $(this).find('.bi-qty-tanpa'), $a = $(this).find('.bi-alasan-tanpa');
            var ada = parseFloat(String($q.val() || '').replace(',', '.')) > 0;
            $q.prop('disabled', !bisa).attr('title', bisa ? 'Jumlah unit yang tidak perlu realisasi (mis. milik sendiri)' : 'Hanya PIC Order yang dapat mengisi');
            $a.prop('disabled', !bisa || !ada);
        });
    }
    return { tanpaTotal: tanpaTotal, tanpaPenuh: tanpaPenuh, tanpaBadge: tanpaBadge, tanpaCells: tanpaCells, tanpaTh: tanpaTh, tanpaCollect: tanpaCollect, daysFromDates: daysFromDates, daysFromNumbers: daysFromNumbers, detail: detail, rowTotal: nominalOf, applyPct: applyPct, fmtPct: fmtPct, fetchBasis: fetchBasis, loadBasis: loadBasis, load: load, fill: fill, initSelects: initSelects, refresh: refresh, addRow: addRow };
})();
