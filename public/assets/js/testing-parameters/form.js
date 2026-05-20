function renderParameterForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.kode ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_testing_parameter,
        editText: 'Edit Parameter',
    })}

    <!-- SECTION 1: INFORMASI PARAMETER -->
    ${formGroup.sectionCard(
        { icon: 'fa-flask', color: 'icon-amber', title: 'Informasi Parameter', subtitle: 'Data parameter pengujian laboratorium',  },
        `<div class="row g-3 form-1">
                    ${formGroup.select(
                        "kelompok",
                        "Kelompok",
                        res.kelompok,
                        [
                            { value: "Fisika", label: "Fisika" },
                            { value: "Kimia Logam", label: "Kimia Logam" },
                            {
                                value: "Kimia Non Logam",
                                label: "Kimia Non Logam",
                            },
                            { value: "Kimia Organik", label: "Kimia Organik" },
                            { value: "Mikrobiologi", label: "Mikrobiologi" },
                        ],
                        { className: "col-md-4" },
                    )}
                    ${formGroup.text("kode", "Kode", res.kode, true, {
                        className: "col-md-4",
                    })}
                    ${formGroup.text(
                        "judul_indonesia",
                        "Judul Indonesia",
                        res.judul_indonesia,
                        true,
                        {
                            className: "col-md-4",
                        },
                    )}
                    ${formGroup.text(
                        "judul_inggris",
                        "Judul Inggris",
                        res.judul_inggris,
                        false,
                        {
                            className: "col-md-4",
                        },
                    )}
                    ${formGroup.text(
                        "rumus_empiris",
                        "Rumus Empiris",
                        res.rumus_empiris,
                        false,
                        {
                            className: "col-md-4",
                        },
                    )}
                    ${formGroup.text(
                        "judul_iupac",
                        "Judul IUPAC",
                        res.judul_iupac,
                        false,
                        {
                            className: "col-md-4",
                        },
                    )}
                    ${formGroup.text(
                        "referensi",
                        "Referensi",
                        res.referensi,
                        false,
                        {
                            className: "col-md-12",
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
                </div>`
    )}

    <!-- SECTION 2: ATTACHMENT -->
    ${formGroup.sectionCard(
        { icon: 'fa-paperclip', color: 'icon-blue', title: 'Attachment', subtitle: 'File pendukung parameter' },
        `${renderAttachmentSection()}`
    )}

</form>
`;
}

