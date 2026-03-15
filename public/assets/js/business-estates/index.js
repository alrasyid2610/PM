let selectedRow = {
    id_bestate: null,
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
            selectedRow.id_bestate = data.id_bestate;
            loadDetail(data.id_bestate);
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

function loadDetail(id_bestate) {
    $("#detailContent").html("Loading...");

    $.get(window.route.update + id_bestate, function (res) {
        console.log(res);
        const html = `
            <form class="row g-3" id="detailForm">
                <input type="hidden" name="_token" value="${window.route.csrf}">
                <input type="hidden" name="_method" value="PUT">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3>Business Estate</h3>
                        <div class="btn-group">
                            <button class="btn btn-warning btn-sm btn-edit-context" title="Edit Business Estate">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row mb-4 g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Nama Estate</label>
                            <input type="text" name="nama" class="form-control" value="${res.nama}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Kode</label>
                            <input type="text" name="kode" class="form-control" value="${res.kode}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="is_aktif" class="form-select">
                                <option value="1" ${res.is_aktif == "1" ? "selected" : ""}>Aktif</option>
                                <option value="0" ${res.is_aktif == "0" ? "selected" : ""}>Non Aktif</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label required">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3" value="${res.alamat ?? ""}" required></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Provinsi</label>
                            <input type="text" name="provinsi" class="form-control" value="${res.provinsi}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Kota / Kabupaten</label>
                            <input type="text" name="kota_kabupaten" class="form-control" value="${res.kota_kabupaten}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Website</label>
                            <input type="text" name="website" class="form-control" value="${res.website ?? ""}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pemilik</label>
                            <input type="text" name="pemilik" class="form-control" value="${res.pemilik ?? ""}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pengurus</label>
                            <input type="text" name="pengurus" class="form-control" value="${res.pengurus ?? ""}">
                        </div>
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
            url: window.route.update + selectedRow.id_bestate,
            method: "POST", // gunakan POST + spoof PUT
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                Notify.success("Data berhasil diperbarui");
                loadDetail(selectedRow.id_bestate);
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
