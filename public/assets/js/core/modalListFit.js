// Daftar item (checklist) di modal "Tambah Item" ikut tinggi layar: daftar diberi tinggi maksimum = sisa ruang di
// layar (tinggi viewport − bagian modal selain daftar − margin dialog), jadi modal mengisi layar tanpa ruang kosong
// dan tetap muat di layar kecil (minimal `min` px; modal sendiri bisa di-scroll bila layar lebih pendek lagi).
// Dihitung ulang saat modal tampil, layar di-resize, atau isi/visibilitas modal berubah (item dimuat, dropdown Paket muncul).
//
// Pemakaian: ModalListFit.watch('#modalAddSection', '#modalItemsList');   // sekali saja per modal
window.ModalListFit = (function () {
    var MARGIN = 56; // margin atas+bawah .modal-dialog (1.75rem × 2)

    function fit(modalSel, listSel, min) {
        var $modal = $(modalSel), $list = $(listSel);
        if (!$modal.length || !$list.length || !$modal.hasClass('show')) return;
        var $content = $modal.find('.modal-content').first();
        $list.css('max-height', 'none');                       // ukur tinggi penuh daftar dulu
        var other = $content.outerHeight() - $list.outerHeight(); // bagian modal selain daftar
        var avail = window.innerHeight - MARGIN - other;
        $list.css('max-height', Math.max(min, Math.floor(avail)) + 'px');
    }

    function watch(modalSel, listSel, min) {
        min = min || 220;
        var queued = false;
        function run() {
            if (queued) return;
            queued = true;
            requestAnimationFrame(function () { queued = false; fit(modalSel, listSel, min); });
        }
        var $modal = $(modalSel);
        if (!$modal.length) return;
        $modal.on('shown.bs.modal', run);
        $(window).on('resize', run);
        // isi daftar / tampil-sembunyi blok (loading, empty, Paket, daftar) berubah → hitung ulang.
        // Hanya childList + class (bukan style) supaya perubahan tinggi daftar sendiri tidak memicu ulang tanpa henti.
        new MutationObserver(run).observe($modal[0], { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    }

    return { watch: watch, fit: fit };
})();
