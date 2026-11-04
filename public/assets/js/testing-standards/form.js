function renderForm(res) {
    return `
<form id="detailForm">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.nomor ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_testing_standard,
        editText: 'Edit',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="stdDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabStdInfo">
                        <i class="fa-solid fa-book me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabStdPoints" id="btnTabStdPoints">
                        <i class="fa-solid fa-vial me-1" style="color:#0369a1;font-size:11px;"></i>
                        Testing Point
                        <span class="badge rounded-pill bg-secondary ms-1" id="stdPointCount" style="font-size:10px;">…</span>
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="stdTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabStdInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-book', color: 'icon-navy', title: 'Testing Standards', subtitle: 'Data standar pengujian laboratorium' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("nomor", "Nomor", res.nomor, true, { className: "col-md-4" })}
                                ${formGroup.text("judul", "Judul", res.judul, true, { className: "col-md-6" })}
                                ${formGroup.select("is_aktif", "Status", res.is_aktif,
                                    [
                                        { value: 1, label: "Aktif" },
                                        { value: 0, label: "Tidak Aktif" },
                                    ],
                                    { className: "col-md-2" }
                                )}
                            </div>`
                        )}

                        ${formGroup.sectionCard(
                            { icon: 'fa-paperclip', color: 'icon-blue', title: 'Attachment', subtitle: 'File pendukung standard' },
                            `${renderAttachmentSection()}`
                        )}

                    </div>
                </div>

                <div class="tab-pane fade" id="tabStdPoints" role="tabpanel">
                    ${formGroup.sectionCard(
                        { icon: 'fa-vial', color: 'icon-blue', title: 'Testing Point', subtitle: 'Titik uji yang memakai standar ini — klik baris untuk membuka detail' },
                        `<div class="table-responsive">
                            <table id="stdPointsTable" class="pm-table" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">#</th>
                                        <th>Nama Testing Point</th>
                                        <th>Matriks Sample</th>
                                        <th class="text-center">Halaman</th>
                                        <th class="text-center">Item</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>`
                    )}
                </div>

            </div>
        </div>
    </div>

</form>
`;
}

// ── Tab "Testing Point": tabel DataTables (search/paging sisi klien) ────────────
function stdPointBadge(aktif) {
    const s = aktif == 1
        ? { bg: '#f0fdf4', color: '#15803d', border: '#bbf7d0', icon: 'fa-circle-check', label: 'Aktif' }
        : { bg: '#f1f5f9', color: '#475569', border: '#e2e8f0', icon: 'fa-circle', label: 'Tidak Aktif' };
    return `<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:6px;background:${s.bg};color:${s.color};font-size:11px;font-weight:600;border:1px solid ${s.border};white-space:nowrap;"><i class="fa-solid ${s.icon}" style="font-size:10px;"></i> ${s.label}</span>`;
}

function initStdPointsTable(idStandard) {
    if ($.fn.DataTable.isDataTable('#stdPointsTable')) {
        $('#stdPointsTable').DataTable().destroy();
    }
    const dash = '<span class="text-muted">—</span>';

    $('#stdPointsTable').DataTable({
        ajax: {
            url: window.route.update + idStandard + '/points',
            dataSrc: function (json) {
                $('#stdPointCount').text(json.data.length);
                return json.data;
            },
        },
        pageLength: 10,
        order: [],
        // Search & "Show entries" harus tetap aktif walau form detail sedang mode baca (toggle Edit/Batal men-disable input di form)
        initComplete: function () {
            $(this.api().table().container()).find('input, select').attr('data-no-disable', '').prop('disabled', false).removeClass('disabled');
        },
        language: { emptyTable: 'Belum ada Testing Point untuk standar ini', zeroRecords: 'Tidak ada Testing Point yang cocok' },
        columns: [
            { data: null, orderable: false, searchable: false, render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            {
                data: 'nama',
                render: (d, t, r) => `<a href="/testing-points?open=${r.id_testing_point}" style="font-weight:600;">${escHtml(d || '-')}</a>`,
            },
            {
                data: 'matriks_kode',
                render: (d, t, r) => d
                    ? `<span style="font-weight:600;">${escHtml(d)}</span><div class="text-muted" style="font-size:11px;">${escHtml(r.matriks_judul || '')}</div>`
                    : dash,
            },
            { data: 'nomor_halaman', className: 'text-center', render: (d) => d ? escHtml(d) : dash },
            { data: 'jumlah_item', className: 'text-center', searchable: false },
            { data: 'is_aktif', className: 'text-center', searchable: false, render: (d) => stdPointBadge(d) },
        ],
    });
}

// Muat tabel saat tab pertama kali dibuka (id standar diambil dari tombol hapus di action bar)
$(document).on('shown.bs.tab', '#btnTabStdPoints', function () {
    const id = $('#detailForm .btn-delete-record').first().data('id');
    if (!id) return;
    if (!$.fn.DataTable.isDataTable('#stdPointsTable')) {
        initStdPointsTable(id);
    } else {
        $('#stdPointsTable').DataTable().columns.adjust();
    }
});

// Jumlah di badge tab dimuat langsung saat detail dirender (dipanggil dari afterLoad di index.js)
function loadStdPointCount(id) {
    $.getJSON(window.route.update + id + '/points', function (json) {
        $('#stdPointCount').text(json.data.length);
    });
}
