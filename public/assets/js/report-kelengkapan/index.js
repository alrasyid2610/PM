// Report "Kelengkapan SO & SQ" (read-only). Tabel + pencarian + Export Excel memakai komponen daftar standar
// (core/datatable.js); nomor SO/SQ adalah link ke halaman detailnya, jadi tidak ada klik-baris khusus.
$(document).ready(function () {
    window.datatableColumnRenderers = {
        // Tampil dd/mm/yyyy; urutan (sort) tetap memakai nilai asli yyyy-mm-dd hh:mm:ss.
        tanggal_buat: function (data, type) {
            if (!data) return '';
            if (type !== 'display' && type !== 'filter') return data;
            var m = String(data).match(/^(\d{4})-(\d{2})-(\d{2})/);
            return m ? m[3] + '/' + m[2] + '/' + m[1] : data;
        },
    };

    initDataTable(tableSelector);
});
