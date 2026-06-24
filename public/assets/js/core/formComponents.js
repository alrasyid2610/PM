const formGroup = {
    text(name, label, value = "", required = false, config = {}) {
        let { className = "" } = config;
        return `
        <div class="${config.className + " mb-3" ?? "col-md-12 col-lg-3"}">

            <label class="form-label ${required ? "required" : ""}">
                ${label}
            </label>

            <input 
                type="text"
                name="${name}"
                class="form-control disabled"
                value="${value ?? ""}"
                ${required ? "required" : ""}
            >

        </div>
        `;
    },

    date(name, label, value = "", required = false, config = {}) {
        let { className = "" } = config;
        return `
            <div class="${config.className + " mb-3" ?? "col-md-12 col-lg-3"}">
                <label class="form-label ${required ? "required" : ""}">
                    ${label}
                </label>
                <input
                    type="text"
                    name="${name}"
                    class="form-control disabled fp-date"
                    value="${value ?? ""}"
                    placeholder="Pilih tanggal"
                    autocomplete="off"
                    ${required ? "required" : ""}
                >
            </div>
            `;
    },

    checkbox(name, label, value = 0, config = {}) {
        let { className = "", checkLabel = "Aktif" } = config;
        return `
        <div class="${config.className + " mb-3" ?? "col-md-12"}">
            <label class="form-label">${label}</label>
            <div class="form-check form-switch">
                <input
                    type="checkbox"
                    name="${name}"
                    id="detail_${name}"
                    class="form-check-input disabled"
                    value="1"
                    ${value == 1 ? "checked" : ""}
                >
                <label class="form-check-label" for="detail_${name}">
                    ${checkLabel}
                </label>
            </div>
        </div>
        `;
    },

    textarea(name, label, value = "", config = {}) {
        let { className = "" } = config;
        return `
        <div class="mb-3 ${config.className}">

            <label class="form-label">
                ${label}
            </label>

            <textarea 
                name="${name}"
                class="form-control disabled"
                rows="3"
            >${value ?? ""}</textarea>

        </div>
        `;
    },

    select(name, label, value, options = [], config = {}) {
        let {
            className = "",
            required = false,
            placeholder = "-- Pilih --",
            mode = "static", // static | ajax
            url = "",
            allowClear = false,
            minimumInputLength = 0,
            showAll = false,
            createUrl = null,
        } = config;

        let opts = "";

        // =========================
        // STATIC MODE
        // =========================
        if (mode === "static") {
            opts += `<option value="" selected>${placeholder}</option>`;

            opts += options
                .map(
                    (opt) => `
                <option 
                    value="${opt.value}"
                    ${opt.value == value ? "selected" : ""}
                >
                    ${opt.label}
                </option>
            `,
                )
                .join("");
        }

        // =========================
        // AJAX MODE
        // =========================
        if (mode === "ajax") {
            // Selalu sertakan option kosong agar name tetap terkirim saat tidak ada pilihan
            opts = `<option value=""></option>`;
            if (value && config.label) {
                opts = `<option value="${value}" selected>${config.label}</option>`;
            }
        }

        return `
            <div class="mb-3 ${className}">

                <label class="form-label ${required ? "required" : ""}">
                    ${label}
                </label>

                <select 
                    name="${name}"
                    class="form-select form-select-dynamic disabled"
                    id="detail_${name}"
                    data-mode="${mode}"
                    data-url="${url}"
                    data-allow-clear="${allowClear}" 
                    data-placeholder="${placeholder}"
                    data-minimum-input="${minimumInputLength}"
                    data-show-all="${showAll}"
                    ${createUrl ? `data-create-url="${createUrl}"` : ""}
                    ${required ? "required" : ""}
                >

                    ${opts}

                </select>

            </div>
        `;
    },

    wilayah(config = {}) {
        let {
            provinsiValue = "",
            kotaValue = "",
            kecamatanValue = "",
            kelurahanValue = "",
            kodePos = "",
            className = "col-md-12",
        } = config;

        return `
        <div class="${className}">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Provinsi</label>
                    <select
                        name="provinsi"
                        id="detail_provinsi"
                        class="form-select wilayah-provinsi disabled"
                        data-value="${provinsiValue ?? ""}"
                    >
                        ${
                            provinsiValue
                                ? `<option value="${provinsiValue}" selected>${provinsiValue}</option>`
                                : `<option value="">-- Pilih Provinsi --</option>`
                        }
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Kota / Kabupaten</label>
                    <select
                        name="kota_kabupaten"
                        id="detail_kota_kabupaten"
                        class="form-select wilayah-kota disabled"
                        data-value="${kotaValue ?? ""}"
                    >
                        ${
                            kotaValue
                                ? `<option value="${kotaValue}" selected>${kotaValue}</option>`
                                : `<option value="">-- Pilih Kota --</option>`
                        }
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Kecamatan</label>
                    <select
                        name="kecamatan"
                        id="detail_kecamatan"
                        class="form-select wilayah-kecamatan disabled"
                        data-value="${kecamatanValue ?? ""}"
                    >
                        ${
                            kecamatanValue
                                ? `<option value="${kecamatanValue}" selected>${kecamatanValue}</option>`
                                : `<option value="">-- Pilih Kecamatan --</option>`
                        }
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Kelurahan</label>
                    <select
                        name="kelurahan"
                        id="detail_kelurahan"
                        class="form-select wilayah-kelurahan disabled"
                        data-value="${kelurahanValue ?? ""}"
                    >
                        ${
                            kelurahanValue
                                ? `<option value="${kelurahanValue}" selected>${kelurahanValue}</option>`
                                : `<option value="">-- Pilih Kelurahan --</option>`
                        }
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Kode Pos</label>
                    <input
                        type="text"
                        name="kode_pos"
                        id="detail_kode_pos"
                        class="form-control disabled"
                        value="${kodePos ?? ""}"
                        placeholder="Kode Pos"
                    >
                </div>

            </div>
        </div>
        `;
    },

    editButton(title = "Edit") {
        return `<button class="btn-edit-context btn-action-edit ms-0" title="${title}">
            <i class="fa-solid fa-pen"></i> Edit
        </button>`;
    },

    actionBar({
        number,
        createdAt = "",
        updatedAt = "",
        subtitle = "",
        deleteId = null,
        deleteClass = "btn-delete-record",
        deleteText = "Hapus",
        editText = "",
        editHtml = "",
        badge = "",
        extra = "",
        leftExtra = "",
        statusBadge = "",
        tags = "",
        noWrap = false,
    }) {
        const editBtn = editHtml
            ? editHtml
            : editText
              ? formGroup.editButton(editText)
              : "";
        const deleteBtn =
            deleteId !== null
                ? `<button type="button" class="btn-action-danger ${deleteClass}" data-id="${deleteId}" data-no-disable><i class="fa-solid fa-trash"></i> ${deleteText}</button>`
                : "";
        const dateHtml = subtitle
            ? `<div class="detail-date">${subtitle}</div>`
            : createdAt || updatedAt
              ? `<div class="detail-date">Dibuat ${createdAt} &nbsp;·&nbsp; Diupdate ${updatedAt}</div>`
              : "";
        const numberEl = statusBadge
            ? `<div class="detail-number-row"><span class="detail-number">${number}</span>${statusBadge}</div>`
            : `<div class="detail-number">${number}</div>`;
        const tagsHtml = tags
            ? `<div class="detail-action-tags">${tags}</div>`
            : "";
        const wrapClass = noWrap
            ? "detail-action-sticky-wrap"
            : "col-md-12 detail-action-sticky-wrap";
        return `
        <div>
            <div class="${wrapClass}">
                <div class="detail-action-bar">
                    <div>
                        ${numberEl}
                        ${dateHtml}
                        ${tagsHtml}
                        ${leftExtra}
                    </div>
                    <div class="d-flex align-items-center gap-2" style="transform: translateY(-20px)">
                        ${badge}
                        ${editBtn}
                        ${deleteBtn}
                        ${extra}
                    </div>
                </div>
            </div>
        </div>`;
    },

    sectionCard(
        {
            icon,
            color,
            title,
            subtitle = null,
            editTitle = null,
            actions = "",
            id = null,
        },
        content,
    ) {
        return `
        <div class="col-md-12"${id ? ` id="${id}"` : ""}>
            <div class="detail-section-card" data-sc-open="true">
                <div class="detail-section-header">
                    <div class="detail-section-icon ${color}">
                        <i class="fa-solid ${icon}"></i>
                    </div>
                    <div class="detail-section-title">${title}</div>
                    ${subtitle ? `<div class="detail-section-sub">${subtitle}</div>` : ""}
                    ${editTitle ? formGroup.editButton(editTitle) : ""}
                    ${actions}
                    <div class="detail-section-icon" style="background-color:#e5e5e5;flex-shrink:0;cursor:pointer;" onclick="scToggle(this, event)">
                        <i class="fa-solid fa-chevron-up sc-chevron" style="transition:transform 0.25s;"></i>
                    </div>
                </div>
                <div class="sc-body">
                    <div class="detail-section-body">
                        ${content}
                    </div>
                </div>
            </div>
        </div>`;
    },
};

function scToggle(chevronDiv) {
    var card = chevronDiv.closest(".detail-section-card");
    var body = card.querySelector(".sc-body");
    var chevron = card.querySelector(".sc-chevron");
    var open = card.dataset.scOpen !== "false";
    card.dataset.scOpen = open ? "false" : "true";
    body.style.display = open ? "none" : "";
    chevron.style.transform = open ? "rotate(180deg)" : "rotate(0deg)";
}

function initDynamicSelect(scope = document) {
    $(scope)
        .find(".form-select-dynamic")
        .each(function () {
            let $el = $(this);
            let mode = $el.data("mode");

            if (mode === "static") {
                $el.select2({
                    width: "100%",
                    placeholder: $el.data("placeholder"),
                    allowClear: $el.data("allow-clear") === true,
                });
                return;
            }

            if (mode !== "ajax") return;

            let showAll = $el.data("show-all") === true;
            let allowClear = $el.data("allow-clear") === true;
            let createUrl = $el.data("create-url") || null;
            let cachedData = null;
            let currentTerm = "";

            const noResultsConfig = createUrl
                ? {
                      language: {
                          noResults: function () {
                              return `<span>Tidak ditemukan. <a href="${createUrl}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`;
                          },
                      },
                      escapeMarkup: function (m) {
                          return m;
                      },
                  }
                : {};

            $el.select2({
                width: "100%",
                placeholder: $el.data("placeholder"),
                allowClear: allowClear,
                minimumInputLength: showAll ? 0 : $el.data("minimum-input"),
                ...noResultsConfig,
                ajax: {
                    url: $el.data("url"),
                    delay: showAll ? 0 : 300,
                    dataType: "json",
                    data: function (params) {
                        currentTerm = params.term || ""; // ← simpan term di sini
                        return { q: showAll ? "" : currentTerm };
                    },
                    transport: function (params, success, failure) {
                        if (showAll && cachedData) {
                            let term = currentTerm.toLowerCase(); // ← pakai dari variable
                            let filtered = cachedData.filter(function (item) {
                                return item.text.toLowerCase().includes(term);
                            });
                            success(filtered);
                            return;
                        }

                        $.ajax(params)
                            .then(function (data) {
                                if (showAll) {
                                    cachedData = data;
                                }
                                success(data);
                            })
                            .fail(failure);
                    },
                    processResults: function (data) {
                        return { results: data };
                    },
                },
            });
        });
}

function renderAttachmentSection() {
    return `
<div class="col-md-12">
    <div id="attachmentPreview" class="row g-3"></div>
    <div id="attachmentUploader" class="mt-3" style="display:none">
        <input type="file" class="filepond-edit" name="attachments[]" multiple>
    </div>
</div>
`;
}
