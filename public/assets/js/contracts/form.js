function renderForm(res) {

    const statusOptions = [
        { value: 'draft',   label: 'Draft'   },
        { value: 'aktif',   label: 'Aktif'   },
        { value: 'selesai', label: 'Selesai' },
        { value: 'batal',   label: 'Batal'   },
    ];

    return `
<form class="row g-3" id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.sectionCard(
        { icon: 'fa-file-contract', color: 'icon-blue', title: 'Informasi Kontrak', subtitle: 'Detail data kontrak pelanggan', editTitle: 'Edit Contract' },
        `<div class="row g-3 form-1">
            ${formGroup.text('no_kontrak', 'No Kontrak', res.no_kontrak, true, { className: 'col-md-4' })}
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
                className: 'col-md-6',
                mode: 'ajax',
                url: window.route.select2BR,
                label: res.nama_pelanggan,
                placeholder: 'Pilih Data',
                createUrl: '/business-relations/create',
            })}
            ${formGroup.select('id_pic_pelanggan', 'PIC Pelanggan', res.id_pic_pelanggan, [], {
                className: 'col-md-6',
                mode: 'ajax',
                url: window.route.select2Contact,
                label: res.nama_pic_pelanggan,
                placeholder: 'Pilih Data',
                createUrl: '/business-relation-contacts/create',
            })}
        </div>`
    )}

    ${formGroup.sectionCard(
        { icon: 'fa-user-tie', color: 'icon-green', title: 'PIC Internal Pramatek', subtitle: res.nama_pic_pramatek ?? '-' },
        `<div class="row g-3 form-3">
            ${formGroup.select('id_pic_pramatek', 'PIC Pramatek', res.id_pic_pramatek, [], {
                className: 'col-md-6',
                mode: 'ajax',
                url: window.route.select2User,
                label: res.nama_pic_pramatek,
                placeholder: 'Pilih Data',
                createUrl: '/users/create',
            })}
        </div>`
    )}

    ${formGroup.sectionCard(
        { icon: 'fa-paperclip', color: 'icon-teal', title: 'Attachment', subtitle: 'File pendukung kontrak' },
        `${renderAttachmentSection()}`
    )}

</form>
    `;
}
