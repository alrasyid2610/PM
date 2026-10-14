// "Isian terdeteksi" + Pratinjau + bantuan menulis isian dirender langsung oleh PoinEditor (editor.js),
// dipanggil lewat afterLoad di index.js — bukan lagi blok statis dari server.
function renderForm(res) {
    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.kategori_label ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_poin,
        editText: 'Edit Poin Keterangan',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="poinDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabPoinInfo">
                        <i class="fa-solid fa-list-ol me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="poinTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabPoinInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-list-ol', color: 'icon-navy', title: 'Poin Keterangan', subtitle: 'Varian poin keterangan penawaran' },
                            `<div class="row g-3 form-1">
                                ${formGroup.select("kategori", "Kategori", res.kategori, res.kategori_opsi || [], { className: "col-md-6 col-12", required: true })}
                                ${formGroup.checkbox("is_default", "Paket", res.is_default, { className: "col-md-3 col-6", checkLabel: "Paket standar" })}
                                ${formGroup.checkbox("is_aktif", "Status", res.is_aktif, { className: "col-md-3 col-6", checkLabel: "Aktif" })}
                                ${formGroup.textarea("template", "Teks Poin", escHtml(res.template ?? ''), { className: "col-12" })}
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
