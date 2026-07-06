let page;

// ── Tab switch ────────────────────────────────────────────────────────────────
$(document).on('shown.bs.tab', '#pointDetailTabs button[data-bs-toggle="tab"]', function (e) {
    const target = $(e.target).data('bs-target');
    $('#pointTabActionsInfo, #pointTabActionsItems').addClass('d-none');
    if (target === '#tabPointInfo')  $('#pointTabActionsInfo').removeClass('d-none');
    if (target === '#tabPointItems') $('#pointTabActionsItems').removeClass('d-none');
});

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
        Notify.confirmDelete('Hapus Testing Point?', function () {
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
