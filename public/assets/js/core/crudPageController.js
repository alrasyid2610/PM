class CrudPageController {
    constructor(options) {
        this.primaryKey = options.primaryKey;
        this.renderForm = options.renderForm;
        this.initSelect = options.initSelect || null;
        this.useAttachment = options.useAttachment || false;
        this.initDynamicTable = options.initDynamicTable || null;
        this.historyConfig = options.historyConfig || null;
        this.onSave = options.onSave || null;
        this.afterLoad = options.afterLoad || null;

        this.selectedRow = { id: null };

        this.attachmentData = [];
        this.pondEdit = null;

        this.dataTemp = "";

        this.init();
    }

    init() {
        const self = this;
        $("#history-tab").addClass("disabled").attr("disabled", true);

        new MasterCrudEngine({
            primaryKey: this.primaryKey,
            onRowClick: function (id) {
                console.log(id, " ini id yang diklik");
                self.selectedRow.id = id;
                self.loadDetail(id);
            },
            onAttachmentDeleted: function (id) {
                self.loadDetail(id); // 🔥 reload detail di sini
            },
        });

        $(document).on("shown.bs.tab", "#history-tab", function () {
            if (self.selectedRow.id) {
                self.loadHistory(self.selectedRow.id);
            }
        });

        // Auto-open detail jika URL mengandung ?open=ID
        const openId = new URLSearchParams(window.location.search).get('open');
        if (openId) {
            const id = parseInt(openId);
            if (!isNaN(id)) {
                // Tunggu DataTable selesai render baru buka detail
                setTimeout(function () {
                    self.selectedRow.id = id;
                    self.loadDetail(id);

                    // Pindah ke tab Detail jika ada tab system
                    const $detailTab = $('#detail-tab');
                    if ($detailTab.length) {
                        new bootstrap.Tab($detailTab[0]).show();
                    }
                }, 400);
            }
        }
    }

    loadDetail(id) {
        const self = this;

        // Enable history tab setelah row diklik
        $("#history-tab").removeClass("disabled").removeAttr("disabled");

        // ← tambah ini — reset history dulu setiap row baru diklik
        $("#historyContent").html(`
            <div class="text-center text-muted py-4">
                <i class="fa-solid fa-clock-rotate-left fa-2x mb-3 d-block"></i>
                Klik tab History untuk melihat riwayat perubahan
            </div>
        `);

        var a = loadDetailEngine({
            id: id,

            url: window.route.update,

            container: "#detailContent",

            render: this.renderForm,

            afterRender: async function (res) {
                $("#detailContent")
                    .find("input, select, textarea")
                    .not("[data-no-disable]")
                    .prop("disabled", true);

                // Inject refresh button — sebelum tombol Edit di manapun letaknya
                $("#detailContent").find('.btn-refresh-detail').remove();
                $("#detailContent").find('.btn-edit-context').first().before(
                    `<button type="button" class="btn-refresh-detail btn-action-secondary" title="Refresh data" data-no-disable>
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>`
                );
                $("#detailContent").off("click.refresh", ".btn-refresh-detail").on("click.refresh", ".btn-refresh-detail", function () {
                    const $icon = $(this).find('i');
                    $icon.addClass('fa-spin');
                    self.loadDetail(self.selectedRow.id);
                });

                if (self.initSelect) {
                    self.initSelect(res);
                }

                if (self.initDynamicTable) {
                    let itemsTable = new DynamicTable({
                        table: "#Table",
                        autoNumber: true,
                    });

                    $(".dynamic-table-wrapper")
                        .find("input, select, textarea, button")
                        .prop("disabled", true);
                    // $.get(
                    //     "/testing-items/by-point/" + res.id_testing_point,
                    //     function (res) {
                    //         window.dataBefore.push(res.data);
                    //         itemsTable.loadData(res.data);
                    //     },
                    // );
                    await loadItems(res, itemsTable);
                }

                if (self.useAttachment) {
                    self.attachmentData = res.attachment;
                    renderAttachments(self.attachmentData);
                }

                self.bindEditBehaviour();
                initDynamicSelect("#detailContent");

                // 🔥 SEKARANG BARU AMBIL INITIAL STATE
                window.initialForms = getAllFormsData();
                window.initialItems = getDynamicTableData();

                if (self.afterLoad) {
                    self.afterLoad(res);
                }
            },
        });
    }

    bindEditBehaviour() {
        const self = this;

        bindEditToggle({
            container: "#detailContent",

            onEditStart: function () {
                if (self.useAttachment) {
                    $("#attachmentUploader").show();

                    self.pondEdit = createFileUploader(".filepond-edit");

                    renderAttachments(self.attachmentData);
                }
            },

            onEditCancel: function () {
                if (self.useAttachment) {
                    $("#attachmentUploader").hide();

                    destroyUploader(self.pondEdit);

                    renderAttachments(self.attachmentData);
                }
            },

            onSave: function () {
                if (self.onSave) {
                    self.onSave(self.selectedRow.id);
                } else {
                    submitCrudForm({
                        id: self.selectedRow.id,
                        reload: self.loadDetail.bind(self),
                        filepond: self.pondEdit,
                    });
                }
            },
        });
    }

    loadHistory(id) {
        if (!window.route.history) return;

        $("#historyContent").html(`
            <div class="text-center text-muted py-4">
                <i class="fa-solid fa-spinner fa-spin me-2"></i> Memuat history...
            </div>
        `);

        $.get(window.route.history + id + "/history", (res) => {
            if (this.historyConfig) {
                this._renderMasterDetailHistory(res);
                return;
            }

            if (!res || res.length === 0) {
                $("#historyContent").html(`
                <div class="history-empty">
                    <div class="history-empty-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div class="history-empty-text">Belum ada riwayat perubahan</div>
                </div>
            `);
                return;
            }

            let html = `
            <div class="history-wrap">
                <div class="history-wrap-header">
                    <div class="history-wrap-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div class="history-wrap-title">Riwayat Perubahan</div>
                    <div class="history-wrap-count">${res.length} riwayat</div>
                </div>
                <div class="history-acc-list">
        `;

            res.forEach(function (log, index) {
                const date = new Date(log.created_at).toLocaleString("id-ID", {
                    day: "2-digit",
                    month: "short",
                    year: "numeric",
                    hour: "2-digit",
                    minute: "2-digit",
                });

                const action = log.action.toLowerCase();

                const actionClass =
                    {
                        create: "badge-create",
                        update: "badge-update",
                        delete: "badge-delete",
                    }[action] ?? "badge-update";

                const actionLabel =
                    {
                        create: "Dibuat",
                        update: "Diubah",
                        delete: "Dihapus",
                    }[action] ?? log.action;

                const initials = (log.created_by_name ?? "SY")
                    .split(" ")
                    .map((w) => w[0])
                    .join("")
                    .substring(0, 2)
                    .toUpperCase();

                // Tabel perubahan
                let tableHtml = "";
                if (log.changes && log.changes.length > 0) {
                    const rows = log.changes
                        .map(
                            (c) => `
                    <tr>
                        <td><span class="td-field">${c.field}</span></td>
                        <td>
                            <span class="td-old ${!c.old_value ? "empty" : ""}">
                                ${c.old_value ?? "kosong"}
                            </span>
                        </td>
                        <td>
                            <span class="td-new ${!c.new_value ? "empty" : ""}">
                                ${c.new_value ?? "kosong"}
                            </span>
                        </td>
                    </tr>
                `,
                        )
                        .join("");

                    tableHtml = `
                    <div class="acc-body">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Nilai Lama</th>
                                    <th>Nilai Baru</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                `;
                }

                // Open pertama secara default
                const isOpen =
                    index === 0 && log.changes && log.changes.length > 0
                        ? "open"
                        : "";

                html += `
                <div class="acc-item ${isOpen}">
                    <div class="acc-header">
                        <span class="acc-badge ${actionClass}">${actionLabel}</span>
                        <div class="acc-by">
                            <div class="acc-avatar">${initials}</div>
                            ${log.created_by_name ?? "System"}
                        </div>
                        ${
                            log.total_changes > 0
                                ? `<span class="acc-count">${log.total_changes} field berubah</span>`
                                : ""
                        }
                        <span class="acc-time">${date}</span>
                        ${
                            log.changes && log.changes.length > 0
                                ? `<span class="acc-chevron"><i class="fa-solid fa-chevron-down"></i></span>`
                                : ""
                        }
                    </div>
                    ${tableHtml}
                </div>
            `;
            });

            html += `</div></div>`;
            $("#historyContent").html(html);

            // Bind accordion toggle
            $("#historyContent").off("click", ".acc-header").on("click", ".acc-header", function () {
                const item = $(this).closest(".acc-item");
                if (!item.find(".acc-body").length) return;
                item.toggleClass("open");
            });
        }).fail(function () {
            $("#historyContent").html(`
            <div class="history-empty">
                <div class="history-empty-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="history-empty-text">Gagal memuat riwayat perubahan</div>
            </div>
        `);
        });
    }

    _renderMasterDetailHistory(res) {
        const cfg = this.historyConfig;
        const masterLabel = cfg.masterLabel ?? "Master";
        const linesLabel  = cfg.linesLabel  ?? "Lines";
        const displayFields = cfg.linesDisplayFields ?? [];

        if (!res || res.length === 0) {
            $("#historyContent").html(`
                <div class="history-empty">
                    <div class="history-empty-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div class="history-empty-text">Belum ada riwayat perubahan</div>
                </div>`);
            return;
        }

        let html = `
            <div class="history-wrap">
                <div class="history-wrap-header">
                    <div class="history-wrap-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div class="history-wrap-title">Riwayat Perubahan</div>
                    <div class="history-wrap-count">${res.length} riwayat</div>
                </div>
                <div class="history-acc-list">`;

        res.forEach((log, index) => {
            const date = new Date(log.created_at).toLocaleString("id-ID", {
                day: "2-digit", month: "short", year: "numeric",
                hour: "2-digit", minute: "2-digit",
            });

            const action      = (log.action ?? "").toLowerCase();
            const actionClass = { create: "badge-create", update: "badge-update", delete: "badge-delete" }[action] ?? "badge-update";
            const actionLabel = { create: "Dibuat", update: "Diubah", delete: "Dihapus" }[action] ?? log.action;

            const initials = (log.created_by_name ?? "SY")
                .split(" ").map(w => w[0]).join("").substring(0, 2).toUpperCase();

            const masterChanges = log.master_changes ?? [];
            const linesDiff     = log.lines_diff ?? { added: [], removed: [], modified: [] };
            const hasContent    = masterChanges.length > 0
                || linesDiff.added.length > 0
                || linesDiff.removed.length > 0
                || linesDiff.modified.length > 0;

            // --- Master section ---
            let masterHtml = "";
            if (masterChanges.length > 0) {
                const rows = masterChanges.map(c => `
                    <tr>
                        <td><span class="td-field">${c.field}</span></td>
                        <td><span class="td-old ${!c.old_value ? "empty" : ""}">${c.old_value ?? "kosong"}</span></td>
                        <td><span class="td-new ${!c.new_value ? "empty" : ""}">${c.new_value ?? "kosong"}</span></td>
                    </tr>`).join("");

                masterHtml = `
                    <div class="history-section-label">${masterLabel}</div>
                    <table class="history-table mb-3">
                        <thead><tr><th>Field</th><th>Nilai Lama</th><th>Nilai Baru</th></tr></thead>
                        <tbody>${rows}</tbody>
                    </table>`;
            }

            // --- Lines section ---
            let linesHtml = "";
            const { added, removed, modified } = linesDiff;

            if (added.length > 0 || removed.length > 0 || modified.length > 0) {
                let linesRows = "";

                added.forEach(row => {
                    const summary = displayFields.map(f => row[f] != null ? `<span class="td-field">${row[f]}</span>` : "").filter(Boolean).join(" · ");
                    linesRows += `<tr><td><span class="badge-create px-2 py-1 rounded">+</span></td><td colspan="3">${summary || "Baris baru ditambahkan"}</td></tr>`;
                });

                removed.forEach(row => {
                    const summary = displayFields.map(f => row[f] != null ? `<span class="td-field">${row[f]}</span>` : "").filter(Boolean).join(" · ");
                    linesRows += `<tr><td><span class="badge-delete px-2 py-1 rounded">−</span></td><td colspan="3">${summary || "Baris dihapus"}</td></tr>`;
                });

                modified.forEach(item => {
                    item.changes.forEach((c, ci) => {
                        const label = ci === 0
                            ? `<span class="badge-update px-2 py-1 rounded">~</span> ${item.label ?? ""}`
                            : "";
                        linesRows += `
                            <tr>
                                <td>${label}</td>
                                <td><span class="td-field">${c.field}</span></td>
                                <td><span class="td-old ${!c.old_value ? "empty" : ""}">${c.old_value ?? "kosong"}</span></td>
                                <td><span class="td-new ${!c.new_value ? "empty" : ""}">${c.new_value ?? "kosong"}</span></td>
                            </tr>`;
                    });
                });

                linesHtml = `
                    <div class="history-section-label">${linesLabel}</div>
                    <table class="history-table">
                        <thead><tr><th>Aksi</th><th>Field</th><th>Nilai Lama</th><th>Nilai Baru</th></tr></thead>
                        <tbody>${linesRows}</tbody>
                    </table>`;
            }

            const bodyContent = (masterHtml || linesHtml)
                ? `${masterHtml}${linesHtml}`
                : `<p class="text-muted mb-0" style="font-size:12px;">Tidak ada perubahan yang terdeteksi.</p>`;

            const bodyHtml = `<div class="acc-body">${bodyContent}</div>`;
            const isOpen = index === 0 ? "open" : "";

            html += `
                <div class="acc-item ${isOpen}">
                    <div class="acc-header">
                        <span class="acc-badge ${actionClass}">${actionLabel}</span>
                        <div class="acc-by">
                            <div class="acc-avatar">${initials}</div>
                            ${log.created_by_name ?? "System"}
                        </div>
                        ${log.total_changes > 0 ? `<span class="acc-count">${log.total_changes} perubahan</span>` : ""}
                        <span class="acc-time">${date}</span>
                        <span class="acc-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                    </div>
                    ${bodyHtml}
                </div>`;
        });

        html += `</div></div>`;
        $("#historyContent").html(html);

        $("#historyContent").off("click", ".acc-header").on("click", ".acc-header", function () {
            const item = $(this).closest(".acc-item");
            if (!item.find(".acc-body").length) return;
            item.toggleClass("open");
        });
    }
}
