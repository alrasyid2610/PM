let selectedRow = {
    id_testing_parameter: null,
};

let table;

$(document).ready(function () {
    initDataTable(tableId);

    $(tableId)
        .find("tbody")
        .on("click", "tr", function () {
            if ($(event.target).closest("button").length) return;
            const data = table.row(this).data();
            if (!data) return;
            selectedRow.id_testing_parameter = data.id_testing_parameter;
            loadDetail(data.id_testing_parameter);
            $(tableId + " tr").removeClass("table-active");
            $(this).addClass("table-active");

            const detailTab = new bootstrap.Tab(
                document.querySelector("#detail-tab"),
            );
            detailTab.show();
        });

    // delete
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

let attachmentData = [];

function loadDetail(id_testing_parameter) {
    $("#detailContent").html("Loading...");

    $.get(window.route.update + id_testing_parameter, function (res) {
        console.log(res);
        const html = `
            <form class="row g-3" id="detailForm">
                <input type="hidden" name="_token" value="${window.route.csrf}">
                <input type="hidden" name="_method" value="PUT">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3>Testing Parameter</h3>
                        <div class="btn-group">
                            <button class="btn btn-warning btn-sm btn-edit-context" title="Edit Testing Parameter">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <input type="hidden" name="_token" value="${window.route.csrf}">

                        <div class="col-md-12 col-lg-3 mb-3">
                            <label class="form-label">Kelompok</label>
                            <select name="kelompok" id="detail_kelompok" class="form-select disabled">

                                <option value="Fisika" ${res.kelompok === "Fisika" ? "selected" : ""}>Fisika</option>

                                <option value="Kimia Logam" ${res.kelompok === "Kimia Logam" ? "selected" : ""}>Kimia Logam</option>

                                <option value="Kimia Non Logam" ${res.kelompok === "Kimia Non Logam" ? "selected" : ""}>Kimia Non Logam</option>

                                <option value="Kimia Organik" ${res.kelompok === "Kimia Organik" ? "selected" : ""}>Kimia Organik</option>

                                <option value="Mikrobiologi" ${res.kelompok === "Mikrobiologi" ? "selected" : ""}>Mikrobiologi</option>

                            </select>
                        </div>

                        <div class="col-md-12 col-lg-3 mb-3">
                            <label class="form-label required">Kode</label>
                            <input type="text" name="kode" class="form-control disabled" value="${res.kode}" required>
                        </div>

                        <div class="col-md-12 col-lg-3 mb-3">
                            <label class="form-label required">Judul Indonesia</label>
                            <input type="text" name="judul_indonesia" class="form-control disabled" value="${res.judul_indonesia}" required>
                        </div>

                        <div class="col-md-12 col-lg-3 mb-3">
                            <label class="form-label">Judul Inggris</label>
                            <input type="text" name="judul_inggris" class="form-control disabled" value="${res.judul_inggris ?? ""}">
                        </div>

                        <div class="col-md-12 col-lg-4 mb-3">
                            <label class="form-label">Rumus Empiris</label>
                            <input type="text" name="rumus_empiris" class="form-control disabled" value="${res.rumus_empiris ?? ""}">
                        </div>

                        <div class="col-md-12 col-lg-4 mb-3">
                            <label class="form-label">Judul IUPAC</label>
                            <input type="text" name="judul_iupac" class="form-control disabled" value="${res.judul_iupac ?? ""}">
                        </div>

                        <div class="col-md-12 col-lg-4 mb-3">
                            <label class="form-label">Referensi</label>
                            <input type="text" name="referensi" class="form-control disabled" value="${res.referensi ?? ""}">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control disabled" rows="3">${res.keterangan ?? ""}</textarea>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Attachment</label>
                            <div id="attachmentPreview" class="row g-3"></div>

                            <div id="attachmentUploader" class="mt-3" style="display:none">

                                <input 
                                type="file"
                                class="filepond-edit"
                                name="attachments[]"
                                multiple>

                            </div>
                            
                        </div>
                    </div>
                </div>
            </form>
        `;

        $("#detailContent").html(html);
        attachmentData = res.attachment;
        renderAttachments(attachmentData);

        $("#detailContent")
            .find("input, select, textarea")
            .prop("disabled", true);

        $("#detail_kelompok").select2({
            width: "100%",
            dropdownParent: $("#detailContent"),
        });

        $(".btn-edit-context").on("click", function (e) {
            e.preventDefault();
            const $btn = $(this);
            const isEditing = $btn.hasClass("editing");

            if (!isEditing) {
                $("#detailContent")
                    .find("input, select, textarea")
                    .prop("disabled", false);

                $("#detailContent")
                    .find("input, select, textarea")
                    .removeClass("disabled");

                $btn.addClass("editing")
                    .removeClass("btn-warning")
                    .addClass("btn-secondary")
                    .html('<i class="fa-solid fa-times"></i>');
                $btn.after(`
                    <button class="btn btn-success btn-sm btn-save-context ms-2" title="Simpan">
                        <i class="fa-solid fa-check"></i>
                    </button>
                `);

                $("#attachmentUploader").show();
                initFilepondEdit();
                renderAttachments(attachmentData);

                $(".btn-save-context").on("click", function (e) {
                    e.preventDefault();
                    submitForm();
                });
            } else {
                $("#detailContent")
                    .find("input, select, textarea")
                    .prop("disabled", true);

                $("#detailContent")
                    .find("input, select, textarea")
                    .addClass("disabled");

                $btn.removeClass("editing")
                    .addClass("btn-warning")
                    .removeClass("btn-secondary")
                    .html('<i class="fa-solid fa-pen"></i>');
                $(".btn-save-context").remove();

                $("#attachmentUploader").hide();
                if (pondEdit) {
                    pondEdit.destroy();
                }
                renderAttachments(attachmentData);
            }
        });
    }).fail(function () {
        $("#detailContent").html("Gagal memuat detail");
    });
}

function submitForm() {
    let form = document.getElementById("detailForm");

    let formData = new FormData(form);

    // ambil file dari FilePond
    if (pondEdit) {
        pondEdit.getFiles().forEach((fileItem) => {
            formData.append("attachments[]", fileItem.file);
        });
    }

    Notify.confirm("Simpan Data?", function () {
        $.ajax({
            url: window.route.update + selectedRow.id_testing_parameter,
            method: "POST", // gunakan POST + spoof PUT

            data: formData,

            processData: false,
            contentType: false,

            success: function (response) {
                Notify.success("Data berhasil diperbarui");

                loadDetail(selectedRow.id_testing_parameter);
            },

            error: function () {
                Notify.error("Gagal memperbarui data");
            },
        });
    });
}

let pondEdit;

function initFilepondEdit() {
    pondEdit = FilePond.create(document.querySelector(".filepond-edit"), {
        allowMultiple: true,

        acceptedFileTypes: [
            "image/*",
            "application/pdf",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        ],

        labelIdle:
            'Drag & Drop file atau <span class="filepond--label-action">Browse</span>',
    });
}
