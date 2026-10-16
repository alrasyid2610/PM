function renderForm(res) {
    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.bank ? res.bank + ' — ' + res.no_rekening : '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_bank_account,
        editText: 'Edit Bank Account',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="bankAccountDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBankAccountInfo">
                        <i class="fa-solid fa-building-columns me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="bankAccountTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabBankAccountInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-building-columns', color: 'icon-navy', title: 'Bank Account', subtitle: 'Data rekening bank' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("bank", "Bank", res.bank, true, { className: "col-md-4 col-12" })}
                                ${formGroup.text("no_rekening", "No. Rekening", res.no_rekening, true, { className: "col-md-4 col-12" })}
                                ${formGroup.text("atas_nama", "Atas Nama", res.atas_nama, true, { className: "col-md-4 col-12" })}
                                ${formGroup.textarea("keterangan", "Keterangan", res.keterangan, { className: "col-12" })}
                                ${formGroup.checkbox("is_utama", "Rekening Utama", res.is_utama, { className: "col-12", checkLabel: "Utama — otomatis terisi di keterangan penawaran SQ (hanya 1 rekening)" })}
                                ${formGroup.checkbox("is_ppn", "Rekening PPN", res.is_ppn, { className: "col-12", checkLabel: "PPN — rekening ini dipakai untuk pembayaran PPN" })}
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
