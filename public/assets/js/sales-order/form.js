// function renderForm(res) {
//     return `

// <form class="row g-3" id="detailForm">
//     <div class="col-md-12">
//         <div class="d-flex justify-content-between align-items-center mb-2">
//             <h3>Sales Orders</h3>
//             <div class="btn-group">
//                 <button class="btn btn-warning btn-sm btn-edit-context" title="Edit Testing Unit">
//                     <i class="fa-solid fa-pen"></i>
//                 </button>
//             </div>
//         </div>
//         <input type="hidden" name="_token" value="${window.route.csrf}">
//         <input type="hidden" name="id_contact" value="${res.id_contact}">

//         <div class="row mb-4 form-1">
//             <input type="hidden" name="_method" value="PUT">

//             <h6 class="fw-bold mb-1">Informasi Order</h6>

//             ${formGroup.date("tanggal_so", "Tanggal SO", res.tanggal_so, true, {
//                 className: "col-md-3",
//             })}

//             ${formGroup.text(
//                 "judul_order",
//                 "Judul Order",
//                 res.judul_order,
//                 true,
//                 {
//                     className: "col-md-9",
//                 },
//             )}

//              ${formGroup.date(
//                  "tanggal_mulai",
//                  "Tanggal Mulai",
//                  res.tanggal_mulai,
//                  true,
//                  {
//                      className: "col-md-4",
//                  },
//              )}

//              ${formGroup.date(
//                  "tanggal_selesai",
//                  "Tanggal Selesai",
//                  res.tanggal_selesai,
//                  true,
//                  {
//                      className: "col-md-4",
//                  },
//              )}

//              ${formGroup.select(
//                  "id_office",
//                  "Office",
//                  res.id_office,
//                  [
//                      { value: 1, label: "Pramatek Jakarta" },
//                      { value: 2, label: "Pramatek Bandung" },
//                  ],
//                  {
//                      className: "col-md-4",
//                  },
//              )}

//             <h6 class="fw-bold mb-1">PO</h6>

//             ${formGroup.checkbox(
//                 "tidak_ada_po",
//                 "Tidak Ada PO",
//                 res.tidak_ada_po,
//                 {
//                     className: "col-md-3",
//                     checkLabel: "Ada PO",
//                 },
//             )}

//             ${formGroup.date("tanggal_po", "Tanggal PO", res.tanggal_po, true, {
//                 className: "col-md-3",
//             })}

//             ${formGroup.text("no_po", "No PO", res.no_po, true, {
//                 className: "col-md-6",
//             })}

//             <h6 class="fw-bold mb-1">Pelanggan</h6>
//             ${formGroup.select(
//                 "id_pelanggan",
//                 "Pelanggan",
//                 res.id_pelanggan,
//                 [],
//                 {
//                     mode: "ajax",
//                     url: "business-relations/select2",
//                     placeholder: "Pilih Data",
//                     label: res.nama_pelanggan,
//                     className: "col-md-4",
//                 },
//             )}

//             ${formGroup.select(
//                 "id_site_pelanggan",
//                 "Pelanggan",
//                 res.id_site_pelanggan,
//                 [],
//                 {
//                     mode: "ajax",
//                     url: "business-relations/sites/select2",
//                     placeholder: "Pilih Data",
//                     label: res.nama_site_pelanggan,
//                     className: "col-md-4",
//                 },
//             )}

//             ${formGroup.select(
//                 "id_pic_pelanggan",
//                 "PIC",
//                 res.id_pic_pelanggan,
//                 [],
//                 {
//                     mode: "ajax",
//                     url: "business-relation-contacts/select2",
//                     placeholder: "Pilih Data",
//                     label: res.pic_pelanggan,
//                     className: "col-md-4",
//                 },
//             )}

//             <h6 class="fw-bold mb-1">Delivery</h6>
//             ${formGroup.select(
//                 "id_pelanggan_delivery",
//                 "Pelanggan",
//                 res.id_pelanggan_delivery,
//                 [],
//                 {
//                     mode: "ajax",
//                     url: "business-relations/select2",
//                     placeholder: "Pilih Data",
//                     label: res.pelanggan_delivery,
//                     className: "col-md-4",
//                 },
//             )}

//             ${formGroup.select(
//                 "id_site_pelanggan_delivery",
//                 "Pelanggan",
//                 res.id_site_pelanggan_delivery,
//                 [],
//                 {
//                     mode: "ajax",
//                     url: "business-relations/sites/select2",
//                     placeholder: "Pilih Data",
//                     label: res.pelanggan_site_delivery,
//                     className: "col-md-4",
//                 },
//             )}

//             ${formGroup.select(
//                 "id_pic_pelanggan_delivery",
//                 "PIC",
//                 res.id_pic_pelanggan_delivery,
//                 [],
//                 {
//                     mode: "ajax",
//                     url: "business-relation-contacts/select2",
//                     placeholder: "Pilih Data",
//                     label: res.pic_pelanggan_del,
//                     className: "col-md-4",
//                 },
//             )}

//             <h6 class="fw-bold mb-1">Payment</h6>
//             ${formGroup.select(
//                 "id_pelanggan_payment",
//                 "Pelanggan",
//                 res.id_pelanggan_payment,
//                 [],
//                 {
//                     mode: "ajax",
//                     url: "business-relations/select2",
//                     placeholder: "Pilih Data",
//                     label: res.pelanggan_pay,
//                     className: "col-md-4",
//                 },
//             )}

//             ${formGroup.select(
//                 "id_site_pelanggan_payment",
//                 "Pelanggan",
//                 res.id_site_pelanggan_payment,
//                 [],
//                 {
//                     mode: "ajax",
//                     url: "business-relations/sites/select2",
//                     placeholder: "Pilih Data",
//                     label: res.pelanggan_site_pay,
//                     className: "col-md-4",
//                 },
//             )}

//             ${formGroup.select(
//                 "id_pic_pelanggan_payment",
//                 "PIC",
//                 res.id_pic_pelanggan_payment,
//                 [],
//                 {
//                     mode: "ajax",
//                     url: "business-relation-contacts/select2",
//                     placeholder: "Pilih Data",
//                     label: res.pic_pelanggan_pay,
//                     className: "col-md-4",
//                 },
//             )}

//             <h6 class="fw-bold mb-1">PIC</h6>

//             ${formGroup.select("pic_input", "PIC Input", res.pic_input, [], {
//                 mode: "ajax",
//                 url: "business-relation-contacts/select2",
//                 placeholder: "Pilih Data",
//                 label: res.pic_input,
//                 className: "col-md-3",
//             })}

//             ${formGroup.select("pic_order", "PIC Order", res.pic_order, [], {
//                 mode: "ajax",
//                 url: "business-relation-contacts/select2",
//                 placeholder: "Pilih Data",
//                 label: res.pic_ordername,
//                 className: "col-md-3",
//             })}

//             ${formGroup.select(
//                 "pic_marketing_internal",
//                 "Marketing Internal",
//                 res.marketing_internal_id,
//                 [],
//                 {
//                     mode: "ajax",
//                     url: "business-relation-contacts/select2",
//                     placeholder: "Pilih Data",
//                     label: res.marketing_internal_name,
//                     className: "col-md-3",
//                 },
//             )}

//             ${formGroup.select(
//                 "pic_marketing_eksternal",
//                 "Marketing Eksternal",
//                 res.marketing_eksternal_id,
//                 [],
//                 {
//                     mode: "ajax",
//                     url: "business-relation-contacts/select2",
//                     placeholder: "Pilih Data",
//                     label: res.marketing_eksternal_name,
//                     className: "col-md-3",
//                 },
//             )}

//             ${formGroup.select(
//                 "status",
//                 "Status PO",
//                 res.status,
//                 [
//                     { value: 0, label: "Close" },
//                     { value: 1, label: "Complete" },
//                     { value: 2, label: "Cancel" },
//                 ],
//                 {
//                     className: "col-md-3",
//                 },
//             )}

//             ${formGroup.textarea(
//                 "keterangan_status",
//                 "Keterangan Status",
//                 res.keterangan_status,
//                 {
//                     className: "col-md-9",
//                 },
//             )}

//             ${formGroup.textarea("keterangan", "Keterangan", res.keterangan, {
//                 className: "col-md-12",
//             })}

//         </div>

//     </div>
// </form>

// `;
// }
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
                ${formGroup.editButton("Edit SO")}
            </div>
        </div>
    </div>

    <!-- SECTION 1: INFORMASI ORDER -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-navy">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div class="detail-section-title">Informasi Order</div>
                <div class="detail-section-sub">Data utama sales order</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
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
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: PURCHASE ORDER -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-amber">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div class="detail-section-title">Purchase Order (PO)</div>
                <div class="detail-section-sub">Referensi PO dari pelanggan</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
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
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3: DATA PELANGGAN -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-blue">
                    <i class="fa-solid fa-building-user"></i>
                </div>
                <div class="detail-section-title">Data Pelanggan</div>
                <div class="detail-section-sub">Billing, Delivery & Payment</div>
            </div>
            <div class="detail-section-body">

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
                        },
                    )}
                </div>

            </div>
        </div>
    </div>

    <!-- SECTION 4: PIC INTERNAL -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-green">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="detail-section-title">PIC Internal</div>
                <div class="detail-section-sub">Penanggung jawab dari Pramatek</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
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
                        },
                    )}
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 5: STATUS & KETERANGAN -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-purple">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div class="detail-section-title">Status & Keterangan</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
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
                </div>
            </div>
        </div>
    </div>

</form>
`;
}
