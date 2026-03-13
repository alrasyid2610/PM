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

            selectedRow.id = data.id_testing_kelompok_matriks_sample;

            loadDetail(selectedRow.id);

            $(tableId + " tr").removeClass("table-active");
            $(this).addClass("table-active");

            const detailTab = new bootstrap.Tab(
                document.querySelector("#detail-tab"),
            );
            detailTab.show();
        });

    // Delete button
    $(tableId).on("click", ".btn-delete", function () {
        const id = $(this).data("id");

        Notify.confirm("Hapus data?", function () {
            $.ajax({
                url: window.route.update + id,
                method: "DELETE",
                data: { _token: window.route.csrf },

                success: function () {
                    Notify.success("Data berhasil dihapus");
                    table.ajax.reload();
                },

                error: function () {
                    Notify.error("Gagal menghapus data");
                },
            });
        });
    });
});

function loadDetail(id) {
    $("#detailContent").html("Loading...");

    $.get(window.route.detail + id + "/detail", function (res) {
        const html = `
            <form class="row g-3" id="detailForm">

                <input type="hidden" name="_token" value="${window.route.csrf}">

                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Testing Kelompok Matriks Sample</h5>
                        <div class="btn-group">
                            <button class="btn btn-warning btn-sm btn-edit-context">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Kode</label>
                    <input type="text" name="kode"
                        class="form-control"
                        value="${res.kode}" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Judul Indonesia</label>
                    <input type="text" name="judul_indonesia"
                        class="form-control"
                        value="${res.judul_indonesia}" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Judul Inggris</label>
                    <input type="text" name="judul_inggris"
                        class="form-control"
                        value="${res.judul_inggris}" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan"
                        class="form-control">${res.keterangan ?? ""}</textarea>
                </div>

            </form>
        `;

        $("#detailContent").html(html);

        // disable initially
        $("#detailContent").find("input, textarea").prop("disabled", true);

        // Edit button logic
        $(".btn-edit-context").on("click", function (e) {
            e.preventDefault();

            const $btn = $(this);
            const isEditing = $btn.hasClass("editing");

            if (!isEditing) {
                $("#detailContent")
                    .find("input, textarea")
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
                    .find("input, textarea")
                    .prop("disabled", true);

                $btn.removeClass("editing")
                    .addClass("btn-warning")
                    .removeClass("btn-secondary")
                    .html('<i class="fa-solid fa-pen"></i>');

                $(".btn-save-context").remove();
            }
        });
    }).fail(function () {
        $("#detailContent").html("Gagal memuat detail");
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
