// Dropdown "Paket" di modal Tambah/Ubah Item BOQ (SQ WO, WO Kelola BOQ, WO daftar BOQ).
// Paket = subset Testing Item milik 1 Testing Point (mis. "parameter 20" dari 50 item), dibuat di tab "Paket"
// detail Testing Point. Memilih paket hanya MENCENTANG item-item paket itu (jalan pintas) — BOQ tidak menyimpan
// nama paketnya, dan user tetap bebas menyesuaikan centang sesudahnya. Data: GET /testing-items/paket-by-point/{id}
// (hanya item aktif; paket tanpa item aktif tidak ditampilkan).
//
// Pemakaian:
//   PaketPicker.load({ pointId, $wrap, $select, $modal, apply: function (idSet /* Set<string id_testing_item> */) { ... } });
//   PaketPicker.reset($wrap, $select);
window.PaketPicker = (function () {
    function reset($wrap, $select) {
        if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
        $select.empty().off('select2:select.paket');
        $wrap.addClass('d-none');
    }

    function load(opts) {
        var $wrap = opts.$wrap, $select = opts.$select;
        reset($wrap, $select);
        if (!opts.pointId) return;

        $.get('/testing-items/paket-by-point/' + opts.pointId).done(function (res) {
            var paket = (res && res.data) || [];
            // titik uji sudah berganti sebelum data selesai dimuat → abaikan
            if (opts.stillCurrent && !opts.stillCurrent()) return;
            if (!paket.length) return;

            $select.empty().append('<option></option>');
            paket.forEach(function (p) {
                $select.append(new Option(p.nama + ' (' + p.jumlah_item + ' item)', p.id_paket, false, false));
            });
            $select.select2({
                width: '100%',
                placeholder: 'Pilih paket (opsional)',
                allowClear: true,
                minimumResultsForSearch: Infinity,
                dropdownParent: opts.$modal,
            });
            $select.on('select2:select.paket', function (e) {
                var p = paket.find(function (x) { return String(x.id_paket) === String(e.params.data.id); });
                if (p) opts.apply(new Set(p.item_ids.map(String)));
            });
            $wrap.removeClass('d-none');
        });
    }

    return { load: load, reset: reset };
})();
