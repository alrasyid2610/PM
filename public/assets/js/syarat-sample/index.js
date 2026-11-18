let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_syarat",
        renderForm: renderForm,
        afterLoad: function () {
            PoinEditor.init($('#detailContent textarea[name="template"]'), { tipe: ['angka'], untuk: 'Testing Point' });
        },
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus syarat sample?', function () {
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