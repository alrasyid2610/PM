let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_output_judul",
        renderForm: renderForm,
        afterLoad: function () {
            syncOutputMasterLhu();
            $(document).off('change.omTipe', '#detailForm select[name="tipe"]')
                .on('change.omTipe', '#detailForm select[name="tipe"]', syncOutputMasterLhu);
        },
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus judul output?', function () {
            $.ajax({
                url: window.route.update + id,
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' },
                success: function (res) {
                    Notify.success(res.message || 'Data berhasil dihapus');
                    setTimeout(function () { window.location.href = window.location.pathname; }, 1000);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                },
            });
        });
    });

    // ── Gabungkan ke judul lain (merapikan varian ejaan) ─────────────────────────
    $(document).on('click', '.btn-om-merge', function () {
        const id      = $(this).data('id');
        const judul   = String($(this).data('judul') || '');
        const tipe    = String($(this).data('tipe') || 'pekerjaan');
        const dipakai = Number($(this).data('dipakai') || 0);

        // Tujuan = judul AKTIF bertipe sama, selain dirinya sendiri
        $.get('/output-judul/select2', { tipe: tipe }).done(function (rows) {
            const pilihan = new Map();
            rows.filter(function (r) { return Number(r.id) !== Number(id); })
                .forEach(function (r) { pilihan.set(String(r.id), r.text); });

            if (!pilihan.size) {
                Swal.fire('Tidak ada tujuan', 'Belum ada judul aktif lain dengan tipe yang sama.', 'info');
                return;
            }

            Swal.fire({
                title: 'Gabungkan judul',
                html: '<div style="text-align:left;font-size:13px;">' +
                    '<b>' + escHtml(judul) + '</b> dipakai <b>' + dipakai + '</b> output.<br>' +
                    'Semua output itu akan dipindah ke judul tujuan di bawah (teks judulnya ikut berubah, termasuk yang tampil di Termin), ' +
                    'lalu judul ini dinonaktifkan.</div>',
                input: 'select',
                inputOptions: pilihan,
                inputPlaceholder: 'Pilih judul tujuan',
                showCancelButton: true,
                confirmButtonText: 'Gabungkan',
                cancelButtonText: 'Batal',
                inputValidator: function (v) { return v ? null : 'Pilih judul tujuan'; },
            }).then(function (r) {
                if (!r.isConfirmed) return;
                $.ajax({
                    url: window.route.update + id + '/merge',
                    method: 'POST',
                    data: { _token: window.route.csrf, _method: 'PUT', target_id: r.value },
                    success: function (res) {
                        Notify.success(res.message || 'Berhasil digabungkan');
                        setTimeout(function () { window.location.href = window.location.pathname + '?open=' + id; }, 900);
                    },
                    error: function (xhr) {
                        Notify.error(xhr.responseJSON?.message || 'Gagal menggabungkan');
                    },
                });
            });
        });
    });
});
