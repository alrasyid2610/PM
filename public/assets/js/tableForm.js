class DynamicTable {
    constructor(options) {
        this.table = $(options.table);
        this.wrapper = this.table.closest(".dynamic-table-wrapper");
        this.autoNumber = options.autoNumber ?? true;
        this._currentActionRow = null;
        this._actionMenu = null;

        this.init();
    }

    init() {
        console.log("Init Dynamic Table Form");
        this.initPlugins(this.table);
        this.updateRowNumbers();
        this._initActionMenu();

        this.wrapper.on("click", ".btn-add-row", () => {
            this.addRow();
        });

        this.table.on("click", ".btn-row-action", (e) => {
            e.stopPropagation();
            this._currentActionRow = $(e.currentTarget).closest("tr");
            this._showActionMenu(e.currentTarget);
        });
    }

    _initActionMenu() {
        if ($("#dynamicTableActionMenu").length) {
            this._actionMenu = $("#dynamicTableActionMenu");
        } else {
            this._actionMenu = $(`
                <ul id="dynamicTableActionMenu" style="position:fixed;z-index:99999;display:none;list-style:none;margin:0;padding:4px 0;background:#fff;border:1px solid rgba(0,0,0,.15);border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,.12);min-width:160px;">
                    <li><a class="dropdown-item" href="#" data-action="insert-above"><i class="fa-solid fa-arrow-up fa-sm me-2"></i>Insert Above</a></li>
                    <li><a class="dropdown-item" href="#" data-action="insert-below"><i class="fa-solid fa-arrow-down fa-sm me-2"></i>Insert Below</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" data-action="remove"><i class="fa-solid fa-trash fa-sm me-2"></i>Hapus</a></li>
                </ul>
            `).appendTo("body");

            $(document).on("click.dynamicTableMenu", () => {
                this._actionMenu.hide();
            });
        }

        this._actionMenu.off("click").on("click", "[data-action]", (e) => {
            e.preventDefault();
            const action = $(e.currentTarget).data("action");
            this._actionMenu.hide();

            if (action === "insert-above") {
                this.insertRow("above", this._currentActionRow);
            } else if (action === "insert-below") {
                this.insertRow("below", this._currentActionRow);
            } else if (action === "remove") {
                if (this.table.find("tbody tr").length > 1) {
                    this._currentActionRow.remove();
                    this.updateRowNumbers();
                } else {
                    Notify.warning("Minimal harus ada 1 baris.!");
                }
            }
        });
    }

    _showActionMenu(button) {
        const rect = button.getBoundingClientRect();
        const menuWidth = 160;
        let top = rect.top;
        let left = rect.left - menuWidth - 4;

        if (left < 4) left = rect.right + 4;
        if (top + 160 > window.innerHeight) top = window.innerHeight - 164;

        this._actionMenu.css({ top, left }).show();
    }

    addRow() {
        let template = $("#row-template").html();
        let newRow = $(template);

        this.table.find("tbody").append(newRow);
        this.initPlugins(newRow);
        this.updateRowNumbers();
    }

    insertRow(position, referenceRow) {
        let template = $("#row-template").html();
        let newRow = $(template);

        if (position === "above") {
            referenceRow.before(newRow);
        } else {
            referenceRow.after(newRow);
        }

        this.initPlugins(newRow);
        this.updateRowNumbers();
    }

    removeRow(e) {
        let row = $(e.currentTarget).closest("tr");

        if (this.table.find("tbody tr").length > 1) {
            row.remove();
        } else {
            Notify.warning("Minimal harus ada 1 baris.!");
        }

        this.updateRowNumbers();
    }

    updateRowNumbers() {
        if (!this.autoNumber) return;

        this.table.find("tbody tr").each(function (index) {
            $(this).find(".row-number").text(index + 1);
            $(this).find("input[name='nomor[]']").val(index + 1);
        });
    }

    initPlugins(scope) {
        scope.find(".parameter-select").select2({
            placeholder: "Pilih Parameter",
            minimumInputLength: 2,
            ajax: {
                url: "/testing-parameters/select2",
                delay: 300,
                dataType: "json",
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
            },
        });

        scope.find(".unit-select").select2({
            placeholder: "Pilih Unit",
            minimumInputLength: 2,
            ajax: {
                url: "/testing-units/select2",
                delay: 300,
                dataType: "json",
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
            },
        });
    }

    loadData(data) {
        console.log("load data for dynamic table");
        let tbody = this.table.find("tbody");

        tbody.empty();

        if (!data || data.length === 0) {
            this.addRow();
            return;
        }

        data.forEach((item) => {
            let template = $("#row-template").html();
            let row = $(template);

            Object.keys(item).forEach((key) => {
                let input = row.find(`[name="${key}[]"]`);
                if (!input.length) return;

                if (input.is(":checkbox")) {
                    input.prop("checked", item[key] == 1);
                } else if (input.is("select")) {
                    let id = "";
                    let val = "";
                    if (key == "parameter") {
                        id = item.parameter;
                        val =
                            item.kode_parameter +
                            " - " +
                            item.judul_indonesia_parameter;
                    } else {
                        id = item.unit;
                        val =
                            item.kode_unit + " - " + item.judul_indonesia_unit;
                    }

                    this.setSelect2Value(input, item[key], val);
                } else {
                    input.val(item[key]);
                }
            });

            tbody.append(row);
            this.initPlugins(row);

            $(".dynamic-table-wrapper")
                .find("input, select, textarea, button")
                .prop("disabled", true);
        });

        this.updateRowNumbers();
    }

    setSelect2Value(select, id, text) {
        if (!id) return;
        let option = new Option(text, id, true, true);
        select.append(option).trigger("change");
    }
}
