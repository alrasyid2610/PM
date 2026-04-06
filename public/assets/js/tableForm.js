class DynamicTable {
    constructor(options) {
        this.table = $(options.table);
        this.wrapper = this.table.closest(".dynamic-table-wrapper");
        this.autoNumber = options.autoNumber ?? true;

        this.init();
    }

    init() {
        console.log("Init Dynamic Table Form");
        this.initPlugins(this.table);
        this.updateRowNumbers();

        this.wrapper.on("click", ".btn-add-row", () => {
            console.log("add row in dynamic table");
            this.addRow();
        });

        this.table.on("click", ".btn-remove", (e) => {
            this.removeRow(e);
        });
    }

    addRow() {
        let template = $("#row-template").html();

        let newRow = $(template);

        this.table.find("tbody").append(newRow);

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

        this.table.find(".row-number").each(function (index) {
            $(this).text(index + 1);
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
            console.log(item);
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
                        id = item.parameter; //id_parameter
                        val =
                            item.kode_parameter +
                            " - " +
                            item.judul_indonesia_parameter;
                    } else {
                        id = item.unit; //id_unit
                        val =
                            item.kode_unit + " - " + item.judul_indonesia_unit;
                    }

                    console.log(id, key);
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
