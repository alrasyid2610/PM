// Menu "Site" (read-only) — daftar semua BRS lintas Perusahaan. Tidak ada panel detail:
// klik baris langsung membuka workspace Site di halaman Business Relation.
$(document).ready(function () {
    initDataTable(tableSelector, function (tableInstance) {
        $(tableSelector).css('cursor', 'pointer');

        bindRowClick({
            table: tableSelector,
            tableInstance: tableInstance,
            primaryKey: 'id_site',
            onRowClick: function (idSite, data) {
                if (!data || !data.id_br || !idSite) return;
                window.location.href =
                    '/business-relations?open=' + data.id_br + '&tab=tabBrsSite&site=' + idSite;
            },
        });
    });
});
