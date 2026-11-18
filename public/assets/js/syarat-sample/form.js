// Teks syarat + pratinjau + bantuan isian dirender oleh PoinEditor (poin-keterangan/editor.js) lewat afterLoad di index.js.
function renderForm(res) {
    const dipakai = Number(res.dipakai || 0);
    const pemakaian = dipakai > 0
        ? `<span class="badge rounded-pill" style="background:#e0f2fe;color:#0369a1;font-size:11px;font-weight:600;">${dipakai} Testing Point</span>
           <span class="text-muted" style="font-size:12px;">memakai syarat ini. Tidak bisa dihapus — nonaktifkan saja.
           Mengubah teks ikut mengubah tampilan di semua Testing Point itu (nilai isian tetap tersimpan per kunci).</span>`
        : `<span class="text-muted" style="font-size:12px;">Belum dipakai Testing Point mana pun — boleh dihapus.</span>`;

    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.preview ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_syarat,
        editText: 'Edit Syarat',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="syaratDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabSyaratInfo">
                        <i class="fa-solid fa-clipboard-check me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabSyaratInfo" role="tabpanel">
                    <div class="row g-3">
                        ${formGroup.sectionCard(
                            { icon: 'fa-clipboard-check', color: 'icon-navy', title: 'Syarat Sample', subtitle: 'Syarat yang bisa dipilih di tiap Testing Point' },
                            `<div class="row g-3 form-1">
                                ${formGroup.textarea("template", "Teks Syarat", escHtml(res.template ?? ''), { className: "col-12" })}
                                ${formGroup.checkbox("is_aktif", "Status", res.is_aktif, { className: "col-md-3 col-6", checkLabel: "Aktif" })}
                                <div class="col-12" data-no-disable>${pemakaian}</div>
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