const POIN_TIPE_LABEL = { angka: 'Angka', rekening: 'Rekening (Bank Account)', personel: 'Personel (nama + no. HP)', termin: 'Daftar Termin' };

function renderForm(res) {
    const isian = (res.isian || []).length
        ? `<ul class="mb-0 ps-3" style="font-size:12px;">${res.isian.map(i =>
            `<li><code>${escHtml(i.key)}</code> — ${escHtml(POIN_TIPE_LABEL[i.tipe] || i.tipe)}${i.default ? ` (default ${escHtml(i.default)})` : ''}</li>`
        ).join('')}</ul>`
        : '<span class="text-muted small">Tidak ada isian — teks poin tetap.</span>';

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
                            </div>
                            <div class="text-muted small mb-2">
                                Isian ditulis <code>{kunci:tipe=default}</code>, mis. <code>{hari:angka=12}</code>. Tipe: angka, rekening, personel, termin.
                            </div>
                            <div class="mb-2"><strong style="font-size:12px;">Isian terdeteksi</strong>${isian}</div>
                            <div><strong style="font-size:12px;">Pratinjau</strong>
                                <div class="border rounded p-2" style="font-size:12px;background:#f8fafc;white-space:pre-line;">${escHtml(res.preview ?? '')}</div>
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
