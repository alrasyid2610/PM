// ============================================================
//  contracts/create.js
//  Form submit untuk create.blade.php
// ============================================================

$(document).ready(function () {

    // ─── Select2: Business Relation ──────────────────────────
    $('#id_business_relation').select2({
        width: '100%',
        placeholder: '-- Pilih Pelanggan --',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: window.route.select2BR,
            delay: 300,
            dataType: 'json',
            data: (params) => ({ q: params.term }),
            processResults: (data) => ({ results: data }),
        },
    });

    // ─── Select2: PIC Pelanggan ──────────────────────────────
    $.ajax({
        url: window.route.select2Contact,
        method: 'GET',
        data: { q: '' },
        success: function (response) {
            $('#id_pic_pelanggan').append(new Option('', ''));
            $.each(response, function (index, item) {
                $('#id_pic_pelanggan').append(new Option(item.text, item.id));
            });
            $('#id_pic_pelanggan').select2({
                width: '100%',
                placeholder: '-- Pilih PIC Pelanggan --',
                allowClear: true,
            });
        },
        error: function () {
            Notify.error('Gagal memuat data PIC pelanggan');
        },
    });

    // ─── Select2: PIC Pramatek ───────────────────────────────
    $.ajax({
        url: window.route.select2User,
        method: 'GET',
        data: { q: '' },
        success: function (response) {
            $('#id_pic_pramatek').append(new Option('', ''));
            $.each(response, function (index, item) {
                $('#id_pic_pramatek').append(new Option(item.text, item.id));
            });
            $('#id_pic_pramatek').select2({
                width: '100%',
                placeholder: '-- Pilih PIC Pramatek --',
                allowClear: true,
            });
        },
        error: function () {
            Notify.error('Gagal memuat data PIC Pramatek');
        },
    });

    // ─── Form Submit ─────────────────────────────────────────
    $('#contractForm').on('submit', function (e) {
        e.preventDefault();

        Notify.confirm('Simpan Data Contract?', function () {

            const formData = new FormData(document.getElementById('contractForm'));

            $.ajax({
                url:         window.route.store,
                method:      'POST',
                data:        formData,
                processData: false,
                contentType: false,

                success: function (res) {
                    Notify.success('Contract berhasil disimpan');
                    setTimeout(() => {
                        window.location.href = window.route.index;
                    }, 1000);
                },

                error: function (xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        const msg = Object.values(errors)
                            .map(e => e[0])
                            .join('<br>');
                        Notify.error(msg);
                    } else {
                        Notify.error('Gagal menyimpan contract');
                    }
                },
            });
        });
    });

});
