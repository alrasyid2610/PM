function initDataTable(tableSelector, onReady) {
    if ($(tableSelector).length === 0) return;

    let autoColumns = $(tableSelector).data("datatable-auto-columns");

    if (!autoColumns) return;

    console.log("Initializing Auto DataTable for:", tableSelector);

    $.ajax({
        url: window.route.data,
        type: "GET",
        dataType: "json",

        success: function (json) {
            console.log(json);
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
                let label = key
                    .replaceAll("_", " ")
                    .replace(/\b\w/g, (l) => l.toUpperCase());

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
                destroy: true,
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

            // 🔥 callback setelah datatable siap
            if (onReady) {
                onReady(tableInstance);
            }
        },

        error: function (xhr) {
            console.error("Gagal load data:", xhr);
        },
    });
}
