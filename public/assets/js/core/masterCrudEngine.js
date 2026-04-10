class MasterCrudEngine {
    constructor(options) {
        this.tableSelector = options.tableSelector || tableSelector;
        this.primaryKey = options.primaryKey;
        this.onRowClick = options.onRowClick;
        this.selectedRow = {
            id: null,
        };

        this.table = null;

        this.init();
        this.onAttachmentDeleted = options.onAttachmentDeleted || null;
    }

    init() {
        const self = this;

        initDataTable(this.tableSelector, function (tableInstance) {
            self.table = tableInstance;
            self.bindRowClick();
        });

        this.bindDelete();
        this.bindAttachmentDelete();
    }

    bindRowClick() {
        const self = this;

        bindRowClick({
            table: this.tableSelector,

            tableInstance: this.table,

            primaryKey: this.primaryKey,

            onRowClick: function (id, data) {
                if (self.onRowClick) {
                    self.selectedRow.id = id;
                    self.onRowClick(id, data);
                }
            },
        });
    }

    bindDelete() {
        const self = this;

        $(this.tableSelector).on("click", ".btn-delete", function () {
            const id = $(this).data("id");

            Notify.confirm("Hapus data?", function () {
                $.ajax({
                    url: window.route.update + id,

                    method: "DELETE",

                    data: {
                        _token: window.route.csrf,
                    },

                    success: function () {
                        Notify.success("Data berhasil dihapus");

                        if (self.table) {
                            self.table.ajax.reload();
                        }
                    },

                    error: function () {
                        Notify.error("Gagal menghapus data");
                    },
                });
            });
        });
    }

    bindAttachmentDelete() {
        const self = this;

        $(document).on("click", ".btn-delete-attachment", function (e) {
            e.preventDefault();

            let file = $(this).data("file");

            Notify.confirm("Hapus attachment ini?", function () {
                $.ajax({
                    url: window.route.deleteAttachment,
                    method: "POST",
                    data: {
                        id: self.selectedRow.id, // ✅ FIX
                        file: file,
                        _token: window.route.csrf,
                    },
                    success: function () {
                        Notify.success("Attachment berhasil dihapus");

                        if (self.onAttachmentDeleted) {
                            self.onAttachmentDeleted(self.selectedRow.id);
                        }
                    },

                    error: function () {
                        Notify.error("Gagal menghapus attachment");
                    },
                });
            });
        });
    }
}
