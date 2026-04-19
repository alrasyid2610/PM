function renderForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <!-- SECTION 1: INFORMASI WORK ORDER -->
    <div class="col-md-12">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-navy">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
                <div class="detail-section-title">Informasi Work Order</div>
                <div class="detail-section-sub">Data pekerjaan lapangan</div>
                ${formGroup.editButton("Edit Work Order")}
            </div>
            <div class="detail-section-body">
                <div class="row g-3 form-1">
                    <div class="col-md-4">
                        <label class="form-label">No WO</label>
                        <p class="form-control" style="margin: 0; line-height: 1.5;">${res.no_wo ?? "-"}</p>
                    </div>
                    ${formGroup.text(
                        "judul_order",
                        "Judul Order",
                        res.judul_pekerjaan,
                        true,
                        {
                            className: "col-md-8",
                        },
                    )}
                    ${formGroup.select("id_so", "Sales Order", res.id_so, [], {
                        mode: "ajax",
                        url: "/sales-orders/select2",
                        placeholder: "Pilih Sales Order",
                        label: res.no_so,
                        className: "col-md-12",
                    })}
                    ${formGroup.select(
                        "id_pelanggan",
                        "Pelanggan",
                        res.id_pelanggan_pekerjaan,
                        [],
                        {
                            mode: "ajax",
                            url: "/business-relations/select2",
                            placeholder: "Pilih Pelanggan",
                            label: res.nama_pelanggan_pekerjaan,
                            className: "col-md-5",
                        },
                    )}
                    ${formGroup.select(
                        "id_site_pelanggan",
                        "Site Pelanggan",
                        res.id_site_pelanggan_pekerjaan,
                        [],
                        {
                            mode: "ajax",
                            url: "/business-relations/sites/select2",
                            placeholder: "Pilih Site",
                            label: res.nama_site_pelanggan_pekerjaan,
                            className: "col-md-5",
                        },
                    )}
                    ${formGroup.select(
                        "pic_pekerjaan",
                        "PIC Pekerjaan",
                        res.id_pic_pelanggan_pekerjaan,
                        [],
                        {
                            mode: "ajax",
                            url: "/business-relation-contacts/select2",
                            placeholder: "Pilih PIC",
                            label: res.nama_pic_pelanggan_pekerjaan,
                            className: "col-md-2",
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
