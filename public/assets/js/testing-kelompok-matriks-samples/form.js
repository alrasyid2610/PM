function renderParameterForm(res) {
    return `
<form id="detailForm" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="_method" value="PUT">

    ${formGroup.actionBar({
        number: escHtml(res.kode ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_testing_kelompok_matriks_sample,
        editText: 'Edit Kelompok Matriks',
        noWrap: true,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="kmDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabKmInfo">
                        <i class="fa-solid fa-layer-group me-1" style="color:#0f766e;font-size:11px;"></i>
                        Informasi
                    </button>
                </li>
                <li role="presentation">
                    <button class="pm-tab-btn" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabKmMatriks" id="btnTabKmMatriks">
                        <i class="fa-solid fa-flask me-1" style="color:#0369a1;font-size:11px;"></i>
                        Matriks Sample
                        <span class="badge rounded-pill bg-secondary ms-1" id="kmMatriksCount" style="font-size:10px;">…</span>
                    </button>
                </li>
                ${TestingPointTable.tabButton('Km')}
            </ul>
            <div class="pm-tab-actions">
                <div id="kmTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">

                <div class="tab-pane fade show active" id="tabKmInfo" role="tabpanel">
                    <div class="row g-3">

                        ${formGroup.sectionCard(
                            { icon: 'fa-layer-group', color: 'icon-teal', title: 'Kelompok Matriks Samples', subtitle: 'Data kelompok matriks sampel' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("kode", "Kode", res.kode, true, { className: "col-md-4" })}
                                ${formGroup.text("judul_indonesia", "Judul Indonesia", res.judul_indonesia, true, { className: "col-md-4" })}
                                ${formGroup.text("judul_inggris", "Judul Inggris", res.judul_inggris, true, { className: "col-md-4" })}
                                ${formGroup.textarea("keterangan", "Keterangan", res.keterangan, { className: "col-md-12" })}
                            </div>`
                        )}

                    </div>
                </div>

                <div class="tab-pane fade" id="tabKmMatriks" role="tabpanel">
                    ${formGroup.sectionCard(
                        { icon: 'fa-flask', color: 'icon-blue', title: 'Matriks Sample', subtitle: 'Matriks sample di kelompok ini — klik kode untuk membuka detail' },
                        `<div class="table-responsive">
                            <table id="kmMatriksTable" class="pm-table" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">#</th>
                                        <th>Kode</th>
                                        <th>Judul Indonesia</th>
                                        <th>Judul Inggris</th>
                                        <th>Keterangan</th>
                                        <th class="text-center">Testing Point</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>`
                    )}
                </div>

                ${TestingPointTable.tabPane('Km', true)}

            </div>
        </div>
    </div>

</form>
`;
}

// ── Tab "Matriks Sample": tabel DataTables (search/paging sisi klien) ───────────
function initKmMatriksTable(idKelompok) {
    if ($.fn.DataTable.isDataTable('#kmMatriksTable')) {
        $('#kmMatriksTable').DataTable().destroy();
    }
    const dash = '<span class="text-muted">—</span>';

    $('#kmMatriksTable').DataTable({
        ajax: {
            url: window.route.update + idKelompok + '/matriks',
            dataSrc: function (json) {
                $('#kmMatriksCount').text(json.data.length);
                return json.data;
            },
        },
        pageLength: 10,
        order: [],
        language: { emptyTable: 'Belum ada Matriks Sample di kelompok ini', zeroRecords: 'Tidak ada Matriks Sample yang cocok' },
        // Search & "Show entries" harus tetap aktif walau form detail sedang mode baca (toggle Edit/Batal men-disable input di form)
        initComplete: function () {
            $(this.api().table().container()).find('input, select').attr('data-no-disable', '').prop('disabled', false).removeClass('disabled');
        },
        columns: [
            { data: null, orderable: false, searchable: false, render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            {
                data: 'kode',
                render: (d, t, r) => `<a href="/testing-matriks-samples?open=${r.id_testing_matriks_sample}" style="font-weight:600;">${escHtml(d || '-')}</a>`,
            },
            { data: 'judul_indonesia', render: (d) => d ? escHtml(d) : dash },
            { data: 'judul_inggris', render: (d) => d ? escHtml(d) : dash },
            { data: 'keterangan', render: (d) => d ? escHtml(d) : dash },
            { data: 'jumlah_point', className: 'text-center', searchable: false },
        ],
    });
}

// Muat tabel saat tab pertama kali dibuka (id kelompok diambil dari tombol hapus di action bar)
$(document).on('shown.bs.tab', '#btnTabKmMatriks', function () {
    const id = $('#detailForm .btn-delete-record').first().data('id');
    if (!id) return;
    if (!$.fn.DataTable.isDataTable('#kmMatriksTable')) {
        initKmMatriksTable(id);
    } else {
        $('#kmMatriksTable').DataTable().columns.adjust();
    }
});

// Jumlah di badge tab dimuat langsung saat detail dirender (dipanggil dari afterLoad di index.js)
function loadKmMatriksCount(id) {
    $.getJSON(window.route.update + id + '/matriks', function (json) {
        $('#kmMatriksCount').text(json.data.length);
    });
}
