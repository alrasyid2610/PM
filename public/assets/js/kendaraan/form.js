// Pilihan tetap — harus sama dengan App\Support\KendaraanOptions
const KENDARAAN_JENIS_OPTIONS = [
    { value: "mobil", label: "Mobil" },
    { value: "pickup", label: "Pickup" },
    { value: "minibus", label: "Minibus" },
    { value: "truk", label: "Truk" },
    { value: "motor", label: "Motor" },
    { value: "lainnya", label: "Lainnya" },
];
const KENDARAAN_KEPEMILIKAN_OPTIONS = [
    { value: "milik_sendiri", label: "Milik Sendiri" },
    { value: "sewa", label: "Sewa" },
];

// Vendor hanya tampil untuk kendaraan sewa (detail form). Input vendor tetap ter-disable/enable oleh toggle Edit.
function syncKendaraanVendor() {
    const sewa = $('#detailForm select[name="kepemilikan"]').val() === 'sewa';
    $('#detailForm .kendaraan-vendor').toggleClass('d-none', !sewa);
}

function renderForm(res) {
    const v = (x) => escHtml(x ?? '');

    return `
<form id="detailForm" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.no_polisi ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_kendaraan,
        editText: 'Edit Kendaraan',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="kendaraanDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabKendaraanInfo">
                        <i class="fa-solid fa-truck-pickup me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="kendaraanTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabKendaraanInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-truck-pickup', color: 'icon-navy', title: 'Data Kendaraan', subtitle: 'Identitas kendaraan operasional' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("nama", "Nama", v(res.nama), true, { className: "col-md-5 col-12" })}
                                ${formGroup.text("no_polisi", "No. Polisi", v(res.no_polisi), true, { className: "col-md-3 col-12", inputClass: "text-uppercase" })}
                                ${formGroup.select("jenis", "Jenis", res.jenis, KENDARAAN_JENIS_OPTIONS, { className: "col-md-2 col-12" })}
                                ${formGroup.select("is_aktif", "Status", res.is_aktif,
                                    [{ value: 1, label: "Aktif" }, { value: 0, label: "Tidak Aktif" }],
                                    { className: "col-md-2 col-12" }
                                )}
                                ${formGroup.text("merek", "Merek", v(res.merek), false, { className: "col-md-3 col-12" })}
                                ${formGroup.text("model", "Model", v(res.model), false, { className: "col-md-3 col-12" })}
                                ${formGroup.text("tahun", "Tahun", v(res.tahun), false, { className: "col-md-2 col-12" })}
                                ${formGroup.text("warna", "Warna", v(res.warna), false, { className: "col-md-2 col-12" })}
                                ${formGroup.date("masa_berlaku_stnk", "STNK Berlaku s/d", v(res.masa_berlaku_stnk), false, { className: "col-md-2 col-12" })}
                            </div>`
                        )}

                        ${formGroup.sectionCard(
                            { icon: 'fa-handshake', color: 'icon-teal', title: 'Kepemilikan', subtitle: 'Milik sendiri atau sewa (vendor wajib untuk sewa)' },
                            `<div class="row g-3 form-1">
                                ${formGroup.select("kepemilikan", "Kepemilikan", res.kepemilikan, KENDARAAN_KEPEMILIKAN_OPTIONS, { className: "col-md-3 col-12" })}
                                ${formGroup.text("nama_vendor", "Nama Vendor / Pemilik", v(res.nama_vendor), false, { className: "col-md-5 col-12 kendaraan-vendor" })}
                                ${formGroup.text("kontak_vendor", "Kontak Vendor", v(res.kontak_vendor), false, { className: "col-md-4 col-12 kendaraan-vendor" })}
                                ${formGroup.textarea("keterangan", "Keterangan", v(res.keterangan), { className: "col-12" })}
                            </div>`
                        )}

                        ${formGroup.sectionCard(
                            { icon: 'fa-paperclip', color: 'icon-blue', title: 'Attachment', subtitle: 'Foto kendaraan, STNK, kontrak sewa' },
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
