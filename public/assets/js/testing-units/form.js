function renderUnitForm(res) {
    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.kode ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_testing_unit,
        deleteClass: 'btn-delete-unit',
        editText: 'Edit Testing Unit',
    })}

    ${formGroup.sectionCard(
        { icon: 'fa-ruler', color: 'icon-amber', title: 'Informasi Unit', subtitle: 'Data satuan pengujian laboratorium' },
        `<div class="row g-3 form-1">
            ${formGroup.text("kode", "Kode", res.kode, true, {
                className: "col-md-4",
            })}
            ${formGroup.text("judul_indonesia", "Judul Indonesia", res.judul_indonesia, true, {
                className: "col-md-4",
            })}
            ${formGroup.text("judul_inggris", "Judul Inggris", res.judul_inggris, true, {
                className: "col-md-4",
            })}
            ${formGroup.textarea("keterangan", "Keterangan", res.keterangan, {
                className: "col-md-12",
            })}
        </div>`
    )}

</form>
`;
}
