const OUTPUT_MASTER_TIPE_OPTIONS = [
    { value: "pekerjaan", label: "Output Pekerjaan" },
    { value: "other", label: "Output Other" },
];

// Penanda LHU hanya berlaku untuk tipe pekerjaan (detail form)
function syncOutputMasterLhu() {
    const pekerjaan = $('#detailForm select[name="tipe"]').val() === 'pekerjaan';
    $('#detailForm .om-lhu-wrap').toggleClass('d-none', !pekerjaan);
    if (!pekerjaan) $('#detailForm input[name="is_lhu"]').prop('checked', false);
}

function renderForm(res) {
    const v = (x) => escHtml(x ?? '');
    const dipakai = Number(res.dipakai || 0);

    const pemakaian = dipakai > 0
        ? `<span class="badge rounded-pill" style="background:#e0f2fe;color:#0369a1;font-size:11px;font-weight:600;">${dipakai} output</span>
           <span class="text-muted" style="font-size:12px;">memakai judul ini (${Number(res.dipakai_pekerjaan || 0)} Output Pekerjaan · ${Number(res.dipakai_other || 0)} Output Other).
           Tidak bisa dihapus — nonaktifkan atau gabungkan ke judul lain.</span>`
        : `<span class="text-muted" style="font-size:12px;">Belum dipakai output mana pun — boleh dihapus.</span>`;

    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: v(res.judul),
        createdAt: v(res.created_at ?? '—'),
        updatedAt: v(res.updated_at ?? '—'),
        deleteId: res.id_output_judul,
        editText: 'Edit Judul',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="outputMasterDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabOutputMasterInfo">
                        <i class="fa-solid fa-file-lines me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="omTabActionsInfo" class="d-flex align-items-center gap-2">
                    ${can('output-master', 'can_update') ? `
                    <button type="button" class="pm-btn-pill btn-om-merge" data-id="${res.id_output_judul}"
                        data-judul="${v(res.judul)}" data-tipe="${v(res.tipe)}" data-dipakai="${dipakai}" data-no-disable
                        style="border-color:#7c3aed;color:#7c3aed;" title="Pindahkan semua output yang memakai judul ini ke judul lain (merapikan varian ejaan)">
                        <i class="fa-solid fa-code-merge" style="font-size:11px;"></i> Gabungkan ke…
                    </button>` : ''}
                </div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabOutputMasterInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-file-lines', color: 'icon-navy', title: 'Judul Output', subtitle: 'Judul yang bisa dipilih di Output Pekerjaan atau Output Other' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("judul", "Judul", v(res.judul), true, { className: "col-md-7 col-12" })}
                                ${formGroup.select("tipe", "Tipe", res.tipe, OUTPUT_MASTER_TIPE_OPTIONS, { className: "col-md-3 col-12" })}
                                ${formGroup.select("is_aktif", "Status", res.is_aktif,
                                    [{ value: 1, label: "Aktif" }, { value: 0, label: "Tidak Aktif" }],
                                    { className: "col-md-2 col-12" }
                                )}
                                ${formGroup.checkbox("is_lhu", "Penanda LHU", res.is_lhu, {
                                    className: "col-12 om-lhu-wrap",
                                    checkLabel: "Judul ini adalah LHU — saklar LHU di Output Pekerjaan otomatis aktif saat judul ini dipilih",
                                })}
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
