// Editor bantuan kolom "Teks Poin" (Poin Keterangan) — dipakai form Tambah & detail Edit.
// Tujuan: user tidak perlu hafal sintaks isian {kunci:tipe=default}. Empat lapis bantuan:
//   1. Ikon (?) + popover penjelasan tipe isian
//   2. Tombol "Sisipkan isian" (isi nama dalam bahasa biasa → kunci dibuat otomatis)
//   3. Autocomplete saat mengetik "{" (pilih tipe, kunci/nilai awal langsung bisa diketik)
//   4. Pratinjau + daftar isian terdeteksi + pesan salah secara langsung
// Aturan sintaks WAJIB sama dengan App\Support\PoinKeteranganTemplate (server tetap validasi akhir).
const PoinEditor = (function () {
    const SRC = '\\{([a-z0-9_]+):([a-z]+)(?:=([^{}]*))?\\}';
    const re = () => new RegExp(SRC, 'g');

    const TIPE = {
        angka:    { label: 'Angka',         icon: 'fa-hashtag',          nama: 'jumlah',
                    desc: 'Bilangan bulat, mis. jumlah hari. Boleh diberi nilai awal.' },
        rekening: { label: 'Rekening',      icon: 'fa-building-columns', nama: 'rekening',
                    desc: 'Pilih dari master Bank Account saat SQ dibuat.' },
        user:     { label: 'Kontak (User)', icon: 'fa-user',             nama: 'kontak',
                    desc: 'Pilih user — nama dan no. HP tercetak otomatis.' },
        termin:   { label: 'Termin',        icon: 'fa-list-check',       nama: 'termin',
                    desc: 'Daftar tahap pembayaran (persen + kapan dibayar).' },
    };

    // ── Sintaks (cermin PoinKeteranganTemplate.php) ─────────────────────────
    function parse(text) {
        const out = [], r = re();
        let m;
        while ((m = r.exec(text))) {
            out.push({ key: m[1], tipe: m[2], default: m[3] !== undefined && m[3] !== '' ? m[3] : null });
        }
        return out;
    }

    function validate(text) {
        if (/[{}]/.test(text.replace(re(), ''))) {
            return 'Sintaks isian salah. Gunakan {kunci:tipe} atau {kunci:tipe=default}, mis. {hari:angka=12}.';
        }
        const keys = [];
        for (const p of parse(text)) {
            if (!TIPE[p.tipe]) return `Tipe isian '${p.tipe}' tidak dikenal. Tipe yang tersedia: ${Object.keys(TIPE).join(', ')}.`;
            if (keys.includes(p.key)) return `Kunci isian '${p.key}' dipakai lebih dari sekali.`;
            if (p.tipe === 'angka' && p.default !== null && !/^\d+$/.test(p.default)) return `Nilai default '${p.key}' harus berupa angka bulat.`;
            if (p.tipe !== 'angka' && p.default !== null) return `Isian '${p.key}' bertipe ${p.tipe} tidak boleh punya nilai default.`;
            keys.push(p.key);
        }
        return null;
    }

    function previewHtml(text) {
        let out = '', last = 0, m;
        const r = re();
        while ((m = r.exec(text))) {
            out += escHtml(text.slice(last, m.index));
            const def = m[3] !== undefined && m[3] !== '' ? m[3] : null;
            out += def !== null
                ? `<span class="poin-pv poin-pv-def">${escHtml(def)}</span>`
                : `<span class="poin-pv poin-pv-empty">[${escHtml(m[1].replace(/_/g, ' '))}]</span>`;
            last = r.lastIndex;
        }
        return out + escHtml(text.slice(last));
    }

    // "Jumlah Hari" → jumlah_hari
    function slug(s) {
        return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '')
            .toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 40);
    }

    function uniqueKey(base, text) {
        if (!base) return '';
        const used = parse(text).map(p => p.key);
        if (!used.includes(base)) return base;
        let n = 2;
        while (used.includes(`${base}_${n}`)) n++;
        return `${base}_${n}`;
    }

    // ── Util textarea ───────────────────────────────────────────────────────
    function insertAt(ta, s, e, text, selStart, selEnd) {
        ta.value = ta.value.slice(0, s) + text + ta.value.slice(e);
        const a = s + (selStart === undefined ? text.length : selStart);
        const b = s + (selEnd === undefined ? text.length : selEnd);
        ta.focus();
        ta.setSelectionRange(a, b);
        $(ta).trigger('input');
    }

    // Koordinat kursor di halaman (teknik "mirror div") untuk posisi dropdown autocomplete
    function caretCoords(ta, pos) {
        const cs = getComputedStyle(ta);
        const div = document.createElement('div');
        ['fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'letterSpacing', 'wordSpacing', 'textIndent',
            'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
            'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth',
            'boxSizing', 'lineHeight', 'tabSize'].forEach(p => { div.style[p] = cs[p]; });
        div.style.position = 'absolute';
        div.style.visibility = 'hidden';
        div.style.whiteSpace = 'pre-wrap';
        div.style.wordWrap = 'break-word';
        div.style.width = ta.offsetWidth + 'px';
        div.textContent = ta.value.substring(0, pos);
        const span = document.createElement('span');
        span.textContent = ta.value.substring(pos) || '.';
        div.appendChild(span);
        document.body.appendChild(div);
        const rect = ta.getBoundingClientRect();
        const lh = parseFloat(cs.lineHeight) || parseFloat(cs.fontSize) * 1.4;
        const top = rect.top + window.scrollY + span.offsetTop - ta.scrollTop + lh;
        const left = rect.left + window.scrollX + span.offsetLeft - ta.scrollLeft;
        document.body.removeChild(div);
        return { top, left };
    }

    // Token "{...": dari "{" terakhir sebelum kursor sampai kursor, selama belum ditutup "}"
    function currentToken(ta) {
        const pos = ta.selectionStart;
        if (ta.selectionEnd !== pos) return null;
        const before = ta.value.slice(0, pos);
        const open = before.lastIndexOf('{');
        if (open === -1) return null;
        const partial = before.slice(open + 1);
        if (/[}\n]/.test(partial) || partial.length > 40) return null;
        return { start: open, end: pos, partial };
    }

    function injectStyle() {
        if (document.getElementById('poin-editor-style')) return;
        const css = `
        .poin-help-pop { position: absolute; z-index: 2100; width: 400px; max-width: calc(100vw - 24px); background: #fff;
            border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 8px 24px rgba(15,23,42,.18); font-size: 12px; }
        .poin-help-title { padding: 8px 12px; font-weight: 600; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; border-radius: 8px 8px 0 0; }
        .poin-help-body { padding: 10px 12px; }
        .poin-help-body ul { padding-left: 16px; margin-bottom: 6px; }
        .poin-help-body code, .poin-live code { font-size: 11px; }
        .poin-pv { border-radius: 4px; padding: 0 3px; font-weight: 600; }
        .poin-pv-def { background: #dbeafe; color: #1d4ed8; }
        .poin-pv-empty { background: #fef3c7; color: #b45309; }
        .poin-ac { position: absolute; z-index: 2000; min-width: 300px; max-width: 360px; background: #fff;
            border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 8px 24px rgba(15,23,42,.15); padding: 4px; }
        .poin-ac-head { font-size: 10.5px; color: #64748b; padding: 4px 8px 2px; }
        .poin-ac-item { display: flex; gap: 8px; align-items: flex-start; padding: 6px 8px; border-radius: 6px; cursor: pointer; }
        .poin-ac-item.active, .poin-ac-item:hover { background: #eff6ff; }
        .poin-ac-item .ic { width: 18px; text-align: center; color: #2563eb; padding-top: 2px; }
        .poin-ac-item .t { font-size: 12.5px; font-weight: 600; color: #1e293b; }
        .poin-ac-item .d { font-size: 11px; color: #64748b; }`;
        $('<style id="poin-editor-style"></style>').text(css).appendTo('head');
    }

    function helpHtml() {
        return `
        <p class="mb-1">Bagian kalimat yang bisa diubah per SQ ditulis sebagai <b>isian</b>:
        <code>{kunci:tipe}</code> atau <code>{kunci:tipe=nilai awal}</code>.</p>
        <ul>
            <li><b>kunci</b> — nama isian, huruf kecil/angka/_ (mis. <code>hari</code>). Tidak boleh sama dalam 1 teks.</li>
            <li><b>tipe</b> — jenis isian:
                <ul class="mt-1">
                    ${Object.keys(TIPE).map(k => `<li><code>${k}</code> — ${TIPE[k].desc}</li>`).join('')}
                </ul>
            </li>
            <li><b>nilai awal</b> — hanya untuk <code>angka</code>, opsional.</li>
        </ul>
        <div>Contoh: <code>Penerbitan laporan adalah {hari:angka=12} hari kerja</code></div>
        <div class="mt-2 text-muted">Cara paling mudah: klik <b>Sisipkan isian</b>, atau ketik <code>{</code> untuk memilih tipe.</div>`;
    }

    // ── Init per textarea ───────────────────────────────────────────────────
    function init($ta) {
        $ta = $($ta).first();
        if (!$ta.length || $ta.data('poinEditor')) return;
        $ta.data('poinEditor', true);
        injectStyle();

        const ta = $ta.get(0);
        const $col = $ta.closest('[class*="col-"]');

        // 1) Ikon bantuan (?)
        const $help = $('<i class="fa-regular fa-circle-question ms-1" tabindex="0" title="" style="color:#2563eb;cursor:help;font-size:13px;"></i>');
        $col.find('label').first().append($help);
        // Popover buatan sendiri (Bootstrap Popover butuh Popper, yang tidak dimuat di layout ini)
        let $pop = null;
        const hidePop = () => { if ($pop) { $pop.remove(); $pop = null; } };
        const showPop = () => {
            hidePop();
            $pop = $(`<div class="poin-help-pop"><div class="poin-help-title">Cara menulis isian</div><div class="poin-help-body">${helpHtml()}</div></div>`)
                .appendTo('body');
            const r = $help[0].getBoundingClientRect();
            const w = $pop.outerWidth();
            let left = r.left + window.scrollX - 10;
            if (left + w > window.scrollX + window.innerWidth - 12) left = window.scrollX + window.innerWidth - w - 12;
            $pop.css({ top: r.bottom + window.scrollY + 8, left: Math.max(8, left) });
        };
        $help.on('mouseenter focus', showPop).on('mouseleave blur', hidePop);

        // 2) Toolbar "Sisipkan isian" + panel kecil
        const $bar = $(`
        <div class="poin-toolbar d-flex flex-wrap align-items-center gap-1 mb-1">
            <span class="text-muted me-1" style="font-size:11px;">Sisipkan isian:</span>
            ${Object.keys(TIPE).map(k => `<button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 poin-ins" data-tipe="${k}" style="font-size:11.5px;">
                <i class="fa-solid ${TIPE[k].icon} me-1"></i>${TIPE[k].label}</button>`).join('')}
        </div>
        <div class="poin-panel d-none border rounded p-2 mb-2" style="background:#f8fafc;">
            <div class="fw-semibold mb-1 poin-panel-title" style="font-size:12px;"></div>
            <div class="row g-2 align-items-end">
                <div class="col-md-5 col-12">
                    <label class="form-label mb-0" style="font-size:11px;">Nama isian</label>
                    <input type="text" class="form-control form-control-sm poin-panel-nama" placeholder="mis. Jumlah hari">
                    <div class="text-muted" style="font-size:10.5px;">Kunci: <code class="poin-panel-key"></code></div>
                </div>
                <div class="col-md-3 col-6 poin-panel-def-wrap">
                    <label class="form-label mb-0" style="font-size:11px;">Nilai awal (opsional)</label>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm poin-panel-def" placeholder="mis. 12">
                </div>
                <div class="col-auto d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-primary poin-panel-ok">Sisipkan</button>
                    <button type="button" class="btn btn-sm btn-light poin-panel-cancel">Batal</button>
                </div>
            </div>
            <div class="text-danger mt-1 d-none poin-panel-err" style="font-size:11px;"></div>
        </div>`);
        $ta.before($bar);
        const $panel = $bar.filter('.poin-panel');
        const $toolbar = $bar.filter('.poin-toolbar');

        // 4) Pratinjau + isian terdeteksi + pesan salah
        const $live = $(`
        <div class="poin-live mt-2">
            <div class="alert alert-danger py-1 px-2 mb-2 d-none poin-live-err" style="font-size:12px;"></div>
            <div class="mb-2"><span class="text-muted me-1" style="font-size:11px;">Isian terdeteksi:</span><span class="poin-live-chips"></span></div>
            <div><strong style="font-size:12px;">Pratinjau</strong>
                <div class="border rounded p-2 poin-live-prev" style="font-size:12.5px;background:#f8fafc;white-space:pre-line;"></div>
            </div>
        </div>`);
        $ta.after($live);

        function refresh() {
            const text = ta.value;
            const err = validate(text);
            $live.find('.poin-live-err').toggleClass('d-none', !err).text(err || '');
            const items = parse(text);
            $live.find('.poin-live-chips').html(items.length
                ? items.map(p => {
                    const ok = !!TIPE[p.tipe];
                    return `<span class="badge me-1" style="background:${ok ? '#eff6ff' : '#fee2e2'};color:${ok ? '#1d4ed8' : '#b91c1c'};border:1px solid ${ok ? '#bfdbfe' : '#fecaca'};font-weight:500;">
                        <code style="background:none;color:inherit;">${escHtml(p.key)}</code> · ${escHtml(ok ? TIPE[p.tipe].label : p.tipe)}${p.default !== null ? ` · awal ${escHtml(p.default)}` : ''}</span>`;
                }).join('')
                : '<span class="text-muted" style="font-size:11px;">Tidak ada isian — teks poin tetap.</span>');
            $live.find('.poin-live-prev').html(text.trim() ? previewHtml(text) : '<span class="text-muted">Pratinjau muncul di sini.</span>');
        }

        function syncDisabled() {
            const dis = $ta.prop('disabled');
            $toolbar.find('button').prop('disabled', dis);
            if (dis) $panel.addClass('d-none');
        }

        // Kursor terakhir di textarea (tetap diingat saat fokus pindah ke panel)
        let caret = { s: 0, e: 0 };
        const saveCaret = () => { caret = { s: ta.selectionStart, e: ta.selectionEnd }; };
        $ta.on('blur keyup click input', saveCaret);

        // ── Panel sisipkan ──
        let pending = null;
        function updateKeyHint() {
            $panel.find('.poin-panel-key').text(uniqueKey(slug($panel.find('.poin-panel-nama').val()), ta.value) || '—');
        }
        function openPanel(tipe) {
            pending = tipe;
            $panel.find('.poin-panel-title').html(`<i class="fa-solid ${TIPE[tipe].icon} me-1"></i>Sisipkan isian ${TIPE[tipe].label}`);
            $panel.find('.poin-panel-nama').val(TIPE[tipe].nama);
            $panel.find('.poin-panel-def').val('');
            $panel.find('.poin-panel-def-wrap').toggle(tipe === 'angka');
            $panel.find('.poin-panel-err').addClass('d-none');
            updateKeyHint();
            $panel.removeClass('d-none');
            $panel.find('.poin-panel-nama').trigger('focus').trigger('select');
        }
        function showErr(msg) { $panel.find('.poin-panel-err').removeClass('d-none').text(msg); }
        function doInsert() {
            const key = uniqueKey(slug($panel.find('.poin-panel-nama').val()), ta.value);
            if (!key) return showErr('Isi nama isian dulu.');
            let token;
            if (pending === 'angka') {
                const d = $panel.find('.poin-panel-def').val().trim();
                if (d !== '' && !/^\d+$/.test(d)) return showErr('Nilai awal harus angka bulat.');
                token = d !== '' ? `{${key}:angka=${d}}` : `{${key}:angka}`;
            } else {
                token = `{${key}:${pending}}`;
            }
            insertAt(ta, caret.s, caret.e, token);
            $panel.addClass('d-none');
        }
        $toolbar.on('click', '.poin-ins', function () { openPanel($(this).data('tipe')); });
        $panel.on('input', '.poin-panel-nama', updateKeyHint);
        $panel.on('click', '.poin-panel-ok', doInsert);
        $panel.on('click', '.poin-panel-cancel', function () { $panel.addClass('d-none'); $ta.trigger('focus'); });
        $panel.on('keydown', 'input', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); doInsert(); }
            if (e.key === 'Escape') { e.preventDefault(); $panel.addClass('d-none'); $ta.trigger('focus'); }
        });

        // ── Autocomplete saat mengetik "{" ──
        let $ac = null, acItems = [], acIdx = 0;
        function closeAc() { if ($ac) { $ac.remove(); $ac = null; } acItems = []; }
        function renderAc() {
            const html = `<div class="poin-ac-head">Pilih tipe isian</div>` + acItems.map((k, i) => `
                <div class="poin-ac-item${i === acIdx ? ' active' : ''}" data-i="${i}">
                    <span class="ic"><i class="fa-solid ${TIPE[k].icon}"></i></span>
                    <span><div class="t">${TIPE[k].label} <code style="font-size:10.5px;">${k}</code></div><div class="d">${TIPE[k].desc}</div></span>
                </div>`).join('');
            if (!$ac) $ac = $('<div class="poin-ac"></div>').appendTo('body');
            $ac.html(html);
        }
        function updateAc() {
            const tk = currentToken(ta);
            if (!tk || $ta.prop('disabled')) return closeAc();
            const colon = tk.partial.indexOf(':');
            let list = Object.keys(TIPE);
            if (colon !== -1) {
                const prefix = tk.partial.slice(colon + 1);
                if (prefix.includes('=')) return closeAc();
                list = list.filter(k => k.startsWith(prefix.toLowerCase()));
            } else if (/[=]/.test(tk.partial)) {
                return closeAc();
            }
            if (!list.length) return closeAc();
            acItems = list;
            acIdx = Math.min(acIdx, list.length - 1);
            renderAc();
            const pos = caretCoords(ta, tk.end);
            $ac.css({ top: pos.top + 4, left: pos.left });
        }
        function chooseAc(tipe) {
            const tk = currentToken(ta);
            closeAc();
            if (!tk) return;
            const colon = tk.partial.indexOf(':');
            const key = slug(colon === -1 ? tk.partial : tk.partial.slice(0, colon));
            let end = tk.end;
            if (ta.value[end] === '}') end++; // "}" yang sudah terlanjur diketik user
            const k = key || 'kunci';
            const text = `{${k}:${tipe}${tipe === 'angka' ? '=' : ''}}`;
            let a, b;
            if (!key) { a = 1; b = 1 + k.length; }            // pilih kata "kunci" → langsung diganti
            else if (tipe === 'angka') { a = b = text.length - 1; } // kursor setelah "=" → ketik nilai awal
            else { a = b = text.length; }
            insertAt(ta, tk.start, end, text, a, b);
        }
        $ta.on('input click keyup', function (e) {
            if (['ArrowDown', 'ArrowUp', 'Enter', 'Tab', 'Escape'].includes(e.key)) return;
            updateAc();
        });
        $ta.on('keydown', function (e) {
            if (!$ac) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); acIdx = (acIdx + 1) % acItems.length; renderAc(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); acIdx = (acIdx - 1 + acItems.length) % acItems.length; renderAc(); }
            else if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); chooseAc(acItems[acIdx]); }
            else if (e.key === 'Escape') { e.preventDefault(); closeAc(); }
        });
        $ta.on('blur', function () { setTimeout(closeAc, 150); });
        // Namespace + off: detail dimuat ulang tiap pilih data → hanya init terbaru yang aktif
        $(document).off('mousedown.poinac').on('mousedown.poinac', '.poin-ac-item', function (e) {
            e.preventDefault(); // jangan lepas fokus textarea
            if ($ac && $ac.is($(this).closest('.poin-ac'))) chooseAc(acItems[$(this).data('i')]);
        });

        // Pratinjau selalu mengikuti teks (juga saat edit dibatalkan → nilai dikembalikan form)
        $ta.on('input change', refresh);
        $ta.closest('form').on('click', function () { setTimeout(function () { refresh(); syncDisabled(); }, 0); });

        refresh();
        syncDisabled();
    }

    return { init, parse, validate };
})();
