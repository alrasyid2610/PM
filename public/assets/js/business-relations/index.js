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

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus Business Relation Site?', function () {
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

const _siteTotalCache = {};

function _renderSiteBadge($switcher, total) {
    const $label = $switcher.closest('.col-md-12').find('label');
    $label.find('.site-count-badge').remove();
    if (total > 1) {
        $label.append(
            ` <span class="site-count-badge badge rounded-pill ms-1" style="background:#e0f2fe;color:#0284c7;font-size:11px;font-weight:600;">`
            + `<i class="fa-solid fa-location-dot me-1" style="font-size:10px;"></i>${total} Site`
            + `</span>`
            + ` <span class="site-count-badge text-muted ms-1" style="font-size:11px;">`
            + `— gunakan dropdown untuk melihat site lainnya`
            + `</span>`
        );
    }
}

function initSiteSwitcher() {
    const $switcher = $("#site-switcher");
    if (!$switcher.length) return;

    const idBr = $switcher.data("id-br");
    const idSiteCur = $switcher.data("id-site");

    // Tampilkan badge dari cache sebelum AJAX selesai (hindari flicker)
    if (_siteTotalCache[idBr]) {
        _renderSiteBadge($switcher, _siteTotalCache[idBr]);
    }

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

        // Cache dan tampilkan badge
        const total = data.length;
        _siteTotalCache[idBr] = total;
        _renderSiteBadge($switcher, total);
    });
}
