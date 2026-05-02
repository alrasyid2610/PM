// ============================================================
//  contracts/form.js
//  renderContractForm() — dipanggil oleh CrudPageController
//  Mengikuti pola: testing-units/form.js + formComponents.js
// ============================================================

function renderContractForm(res) {

    const statusOptions = [
        { value: 'draft',   label: 'Draft'   },
        { value: 'aktif',   label: 'Aktif'   },
        { value: 'selesai', label: 'Selesai' },
        { value: 'batal',   label: 'Batal'   },
    ];

    return `
<form class="row g-0" id="detailForm">

    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    {{-- ── HEADER AKSI ── --}}
    <div class="col-12 mb-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fa-solid fa-file-contract me-2 text-primary"></i>
            Detail Contract
        </h5>
        <button class="btn btn-warning btn-sm btn-edit-context" type="button" title="Edit">
            <i class="fa-solid fa-pen"></i> Edit
        </button>
    </div>

    {{-- ── SECTION: Informasi Kontrak ── --}}
    <div class="col-12 mb-3">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-blue">
                    <i class="fa-solid fa-file-contract"></i>
                </div>
                <div>
                    <div class="detail-section-title">Informasi Kontrak</div>
                </div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">

                    ${formGroup.text('no_kontrak', 'No Kontrak', res.no_kontrak, true, { className: 'col-md-4' })}

                    ${formGroup.date('tanggal_kontrak', 'Tanggal Kontrak', res.tanggal_kontrak, false, { className: 'col-md-4' })}

                    ${formGroup.select('status', 'Status', res.status, statusOptions, {
                        className: 'col-md-4',
                        mode: 'static',
                    })}

                    ${formGroup.date('tanggal_mulai', 'Tanggal Mulai', res.tanggal_mulai, false, { className: 'col-md-4' })}

                    ${formGroup.date('tanggal_selesai', 'Tanggal Selesai', res.tanggal_selesai, false, { className: 'col-md-4' })}

                    ${formGroup.text('durasi_bulan', 'Durasi (Bulan)', res.durasi_bulan, false, { className: 'col-md-4' })}

                    ${formGroup.text('nilai_kontrak', 'Nilai Kontrak (Rp)', res.nilai_kontrak
                        ? Number(res.nilai_kontrak).toLocaleString('id-ID')
                        : '', false, { className: 'col-md-6' })}

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Attachment</label>
                        <div>
                            ${res.attachment
                                ? `<a href="/storage/${res.attachment}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-solid fa-file me-1"></i> Lihat File
                                   </a>`
                                : '<span class="text-muted">-</span>'
                            }
                        </div>
                    </div>

                    ${formGroup.textarea('catatan', 'Catatan', res.catatan)}

                </div>
            </div>
        </div>
    </div>

    {{-- ── SECTION: Data Pelanggan ── --}}
    <div class="col-12 mb-3">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-navy">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div>
                    <div class="detail-section-title">Data Pelanggan</div>
                    <div class="detail-section-sub">${res.nama_pelanggan ?? '-'}</div>
                </div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">

                    ${formGroup.select('id_business_relation', 'Pelanggan', res.id_business_relation, [], {
                        className: 'col-md-6',
                        mode: 'ajax',
                        url: window.route.select2BR,
                        label: res.nama_pelanggan,
                        placeholder: '-- Pilih Pelanggan --',
                        minimumInputLength: 2,
                    })}

                    ${formGroup.select('id_pic_pelanggan', 'PIC Pelanggan', res.id_pic_pelanggan, [], {
                        className: 'col-md-6',
                        mode: 'ajax',
                        url: window.route.select2Contact,
                        label: res.nama_pic_pelanggan,
                        placeholder: '-- Pilih PIC --',
                        minimumInputLength: 2,
                    })}

                </div>
            </div>
        </div>
    </div>

    {{-- ── SECTION: PIC Pramatek ── --}}
    <div class="col-12 mb-3">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-green">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div>
                    <div class="detail-section-title">PIC Internal Pramatek</div>
                    <div class="detail-section-sub">${res.nama_pic_pramatek ?? '-'}</div>
                </div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">

                    ${formGroup.select('id_pic_pramatek', 'PIC Pramatek', res.id_pic_pramatek, [], {
                        className: 'col-md-6',
                        mode: 'ajax',
                        url: window.route.select2User,
                        label: res.nama_pic_pramatek,
                        placeholder: '-- Pilih User --',
                        minimumInputLength: 2,
                    })}

                </div>
            </div>
        </div>
    </div>

    {{-- ── SECTION: Timestamps ── --}}
    <div class="col-12 mb-3">
        <div class="detail-section-card">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-teal">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <div class="detail-section-title">Timestamps</div>
                </div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">
                    ${formGroup.text('created_at', 'Created At', res.created_at, false, { className: 'col-md-6' })}
                    ${formGroup.text('updated_at', 'Updated At', res.updated_at, false, { className: 'col-md-6' })}
                </div>
            </div>
        </div>
    </div>

</form>
    `;
}
