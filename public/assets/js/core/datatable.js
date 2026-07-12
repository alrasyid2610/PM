// Param state — semua filter disimpan di sini, bukan di-embed ke window.route.data
window._dtParams = {};

function _buildDtUrl() {
    var base = (window.route.data || '').split('?')[0];
    var params = Object.entries(window._dtParams)
        .filter(function (e) { return e[1] !== '' && e[1] !== null && e[1] !== undefined; })
        .map(function (e) { return encodeURIComponent(e[0]) + '=' + encodeURIComponent(e[1]); })
        .join('&');
    return params ? base + '?' + params : base;
}

// ── Toggle search bar ──────────────────────────────────────────────────────────
$(document).on('click', '#btnToggleDtSearch', function () {
    var $bar = $('#dtSearchBar');
    var $btn = $(this);
    var isOpen = $bar.is(':visible');

    if (isOpen) {
        $bar.slideUp(150);
        $btn.removeClass('btn-primary').addClass('btn-outline-secondary');
    } else {
        $bar.slideDown(150);
        $btn.removeClass('btn-outline-secondary').addClass('btn-primary');
        $('#dtSearchBy').trigger('focus');
    }
});

// ── Toggle selesai ─────────────────────────────────────────────────────────────
$(document).on('change', '#toggleShowSelesai', function () {
    if (this.checked) {
        window._dtParams.show_selesai = 1;
    } else {
        delete window._dtParams.show_selesai;
    }
    initDataTable(window.tableSelector, window._dtOnReady);
});

// ── Search bar ─────────────────────────────────────────────────────────────────
$(document).on('change', '#dtSearchBy', function () {
    var $opt     = $(this).find('option:selected');
    var hasVal   = !!$(this).val();
    var type     = $opt.data('type') || 'text';
    var options  = $opt.data('options') || null;

    // Hapus elemen input/select lama, ganti sesuai type
    $('#dtSearchQ, #dtSearchQSelect').remove();

    if (!hasVal) {
        // Kembalikan ke text input kosong
        $('#btnDtSearch').before('<input type="text" id="dtSearchQ" class="form-control form-control-sm" placeholder="Kata kunci..." style="max-width:240px;" disabled>');
        $('#btnDtSearch').prop('disabled', true);
        return;
    }

    if (type === 'select' && options) {
        var selectHtml = '<select id="dtSearchQSelect" class="form-select form-select-sm" style="width:auto;min-width:160px;">';
        selectHtml += '<option value="">-- Pilih --</option>';
        $.each(options, function (i, opt) {
            selectHtml += '<option value="' + opt.value + '">' + opt.label + '</option>';
        });
        selectHtml += '</select>';
        $('#btnDtSearch').before(selectHtml);
        $('#btnDtSearch').prop('disabled', false);
    } else {
        $('#btnDtSearch').before('<input type="text" id="dtSearchQ" class="form-control form-control-sm" placeholder="Kata kunci..." style="max-width:240px;">');
        $('#btnDtSearch').prop('disabled', false);
    }
});

$(document).on('click', '#btnDtSearch', function () {
    _applyDtSearch();
});

$(document).on('keydown', '#dtSearchQ', function (e) {
    if (e.key === 'Enter') _applyDtSearch();
});

$(document).on('click', '#btnDtClearSearch', function () {
    delete window._dtParams.search_by;
    delete window._dtParams.search_q;
    $('#dtSearchBy').val('').trigger('change');
    $('#btnDtSearch').prop('disabled', true);
    $('#dtSearchBadge').hide();
    $('#btnDtClearSearch').hide();
    // Kembalikan icon ke state normal jika panel tertutup
    if (!$('#dtSearchBar').is(':visible')) {
        $('#btnToggleDtSearch').removeClass('btn-primary').addClass('btn-outline-secondary');
    }
    initDataTable(window.tableSelector, window._dtOnReady);
});

function _applyDtSearch() {
    var by = $('#dtSearchBy').val();
    var q  = ($('#dtSearchQSelect').length ? $('#dtSearchQSelect').val() : $('#dtSearchQ').val()).trim();
    if (!by || !q) return;

    window._dtParams.search_by = by;
    window._dtParams.search_q  = q;

    var label = $('#dtSearchBy option:selected').text();
    $('#dtSearchBadge').text(label + ': "' + q + '"').show();
    $('#btnDtClearSearch').show();
    // Icon tetap biru sebagai indikator filter aktif
    $('#btnToggleDtSearch').removeClass('btn-outline-secondary').addClass('btn-primary');

    initDataTable(window.tableSelector, window._dtOnReady);
}

// ── DataTable init ─────────────────────────────────────────────────────────────
function initDataTable(tableSelector, onReady) {
    window.tableSelector = tableSelector;
    window._dtOnReady    = onReady;
    if ($(tableSelector).length === 0) return;

    let autoColumns = $(tableSelector).data("datatable-auto-columns");
    if (!autoColumns) return;

    console.log("Initializing Auto DataTable for:", tableSelector);

    $.ajax({
        url: _buildDtUrl(),
        type: "GET",
        dataType: "json",

        success: function (json) {
            console.log(json);

            // Destroy dulu sebelum rebuild agar column count tidak konflik
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().destroy();
            }
            $(tableSelector).find('thead').empty();
            $(tableSelector).find('tbody').empty();

            let dataRows = json.data ?? [];
            let keys = json.header ?? [];

            if (dataRows.length > 0) {
                keys = Object.keys(dataRows[0]);
                keys = keys.filter(
                    (key) => key !== "DT_RowIndex" && !key.startsWith("id_"),
                );
            }

            // BUILD HEADER
            let thead = "<tr>";
            thead += "<th>No</th>";

            keys.forEach((key) => {
                let label = (window.datatableHeaderLabels && window.datatableHeaderLabels[key])
                    ? window.datatableHeaderLabels[key]
                    : key.replaceAll("_", " ").replace(/\b\w/g, (l) => l.toUpperCase());

                thead += `<th>${label}</th>`;
            });

            thead += "</tr>";
            $(tableSelector).find("thead").html(thead);

            // BUILD COLUMNS
            let columns = [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + 1;
                    },
                },
            ];

            keys.forEach((key) => {
                const renderer = window.datatableColumnRenderers?.[key];
                columns.push(renderer ? { data: key, render: renderer } : { data: key });
            });

            let exportFilename = (window.currentMenuSlug || tableSelector.replace('#', '').replace('-table', '')).replace(/-/g, '_');

            let tableInstance = $(tableSelector).DataTable({
                data: dataRows,
                columns: columns,
                processing: true,
                scrollX: true,
                scrollY: "500px",
                scrollCollapse: true,
                fixedHeader: true,

                dom: '<"dt-top d-flex align-items-center gap-2 mb-3"Bl<"ms-auto"f>>rt<"dt-bottom d-flex align-items-center justify-content-between mt-3"ip>',

                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fa-solid fa-file-excel me-1"></i> Export Excel',
                        className: 'btn btn-success btn-sm',
                        filename: exportFilename,
                        title: null,
                        exportOptions: {
                            modifier: { search: 'applied', order: 'applied' },
                        },
                    },
                ],

                language: {
                    emptyTable: "Data tidak ditemukan",
                    search: "",
                    searchPlaceholder: "Cari...",
                },
            });

            if (onReady) {
                onReady(tableInstance);
            }
        },

        error: function (xhr) {
            console.error("Gagal load data:", xhr);
        },
    });
}
