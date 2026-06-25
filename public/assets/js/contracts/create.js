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
        minimumInputLength: 0,
        ajax: {
            url: window.route.select2BR,
            delay: 250,
            dataType: 'json',
            data: (params) => ({ q: params.term }),
            processResults: (data) => ({ results: data }),
            cache: true,
        },
        language: { noResults: () => `<span>Tidak ditemukan. <a href="/business-relations/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>` },
        escapeMarkup: (m) => m,
    });

    // ─── Select2: PIC Pelanggan ──────────────────────────────
    $('#id_pic_pelanggan').select2({
        width: '100%',
        placeholder: '-- Pilih PIC Pelanggan --',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: window.route.select2Contact,
            delay: 300,
            dataType: 'json',
            data: (params) => ({ q: params.term }),
            processResults: (data) => ({ results: data }),
        },
        language: { noResults: () => `<span>Tidak ditemukan. <a href="/business-relation-contacts/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>` },
        escapeMarkup: (m) => m,
    });

    // ─── Select2: PIC Pramatek ───────────────────────────────
    $('#id_pic_pramatek').select2({
        width: '100%',
        placeholder: '-- Pilih PIC Pramatek --',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: window.route.select2User,
            delay: 300,
            dataType: 'json',
            data: (params) => ({ q: params.term }),
            processResults: (data) => ({ results: data }),
        },
        language: { noResults: () => `<span>Tidak ditemukan. <a href="/users/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>` },
        escapeMarkup: (m) => m,
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
