function renderForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.nomor ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_termin,
        editText: 'Edit Termin',
    })}

    ${formGroup.sectionCard(
        { icon: 'fa-file-invoice-dollar', color: 'icon-navy', title: 'Termin', subtitle: 'Data termin pembayaran proyek' },
        `<div class="row g-3 form-1">
            ${formGroup.text("nomor", "Nomor", res.nomor, true, { className: "col-md-3" })}
            ${formGroup.text("nama", "Nama", res.nama, true, { className: "col-md-5" })}
            ${formGroup.text("persentase", "Persentase (%)", res.persentase, true, { className: "col-md-2", type: "number" })}
            ${formGroup.select(
                "status",
                "Status",
                res.status,
                [
                    { value: "pending", label: "Pending" },
                    { value: "proses",  label: "Proses" },
                    { value: "selesai", label: "Selesai" },
                ],
                { className: "col-md-2" }
            )}
            ${formGroup.text("nilai", "Nilai (Rp)", res.nilai, true, { className: "col-md-4", type: "number" })}
            ${formGroup.text("tanggal", "Tanggal", res.tanggal ? res.tanggal.substring(0, 10) : '', true, { className: "col-md-4", type: "date" })}
            ${formGroup.textarea("keterangan", "Keterangan", res.keterangan, false, { className: "col-md-4", rows: 1 })}
        </div>`
    )}

    ${formGroup.sectionCard(
        { icon: 'fa-paperclip', color: 'icon-blue', title: 'Attachment', subtitle: 'File pendukung termin' },
        `${renderAttachmentSection()}`
    )}

</form>
`;
}
