function renderForm(res) {
    return `
<form id="detailForm" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="id_testing_kelompok_matriks_sample" value="${res.id_testing_kelompok_matriks_sample}">

    ${formGroup.actionBar({
        number: escHtml(res.kode ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_testing_matriks_sample,
        editText: 'Edit Matriks Sample',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="msDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabMsInfo">
                        <i class="fa-solid fa-vials me-1" style="color:#0f766e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="msTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabMsInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-vials', color: 'icon-teal', title: 'Testing Matriks Samples', subtitle: 'Data matriks sampel pengujian' },
                            `<div class="row g-3 form-1">
                                ${formGroup.select("kelompok", "Kelompok", res.id_testing_kelompok_matriks_sample, [], {
                                    mode: "ajax", url: "testing-kelompok-matriks-samples/select2",
                                    placeholder: "Pilih Kelompok", label: res.kelompok_matriks_judul_indonesia,
                                    className: "col-md-12", createUrl: "/testing-kelompok-matriks-samples/create",
                                })}
                                ${formGroup.text("kode", "Kode", res.kode, true, { className: "col-md-4" })}
                                ${formGroup.text("judul_indonesia", "Judul Indonesia", res.judul_indonesia, true, { className: "col-md-4" })}
                                ${formGroup.text("judul_inggris", "Judul Inggris", res.judul_inggris, true, { className: "col-md-4" })}
                                ${formGroup.textarea("keterangan", "Keterangan", res.keterangan, { className: "col-md-12" })}
                            </div>`
                        )}

                    </div>
                </div>

            </div>
        </div>
    </div>

</form>
`;
}
