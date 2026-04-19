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

function setDynamicFormState(disabled = true) {
    let table = $(".dynamic-table-wrapper");

    table.find("input, select, button").prop("disabled", disabled);

    // khusus select2 harus trigger ulang
    table
        .find(".parameter-select, .unit-select")
        .prop("disabled", disabled)
        .trigger("change.select2");
}

function getFormData(container) {
    let data = {};

    $(container)
        .find("input, select, textarea")
        .each(function () {
            let name = $(this).attr("name");
            if (!name) return;

            if (name === "_token" || name === "_method") return;

            name = name.replace("[]", "");

            if ($(this).is(":checkbox")) {
                data[name] = $(this).is(":checked") ? 1 : 0;
            } else {
                data[name] = $(this).val()?.trim();
            }
        });

    return data;
}

function getForm1Data() {
    let data = {};

    $(".form-1")
        .find("input, select, textarea")
        .each(function () {
            let name = $(this).attr("name");
            if (!name) return;

            if ($(this).is(":checkbox")) {
                data[name] = $(this).is(":checked") ? 1 : 0;
            } else {
                data[name] = $(this).val();
            }
        });

    return data;
}

function getAllFormsData() {
    let result = {};

    $('[class*="form-"]').each(function () {
        let classes = $(this).attr("class").split(" ");

        classes.forEach((cls) => {
            if (cls.startsWith("form-")) {
                result[cls] = getFormData(this);
            }
        });
    });

    return result;
}

function getDynamicTableData() {
    let table = $(".dynamic-table");

    if (!table.length) return [];

    let items = [];

    table.find("tbody tr").each(function () {
        let row = {};

        $(this)
            .find("[name]")
            .each(function () {
                let name = $(this).attr("name");
                let key = name.replace(/\[\]$/, "");
                if ($(this).attr("type") === "checkbox") {
                    row[key] = $(this).is(":checked") ? 1 : 0;
                } else {
                    row[key] = $(this).val();
                }
            });

        // Ambil id dari hidden field pertama untuk keperluan compareItems
        let firstHidden = $(this).find("input[type='hidden'][name]").first();
        if (firstHidden.length) {
            row.id = firstHidden.val();
        }

        items.push(row);
    });

    return items;
}

function compareItems(initial, current) {
    let result = {
        inserted: [],
        updated: [],
        deleted: [],
    };

    // 🔹 map initial by id
    let initialMap = {};
    initial.forEach((item) => {
        if (item.id) {
            initialMap[item.id] = item;
        }
    });

    // 🔹 current ids
    let currentIds = [];

    current.forEach((curr) => {
        // INSERT
        if (!curr.id) {
            result.inserted.push(curr);
            return;
        }

        currentIds.push(curr.id);

        let old = initialMap[curr.id];

        if (!old) return;

        // UPDATE
        if (JSON.stringify(old) !== JSON.stringify(curr)) {
            result.updated.push(curr);
        }
    });

    // 🔹 DELETE
    initial.forEach((old) => {
        if (old.id && !currentIds.includes(old.id)) {
            result.deleted.push(old.id);
        }
    });

    return result;
}

function loadItems(res, itemsTable) {
    return new Promise((resolve) => {
        $.get(
            "/testing-items/by-point/" + res.id_testing_point,
            function (res) {
                itemsTable.loadData(res.data);
                resolve(); // ✅ tandai selesai
            },
        );
    });
}
