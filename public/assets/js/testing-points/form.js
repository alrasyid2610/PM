// Badge status approval (Draft / Menunggu Approval / Disetujui) — dipakai di action bar detail & kolom index
function approvalBadge(status, extraStyle) {
    const map = {
        draft:    { bg: '#f1f5f9', color: '#475569', border: '#e2e8f0', icon: 'fa-pen', label: 'Draft' },
        pending:  { bg: '#fffbeb', color: '#b45309', border: '#fde68a', icon: 'fa-hourglass-half', label: 'Menunggu Approval' },
        approved: { bg: '#f0fdf4', color: '#15803d', border: '#bbf7d0', icon: 'fa-circle-check', label: 'Disetujui' },
    };
    const s = map[status] || map.draft;
    return `<span class="pm-badge" style="background:${s.bg};color:${s.color};border:1px solid ${s.border};${extraStyle || ''}">
        <i class="fa-solid ${s.icon}" style="font-size:10px;"></i> ${s.label}</span>`;
}

function renderForm(res) {
    const isPending  = res.status_approval === 'pending';
    const isDraft    = !res.status_approval || res.status_approval === 'draft';
    const isApproved = res.status_approval === 'approved';
    const fmtTgl = (t) => t ? new Date(t).toLocaleString('id-ID') : '';

    return `
<form id="detailForm" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml([res.matrik_sample_judul_indonesia, res.standard_nomor, res.nama].filter(Boolean).join(' ') || '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_testing_point,
        editText: isPending ? '' : 'Edit Testing Point', // menunggu approval = terkunci
        statusBadge: approvalBadge(res.status_approval),
        noWrap: true,
        moreActions: [
            ...(isDraft && can('testing-points', 'can_update') ? [{
                label: 'Ajukan Approval', icon: 'fa-solid fa-paper-plane',
                attrs: `onclick="aksiApprovalPoint('ajukan', ${res.id_testing_point})"`,
            }] : []),
            ...(isPending && res.bisa_tarik ? [{
                label: 'Tarik Pengajuan', icon: 'fa-solid fa-rotate-left',
                attrs: `onclick="aksiApprovalPoint('tarik', ${res.id_testing_point})"`,
            }] : []),
            ...(isPending && can('testing-point-approve', 'can_update') ? [{
                label: 'Setujui Testing Point', icon: 'fa-solid fa-circle-check',
                attrs: `onclick="aksiApprovalPoint('setujui', ${res.id_testing_point})"`,
            }, {
                label: 'Tolak Testing Point', icon: 'fa-solid fa-circle-xmark',
                attrs: `onclick="aksiApprovalPoint('tolak', ${res.id_testing_point})"`,
            }] : []),
        ],
        extra: isPending
            ? `<span style="font-size:11px;color:#b45309;display:flex;align-items:center;gap:5px;">
                   <i class="fa-solid fa-hourglass-half" style="font-size:10px;"></i>
                   Menunggu approval — diajukan oleh <b>${escHtml(res.diajukan_oleh_nama || '—')}</b>${res.diajukan_at ? ' pada ' + fmtTgl(res.diajukan_at) : ''}. Terkunci dan belum bisa dipakai di BOQ baru sampai ada keputusan.
               </span>`
            : (isDraft && res.catatan_approval)
            ? `<span style="font-size:11px;color:#b91c1c;display:flex;align-items:center;gap:5px;">
                   <i class="fa-solid fa-circle-xmark" style="font-size:10px;"></i>
                   Pengajuan ditolak oleh <b>${escHtml(res.approval_oleh_nama || '—')}</b>${res.approval_at ? ' pada ' + fmtTgl(res.approval_at) : ''}: ${escHtml(res.catatan_approval)}
               </span>`
            : isDraft
            ? `<span style="font-size:11px;color:#64748b;display:flex;align-items:center;gap:5px;">
                   <i class="fa-solid fa-lock" style="font-size:10px;"></i>
                   Draft — belum bisa dipakai di BOQ. Lengkapi lalu <b>Ajukan Approval</b> (menu ⋮).
               </span>`
            : '',
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
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabPointPaket">
                        <i class="fa-solid fa-boxes-stacked me-1" style="color:#b45309;font-size:11px;"></i>
                        Paket
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="pointTabActionsInfo" class="d-flex align-items-center gap-2"></div>
                <div id="pointTabActionsItems" class="d-flex align-items-center gap-2 d-none"></div>
                <div id="pointTabActionsPaket" class="d-flex align-items-center gap-2 d-none"></div>
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
                                <div class="p-3 pb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="pm-search" data-no-disable>
                                        <span class="pm-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                                        <input type="text" class="dt-search" placeholder="Cari judul, nilai, keterangan..." data-no-disable>
                                        <button type="button" class="pm-search-clear dt-search-clear d-none" title="Hapus" data-no-disable>
                                            <i class="fa-solid fa-times"></i>
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm btn-add-row">
                                        <i class="fa-solid fa-plus me-1"></i> Tambah Baris
                                    </button>
                                </div>
                                <div class="table-responsive p-3">
                                    <table id="Table" class="table table-bordered table-sm dynamic-table mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:32px"></th>
                                                <th style="white-space:nowrap;width:40px">No</th>
                                                <th style="min-width:240px">Judul Indonesia</th>
                                                <th style="min-width:240px">Judul Inggris</th>
                                                <th style="min-width:160px">Parameter</th>
                                                <th style="min-width:140px">Unit</th>
                                                <th style="min-width:120px">Nilai</th>
                                                <th style="min-width:140px">Keterangan</th>
                                                <th style="white-space:nowrap;width:60px">Status</th>
                                                <th style="white-space:nowrap;width:90px">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <input type="hidden" name="id_testing_item[]" value="">
                                                <input type="hidden" name="nomor[]" value="">
                                                <td class="text-center" style="cursor:grab;">
                                                    <button type="button" class="drag-handle-btn" tabindex="-1" title="Geser untuk urutkan">
                                                        <i class="fa-solid fa-grip-vertical"></i>
                                                    </button>
                                                </td>
                                                <td class="row-number"></td>
                                                <td><input type="text" name="judul_indonesia[]" class="form-control form-control-sm"></td>
                                                <td><input type="text" name="judul_inggris[]" class="form-control form-control-sm"></td>
                                                <td><select name="parameter[]" class="form-control form-control-sm parameter-select"></select></td>
                                                <td><select name="unit[]" class="form-control form-control-sm unit-select"></select></td>
                                                <td><input type="text" name="nilai[]" class="form-control form-control-sm"></td>
                                                <td><input type="text" name="keterangan[]" class="form-control form-control-sm"></td>
                                                <td class="text-center">
                                                    <input type="hidden" name="status[]" value="0" class="status-hidden">
                                                    <input type="checkbox" class="status-checkbox" value="1">
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-1 justify-content-center">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary px-2 btn-row-action" title="Sisipkan baris">
                                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger px-2 btn-row-delete" title="Hapus baris">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <script type="text/template" id="row-template">
                                    <tr>
                                        <input type="hidden" name="id_testing_item[]" value="">
                                        <input type="hidden" name="nomor[]" value="">
                                        <td class="text-center" style="cursor:grab;">
                                            <button type="button" class="drag-handle-btn" tabindex="-1" title="Geser untuk urutkan">
                                                <i class="fa-solid fa-grip-vertical"></i>
                                            </button>
                                        </td>
                                        <td class="row-number"></td>
                                        <td><input type="text" name="judul_indonesia[]" class="form-control form-control-sm"></td>
                                        <td><input type="text" name="judul_inggris[]" class="form-control form-control-sm"></td>
                                        <td><select name="parameter[]" class="form-control form-control-sm parameter-select"></select></td>
                                        <td><select name="unit[]" class="form-control form-control-sm unit-select"></select></td>
                                        <td><input type="text" name="nilai[]" class="form-control form-control-sm"></td>
                                        <td><input type="text" name="keterangan[]" class="form-control form-control-sm"></td>
                                        <td class="text-center">
                                            <input type="hidden" name="status[]" value="0" class="status-hidden">
                                            <input type="checkbox" class="status-checkbox" value="1">
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-sm btn-outline-secondary px-2 btn-row-action" title="Sisipkan baris">
                                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger px-2 btn-row-delete" title="Hapus baris">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </script>
                            </div>`
                        )}
                    </div>
                </div>

                <!-- TAB: PAKET — subset Testing Item (mis. "parameter 20" dari 50 item); CRUD sendiri lewat modal, tidak ikut tombol Edit/Simpan form -->
                <div class="tab-pane fade" id="tabPointPaket" role="tabpanel">
                    <div class="card card-body">
                        ${formGroup.sectionCard(
                            { icon: 'fa-boxes-stacked', color: 'icon-amber', title: 'Paket', subtitle: 'Kumpulan item pilihan dari Testing Point ini — dipakai sebagai jalan pintas saat membuat BOQ' },
                            `<div id="pointPaketWrap" data-id="${res.id_testing_point}" data-no-disable>
                                <div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...</div>
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
