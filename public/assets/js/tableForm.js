class DynamicTable {
    constructor(options) {
        console.log("init table form");
        this.table = $(options.table);
        this.wrapper = this.table.closest(".dynamic-table-wrapper");
        this.autoNumber = options.autoNumber ?? true;

        this.init();
    }

    init() {
        this.initPlugins(this.table);
        this.updateRowNumbers();

        this.wrapper.on("click", ".btn-add-row", () => {
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
        // console.log(data);
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
                    this.setSelect2Value(input, item[key]);
                } else {
                    input.val(item[key]);
                }
            });

            tbody.append(row);

            this.initPlugins(row);
        });

        this.updateRowNumbers();
    }

    setSelect2Value(select, id) {
        // console.log(select, id);

        if (!id) return;

        $.ajax({
            url: select.hasClass("parameter-select")
                ? "/testing-parameters/select2byid"
                : "/testing-units/select2byid",
            data: { q: id },
            success: (res) => {
                if (!res || res.length === 0) return;

                let item = res[0];

                let option = new Option(item.text, item.id, true, true);

                select.append(option).trigger("change");
            },
        });
    }
}
