// Tabel "Testing Point" di tab detail Matriks Sample & Kelompok Matriks Sample (DataTables, search/paging sisi klien).
// Pakai: TestingPointTable.tab(prefix) → HTML tab-pane; TestingPointTable.bind(prefix, {url, showMatriks}) → muat saat tab dibuka.
window.TestingPointTable = (function () {
    function badge(aktif) {
        const s = aktif == 1
            ? { bg: '#f0fdf4', color: '#15803d', border: '#bbf7d0', icon: 'fa-circle-check', label: 'Aktif' }
            : { bg: '#f1f5f9', color: '#475569', border: '#e2e8f0', icon: 'fa-circle', label: 'Tidak Aktif' };
        return `<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:6px;background:${s.bg};color:${s.color};font-size:11px;font-weight:600;border:1px solid ${s.border};white-space:nowrap;"><i class="fa-solid ${s.icon}" style="font-size:10px;"></i> ${s.label}</span>`;
    }

    // Tombol tab (di .pm-tab-nav)
    function tabButton(prefix) {
        return `<li role="presentation">
            <button class="pm-tab-btn" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#tab${prefix}Points" id="btnTab${prefix}Points">
                <i class="fa-solid fa-vial me-1" style="color:#0369a1;font-size:11px;"></i>
                Testing Point
                <span class="badge rounded-pill bg-secondary ms-1" id="${prefix}PointCount" style="font-size:10px;">…</span>
            </button>
        </li>`;
    }

    // Isi tab-pane
    function tabPane(prefix, showMatriks) {
        return `<div class="tab-pane fade" id="tab${prefix}Points" role="tabpanel">
            ${formGroup.sectionCard(
                { icon: 'fa-vial', color: 'icon-blue', title: 'Testing Point', subtitle: 'Titik uji yang terkait — klik nama untuk membuka detail' },
                `<div class="table-responsive">
                    <table id="${prefix}PointsTable" class="pm-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Nama Testing Point</th>
                                ${showMatriks ? '<th>Matriks Sample</th>' : ''}
                                <th>Standard</th>
                                <th class="text-center">Halaman</th>
                                <th class="text-center">Item</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>`
            )}
        </div>`;
    }

    function init(prefix, url, showMatriks) {
        const sel = `#${prefix}PointsTable`;
        if ($.fn.DataTable.isDataTable(sel)) $(sel).DataTable().destroy();
        const dash = '<span class="text-muted">—</span>';

        const cols = [
            { data: null, orderable: false, searchable: false, render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'nama', render: (d, t, r) => `<a href="/testing-points?open=${r.id_testing_point}" style="font-weight:600;">${escHtml(d || '-')}</a>` },
        ];
        if (showMatriks) {
            cols.push({
                data: 'matriks_kode',
                render: (d, t, r) => d
                    ? `<span style="font-weight:600;">${escHtml(d)}</span><div class="text-muted" style="font-size:11px;">${escHtml(r.matriks_judul || '')}</div>`
                    : dash,
            });
        }
        cols.push(
            {
                data: 'standard_nomor',
                render: (d, t, r) => d
                    ? `<span style="font-weight:600;">${escHtml(d)}</span><div class="text-muted" style="font-size:11px;">${escHtml(r.standard_judul || '')}</div>`
                    : dash,
            },
            { data: 'nomor_halaman', className: 'text-center', render: (d) => d ? escHtml(d) : dash },
            { data: 'jumlah_item', className: 'text-center', searchable: false },
            { data: 'is_aktif', className: 'text-center', searchable: false, render: (d) => badge(d) },
        );

        $(sel).DataTable({
            ajax: {
                url: url,
                dataSrc: function (json) {
                    $(`#${prefix}PointCount`).text(json.data.length);
                    return json.data;
                },
            },
            pageLength: 10,
            order: [],
            language: { emptyTable: 'Belum ada Testing Point', zeroRecords: 'Tidak ada Testing Point yang cocok' },
            // Search & "Show entries" harus tetap aktif walau form detail sedang mode baca
            initComplete: function () {
                $(this.api().table().container()).find('input, select').attr('data-no-disable', '').prop('disabled', false).removeClass('disabled');
            },
            columns: cols,
        });
    }

    // Pasang pemuat: tabel dibuat saat tab pertama dibuka; badge jumlah dimuat saat detail dirender
    function bind(prefix, getUrl, showMatriks) {
        $(document).off('shown.bs.tab', `#btnTab${prefix}Points`).on('shown.bs.tab', `#btnTab${prefix}Points`, function () {
            const sel = `#${prefix}PointsTable`;
            if (!$.fn.DataTable.isDataTable(sel)) init(prefix, getUrl(), showMatriks);
            else $(sel).DataTable().columns.adjust();
        });
    }

    function loadCount(prefix, url) {
        $.getJSON(url, function (json) { $(`#${prefix}PointCount`).text(json.data.length); });
    }

    return { tabButton, tabPane, bind, loadCount };
})();
