function renderForm(res) {
    const statusOptions = [
        { value: 'draft',   label: 'Draft'   },
        { value: 'aktif',   label: 'Aktif'   },
        { value: 'selesai', label: 'Selesai' },
        { value: 'batal',   label: 'Batal'   },
    ];

    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.no_contract ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_contract,
        editText: 'Edit Contract',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="contractDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabContractInfo">
                        <i class="fa-solid fa-file-contract me-1" style="color:#1a56db;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="contractTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabContractInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-file-contract', color: 'icon-blue', title: 'Informasi Kontrak', subtitle: 'Detail data kontrak pelanggan' },
                            `<div class="row g-3 form-1">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">No Kontrak</label>
                                    <p class="form-control mb-0">${escHtml(res.no_contract ?? '—')}</p>
                                </div>
                                ${formGroup.text('no_contract_client', 'No Kontrak Client', res.no_contract_client ?? '', false, { className: 'col-md-4' })}
                                ${formGroup.date('tanggal_kontrak', 'Tanggal Kontrak', res.tanggal_kontrak, false, { className: 'col-md-4' })}
                                ${formGroup.select('status', 'Status', res.status, statusOptions, { className: 'col-md-4' })}
                                ${formGroup.date('tanggal_mulai', 'Tanggal Mulai', res.tanggal_mulai, false, { className: 'col-md-4' })}
                                ${formGroup.date('tanggal_selesai', 'Tanggal Selesai', res.tanggal_selesai, false, { className: 'col-md-4' })}
                                ${formGroup.text('durasi_bulan', 'Durasi (Bulan)', res.durasi_bulan, false, { className: 'col-md-4' })}
                                ${formGroup.text('nilai_kontrak', 'Nilai Kontrak (Rp)', res.nilai_kontrak, false, { className: 'col-md-6' })}
                                ${formGroup.textarea('catatan', 'Catatan', res.catatan, { className: 'col-md-6' })}
                            </div>`
                        )}

                        ${formGroup.sectionCard(
                            { icon: 'fa-building', color: 'icon-navy', title: 'Data Pelanggan', subtitle: res.nama_pelanggan ?? '-' },
                            `<div class="row g-3 form-2">
                                ${formGroup.select('id_business_relation', 'Pelanggan', res.id_business_relation, [], {
                                    className: 'col-md-6', mode: 'ajax', url: window.route.select2BR,
                                    label: res.nama_pelanggan, placeholder: '-- Pilih Pelanggan --', minimumInputLength: 2,
                                })}
                                ${formGroup.select('id_pic_pelanggan', 'PIC Pelanggan', res.id_pic_pelanggan, [], {
                                    className: 'col-md-6', mode: 'ajax', url: window.route.select2Contact,
                                    label: res.nama_pic_pelanggan, placeholder: '-- Pilih PIC --', minimumInputLength: 2,
                                })}
                            </div>`
                        )}

                        ${formGroup.sectionCard(
                            { icon: 'fa-user-tie', color: 'icon-green', title: 'PIC Internal Pramatek', subtitle: res.nama_pic_pramatek ?? '-' },
                            `<div class="row g-3 form-3">
                                ${formGroup.select('id_pic_pramatek', 'PIC Pramatek', res.id_pic_pramatek, [], {
                                    className: 'col-md-6', mode: 'ajax', url: window.route.select2User,
                                    label: res.nama_pic_pramatek, placeholder: '-- Pilih User --', minimumInputLength: 2,
                                })}
                            </div>`
                        )}

                        ${formGroup.sectionCard(
                            { icon: 'fa-paperclip', color: 'icon-teal', title: 'Attachment', subtitle: 'File pendukung kontrak' },
                            `${renderAttachmentSection()}`
                        )}

                    </div>
                </div>

            </div>
        </div>
    </div>

</form>
    `;
}
