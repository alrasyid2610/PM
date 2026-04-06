class CrudPageController {
    constructor(options) {
        this.primaryKey = options.primaryKey;
        this.renderForm = options.renderForm;
        this.initSelect = options.initSelect || null;
        this.useAttachment = options.useAttachment || false;
        this.initDynamicTable = options.initDynamicTable || null;

        this.selectedRow = { id: null };

        this.attachmentData = [];
        this.pondEdit = null;

        this.dataTemp = "";

        this.init();
    }

    init() {
        console.log("cascascsa kocak");
        const self = this;

        new MasterCrudEngine({
            primaryKey: this.primaryKey,
            onRowClick: function (id) {
                console.log(id, " ini id yang diklik");
                self.selectedRow.id = id;
                self.loadDetail(id);
            },
            onAttachmentDeleted: function (id) {
                self.loadDetail(id); // 🔥 reload detail di sini
            },
        });
    }

    loadDetail(id) {
        const self = this;
        var a = loadDetailEngine({
            id: id,

            url: window.route.update,

            container: "#detailContent",

            render: this.renderForm,

            afterRender: async function (res) {
                $("#detailContent")
                    .find("input, select, textarea")
                    .prop("disabled", true);

                if (self.initSelect) {
                    self.initSelect(res);
                }

                if (self.initDynamicTable) {
                    let itemsTable = new DynamicTable({
                        table: "#Table",
                        autoNumber: true,
                    });

                    $(".dynamic-table-wrapper")
                        .find("input, select, textarea, button")
                        .prop("disabled", true);
                    // $.get(
                    //     "/testing-items/by-point/" + res.id_testing_point,
                    //     function (res) {
                    //         window.dataBefore.push(res.data);
                    //         itemsTable.loadData(res.data);
                    //     },
                    // );
                    await loadItems(res, itemsTable);
                }

                if (self.useAttachment) {
                    self.attachmentData = res.attachment;
                    renderAttachments(self.attachmentData);
                }

                self.bindEditBehaviour();
                initDynamicSelect("#detailContent");

                // 🔥 SEKARANG BARU AMBIL INITIAL STATE
                window.initialForms = getAllFormsData();
                window.initialItems = getDynamicTableData();
            },
        });
    }

    bindEditBehaviour() {
        const self = this;

        bindEditToggle({
            container: "#detailContent",

            onEditStart: function () {
                if (self.useAttachment) {
                    $("#attachmentUploader").show();

                    self.pondEdit = createFileUploader(".filepond-edit");

                    renderAttachments(self.attachmentData);
                }
            },

            onEditCancel: function () {
                if (self.useAttachment) {
                    $("#attachmentUploader").hide();

                    destroyUploader(self.pondEdit);

                    renderAttachments(self.attachmentData);
                }
            },

            onSave: function () {
                console.log("lah kocak");

                submitCrudForm({
                    id: self.selectedRow.id,

                    reload: self.loadDetail.bind(self),

                    filepond: self.pondEdit,
                });
            },
        });
    }
}
