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
                columns.push({ data: key });
            });

            let tableInstance = $(tableSelector).DataTable({
                data: dataRows,
                columns: columns,
                processing: true,
                destroy: true,
                scrollX: true,

                language: {
                    emptyTable: "Data tidak ditemukan",
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
