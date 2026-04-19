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
                    type="date"
                    name="${name}"
                    class="form-control disabled"
                    value="${value ?? ""}"
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
            minimumInputLength = 2,
            showAll = false,
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
};

function initDynamicSelect(scope = document) {
    $(scope)
        .find(".form-select-dynamic")
        .each(function () {
            let $el = $(this);
            let mode = $el.data("mode");

            if (mode !== "ajax") return;

            let showAll = $el.data("show-all") === true;
            let allowClear = $el.data("allow-clear") === true;
            let cachedData = null;
            let currentTerm = ""; // ← tambah variable untuk simpan term

            $el.select2({
                width: "100%",
                placeholder: $el.data("placeholder"),
                allowClear: allowClear,
                minimumInputLength: showAll ? 0 : $el.data("minimum-input"),
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
