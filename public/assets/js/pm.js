function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

$(document).ready(function () {
    if (
        document.getElementById("advanceSearchForm") &&
        document.getElementById("toggleAdvanceSearch")
    ) {
        console.log("Initializing advance search toggle...");
        toggleAdvanceSearch();
    }
});

$(window).on('load', function () {
    $("#global-loader").fadeOut(400);
});

// Sticky action bar — scroll-based fixed positioning
// position:sticky tidak andal dalam layout flex/card bertumpuk.
// Solusi: gunakan position:fixed via scroll event, dijamin bekerja di semua kondisi.
(function () {
    var wrap = null;
    var placeholder = null;
    var isFixed = false;
    var naturalTop = null;
    var raf = null;

    function findWrap() {
        return document.querySelector('.detail-action-sticky-wrap');
    }

    function measure() {
        wrap = findWrap();
        if (!wrap || isFixed) return;
        naturalTop = wrap.getBoundingClientRect().top + window.pageYOffset;
    }

    function getMainLeft() {
        var main = document.getElementById('main');
        return main ? main.getBoundingClientRect().left : 0;
    }

    function applyFixed() {
        if (isFixed || !wrap) return;
        var h = wrap.offsetHeight;
        placeholder = document.createElement('div');
        placeholder.style.height = h + 'px';
        wrap.parentNode.insertBefore(placeholder, wrap);
        wrap.style.position = 'fixed';
        wrap.style.top = '0';
        wrap.style.left = getMainLeft() + 'px';
        wrap.style.right = '0';
        wrap.style.width = 'auto'; // override col-md-12 width:100% agar left+right yg menentukan lebar
        wrap.style.zIndex = '200';
        wrap.style.margin = '0';
        wrap.classList.add('is-stuck');
        isFixed = true;
    }

    function removeFixed() {
        if (!isFixed || !wrap) return;
        wrap.style.position = '';
        wrap.style.top = '';
        wrap.style.left = '';
        wrap.style.right = '';
        wrap.style.width = '';
        wrap.style.zIndex = '';
        wrap.style.margin = '';
        wrap.classList.remove('is-stuck');
        isFixed = false;
        if (placeholder) { placeholder.parentNode && placeholder.parentNode.removeChild(placeholder); placeholder = null; }
    }

    function tick() {
        if (!wrap) { measure(); return; }
        if (naturalTop === null) { measure(); return; }
        var scrollY = window.pageYOffset;
        if (scrollY > naturalTop - 1 && !isFixed) applyFixed();
        else if (scrollY <= naturalTop - 1 && isFixed) removeFixed();
    }

    function onScroll() {
        if (raf) cancelAnimationFrame(raf);
        raf = requestAnimationFrame(tick);
    }

    function reset() {
        removeFixed();
        wrap = null;
        naturalTop = null;
    }

    window.addEventListener('scroll', onScroll, { passive: true });

    window.addEventListener('resize', function () {
        if (isFixed && wrap) wrap.style.left = getMainLeft() + 'px';
        if (!isFixed) { naturalTop = null; measure(); }
    });

    // Re-anchor saat form baru di-render ke #detailContent
    new MutationObserver(function (mutations) {
        var hasBar = mutations.some(function (m) {
            return Array.from(m.addedNodes).some(function (n) {
                return n.nodeType === 1 && (
                    (n.classList && n.classList.contains('detail-action-sticky-wrap')) ||
                    (n.querySelector && n.querySelector('.detail-action-sticky-wrap'))
                );
            });
        });
        if (hasBar) { reset(); requestAnimationFrame(measure); }
    }).observe(document.body, { childList: true, subtree: true });

    measure();
}());

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


function renderAttachments(attachments) {
    let files = [];

    if (!attachments) {
        $("#attachmentPreview").html(
            `<div class="att-empty"><i class="fa-solid fa-paperclip"></i> Tidak ada attachment</div>`,
        );
        return;
    }

    try {
        files = typeof attachments === "string" ? JSON.parse(attachments) : attachments;
    } catch (e) {
        files = [];
    }

    if (!files || files.length === 0) {
        $("#attachmentPreview").html(
            `<div class="att-empty"><i class="fa-solid fa-paperclip"></i> Tidak ada attachment</div>`,
        );
        return;
    }

    const isEditing = $(".btn-edit-context").hasClass("editing");

    const iconMap = {
        jpg:  { icon: "fa-image",      bg: "#e8f0fe", color: "#1a5fbe" },
        jpeg: { icon: "fa-image",      bg: "#e8f0fe", color: "#1a5fbe" },
        png:  { icon: "fa-image",      bg: "#e8f0fe", color: "#1a5fbe" },
        gif:  { icon: "fa-image",      bg: "#e8f0fe", color: "#1a5fbe" },
        webp: { icon: "fa-image",      bg: "#e8f0fe", color: "#1a5fbe" },
        pdf:  { icon: "fa-file-pdf",   bg: "#fee2e2", color: "#dc2626" },
        xls:  { icon: "fa-file-excel", bg: "#dcfce7", color: "#166534" },
        xlsx: { icon: "fa-file-excel", bg: "#dcfce7", color: "#166534" },
        doc:  { icon: "fa-file-word",  bg: "#dbeafe", color: "#1d4ed8" },
        docx: { icon: "fa-file-word",  bg: "#dbeafe", color: "#1d4ed8" },
    };

    const rows = files.map((file) => {
        const url  = "/storage/" + file;
        const ext  = file.split(".").pop().toLowerCase();
        const name = file.split("/").pop();
        const ic   = iconMap[ext] ?? { icon: "fa-file", bg: "#f3f4f6", color: "#6b7280" };
        const isImage = ["jpg", "jpeg", "png", "gif", "webp"].includes(ext);

        const previewBtn = isImage
            ? `<button type="button" class="att-btn att-btn-preview attachment-image-trigger" data-src="${url}" title="Preview">
                   <i class="fa-solid fa-eye"></i>
               </button>`
            : "";

        const deleteBtn = isEditing
            ? `<button type="button" class="att-btn att-btn-delete btn-delete-attachment" data-file="${file}" title="Hapus">
                   <i class="fa-solid fa-trash"></i>
               </button>`
            : "";

        return `
            <div class="att-row">
                <input type="hidden" name="existing_attachments[]" value="${file}">
                <div class="att-icon" style="background:${ic.bg};color:${ic.color};">
                    <i class="fa-solid ${ic.icon}"></i>
                </div>
                <div class="att-info">
                    <span class="att-name" title="${name}">${name}</span>
                    <span class="att-ext">${ext.toUpperCase()}</span>
                </div>
                <div class="att-actions">
                    ${previewBtn}
                    <a href="${url}" download class="att-btn att-btn-download" title="Download">
                        <i class="fa-solid fa-download"></i>
                    </a>
                    ${deleteBtn}
                </div>
            </div>`;
    }).join("");

    $("#attachmentPreview").html(`<div class="att-list">${rows}</div>`);
}

$(document).on("click", ".attachment-image-trigger", function () {
    $("#previewImage").attr("src", $(this).data("src"));
    $("#imagePreviewModal").modal("show");
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
