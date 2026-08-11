let page;

window.datatableColumnRenderers = {
    nilai_kontrak: function (data) {
        if (data === null || data === undefined || data === '') return '—';
        return 'Rp ' + Number(data).toLocaleString('id-ID');
    },
};

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_contract",
        renderForm: renderForm,
        useAttachment: true,
        afterLoad: function () {
            initFpDate('#detailContent');
            initNumericMask(document.getElementById('detailContent'));
            initPicPelangganFiltered();
        },
    });

    // PIC Pelanggan harus ikut Pelanggan yang dipilih — sama seperti create.blade.php
    function initPicPelangganFiltered() {
        const $pic = $('#detail_id_pic_pelanggan');
        if (!$pic.length) return;

        if ($pic.hasClass('select2-hidden-accessible')) $pic.select2('destroy');

        $pic.select2({
            width: '100%',
            placeholder: '-- Pilih PIC --',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: window.route.select2Contact,
                delay: 250,
                dataType: 'json',
                data: (p) => ({ q: p.term, id_br: $('#detail_id_business_relation').val() }),
                processResults: (d) => ({ results: d }),
            },
            escapeMarkup: (m) => m,
        });

        // Ganti Pelanggan → PIC lama sudah tentu tidak valid lagi untuk Pelanggan baru
        $('#detail_id_business_relation').off('select2:select.picfilter select2:clear.picfilter')
            .on('select2:select.picfilter select2:clear.picfilter', function () {
                $pic.val(null).trigger('change');
            });
    }

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus Contract?', function () {
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
