function renderForm(res) {
    const v = (x) => escHtml(x ?? '');
    const dipakai = Number(res.dipakai || 0);

    const pemakaian = dipakai > 0
        ? `<span class="badge rounded-pill" style="background:#e0f2fe;color:#0369a1;font-size:11px;font-weight:600;">${dipakai} plan</span>
           <span class="text-muted" style="font-size:12px;">memakai label ini. Tidak bisa dihapus — nonaktifkan saja (plan lama tetap valid).
           Mengubah nama di sini ikut mengubah label di semua plan itu.</span>`
        : `<span class="text-muted" style="font-size:12px;">Belum dipakai budget plan mana pun — boleh dihapus.</span>`;

    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: v(res.nama),
        createdAt: v(res.created_at ?? '—'),
        updatedAt: v(res.updated_at ?? '—'),
        deleteId: res.id_budget_label,
        editText: 'Edit Label',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="budgetLabelDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabBudgetLabelInfo">
                        <i class="fa-solid fa-tags me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabBudgetLabelInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-tags', color: 'icon-navy', title: 'Label Budget', subtitle: 'Label yang bisa dipilih di Budget Plan WO, FWO, SQ WO, dan SQ FWO' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("nama", "Label", v(res.nama), true, { className: "col-md-6 col-12" })}
                                ${formGroup.text("urutan", "Urutan", v(res.urutan ?? 0), false, { className: "col-md-3 col-6", type: "number" })}
                                ${formGroup.select("is_aktif", "Status", res.is_aktif,
                                    [{ value: 1, label: "Aktif" }, { value: 0, label: "Tidak Aktif" }],
                                    { className: "col-md-3 col-6" }
                                )}
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
