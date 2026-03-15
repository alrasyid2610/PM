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

            selectedRow.id = data.id_testing_standard;

            loadDetail(selectedRow.id);

            $(tableId + " tr").removeClass("table-active");
            $(this).addClass("table-active");

            const detailTab = new bootstrap.Tab(
                document.querySelector("#detail-tab"),
            );
            detailTab.show();
        });

    // Delete
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
            <form id="detailForm" class="row g-3">

                <input type="hidden" name="_token" value="${window.route.csrf}">
                <input type="hidden" name="_method" value="PUT">


                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Testing Standard</h5>
                        <div class="btn-group">
                            <button class="btn btn-warning btn-sm btn-edit-context">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Nomor</label>
                    <input type="text"
                           name="nomor"
                           class="form-control"
                           value="${res.nomor}"
                           required>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Judul</label>
                    <input type="text"
                           name="judul"
                           class="form-control"
                           value="${res.judul}"
                           required>
                </div>

                <div class="col-md-12">
                    <label class="form-label required">Status</label>
                    <select name="is_aktif"
                            class="form-select">
                        <option value="1" ${res.is_aktif == 1 ? "selected" : ""}>Aktif</option>
                        <option value="0" ${res.is_aktif == 0 ? "selected" : ""}>Tidak Aktif</option>
                    </select>
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

            </form>
        `;

        $("#detailContent").html(html);

        // Untuk element Attachment
        attachmentData = res.attachment;
        renderAttachments(attachmentData);
        $("#detailContent")
            .find("input, select, textarea")
            .prop("disabled", true);

        // Disable by default
        $("#detailContent").find("input, select").prop("disabled", true);

        // Edit toggle
        $(".btn-edit-context").on("click", function (e) {
            e.preventDefault();

            const $btn = $(this);
            const isEditing = $btn.hasClass("editing");

            if (!isEditing) {
                $("#detailContent")
                    .find("input, select")
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

                $("#attachmentUploader").show();
                initFilepondEdit();
                renderAttachments(attachmentData);

                $(".btn-save-context").on("click", function (e) {
                    e.preventDefault();
                    submitForm();
                });
            } else {
                $("#detailContent")
                    .find("input, select")
                    .prop("disabled", true);

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
            url: window.route.update + selectedRow.id,
            method: "POST", // gunakan POST + spoof PUT
            data: formData,
            processData: false,
            contentType: false,
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
