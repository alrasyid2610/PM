let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_br",
        renderForm: renderForm,
        initSelect: function () {
            // Sembunyikan semua action-bar per-tab dulu setiap kali tab manapun
            // (top-level atau nested di dalam Site) baru ditampilkan — modul
            // masing-masing (Contact/Product/Site/Sampling/MP) menambahkan lagi
            // action-bar miliknya sendiri lewat handler yang didaftarkan setelah ini.
            $("#detailContent").on("shown.bs.tab", '[data-bs-toggle="tab"]', hideAllBrTabActions);

            $("#detail_nama_br").select2({
                width: "100%",
                dropdownParent: $("#detailContent"),
                allowClear: true,
            });
            initSiteTabEvents();
            initSamplingTabEvents();
            initMpTabEvents();
            initContactTabEvents();
            initProductTabEvents();
        },
        // initDynamicSelect("#detailContent") (generik, ajax-nya cuma kirim `q`)
        // jalan SETELAH initSelect tapi SEBELUM afterLoad — jadi override select2
        // Sub Kategori Bisnis harus di afterLoad, bukan initSelect, supaya tidak
        // ketiban-timpa lagi oleh init generik.
        afterLoad: function () {
            initSubKategoriBisnisFiltered();

            // Ganti Kategori Bisnis → Sub Kategori Bisnis lama sudah tentu tidak valid lagi
            $('#detail_id_kategori_bisnis').off('select2:select.subkat select2:clear.subkat')
                .on('select2:select.subkat select2:clear.subkat', function () {
                    $('#detail_id_sub_kategori_bisnis').val(null).trigger('change');
                });
        },
        historyConfig: {
            masterLabel: "Business Relation",
            linesLabel: "Sites",
            linesDisplayFields: ["nama_lokasi", "provinsi", "kota_kabupaten"],
        },
    });

    // Sub Kategori Bisnis harus ikut Kategori Bisnis yang dipilih — override
    // select2 generik (formGroup.select ajax mode cuma kirim `q`, tidak tahu
    // soal filter id_kategori_bisnis).
    function initSubKategoriBisnisFiltered() {
        const $sub = $('#detail_id_sub_kategori_bisnis');
        if (!$sub.length) return;

        if ($sub.hasClass('select2-hidden-accessible')) $sub.select2('destroy');

        $sub.select2({
            width: '100%',
            placeholder: 'Pilih Sub Kategori Bisnis',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: '/sub-kategori-bisnis/select2',
                delay: 200,
                dataType: 'json',
                data: (p) => ({ q: p.term ?? '', id_kategori_bisnis: $('#detail_id_kategori_bisnis').val() }),
                processResults: (d) => ({ results: d }),
            },
            language: {
                noResults: () => `<span>Tidak ditemukan. <a href="/sub-kategori-bisnis/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`,
            },
            escapeMarkup: (m) => m,
        });
    }
});
