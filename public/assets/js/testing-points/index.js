let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_testing_point",
        renderForm: renderForm,
        initSelect: function () {},
        initDynamicTable: true,
        useAttachment: true,
        historyConfig: {
            masterLabel: "Testing Point",
            linesLabel: "Testing Items",
            linesDisplayFields: ["nomor", "judul_indonesia", "judul_inggris", "nilai"],
        },
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirm('Hapus Testing Point?', function () {
            $.ajax({
                url: window.route.update + id,
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' },
                success: function (res) {
                    Notify.success(res.message || 'Data berhasil dihapus');
                    $('#detailContent').html('');
                    page.selectedRow.id = null;
                    if ($.fn.DataTable.isDataTable('#masterTable')) {
                        $('#masterTable').DataTable().ajax.reload(null, false);
                    }
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                },
            });
        });
    });
});
