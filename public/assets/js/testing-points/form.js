function renderForm(res) {
    return `
<form id="detailForm" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="id_testing_standard" value="${res.id_testing_standard}">
    <input type="hidden" name="id_testing_matriks_sample" value="${res.id_testing_matriks_sample}">

    ${formGroup.actionBar({
        number: escHtml(res.nama ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_testing_point,
        editText: 'Edit Testing Point',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="pointDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabPointInfo">
                        <i class="fa-solid fa-map-pin me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabPointItems">
                        <i class="fa-solid fa-table-list me-1" style="color:#16a34a;font-size:11px;"></i>
                        Testing Items
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="pointTabActionsInfo" class="d-flex align-items-center gap-2"></div>
                <div id="pointTabActionsItems" class="d-flex align-items-center gap-2 d-none"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <!-- TAB: INFORMASI -->
                <div class="tab-pane fade show active" id="tabPointInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-map-pin', color: 'icon-navy', title: 'Testing Points', subtitle: 'Data titik pengujian laboratorium' },
                            `<div class="row g-3 form-1">
                                ${formGroup.select("id_testing_standard", "Testing Standard", res.id_testing_standard, [], {
                                    mode: "ajax", url: "testing-standards/select2",
                                    placeholder: "Pilih Standard", label: res.standard_judul,
                                    className: "col-md-6", createUrl: "/testing-standards/create",
                                })}
                                ${formGroup.select("id_testing_matriks_sample", "Testing Matriks Sample", res.id_testing_matriks_sample, [], {
                                    mode: "ajax", url: "testing-matriks-samples/select2",
                                    placeholder: "Pilih Matriks Sample", label: res.matrik_sample_judul_indonesia,
                                    className: "col-md-6", createUrl: "/testing-matriks-samples/create",
                                })}
                                ${formGroup.text("nama", "Nama", res.nama, true, { className: "col-md-6" })}
                                ${formGroup.text("nomor_halaman", "Nomor Halaman", res.nomor_halaman, true, { className: "col-md-4" })}
                                ${formGroup.select("is_aktif", "Status", res.is_aktif,
                                    [
                                        { value: 1, label: "Aktif" },
                                        { value: 0, label: "Tidak Aktif" },
                                    ],
                                    { className: "col-md-2" }
                                )}
                                ${formGroup.textarea("deskripsi", "Deskripsi", res.deskripsi, { className: "col-md-12" })}
                                ${formGroup.textarea("keterangan_point", "Keterangan", res.keterangan, { className: "col-md-12" })}
                            </div>`
                        )}

                        ${formGroup.sectionCard(
                            { icon: 'fa-paperclip', color: 'icon-blue', title: 'Attachment', subtitle: 'File pendukung testing point' },
                            `${renderAttachmentSection()}`
                        )}

                    </div>
                </div>

                <!-- TAB: TESTING ITEMS -->
                <div class="tab-pane fade" id="tabPointItems" role="tabpanel">
                    <div class="card card-body">
                        ${formGroup.sectionCard(
                            { icon: 'fa-table-list', color: 'icon-green', title: 'Testing Items', subtitle: 'Detail item pengujian per point' },
                            `<div class="dynamic-table-wrapper">
                                <div class="p-3 pb-0">
                                    <button type="button" class="btn btn-primary btn-sm btn-add-row">
                                        <i class="fa-solid fa-plus me-1"></i> Tambah Baris
                                    </button>
                                </div>
                                <div class="table-responsive p-3">
                                    <table id="Table" class="table table-bordered table-sm dynamic-table mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="white-space:nowrap;width:40px">No</th>
                                                <th style="min-width:240px">Judul Indonesia</th>
                                                <th style="min-width:240px">Judul Inggris</th>
                                                <th style="min-width:160px">Parameter</th>
                                                <th style="min-width:140px">Unit</th>
                                                <th style="min-width:120px">Nilai</th>
                                                <th style="min-width:140px">Keterangan</th>
                                                <th style="white-space:nowrap;width:60px">Status</th>
                                                <th style="white-space:nowrap;width:60px">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <input type="hidden" name="id_testing_item[]" value="">
                                                <input type="hidden" name="nomor[]" value="">
                                                <td class="row-number"></td>
                                                <td><input type="text" name="judul_indonesia[]" class="form-control form-control-sm"></td>
                                                <td><input type="text" name="judul_inggris[]" class="form-control form-control-sm"></td>
                                                <td><select name="parameter[]" class="form-control form-control-sm parameter-select"></select></td>
                                                <td><select name="unit[]" class="form-control form-control-sm unit-select"></select></td>
                                                <td><input type="text" name="nilai[]" class="form-control form-control-sm"></td>
                                                <td><input type="text" name="keterangan[]" class="form-control form-control-sm"></td>
                                                <td class="text-center"><input type="checkbox" name="status[]" value="1"></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary px-2 btn-row-action">
                                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <script type="text/template" id="row-template">
                                    <tr>
                                        <input type="hidden" name="id_testing_item[]" value="">
                                        <input type="hidden" name="nomor[]" value="">
                                        <td class="row-number"></td>
                                        <td><input type="text" name="judul_indonesia[]" class="form-control form-control-sm"></td>
                                        <td><input type="text" name="judul_inggris[]" class="form-control form-control-sm"></td>
                                        <td><select name="parameter[]" class="form-control form-control-sm parameter-select"></select></td>
                                        <td><select name="unit[]" class="form-control form-control-sm unit-select"></select></td>
                                        <td><input type="text" name="nilai[]" class="form-control form-control-sm"></td>
                                        <td><input type="text" name="keterangan[]" class="form-control form-control-sm"></td>
                                        <td class="text-center"><input type="checkbox" name="status[]" value="1"></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-secondary px-2 btn-row-action">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </script>
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
