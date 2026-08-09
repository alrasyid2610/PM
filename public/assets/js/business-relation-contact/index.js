let page;

function reloadContactSiteOptions(idBr, keepValue) {
    const $siteSelect = $("#detail_id_site");
    if (!idBr) {
        $siteSelect.empty().trigger("change.select2");
        return;
    }
    $.get(`/business-relations/${idBr}/sites`, function (data) {
        $siteSelect.empty();
        (data || []).forEach(function (s) {
            const selected = keepValue && String(s.id) === String(keepValue);
            $siteSelect.append(new Option(s.text, s.id, selected, selected));
        });
        $siteSelect.trigger("change.select2");
    });
}

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_contact",
        renderForm: renderForm,
        initSelect: function (res) {
            $("#detail_id_br").select2({
                width: "100%",
                dropdownParent: $("#detailContent"),
            });

            $("#detail_id_site").select2({
                width: "100%",
                dropdownParent: $("#detailContent"),
                placeholder: "— Umum (semua site) —",
                allowClear: true,
            });

            reloadContactSiteOptions(res.id_br, res.id_site);

            // Site mengikuti Business Relation yang dipilih — reset saat BR berubah
            $("#detail_id_br")
                .off("select2:select.contactSite select2:clear.contactSite")
                .on("select2:select.contactSite", function (e) {
                    reloadContactSiteOptions(e.params.data.id, null);
                })
                .on("select2:clear.contactSite", function () {
                    reloadContactSiteOptions(null, null);
                });
        },
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus Contact?', function () {
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
