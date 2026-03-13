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

            selectedRow.id = data.id_testing_point;

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
                        <h5>Testing Point</h5>
                        <div class="btn-group">
                            <button class="btn btn-warning btn-sm btn-edit-context">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-1">Informasi Testing Poin</h6>
                
                <div class="col-md-12">
                    <label class="form-label required">Testing Standard</label>
                    <select name="id_testing_standard"
                            id="detail_standard"
                            class="form-select"
                            required></select>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Testing Matriks Sample</label>
                    <select name="id_testing_matriks_sample"
                            id="detail_matriks"
                            class="form-select"
                            required></select>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Nama</label>
                    <input type="text"
                           name="nama"
                           class="form-control"
                           value="${res.nama}"
                           required>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi"
                              class="form-control">${res.deskripsi ?? ""}</textarea>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Nomor Halaman</label>
                    <input type="text"
                           name="nomor_halaman"
                           class="form-control"
                           value="${res.nomor_halaman ?? ""}">
                </div>

                <div class="col-md-12">
                    <label class="form-label">Attachment</label>
                    <input type="text"
                           name="attachment"
                           class="form-control"
                           value="${res.attachment ?? ""}">
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Status</label>
                    <select name="is_aktif" class="form-select">
                        <option value="1" ${res.is_aktif == 1 ? "selected" : ""}>Aktif</option>
                        <option value="0" ${res.is_aktif == 0 ? "selected" : ""}>Tidak Aktif</option>
                    </select>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan"
                              class="form-control">${res.keterangan ?? ""}</textarea>
                </div>

            </form>
        `;

        $("#detailContent").html(html);

        // Init select2
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
    console.log(res);
    $("#detail_standard").select2({
        ajax: {
            url: "/testing-standards/data",
            dataType: "json",
            processResults: function (data) {
                return {
                    results: data.data.map((item) => ({
                        id: item.id_testing_standard,
                        text: item.nomor + " - " + item.judul,
                    })),
                };
            },
        },
    });

    $("#detail_matriks").select2({
        ajax: {
            url: "/testing-matriks-samples/data",
            dataType: "json",
            processResults: function (data) {
                return {
                    results: data.data.map((item) => ({
                        id: item.id_testing_matriks_sample,
                        text: item.kode + " - " + item.judul_indonesia,
                    })),
                };
            },
        },
    });

    // Set selected
    if (res.id_testing_standard) {
        const option = new Option(
            res.standard_nomor + " - " + res.standard_judul,
            res.id_testing_standard,
            true,
            true,
        );
        $("#detail_standard").append(option).trigger("change");
    }

    if (res.id_testing_matriks_sample) {
        const option = new Option(
            res.matrik_sample_kode + " - " + res.matrik_sample_judul_indonesia,
            res.id_testing_matriks_sample,
            true,
            true,
        );
        $("#detail_matriks").append(option).trigger("change");
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
