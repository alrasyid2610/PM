// SQ Fieldwork — modul mandiri (Fase 3), meniru pola SQ Work Order persis.
// Scope Fase 3 dibatasi ke tab Informasi/BOQ/Budget (kesepakatan user
// 2026-09-27) — lihat Obsidian Modules/Sales Quotation.md.
let currentSqFwoId = null;
let currentSqFwoData = null;

window.datatableHeaderLabels = Object.assign({}, window.datatableHeaderLabels, {
    no_sq_fwo: 'No FWO',
    no_sq_wo: 'No WO',
    no_sq: 'No SQ',
    judul_pekerjaan: 'Judul Pekerjaan',
    hari_ke: 'Hari Ke-',
    durasi_hari: 'Durasi',
});

// PIC Pekerjaan di-scope oleh Site Pekerjaan yang dipilih — pola sama
// initSqWoPicField() di sq-work-order/index.js.
function initSqFwoPicField() {
    const $pic = $('#detail_id_pic_pelanggan_pekerjaan');
    const $site = $('#detail_id_site_pelanggan_pekerjaan');
    if (!$pic.length) return;
    if ($pic.hasClass('select2-hidden-accessible')) $pic.select2('destroy');

    $pic.select2({
        width: '100%',
        dropdownParent: $('#detailContent'),
        placeholder: 'Pilih PIC',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: '/business-relation-contacts/select2',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term || '', with_site: 1, id_site: $site.val() || '' };
            },
            processResults: function (data) { return { results: data }; },
            cache: false,
        },
        language: {
            noResults: function () {
                return '<span>Tidak ditemukan. <a href="/business-relation-contacts/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>';
            },
        },
        escapeMarkup: function (m) { return m; },
    });

    $site.off('change.sqFwoPicClear').on('change.sqFwoPicClear', function () {
        $pic.val(null).trigger('change');
    });
}

function initSqFwoSiteField() {
    const $site = $('#detail_id_site_pelanggan_pekerjaan');
    if (!$site.length) return;
    if ($site.hasClass('select2-hidden-accessible')) $site.select2('destroy');

    $site.select2({
        width: '100%',
        dropdownParent: $('#detailContent'),
        placeholder: 'Pilih Site',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: '/business-relations/sites/select2',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term || '' }; },
            processResults: function (data) { return { results: data }; },
            cache: false,
        },
        escapeMarkup: function (m) { return m; },
    });
}

$(document).ready(function () {
    if ($('#sq-fieldworks-table').length === 0) return;

    new CrudPageController({
        primaryKey: 'id_sq_fwo',
        renderForm: renderForm,
        detailTitle: function (res) { return res.no_sq_fwo || res.judul_pekerjaan || 'SQ Fieldwork'; },
        initSelect: function () {
            initNumericMask(document.getElementById('detailContent'));
        },
        afterLoad: function (res) {
            currentSqFwoId = res.id_sq_fwo;
            currentSqFwoData = res;
            initSqFwoSiteField();
            initSqFwoPicField();
            initFpDate('#detailContent');
        },
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus SQ Fieldwork ini beserta seluruh BOQ & Budget-nya?', function () {
            $.ajax({
                url: window.route.update + id,
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' },
                success: function (res) {
                    Notify.success(res.message || 'Data berhasil dihapus');
                    setTimeout(function () { window.location.href = window.location.pathname; }, 1000);
                },
                error: function (xhr) {
                    Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus data');
                },
            });
        });
    });
});
