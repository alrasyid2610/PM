function renderForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="id_contact" value="${res.id_contact}">

    <!-- ACTION BAR -->
    <div class="col-md-12">
        <div class="detail-action-bar">
            <div>
                <div class="detail-number">${res.no_so ?? "—"}</div>
                <div class="detail-date">
                    Dibuat ${res.created_at ?? "—"} &nbsp;·&nbsp; Diupdate ${res.updated_at ?? "—"}
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="detail-status-badge detail-status-${(res.status ?? "draft").toString().toLowerCase().replace(/\s+/g, "-")}">
                    <span class="detail-status-dot"></span>
                    ${res.status ?? "Draft"}
                </span>
                <span id="woBadge"
                    title="Lihat daftar Work Order"
                    style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;background:#e8f0fe;color:#1a56db;font-size:12px;font-weight:600;cursor:pointer;border:1px solid #c7d7f8;transition:background 0.15s;"
                    onmouseover="this.style.background='#c7d7f8'"
                    onmouseout="this.style.background='#e8f0fe'"
                    onclick="document.getElementById('wo-section')?.scrollIntoView({behavior:'smooth',block:'start'})">
                    <i class="fa-solid fa-briefcase" style="font-size:11px; transform: translateY(3px);"></i>
                    <span id="woBadgeCount">...</span> WO
                </span>
                ${formGroup.editButton("Edit SO")}
            </div>
        </div>
    </div>

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
                            label: res.pic_input,
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


    <!-- SECTION 6: PERIOD JADWAL -->
    ${formGroup.sectionCard(
        {
            icon: 'fa-calendar-days',
            color: 'icon-navy',
            title: 'Period Jadwal',
            subtitle: 'Jadwal kunjungan per lokasi dalam SO ini',
            id: 'so-period-section',
            actions: `<button type="button" id="btnTambahPeriodSo" class="btn btn-sm btn-outline-primary py-0 px-2"
                style="font-size:12px;" data-no-disable>
                <i class="fa-solid fa-plus me-1"></i> Tambah Period
            </button>`,
        },
        `<div id="soPeriodContent">
            <div class="text-center text-muted py-3">
                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
            </div>
        </div>`
    )}

    <!-- SECTION 7: WO Progress -->
    ${formGroup.sectionCard(
        {
            icon: "fa-briefcase",
            color: "icon-navy",
            title: "Work Order Progress",
            subtitle: "Status eksekusi BOQ per Work Order",
            id: "wo-section",
            actions: `<div class="d-flex align-items-center gap-2">
                <button type="button" id="btnRefreshWoProgress" data-so-id="${res.id_so}"
                    class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:12px;" title="Refresh">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
                <button type="button" id="btnToggleWoView"
                    class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:12px;" title="Tampilan Tabel">
                    <i class="fa-solid fa-table-list"></i>
                </button>
                <a href="/work-orders/create?id_so=${res.id_so}" target="_blank"
                    style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;background:#e8f0fe;color:#1a56db;font-size:12px;font-weight:600;text-decoration:none;border:1px solid #c7d7f8;transition:background 0.15s;"
                    onmouseover="this.style.background='#c7d7f8'" onmouseout="this.style.background='#e8f0fe'">
                    <i class="fa-solid fa-plus" style="font-size:11px;"></i> Tambah WO
                </a>
            </div>`,
        },
        `<div class="mb-3">
            <div class="input-group input-group-sm" style="max-width:320px;">
                <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0;">
                    <i class="fa-solid fa-magnifying-glass text-muted" style="font-size:11px;"></i>
                </span>
                <input type="text" id="woProgressSearch" class="form-control"
                    placeholder="Cari No WO atau No FWO..."
                    style="border-color:#e2e8f0;font-size:12px;"
                    data-no-disable>
                <button type="button" id="btnClearWoSearch" class="btn btn-outline-secondary d-none"
                    style="border-color:#e2e8f0;font-size:11px;" title="Hapus pencarian">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        </div>
        <div id="woProgressContent">
            <div class="text-center text-muted py-4">
                <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...
            </div>
        </div>`,
    )}

</form>
`;
}
