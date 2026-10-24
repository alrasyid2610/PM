/**
 * Modal Guard — mencegah isian modal form hilang karena modal tertutup tanpa sengaja.
 *
 * AKTIF OTOMATIS di semua `.modal` (modal baru tidak perlu apa-apa), kecuali:
 *   - atribut `data-no-modal-guard` di elemen modal, atau
 *   - id ada di daftar EXCLUDE_IDS (modal baca-saja / tanpa isian).
 *
 * Perilaku:
 *  - Klik di luar modal & tombol Esc TIDAK menutup modal (backdrop static + keyboard off, di-set saat modal dibuka).
 *  - Tombol tutup user ([data-bs-dismiss="modal"]: X / Batal): bila ada isian yang berubah sejak modal dibuka,
 *    tampil konfirmasi "Tutup tanpa menyimpan?" (Lanjut Isi / Ya, Tutup). Tidak ada perubahan → langsung tutup.
 *  - Penutupan lewat kode (mis. modal.hide() setelah Simpan sukses) TIDAK dicegat, jadi alur simpan normal.
 *  - Modal iframe (form di dalam <iframe src="...?embed=1">): isian di dalam iframe (same-origin) ikut dibaca.
 *
 * Deteksi "berubah" = bandingkan foto nilai semua field (input/select/textarea + jumlah file FilePond)
 * dengan foto awal. Foto awal diambil saat modal tampil, lalu diambil ulang tiap AJAX selesai / iframe selesai
 * dimuat SELAMA user belum menyentuh form (modal Edit mengisi field setelah data dari server datang).
 *
 * Field yang tidak dihitung: hidden, tombol, kolom cari (select2 / type=search / id-name-placeholder berisi
 * "search"/"cari"), dan elemen ber-atribut `data-no-guard`.
 */
(function ($) {
    'use strict';

    var EXCLUDE_IDS = [
        'imagePreviewModal', 'filePreviewModal', 'ugModalDemo', // preview / demo
        'modalCompareSq',                                       // hasil perbandingan SQ (baca-saja)
        'bulkVerifyModal', 'woBulkVerifyModal'                  // verifikasi massal (tanpa isian)
    ];
    var CONTROL = 'input, select, textarea, .select2-container, .filepond--root, .flatpickr-calendar, .select2-dropdown, [contenteditable="true"]';
    var states = new WeakMap(); // modal element → { base: string, touched: bool }

    function guarded(modal) {
        return !!modal && modal.nodeType === 1 && modal.classList.contains('modal') &&
            !modal.hasAttribute('data-no-modal-guard') && EXCLUDE_IDS.indexOf(modal.id) === -1;
    }

    function closestGuarded(node) {
        var m = node && node.closest ? node.closest('.modal') : null;
        return guarded(m) ? m : null;
    }

    function isTracked(el) {
        var t = el.type;
        if (t === 'hidden' || t === 'button' || t === 'submit' || t === 'reset' || t === 'search') return false;
        if (el.classList.contains('select2-search__field') || el.hasAttribute('data-no-guard')) return false;
        if (/search|cari/i.test((el.id || '') + ' ' + (el.name || '') + ' ' + (el.placeholder || ''))) return false;
        return true;
    }

    function fieldParts(root, prefix) {
        var parts = [];
        $(root).find('input, select, textarea').each(function (i, el) {
            if (!isTracked(el)) return;
            var key = prefix + (el.id || el.name || ('#' + i));
            var val = (el.type === 'checkbox' || el.type === 'radio') ? (el.checked ? '1' : '0')
                : (el.type === 'file' ? String(el.files ? el.files.length : 0) : $(el).val());
            parts.push(key + '=' + (Array.isArray(val) ? val.join(',') : (val == null ? '' : val)));
        });
        // FilePond menyimpan file di memori, bukan di <input>: hitung item yang tampil
        parts.push(prefix + 'filepond=' + $(root).find('.filepond--item').length);
        return parts;
    }

    function snapshot(modal) {
        var parts = fieldParts(modal, '');
        // Modal iframe: baca isian di dalam iframe (same-origin); gagal akses = diabaikan
        $(modal).find('iframe').each(function (n, fr) {
            try {
                if (fr.contentDocument && fr.contentDocument.body) parts = parts.concat(fieldParts(fr.contentDocument, 'iframe' + n + ':'));
            } catch (e) { /* lintas origin */ }
        });
        return parts.join('|');
    }

    function rebase(modal) {
        states.set(modal, { base: snapshot(modal), touched: false });
    }

    function isDirty(modal) {
        var st = states.get(modal);
        return !!st && snapshot(modal) !== st.base;
    }

    function touch(modal) {
        var st = states.get(modal);
        if (st) st.touched = true;
    }

    // Klik di luar & Esc tidak menutup: set config Bootstrap saat modal dibuka (atribut di markup tidak perlu).
    // _config adalah properti internal Bootstrap 5; dibungkus try/catch agar tidak merusak bila versi berubah.
    $(document).on('show.bs.modal', '.modal', function (e) {
        if (e.target !== this || !guarded(this)) return;
        try {
            var inst = bootstrap.Modal.getInstance(this);
            if (inst && inst._config) { inst._config.backdrop = 'static'; inst._config.keyboard = false; }
        } catch (err) { /* abaikan */ }
    });

    // Foto awal: saat modal tampil, lalu sekali lagi sesaat kemudian (field yang diisi kode secara asinkron)
    $(document).on('shown.bs.modal', '.modal', function (e) {
        if (e.target !== this || !guarded(this)) return;
        var modal = this;
        rebase(modal);
        setTimeout(function () { var st = states.get(modal); if (st && !st.touched && modal.classList.contains('show')) rebase(modal); }, 700);
        hookIframes(modal);
    });

    // Modal Edit mengisi field setelah AJAX selesai → ambil foto ulang selama user belum menyentuh form
    $(document).ajaxStop(function () {
        $('.modal.show').each(function () {
            if (!guarded(this)) return;
            var st = states.get(this);
            if (st && !st.touched) rebase(this);
        });
    });

    // Modal iframe: foto ulang setelah iframe selesai dimuat (+ jeda untuk select2/ajax di dalam iframe),
    // dan pantau sentuhan user di dalam iframe
    function hookIframes(modal) {
        $(modal).find('iframe').each(function () {
            var fr = this;
            function onLoad() {
                try {
                    var doc = fr.contentDocument;
                    if (!doc || !doc.body || doc.location.href === 'about:blank') return;
                    var mark = function (ev) { if (ev.isTrusted && ev.target && ev.target.closest && ev.target.closest(CONTROL)) touch(modal); };
                    doc.addEventListener('keydown', function (ev) { if (ev.key !== 'Escape' && ev.key !== 'Tab') mark(ev); }, true);
                    doc.addEventListener('mousedown', mark, true);
                    doc.addEventListener('input', mark, true);
                } catch (e) { return; }
                rebase(modal);
                setTimeout(function () { var st = states.get(modal); if (st && !st.touched && modal.classList.contains('show')) rebase(modal); }, 1200);
            }
            if (!fr._guardHooked) { fr._guardHooked = true; fr.addEventListener('load', onLoad); }
            onLoad(); // iframe yang sudah selesai dimuat sebelum modal tampil
        });
    }

    // User mulai berinteraksi dengan field (event asli, bukan dari kode) → bekukan foto awal
    function markTouched(e) {
        if (!e.isTrusted || !e.target || !e.target.closest) return;
        var modal = closestGuarded(e.target);
        // select2 dropdown & kalender flatpickr tidak berada di dalam modal → pakai modal yang sedang tampil
        if (!modal && e.target.closest('.select2-dropdown, .flatpickr-calendar')) {
            modal = closestGuarded(document.querySelector('.modal.show'));
        }
        if (!modal || !e.target.closest(CONTROL)) return;
        touch(modal);
    }
    document.addEventListener('keydown', function (e) { if (e.key !== 'Escape' && e.key !== 'Tab') markTouched(e); }, true);
    document.addEventListener('mousedown', markTouched, true);
    document.addEventListener('input', markTouched, true);

    // Cegat tombol tutup (X / Batal) saat ada perubahan. Dipasang di WINDOW fase capture: handler tutup
    // Bootstrap terdaftar di document (juga fase capture) dan lebih dulu terdaftar, jadi pencegat di
    // document terlambat — window dilalui event lebih awal.
    window.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('[data-bs-dismiss="modal"]');
        if (!btn) return;
        var modal = closestGuarded(btn);
        if (!modal || !isDirty(modal)) return;

        e.preventDefault();
        e.stopPropagation();

        Swal.fire({
            icon: 'warning',
            title: 'Tutup tanpa menyimpan?',
            html: 'Isian yang belum disimpan akan <b>hilang</b>.',
            showCancelButton: true,
            confirmButtonText: 'Ya, Tutup',
            cancelButtonText: 'Lanjut Isi',
            confirmButtonColor: '#dc2626',
            reverseButtons: true,
            focusCancel: true,
        }).then(function (res) {
            if (!res.isConfirmed) return;
            var inst = bootstrap.Modal.getInstance(modal);
            if (inst) inst.hide();
        });
    }, true);

    // Util untuk modul lain: modal ini sedang punya perubahan yang belum disimpan?
    window.ModalGuard = { isDirty: isDirty, rebase: rebase };
})(jQuery);
