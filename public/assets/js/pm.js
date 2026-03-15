// Advance search
tableId = "#" + tableId;

$(document).ready(function () {
    if (
        document.getElementById("advanceSearchForm") &&
        document.getElementById("toggleAdvanceSearch")
    ) {
        console.log("Initializing advance search toggle...");
        toggleAdvanceSearch();
    }
});

$(document).ajaxStart(function () {
    $("#global-loader").fadeIn(150);
});

$(document).ajaxStop(function () {
    $("#global-loader").fadeOut(150);
});

function toggleAdvanceSearch() {
    const advanceSearch = document.getElementById("advanceSearchForm");
    const toggleBtn = document.getElementById("toggleAdvanceSearch");

    if (!advanceSearch || !toggleBtn) return;

    const bsCollapse = new bootstrap.Collapse(advanceSearch, { toggle: false });
    bsCollapse.hide();
    toggleBtn.textContent = "Show";

    toggleBtn.addEventListener("click", function () {
        const isShown = advanceSearch.classList.contains("show");
        isShown ? bsCollapse.hide() : bsCollapse.show();
        toggleBtn.textContent = isShown ? "Show" : "Hide";
    });
}

function initDataTable(tableId) {
    if ($(tableId).length > 0) {
        var autoColumns = $(tableId).data("datatable-auto-columns");

        if (autoColumns) {
            console.log("Initializing Auto DataTable for:", tableId);

            $.ajax({
                url: window.route.data,
                type: "GET",
                dataType: "json",
                success: function (json) {
                    let dataRows = json.data ?? [];

                    // Kalau kosong, kita tetap lanjut
                    let keys = json.header ?? [];

                    if (dataRows.length > 0) {
                        keys = Object.keys(dataRows[0]);
                        // filter
                        keys = keys.filter(
                            (key) =>
                                key !== "DT_RowIndex" && !key.startsWith("id_"),
                        );
                    }

                    // ===============================
                    // BUILD HEADER
                    // ===============================
                    let thead = "<tr>";
                    thead += "<th>No</th>";

                    keys.forEach((key) => {
                        let label = key
                            .replaceAll("_", " ")
                            .replace(/\b\w/g, (l) => l.toUpperCase());

                        thead += `<th>${label}</th>`;
                    });

                    thead += "</tr>";

                    $(tableId).find("thead").html(thead);

                    // ===============================
                    // BUILD COLUMN CONFIG
                    // ===============================
                    let columns = [
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function (data, type, row, meta) {
                                return meta.row + 1;
                            },
                        },
                    ];

                    keys.forEach((key) => {
                        columns.push({ data: key });
                    });

                    // ===============================
                    // INIT DATATABLE
                    // ===============================
                    table = $(tableId).DataTable({
                        data: dataRows,
                        columns: columns,
                        processing: true,
                        destroy: true,
                        language: {
                            emptyTable: "Data tidak ditemukan",
                        },
                    });
                },
                error: function (xhr) {
                    console.error("Gagal load data:", xhr);
                },
            });
        }
    }
}

$(document).on("click", ".attachment-image", function () {
    let src = $(this).attr("src");

    $("#previewImage").attr("src", src);

    $("#imagePreviewModal").modal("show");
});

function renderAttachments(attachments) {
    let files = [];

    if (!attachments) {
        $("#attachmentPreview").html(
            `<div class="text-muted">Tidak ada attachment</div>`,
        );
        return;
    }

    try {
        files =
            typeof attachments === "string"
                ? JSON.parse(attachments)
                : attachments;
    } catch (e) {
        files = [];
    }

    if (!files || files.length === 0) {
        $("#attachmentPreview").html(
            `<div class="text-muted">Tidak ada attachment</div>`,
        );
        return;
    }

    let html = "";

    files.forEach((file) => {
        let url = "/storage/" + file;
        let ext = file.split(".").pop().toLowerCase();
        let name = file.split("/").pop();

        let deleteBtn = "";

        if ($(".btn-edit-context").hasClass("editing")) {
            deleteBtn = `
                <button 
                    class="btn btn-sm btn-danger btn-delete-attachment"
                    data-file="${file}">
                    <i class="fa-solid fa-trash"></i>
                </button>
            `;
        }

        let preview = "";

        if (["jpg", "jpeg", "png", "gif", "webp"].includes(ext)) {
            preview = `
                <img src="${url}" 
                     class="img-fluid rounded attachment-image"
                     style="height:120px;object-fit:cover;cursor:pointer;">
            `;
        } else {
            preview = `
                <div class="attachment-icon">
                    <i class="fa-solid fa-file fa-2x text-secondary"></i>
                    <div class="small mt-1">${ext.toUpperCase()}</div>
                </div>
            `;
        }

        html += `
            <div class="col-md-3">

                <input type="hidden" name="existing_attachments[]" value="${file}">

                <div class="attachment-card border rounded p-2 text-center">

                    ${preview}

                    <p class="small mt-2">${name}</p>

                    <div class="mt-2 d-flex justify-content-center gap-2">

                        <a href="${url}" 
                           download
                           class="btn btn-sm btn-primary">
                           <i class="fa-solid fa-download"></i>
                        </a>

                        ${deleteBtn}

                    </div>

                </div>

            </div>
        `;
    });

    $("#attachmentPreview").html(html);
}

$(document).on("click", ".btn-delete-attachment", function (e) {
    e.preventDefault();
    let file = $(this).data("file");

    Notify.confirm("Hapus attachment ini?", function () {
        $.ajax({
            url: window.route.deleteAttachment,
            method: "POST",

            data: {
                id: selectedRow.id_testing_parameter,
                file: file,
                _token: window.route.csrf,
            },

            success: function () {
                Notify.success("Attachment berhasil dihapus");

                loadDetail(selectedRow.id_testing_parameter);
            },

            error: function () {
                Notify.error("Gagal menghapus attachment");
            },
        });
    });
});

function fillFormFromObject(data) {
    Object.keys(data).forEach(function (key) {
        if (["id", "text", "selected"].includes(key)) {
            return;
        }

        let el = $("#" + key);

        if (!el.length) {
            return;
        }

        if (el.hasClass("select2-hidden-accessible")) {
            el.val(data[key]).trigger("change");
        } else if (el.is("select")) {
            el.val(data[key]).trigger("change");
        } else {
            el.val(data[key]);
            el.text(data[key]);
        }
    });
}
