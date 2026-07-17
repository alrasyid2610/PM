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
        $btn.removeClass('btn-primary search-on').addClass('btn-outline-secondary');
    } else {
        $bar.slideDown(150);
        $btn.removeClass('btn-outline-secondary').addClass('btn-primary search-on');
        // Inisialisasi baris pertama jika belum ada
        if ($('#dtFilterRows .dt-filter-row').length === 0) {
            _addFilterRow(window._dtSearchFields || []);
        }
        $('#dtFilterRows .dt-filter-by').first().trigger('focus');
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

// ── Multi-filter search bar ────────────────────────────────────────────────────

// Render satu baris filter berdasarkan config field yang dipilih
function _renderFilterValueInput(rowId, field) {
    if (!field) return '<input type="text" class="form-control form-control-sm dt-filter-q" data-row="' + rowId + '" placeholder="Kata kunci..." style="min-width:180px;" disabled>';
    var type    = field.type    || 'text';
    var options = field.options || null;
    if (type === 'select' && options) {
        var html = '<select class="form-select form-select-sm dt-filter-q" data-row="' + rowId + '" style="min-width:160px;"><option value="">-- Pilih --</option>';
        $.each(options, function (i, opt) {
            html += '<option value="' + opt.value + '">' + opt.label + '</option>';
        });
        return html + '</select>';
    }
    return '<input type="text" class="form-control form-control-sm dt-filter-q" data-row="' + rowId + '" placeholder="Kata kunci..." style="min-width:180px;">';
}

function _addFilterRow(fieldDefs) {
    var rowId  = Date.now();
    var opts   = '<option value="">-- Cari berdasarkan --</option>';
    $.each(fieldDefs, function (i, f) {
        opts += '<option value="' + f.value + '" data-type="' + (f.type || 'text') + '" data-options=\'' + JSON.stringify(f.options || []) + '\'>' + f.label + '</option>';
    });

    var $row = $('<div class="d-flex align-items-center gap-2 dt-filter-row" data-row="' + rowId + '" style="flex-wrap:nowrap;">' +
        '<select class="form-select form-select-sm dt-filter-by" data-row="' + rowId + '" style="min-width:170px;">' + opts + '</select>' +
        _renderFilterValueInput(rowId, null) +
        '<button type="button" class="btn btn-sm btn-outline-danger dt-filter-remove" data-row="' + rowId + '" title="Hapus baris"><i class="fa-solid fa-xmark"></i></button>' +
    '</div>');

    $('#dtFilterRows').append($row);
    _syncFilterActions();
}

function _syncFilterActions() {
    var count = $('#dtFilterRows .dt-filter-row').length;
    $('#btnDtSearch').prop('disabled', count === 0);
}

// Saat field dipilih → ganti input sesuai type
$(document).on('change', '.dt-filter-by', function () {
    var rowId   = $(this).data('row');
    var val     = $(this).val();
    var $row    = $(this).closest('.dt-filter-row');
    var fieldDefs = window._dtSearchFields || [];
    var field   = fieldDefs.find(function (f) { return f.value === val; }) || null;
    $row.find('.dt-filter-q').replaceWith(_renderFilterValueInput(rowId, field));
    if (!val) $row.find('.dt-filter-q').prop('disabled', true);
});

// Hapus baris filter
$(document).on('click', '.dt-filter-remove', function () {
    $(this).closest('.dt-filter-row').remove();
    _syncFilterActions();
});

// Enter di input teks → langsung cari
$(document).on('keydown', '.dt-filter-q', function (e) {
    if (e.key === 'Enter') _applyDtSearch();
});

// Tambah baris filter
$(document).on('click', '#btnAddFilterRow', function () {
    _addFilterRow(window._dtSearchFields || []);
});

$(document).on('click', '#btnDtSearch', function () {
    _applyDtSearch();
});

$(document).on('click', '#btnDtClearSearch', function () {
    // Hapus semua params filter
    Object.keys(window._dtParams).forEach(function (k) {
        if (k.startsWith('filters[')) delete window._dtParams[k];
    });
    $('#dtFilterRows').empty();
    _addFilterRow(window._dtSearchFields || []);
    $('#dtSearchBadge').hide().empty();
    $('#btnDtClearSearch').hide();
    if (!$('#dtSearchBar').is(':visible')) {
        $('#btnToggleDtSearch').removeClass('btn-primary search-on').addClass('btn-outline-secondary');
    }
    initDataTable(window.tableSelector, window._dtOnReady);
});

function _applyDtSearch() {
    // Hapus params filter lama
    Object.keys(window._dtParams).forEach(function (k) {
        if (k.startsWith('filters[')) delete window._dtParams[k];
    });

    var filters = [];
    var valid   = true;

    $('#dtFilterRows .dt-filter-row').each(function () {
        var by = $(this).find('.dt-filter-by').val();
        var q  = $(this).find('.dt-filter-q').val();
        if (!by) return; // skip baris kosong
        if (!q || !q.trim()) { valid = false; return false; }
        filters.push({ by: by, q: q.trim() });
    });

    if (!valid) { alert('Isi semua kata kunci filter yang sudah dipilih.'); return; }
    if (!filters.length) return;

    // Kirim sebagai filters[0][by], filters[0][q], dst.
    $.each(filters, function (i, f) {
        window._dtParams['filters[' + i + '][by]'] = f.by;
        window._dtParams['filters[' + i + '][q]']  = f.q;
    });

    // Badge ringkasan
    var labels = filters.map(function (f) {
        var fieldDef = (window._dtSearchFields || []).find(function (d) { return d.value === f.by; });
        var fieldLabel = fieldDef ? fieldDef.label : f.by;
        return fieldLabel + ': "' + f.q + '"';
    });
    $('#dtSearchBadge').html(labels.join(' &amp; ')).show();
    $('#btnDtClearSearch').show();
    $('#btnToggleDtSearch').removeClass('btn-outline-secondary').addClass('btn-primary search-on');

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
