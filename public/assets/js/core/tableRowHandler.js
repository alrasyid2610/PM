function bindRowClick(options) {
    console.log("init bindrow");
    const tableSelector = options.table;
    const table = options.tableInstance;
    const primaryKey = options.primaryKey;
    const onRowClick = options.onRowClick;

    // Hapus listener lama agar tidak menumpuk saat datatable di-reinit (misal setelah search)
    $(document).off("click", tableSelector + " tbody tr");

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

        // pindah ke tab detail — simulasi klik native agar Bootstrap handle sendiri
        var detailTabEl = document.querySelector("#detail-tab");
        if (detailTabEl) detailTabEl.click();
    });
}
