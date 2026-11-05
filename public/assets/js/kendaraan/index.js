let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_kendaraan",
        renderForm: renderForm,
        useAttachment: true,
        afterLoad: function () {
            syncKendaraanVendor();
            $(document).off('change.kendaraanVendor', '#detailForm select[name="kepemilikan"]')
                .on('change.kendaraanVendor', '#detailForm select[name="kepemilikan"]', syncKendaraanVendor);
        },
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus Kendaraan?', function () {
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
});
