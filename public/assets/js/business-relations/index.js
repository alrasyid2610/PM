let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_site",
        renderForm: renderForm,
        initSelect: function () {
            WilayahEngine.init("#detailContent");
            $("#detail_nama_br").select2({
                width: "100%",
                dropdownParent: $("#detailContent"),
                allowClear: true,
            });
            initSiteSwitcher();
        },
        historyConfig: {
            masterLabel: "Business Relation",
            linesLabel: "Sites",
            linesDisplayFields: ["nama_lokasi", "provinsi", "kota_kabupaten"],
        },
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirm('Hapus Business Relation Site?', function () {
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

function initSiteSwitcher() {
    const $switcher = $("#site-switcher");
    if (!$switcher.length) return;

    const idBr = $switcher.data("id-br");
    const idSiteCur = $switcher.data("id-site");

    // Init select2 dulu dengan opsi yang sudah ada (current site)
    $switcher
        .select2({
            width: "100%",
            dropdownParent: $("#detailContent"),
        })
        .on("select2:select", function (e) {
            const newId = e.params.data.id;
            if (newId != idSiteCur) {
                page.loadDetail(newId);
            }
        });

    // Fetch semua sites di background — tidak ada loading indicator
    $.get(`/business-relations/${idBr}/sites`, function (data) {
        $switcher.empty();
        data.forEach(function (s) {
            const selected = s.id == idSiteCur;
            $switcher.append(new Option(s.text, s.id, selected, selected));
        });
        $switcher.trigger("change.select2");
    });
}
