function bindRowClick(options) {
    console.log("init bindrow");
    const tableSelector = options.table;
    const table = options.tableInstance;
    const primaryKey = options.primaryKey;
    const onRowClick = options.onRowClick;

    // gunakan delegated event langsung ke table
    $(document).on("click", tableSelector + " tbody tr", function (event) {
        if ($(event.target).closest("button").length) return;

        const data = table.row(this).data();

        if (!data) return;

        const id = data[primaryKey];

        if (onRowClick) {
            onRowClick(id, data);
        }

        // highlight row
        $(tableSelector + " tr").removeClass("table-active");
        $(this).addClass("table-active");

        // pindah ke tab detail
        const detailTab = new bootstrap.Tab(
            document.querySelector("#detail-tab"),
        );

        detailTab.show();
    });
}
