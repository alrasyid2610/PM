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
        this._initDragReorder();

        this.wrapper.on("click", ".btn-add-row", () => {
            this.addRow();
        });

        this.table.on("click", ".btn-row-action", (e) => {
            e.stopPropagation();
            this._currentActionRow = $(e.currentTarget).closest("tr");
            this._showActionMenu(e.currentTarget);
        });

        // Checkbox status[] tidak boleh punya `name` langsung — browser tidak
        // ikut mengirim checkbox yang UNCHECKED sama sekali, jadi array
        // status[] yang sampai ke server jadi lebih pendek & ke-reindex ulang
        // (index 0,1,2,... dari checkbox yang KECEKLIS saja), bukan align ke
        // posisi baris aslinya seperti judul_indonesia[]/dst yang selalu
        // terkirim. Akibatnya baris yang ke-set aktif jadi salah/acak (bug
        // dilaporkan: ceklis 10 baris, yang aktif kesimpan cuma 1 & bukan
        // yang benar). Fix: checkbox cuma UI, nilai sungguhan disimpan di
        // hidden input `status[]` yang selalu ada 1 per baris (disinkron di
        // sini), jadi urutan & jumlah array-nya selalu sama dengan baris.
        this.table.on("change", ".status-checkbox", (e) => {
            $(e.currentTarget)
                .closest("td")
                .find(".status-hidden")
                .val(e.currentTarget.checked ? "1" : "0");
        });
    }

    // Drag-and-drop reorder baris lewat handle (ikon grip di kolom pertama)
    // pakai dragula (sudah ada di public/assets/vendor/dragula, sebelumnya
    // tidak dipakai di mana pun). Cuma bisa drag kalau handle-nya tidak
    // disabled — otomatis ikut ter-lock/unlock sama seperti field lain di
    // dynamic-table-wrapper, karena drag-handle-btn adalah <button> biasa
    // yang ikut ter-enable/disable oleh toggle Edit (formEditHandler.js).
    _initDragReorder() {
        if (typeof dragula === "undefined") return;
        if (!this.table.find(".drag-handle-btn").length) return;

        const tbody = this.table.find("tbody").get(0);
        if (!tbody) return;

        const self = this;
        this._drake = dragula([tbody], {
            moves: function (el, source, handle) {
                const $handle = $(handle).closest(".drag-handle-btn");
                return $handle.length > 0 && !$handle.prop("disabled");
            },
        });

        this._drake.on("drop", function () {
            self.updateRowNumbers();
            if (window.Notify) Notify.toast("Urutan baris diperbarui");
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
            minimumInputLength: 0,
            ajax: {
                url: "/testing-parameters/select2",
                delay: 300,
                dataType: "json",
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
            },
            language: {
                noResults: () => `<span>Tidak ditemukan. <a href="/testing-parameters/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`,
            },
            escapeMarkup: (m) => m,
        });

        scope.find(".unit-select").select2({
            placeholder: "Pilih Unit",
            minimumInputLength: 0,
            ajax: {
                url: "/testing-units/select2",
                delay: 300,
                dataType: "json",
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
            },
            language: {
                noResults: () => `<span>Tidak ditemukan. <a href="/testing-units/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`,
            },
            escapeMarkup: (m) => m,
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
                if (key === "status") {
                    // Checkbox-nya tidak punya `name` (lihat catatan di
                    // _initDragReorder/init) — set hidden-nya, lalu sinkronkan
                    // checkbox visual-nya lewat class sibling-nya.
                    const $hidden = row.find('.status-hidden');
                    $hidden.val(item[key] == 1 ? '1' : '0');
                    $hidden.siblings('.status-checkbox').prop('checked', item[key] == 1);
                    return;
                }

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
