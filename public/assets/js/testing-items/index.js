let selectedRow = {
    id: null,
};

let table;

$(document).ready(function () {
    table = initDataTable(tableId);

    // Row click
    $(tableId)
        .find("tbody")
        .on("click", "tr", function (event) {
            if ($(event.target).closest("button").length) return;

            const data = table.row(this).data();
            if (!data) return;

            selectedRow.id = data.id_testing_item;

            loadDetail(selectedRow.id);

            $(tableId + " tr").removeClass("table-active");
            $(this).addClass("table-active");

            const detailTab = new bootstrap.Tab(
                document.querySelector("#detail-tab"),
            );
            detailTab.show();
        });
});

function loadDetail(id) {
    $("#detailContent").html("Loading...");

    $.get(window.route.detail + id + "/detail", function (res) {
        const html = `
            <form id="detailForm" class="row g-3">

                <input type="hidden" name="_token" value="${window.route.csrf}">

                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Testing Item</h5>
                        <div class="btn-group">
                            <button class="btn btn-warning btn-sm btn-edit-context">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Testing Point</label>
                    <select id="detail_point"
                            name="id_testing_point"
                            class="form-select"
                            required></select>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Testing Parameter</label>
                    <select id="detail_parameter"
                            name="id_testing_parameter"
                            class="form-select"
                            required></select>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Testing Unit</label>
                    <select id="detail_unit"
                            name="id_testing_unit"
                            class="form-select"
                            required></select>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Nilai</label>
                    <input type="number"
                           step="any"
                           name="nilai"
                           class="form-control"
                           value="${res.nilai ?? ""}">
                </div>

                <div class="col-md-12">
                    <label class="form-label">Nomor</label>
                    <input type="text"
                           name="nomor"
                           class="form-control"
                           value="${res.nomor ?? ""}">
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Judul Indonesia</label>
                    <input type="text"
                           name="judul_indonesia"
                           class="form-control"
                           value="${res.judul_indonesia}"
                           required>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Judul Inggris</label>
                    <input type="text"
                           name="judul_inggris"
                           class="form-control"
                           value="${res.judul_inggris}"
                           required>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Status</label>
                    <select name="is_aktif" class="form-select">
                        <option value="1" ${res.is_aktif == 1 ? "selected" : ""}>Aktif</option>
                        <option value="0" ${res.is_aktif == 0 ? "selected" : ""}>Tidak Aktif</option>
                    </select>
                </div>

            </form>
        `;

        $("#detailContent").html(html);

        initDetailSelect2(res);

        // Disable default
        $("#detailContent")
            .find("input, select, textarea")
            .prop("disabled", true);

        // Edit toggle
        $(".btn-edit-context").on("click", function (e) {
            e.preventDefault();

            const $btn = $(this);
            const isEditing = $btn.hasClass("editing");

            if (!isEditing) {
                $("#detailContent")
                    .find("input, select, textarea")
                    .prop("disabled", false);

                $btn.addClass("editing")
                    .removeClass("btn-warning")
                    .addClass("btn-secondary")
                    .html('<i class="fa-solid fa-times"></i>');

                $btn.after(`
                    <button class="btn btn-success btn-sm btn-save-context ms-2">
                        <i class="fa-solid fa-check"></i>
                    </button>
                `);

                $(".btn-save-context").on("click", function (e) {
                    e.preventDefault();
                    submitForm();
                });
            } else {
                $("#detailContent")
                    .find("input, select, textarea")
                    .prop("disabled", true);

                $btn.removeClass("editing")
                    .addClass("btn-warning")
                    .removeClass("btn-secondary")
                    .html('<i class="fa-solid fa-pen"></i>');

                $(".btn-save-context").remove();
            }
        });
    });
}

function initDetailSelect2(res) {
    $("#detail_point").select2({
        ajax: {
            url: "/testing-points/data",
            dataType: "json",
            processResults: function (data) {
                return {
                    results: data.data.map((item) => ({
                        id: item.id_testing_point,
                        text: item.nama,
                    })),
                };
            },
        },
    });

    $("#detail_parameter").select2({
        ajax: {
            url: "/testing-parameters/data",
            dataType: "json",
            processResults: function (data) {
                return {
                    results: data.data.map((item) => ({
                        id: item.id_testing_parameter,
                        text: item.kode + " - " + item.judul_indonesia,
                    })),
                };
            },
        },
    });

    $("#detail_unit").select2({
        ajax: {
            url: "/testing-units/data",
            dataType: "json",
            processResults: function (data) {
                return {
                    results: data.data.map((item) => ({
                        id: item.id_testing_unit,
                        text: item.kode + " - " + item.judul_indonesia,
                    })),
                };
            },
        },
    });

    // Set selected
    if (res.id_testing_point) {
        const option = new Option(
            res.id_testing_point,
            res.id_testing_point,
            true,
            true,
        );
        $("#detail_point").append(option).trigger("change");
    }

    if (res.id_testing_parameter) {
        const option = new Option(
            res.id_testing_parameter,
            res.id_testing_parameter,
            true,
            true,
        );
        $("#detail_parameter").append(option).trigger("change");
    }

    if (res.id_testing_unit) {
        const option = new Option(
            res.id_testing_unit,
            res.id_testing_unit,
            true,
            true,
        );
        $("#detail_unit").append(option).trigger("change");
    }
}

function submitForm() {
    const formData = $("#detailForm").serialize();

    Notify.confirm("Simpan Data?", function () {
        $.ajax({
            url: window.route.update + selectedRow.id,
            method: "PUT",
            data: formData,

            success: function () {
                Notify.success("Data berhasil diperbarui");
                loadDetail(selectedRow.id);
            },

            error: function () {
                Notify.error("Gagal memperbarui data");
            },
        });
    });
}
