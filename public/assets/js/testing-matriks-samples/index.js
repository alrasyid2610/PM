let selectedRow = {
    id: null,
};

let table;

$(document).ready(function () {
    // Init DataTable
    table = initDataTable(tableId);

    // Row click → load detail
    $(tableId)
        .find("tbody")
        .on("click", "tr", function (event) {
            if ($(event.target).closest("button").length) return;

            const data = table.row(this).data();
            if (!data) return;

            selectedRow.id = data.id_testing_matriks_sample;

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
                        <h5>Testing Matriks Sample</h5>
                        <div class="btn-group">
                            <button class="btn btn-warning btn-sm btn-edit-context">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Matrik Kelompok</label>
                    <select name="id_testing_kelompok_matriks_sample"
                            id="detail_kelompok"
                            class="form-select"
                            required></select>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Kode Matrik Sample</label>
                    <input type="text"
                           name="kode"
                           class="form-control"
                           value="${res.kode}"
                           required>
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
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan"
                              class="form-control">${res.keterangan ?? ""}</textarea>
                </div>

            </form>
        `;

        $("#detailContent").html(html);

        // Init select2 FK
        $("#detail_kelompok").select2({
            ajax: {
                url: "/testing-kelompok-matriks-samples/select2",
                dataType: "json",
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data };
                },
            },
        });

        // Set selected FK
        if (res.id_testing_kelompok_matriks_sample) {
            console.log(res);
            const option = new Option(
                res.kelompok_matriks_judul_indonesia,
                res.id_testing_kelompok_matriks_sample,
                true,
                true,
            );
            $("#detail_kelompok").append(option).trigger("change");
        }

        // Disable by default
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
