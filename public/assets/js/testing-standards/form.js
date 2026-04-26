function renderForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    <!-- SECTION 1: INFORMASI STANDARD -->
    ${formGroup.sectionCard(
        { icon: 'fa-book', color: 'icon-navy', title: 'Testing Standards', subtitle: 'Data standar pengujian laboratorium', editTitle: 'Edit Testing Standard' },
        `<div class="row g-3 form-1">
                    ${formGroup.text("nomor", "Nomor", res.nomor, true, {
                        className: "col-md-4",
                    })}
                    ${formGroup.text("judul", "Judul", res.judul, true, {
                        className: "col-md-6",
                    })}
                    ${formGroup.select(
                        "is_aktif",
                        "Status",
                        res.is_aktif,
                        [
                            { value: 1, label: "Aktif" },
                            { value: 0, label: "Tidak Aktif" },
                        ],
                        { className: "col-md-2" },
                    )}
                </div>`
    )}

    <!-- SECTION 2: ATTACHMENT -->
    ${formGroup.sectionCard(
        { icon: 'fa-paperclip', color: 'icon-blue', title: 'Attachment', subtitle: 'File pendukung standard' },
        `${renderAttachmentSection()}`
    )}

</form>
`;
}
