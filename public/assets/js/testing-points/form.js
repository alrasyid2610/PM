function renderForm(res) {
    return `
        <form id="detailForm" enctype="multipart/form-data">
        
        <div class="col-md-12 form-1">
                <input type="hidden" name="_token" value="${window.route.csrf}">
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="id_testing_standard" value="${res.id_testing_standard}">
                <input type="hidden" name="id_testing_matriks_sample" value="${res.id_testing_matriks_sample}">
                <div class="d-flex justify-content-between align-items-center mb-2">

                    <h3>Testing Matriks Samples</h3>

                    <div class="btn-group">
                        <button class="btn btn-warning btn-sm btn-edit-context" title="Edit Testing Unit">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                    </div>

                </div>
                

                <div class="row mb-4">

                        ${formGroup.select(
                            "id_testing_standard",
                            "Testing Standard",
                            res.id_testing_standard,
                            [],
                            {
                                mode: "ajax",
                                url: "testing-standards/select2",
                                placeholder: "Pilih Standard",
                                label: res.standard_judul,
                                className: "col-lg-12",
                            },
                        )}

                        ${formGroup.select(
                            "Testing Matriks Sample",
                            "Testing Matriks Sample",
                            res.id_testing_matriks_sample,
                            [],
                            {
                                mode: "ajax",
                                url: "testing-standards/select2",
                                placeholder: "Pilih Matrik Sample",
                                label: res.matrik_sample_judul_indonesia,
                                className: "col-lg-12",
                            },
                        )}

                        ${formGroup.text("nama", "nama", res.nama, true, {
                            className: "col-md-6",
                        })}
                        ${formGroup.text(
                            "nomor_halaman",
                            "nomor_halaman",
                            res.nomor_halaman,
                            true,
                            {
                                className: "col-md-4",
                            },
                        )}
                        ${formGroup.select(
                            "is_aktif",
                            "is_aktif",
                            res.is_aktif,
                            [
                                { value: 1, label: "Aktif" },
                                { value: 0, label: "Tidak Aktif" },
                            ],
                            {
                                className: "col-md-2",
                            },
                        )}
                        ${formGroup.textarea("deskripsi", "deskripsi", res.deskripsi)}
                        ${formGroup.textarea("keterangan", "Keterangan", res.keterangan)}
                        ${renderAttachmentSection()}


                        <hr>
                            
                </div>
            </div>

            <div class="dynamic-table-wrapper">
                <button type="button" class="btn btn-primary btn-sm btn-add-row mb-2">
                    Tambah Baris
                </button>

                <div class="table-responsive">
                    <table id="Table" class="table table-bordered table-sm dynamic-table">

                        <thead class="table-light">
                            <tr>
                                <th width="3%">No</th>
                                <th width="25%">Judul Indonesia</th>
                                <th width="25%">Judul Inggris</th>
                                <th width="10%">Parameter</th>
                                <th width="10%">Unit</th>
                                <th width="10%">Nilai</th>
                                <th width="10%">Keterangan</th>
                                <th width="5%">Status</th>
                                <th width="5%">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>
                                <input type="hidden" name="id_testing_item[]" value="">

                                <td class="row-number"></td>

                                <td>
                                    <input type="text" name="judul_indonesia[]" class="form-control">
                                </td>

                                <td>
                                    <input type="text" name="judul_inggris[]" class="form-control">
                                </td>

                                <td>
                                    <select name="parameter[]" class="form-control parameter-select"></select>
                                </td>

                                <td>
                                    <select name="unit[]" class="form-control unit-select"></select>
                                </td>

                                <td>
                                    <input type="text" name="nilai[]" class="form-control">
                                </td>

                                <td>
                                    <input type="text" name="keterangan[]" class="form-control">
                                </td>

                                <td class="text-center">
                                    <input type="checkbox" name="status[]" value="1">
                                </td>

                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm btn-remove">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>

                            </tr>

                        </tbody>

                    </table>
                </div>

            </div>
        </form>

`;
}

function renderAttachmentSection() {
    return `
<div class="col-md-12 mb-3">

    <label class="form-label">Attachment</label>

    <div id="attachmentPreview" class="row g-3">
    
    </div>

    <div id="attachmentUploader" class="mt-3" style="display:none">

        <input
        type="file"
        class="filepond-edit"
        name="attachments[]"
        multiple>

    </div>

</div>
`;
}
