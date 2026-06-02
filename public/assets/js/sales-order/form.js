function renderForm(res) {
    const statusKey = (res.status ?? "draft")
        .toString()
        .toLowerCase()
        .replace(/\s+/g, "-");
    const pelangganTag = res.nama_pelanggan
        ? `<span class="pm-badge" style="background:#f1f5f9;color:#475569;">
               <i class="fa-solid fa-building" style="font-size:10px;"></i>
               ${escHtml(res.nama_pelanggan)}
           </span>`
        : "";

    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="id_contact" value="${res.id_contact}">

    ${formGroup.actionBar({
        number: escHtml(res.no_so ?? "—"),
        createdAt: escHtml(res.created_at ?? "—"),
        updatedAt: escHtml(res.updated_at ?? "—"),
        deleteId: res.id_so,
        editText: "Edit SO",
        statusBadge: `<span class="detail-status-inline detail-status-${statusKey}">${escHtml(res.status ?? "Draft")}</span>`,
        tags: pelangganTag,
        noWrap: true,
    })}

    <!-- KPI ROW -->
    <div class="detail-kpi-section">
        <div class="pm-kpi-row" id="soSummaryCard"></div>
    </div>

    <!-- TABS: Informasi | Work Orders | Termin -->
    <div class="pm-tab-card">
            <div class="pm-tab-header">
                <ul class="pm-tab-nav" id="soDetailTabs" role="tablist">
                    <li role="presentation">
                        <button class="pm-tab-btn active" id="tab-info-so-btn" type="button" role="tab"
                            data-bs-toggle="tab" data-bs-target="#tabInfoSo">
                            <i class="fa-solid fa-circle-info me-1" style="color:#6366f1;font-size:11px;"></i>
                            Informasi
                        </button>
                    </li>
                    <li role="presentation">
                        <button class="pm-tab-btn" type="button" role="tab"
                            data-bs-toggle="tab" data-bs-target="#tabWo">
                            <i class="fa-solid fa-briefcase me-1" style="color:#1a56db;font-size:11px;"></i>
                            Work Orders
                        </button>
                    </li>
                    <li role="presentation">
                        <button class="pm-tab-btn" type="button" role="tab"
                            data-bs-toggle="tab" data-bs-target="#tabTermin">
                            <i class="fa-solid fa-file-invoice-dollar me-1" style="color:#7c3aed;font-size:11px;"></i>
                            Termin
                        </button>
                    </li>
                </ul>
                <div class="pm-tab-actions">
                    <div id="soTabActionsInfo" class="d-flex align-items-center gap-2">
                        <!-- Edit/Hapus ada di action bar atas -->
                    </div>
                    <div id="soTabActionsWo" class="d-flex align-items-center gap-2 d-none">
                        <button type="button" id="btnRefreshWoProgress" data-so-id="${res.id_so}"
                            class="pm-btn-icon" title="Refresh" data-no-disable>
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                        <button type="button" class="pm-btn-pill pm-btn-pill--blue btn-add-wo-modal"
                            data-so-id="${res.id_so}" data-no-disable>
                            <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                            <i class="fa-solid fa-briefcase" style="font-size:11px;"></i> WO
                        </button>
                    </div>
                    <div id="soTabActionsTermin" class="d-flex align-items-center gap-2 d-none">
                        <button type="button" id="btnRefreshTermin" data-so-id="${res.id_so}"
                            class="pm-btn-icon" title="Refresh" data-no-disable>
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                        <button type="button" class="pm-btn-pill pm-btn-pill--purple btn-add-termin-modal"
                            data-so-id="${res.id_so}" data-no-disable>
                            <i class="fa-solid fa-plus" style="font-size:10px;"></i>
                            <i class="fa-solid fa-file-invoice-dollar" style="font-size:11px;"></i> Termin
                        </button>
                    </div>
                </div>
            </div>
            <div class="pm-tab-body">
                <div class="tab-content">

                    <!-- TAB: INFORMASI SO -->
                    <div class="tab-pane fade show active" id="tabInfoSo" role="tabpanel">
                        <div class="row g-3">

    <!-- SECTION 1: INFORMASI ORDER -->
    ${formGroup.sectionCard(
        {
            icon: "fa-file-lines",
            color: "icon-navy",
            title: "Informasi Order",
            subtitle: "Data utama sales order",
        },
        `<div class="row g-3 form-1">
                    ${formGroup.date(
                        "tanggal_so",
                        "Tanggal SO",
                        res.tanggal_so,
                        true,
                        {
                            className: "col-md-3",
                        },
                    )}
                    ${formGroup.text(
                        "judul_order",
                        "Judul Order",
                        res.judul_order,
                        true,
                        {
                            className: "col-md-9",
                        },
                    )}
                    ${formGroup.date(
                        "tanggal_mulai",
                        "Tanggal Mulai",
                        res.tanggal_mulai,
                        true,
                        {
                            className: "col-md-4",
                        },
                    )}
                    ${formGroup.date(
                        "tanggal_selesai",
                        "Tanggal Selesai",
                        res.tanggal_selesai,
                        true,
                        {
                            className: "col-md-4",
                        },
                    )}
                    ${formGroup.select(
                        "id_office",
                        "Office",
                        res.id_office,
                        [
                            { value: 1, label: "Pramatek Jakarta" },
                            { value: 2, label: "Pramatek Bandung" },
                        ],
                        { className: "col-md-4" },
                    )}
                    ${formGroup.select(
                        "id_sc",
                        "Sales Contract",
                        res.id_sc,
                        [],
                        {
                            mode: "ajax",
                            url: "/contracts/select2",
                            placeholder:
                                "Cari no. kontrak atau nama pelanggan...",
                            label: res.id_sc
                                ? (res.contract_no || "SC #" + res.id_sc) +
                                  (res.contract_no_client
                                      ? " / " + res.contract_no_client
                                      : "")
                                : null,
                            className: "col-md-6",
                            allowClear: true,
                            createUrl: "/contracts/create",
                        },
                    )}
                </div>`,
    )}

    <!-- SECTION 2: PURCHASE ORDER -->
    ${formGroup.sectionCard(
        {
            icon: "fa-receipt",
            color: "icon-amber",
            title: "Purchase Order (PO)",
            subtitle: "Referensi PO dari pelanggan",
        },
        `<div class="row g-3 form-1">
                    ${formGroup.checkbox(
                        "tidak_ada_po",
                        "Tidak Ada PO",
                        res.tidak_ada_po,
                        {
                            className: "col-md-12",
                            checkLabel: "Ada PO",
                        },
                    )}
                    ${formGroup.date(
                        "tanggal_po",
                        "Tanggal PO",
                        res.tanggal_po,
                        false,
                        {
                            className: "col-md-3",
                        },
                    )}
                    ${formGroup.text("no_po", "No PO", res.no_po, false, {
                        className: "col-md-9",
                    })}
                </div>`,
    )}

    <!-- SECTION 3: DATA PELANGGAN -->
    ${formGroup.sectionCard(
        {
            icon: "fa-building-user",
            color: "icon-blue",
            title: "Data Pelanggan",
            subtitle: "Billing, Delivery & Payment",
        },
        `
                <!-- Desktop: party header -->
                <div class="detail-party-header d-none d-md-grid">
                    <div class="detail-party-label">
                        <i class="fa-solid fa-file-invoice me-1"></i> Billing (Pemesan)
                    </div>
                    <div class="detail-party-label">
                        <i class="fa-solid fa-truck me-1"></i> Delivery (Pengiriman)
                    </div>
                    <div class="detail-party-label">
                        <i class="fa-solid fa-money-bill me-1"></i> Payment (Pembayaran)
                    </div>
                </div>

                <!-- Perusahaan -->
                <div class="row g-3 form-1">
                    <div class="col-12 d-md-none">
                        <div class="detail-mobile-section-label">
                            <i class="fa-solid fa-file-invoice me-1"></i> Billing (Pemesan)
                        </div>
                    </div>
                    ${formGroup.select(
                        "id_pelanggan",
                        "Perusahaan",
                        res.id_pelanggan,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relations/select2",
                            placeholder: "Pilih Data",
                            label: res.nama_pelanggan,
                            className: "col-md-4",
                            createUrl: "/business-relations/create",
                        },
                    )}
                    <div class="col-12 d-md-none">
                        <div class="detail-mobile-section-label">
                            <i class="fa-solid fa-truck me-1"></i> Delivery (Pengiriman)
                        </div>
                    </div>
                    ${formGroup.select(
                        "id_pelanggan_delivery",
                        "Perusahaan",
                        res.id_pelanggan_delivery,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relations/select2",
                            placeholder: "Pilih Data",
                            label: res.pelanggan_delivery,
                            className: "col-md-4",
                            createUrl: "/business-relations/create",
                        },
                    )}
                    <div class="col-12 d-md-none">
                        <div class="detail-mobile-section-label">
                            <i class="fa-solid fa-money-bill me-1"></i> Payment (Pembayaran)
                        </div>
                    </div>
                    ${formGroup.select(
                        "id_pelanggan_payment",
                        "Perusahaan",
                        res.id_pelanggan_payment,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relations/select2",
                            placeholder: "Pilih Data",
                            label: res.pelanggan_pay,
                            className: "col-md-4",
                            createUrl: "/business-relations/create",
                        },
                    )}
                </div>

                <!-- Site -->
                <div class="row g-3 form-1">
                    ${formGroup.select(
                        "id_site_pelanggan",
                        "Site",
                        res.id_site_pelanggan,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relations/sites/select2",
                            placeholder: "Pilih Data",
                            label: res.nama_site_pelanggan,
                            className: "col-md-4",
                            createUrl: "/business-relations/create",
                        },
                    )}
                    ${formGroup.select(
                        "id_site_pelanggan_delivery",
                        "Site",
                        res.id_site_pelanggan_delivery,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relations/sites/select2",
                            placeholder: "Pilih Data",
                            label: res.pelanggan_site_delivery,
                            className: "col-md-4",
                            createUrl: "/business-relations/create",
                        },
                    )}
                    ${formGroup.select(
                        "id_site_pelanggan_payment",
                        "Site",
                        res.id_site_pelanggan_payment,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relations/sites/select2",
                            placeholder: "Pilih Data",
                            label: res.pelanggan_site_pay,
                            className: "col-md-4",
                            createUrl: "/business-relations/create",
                        },
                    )}
                </div>

                <!-- PIC -->
                <div class="row g-3 form-1">
                    ${formGroup.select(
                        "id_pic_pelanggan",
                        "PIC",
                        res.id_pic_pelanggan,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relation-contacts/select2",
                            placeholder: "Pilih Data",
                            label: res.pic_pelanggan,
                            className: "col-md-4",
                            createUrl: "/business-relation-contacts/create",
                        },
                    )}
                    ${formGroup.select(
                        "id_pic_pelanggan_delivery",
                        "PIC",
                        res.id_pic_pelanggan_delivery,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relation-contacts/select2",
                            placeholder: "Pilih Data",
                            label: res.pic_pelanggan_del,
                            className: "col-md-4",
                            createUrl: "/business-relation-contacts/create",
                        },
                    )}
                    ${formGroup.select(
                        "id_pic_pelanggan_payment",
                        "PIC",
                        res.id_pic_pelanggan_payment,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relation-contacts/select2",
                            placeholder: "Pilih Data",
                            label: res.pic_pelanggan_pay,
                            className: "col-md-4",
                            createUrl: "/business-relation-contacts/create",
                        },
                    )}
                </div>
            `,
    )}

    <!-- SECTION 4: PIC INTERNAL -->
    ${formGroup.sectionCard(
        {
            icon: "fa-users",
            color: "icon-green",
            title: "PIC Internal",
            subtitle: "Penanggung jawab dari Pramatek",
        },
        `<div class="row g-3 form-1">
                    ${formGroup.select(
                        "pic_input",
                        "PIC Input",
                        res.pic_input,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relation-contacts/select2",
                            placeholder: "Pilih Data",
                            label: res.pic_input_name,
                            className: "col-md-3",
                            createUrl: "/business-relation-contacts/create",
                        },
                    )}
                    ${formGroup.select(
                        "pic_order",
                        "PIC Order",
                        res.pic_order,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relation-contacts/select2",
                            placeholder: "Pilih Data",
                            label: res.pic_ordername,
                            className: "col-md-3",
                            createUrl: "/business-relation-contacts/create",
                        },
                    )}
                    ${formGroup.select(
                        "pic_marketing_internal",
                        "Marketing Internal",
                        res.marketing_internal_id,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relation-contacts/select2",
                            placeholder: "Pilih Data",
                            label: res.marketing_internal_name,
                            className: "col-md-3",
                            createUrl: "/business-relation-contacts/create",
                        },
                    )}
                    ${formGroup.select(
                        "pic_marketing_eksternal",
                        "Marketing Eksternal",
                        res.marketing_eksternal_id,
                        [],
                        {
                            mode: "ajax",
                            url: "business-relation-contacts/select2",
                            placeholder: "Pilih Data",
                            label: res.marketing_eksternal_name,
                            className: "col-md-3",
                            createUrl: "/business-relation-contacts/create",
                        },
                    )}
                </div>`,
    )}

    <!-- SECTION 5: STATUS & KETERANGAN -->
    ${formGroup.sectionCard(
        {
            icon: "fa-circle-info",
            color: "icon-purple",
            title: "Status & Keterangan",
            subtitle: "Kete",
        },
        `<div class="row g-3 form-1">
                    ${formGroup.select(
                        "status",
                        "Status SO",
                        res.status,
                        [
                            { value: "Close", label: "Close" },
                            { value: "Complete", label: "Complete" },
                            { value: "Cancel", label: "Cancel" },
                            { value: "Draft", label: "Draft" },
                        ],
                        { className: "col-md-3" },
                    )}
                    ${formGroup.text(
                        "keterangan_status",
                        "Keterangan Status",
                        res.keterangan_status,
                        false,
                        {
                            className: "col-md-9",
                        },
                    )}
                    ${formGroup.textarea(
                        "keterangan",
                        "Keterangan",
                        res.keterangan,
                        {
                            className: "col-md-12",
                        },
                    )}
                </div>`,
    )}

                        </div>
                    </div><!-- /tabInfoSo -->

                    <!-- TAB: WORK ORDERS -->
                    <div class="tab-pane fade" id="tabWo" role="tabpanel">
                        <div class="card card-body">
                            <div class="mb-3">
                                <div class="pm-search">
                                    <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" id="woProgressSearch"
                                        placeholder="Cari No WO atau judul..." data-no-disable>
                                    <button type="button" id="btnClearWoSearch" class="pm-search-clear d-none" title="Hapus">
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="woProgressContent">
                                <div class="text-center text-muted py-4">
                                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: TERMIN -->
                    <div class="tab-pane fade" id="tabTermin" role="tabpanel">
                        <div class="card card-body">
                            <div id="terminContent">
                                <div class="text-center text-muted py-4">
                                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

</form>
`;
}
