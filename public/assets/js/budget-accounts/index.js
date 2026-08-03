$(document).ready(function () {
    loadAccountData();
    initEvents();
});

function loadAccountData() {
    $('#account-table-wrap').html('<div class="text-center text-muted py-5"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...</div>');

    $.get('/budget-accounts/data')
        .done(function (res) {
            $('#account-table-wrap').html(renderAccountTable(res.data || []));
        })
        .fail(function () {
            $('#account-table-wrap').html('<div class="text-center text-danger py-4">Gagal memuat data.</div>');
        });
}

function renderAccountTable(parents) {
    if (!parents.length) {
        return `<div class="text-center text-muted py-5">
            <i class="fa-solid fa-layer-group fa-2x d-block mb-2 opacity-25"></i>
            Belum ada akun. Tambah kategori terlebih dahulu.
        </div>`;
    }

    const rows = parents.map(function (p, idx) {
        const statusBadge = p.is_aktif
            ? `<span class="pm-badge pm-badge--completed" style="font-size:10px;">Aktif</span>`
            : `<span class="pm-badge pm-badge--cancelled" style="font-size:10px;">Non-aktif</span>`;

        const hasChildren = !!(p.children || []).length;
        const childCount  = (p.children || []).length;

        const childRows = (p.children || []).map(function (c) {
            const cStatus = c.is_aktif
                ? `<span class="pm-badge pm-badge--completed" style="font-size:10px;">Aktif</span>`
                : `<span class="pm-badge pm-badge--cancelled" style="font-size:10px;">Non-aktif</span>`;
            return `<tr class="account-child-row" data-parent="${p.id_account}" style="background:#fafffe;">
                <td style="text-align:center;color:#94a3b8;font-size:12px;">
                    <i class="fa-solid fa-turn-down-right" style="color:#cbd5e1;font-size:11px;"></i>
                </td>
                <td style="padding-left:28px;color:#475569;font-size:13px;">
                    ${escHtml(c.nama)}
                </td>
                <td><code style="font-size:11px;color:#64748b;">${escHtml(c.kode)}</code></td>
                <td>${cStatus}</td>
                <td class="text-center" style="white-space:nowrap;">
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-account-edit"
                        data-id="${c.id_account}" title="Edit" style="font-size:11px;">
                        <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-account-delete"
                        data-id="${c.id_account}" data-nama="${escHtml(c.nama)}" title="Hapus" style="font-size:11px;">
                        <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
                    </button>
                </td>
            </tr>`;
        }).join('');

        const emptyRow = !hasChildren
            ? `<tr class="account-child-row" data-parent="${p.id_account}">
                 <td colspan="5" style="padding-left:40px;color:#94a3b8;font-size:12px;font-style:italic;border-bottom:none;">
                     Belum ada sub kategori
                 </td>
               </tr>`
            : '';

        const chevron = `<i class="fa-solid fa-chevron-down account-chevron" style="font-size:10px;color:#0f766e;transition:transform .2s;"></i>`;
        const countBadge = hasChildren
            ? `<span style="font-size:10px;background:#dcfce7;color:#166534;border-radius:10px;padding:1px 7px;margin-left:6px;">${childCount}</span>`
            : '';

        return `
        <tr class="account-parent-row" data-id="${p.id_account}" style="background:#f0fdf4;border-top:2px solid #bbf7d0;cursor:pointer;" title="Klik untuk expand/collapse">
            <td style="text-align:center;width:36px;color:#0f766e;font-weight:600;font-size:13px;">${idx + 1}</td>
            <td class="fw-semibold" style="color:#0f766e;">
                <span style="display:inline-flex;align-items:center;gap:6px;">
                    ${chevron}
                    <i class="fa-solid fa-layer-group" style="color:#0f766e;font-size:11px;"></i>
                    ${escHtml(p.nama)}
                    ${countBadge}
                </span>
            </td>
            <td><code style="font-size:11px;background:#dcfce7;color:#166534;padding:2px 6px;border-radius:4px;">${escHtml(p.kode)}</code></td>
            <td>${statusBadge}</td>
            <td class="text-center" style="white-space:nowrap;" onclick="event.stopPropagation()">
                ${can('budget-accounts', 'can_create') ? `<button type="button" class="btn btn-sm py-0 px-2 me-1 btn-add-item"
                    data-id-parent="${p.id_account}" data-parent-nama="${escHtml(p.nama)}"
                    style="font-size:11px;border:1px solid #0f766e;color:#0f766e;background:transparent;" title="Tambah sub kategori">
                    <i class="fa-solid fa-plus"></i> Sub Kategori
                </button>` : ''}
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 btn-account-edit"
                    data-id="${p.id_account}" title="Edit" style="font-size:11px;">
                    <i class="fa-solid fa-pen-to-square" style="color:#1e40af;"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-account-delete"
                    data-id="${p.id_account}" data-nama="${escHtml(p.nama)}" title="Hapus" style="font-size:11px;">
                    <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
                </button>
            </td>
        </tr>
        ${childRows}${emptyRow}`;
    }).join('');

    return `<div class="table-responsive">
        <table class="pm-table">
            <thead>
                <tr>
                    <th style="width:36px;text-align:center;">No.</th>
                    <th>NAMA</th>
                    <th style="width:140px;">KODE</th>
                    <th style="width:100px;">STATUS</th>
                    <th style="min-width:200px;text-align:center;">AKSI</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
}

function openAccountModal({ id, idParent, parentNama, isEdit, data }) {
    const isItem = !!idParent;
    const icon   = isItem ? 'fa-circle-dot' : 'fa-layer-group';
    const label  = (isEdit ? 'Edit' : 'Tambah') + (isItem ? ` Sub Kategori — ${parentNama}` : ' Kategori');

    $('#accountModalLabel').html(
        `<i class="fa-solid ${icon} me-2" style="color:#0f766e;"></i>${escHtml(label)}`
    );

    // Label field nama berubah konteks
    $('#accountModal-label-nama').html(
        (isItem ? 'Nama Sub Kategori' : 'Nama Kategori') + ' <span class="text-danger">*</span>'
    );

    $('#accountModal-id').val(isEdit && data ? data.id_account : '');
    $('#accountModal-id-parent').val(idParent || '');
    $('#accountModal-nama').val(isEdit && data ? data.nama : '');
    $('#accountModal-kode').val(isEdit && data ? data.kode : '');
    $('#accountModal-keterangan').val(isEdit && data ? (data.keterangan || '') : '');
    $('#accountModal-is_aktif').val(isEdit && data ? String(data.is_aktif) : '1');
    $('#accountModal-aktif-wrap').toggle(!!isEdit);

    bootstrap.Modal.getOrCreateInstance(document.getElementById('accountModal')).show();
    setTimeout(function () { $('#accountModal-nama').focus(); }, 300);
}

function initEvents() {
    // Expand / Collapse parent row
    $(document).on('click', '.account-parent-row', function () {
        const id    = $(this).data('id');
        const $rows = $(`.account-child-row[data-parent="${id}"]`);
        const $chev = $(this).find('.account-chevron');
        const open  = $rows.first().is(':visible');

        if (open) {
            $rows.hide();
            $chev.css('transform', 'rotate(-90deg)');
        } else {
            $rows.show();
            $chev.css('transform', 'rotate(0deg)');
        }
    });

    // Tambah Kategori
    $(document).on('click', '#btn-add-kategori', function () {
        openAccountModal({ isEdit: false });
    });

    // Tambah Sub Kategori (child)
    $(document).on('click', '.btn-add-item', function () {
        const idParent   = $(this).data('id-parent');
        const parentNama = $(this).data('parent-nama');
        openAccountModal({ idParent, parentNama, isEdit: false });
    });

    // Edit
    $(document).on('click', '.btn-account-edit', function () {
        const id = $(this).data('id');
        $.get(`/budget-accounts/${id}/show`)
            .done(function (r) {
                openAccountModal({
                    isEdit:     true,
                    idParent:   r.id_parent || null,
                    parentNama: r.parent_nama || '',
                    data:       r,
                });
            })
            .fail(function () { Swal.fire('Gagal', 'Data tidak ditemukan.', 'error'); });
    });

    // Hapus
    $(document).on('click', '.btn-account-delete', function () {
        const id   = $(this).data('id');
        const nama = $(this).data('nama');

        Swal.fire({
            title: 'Hapus Akun?',
            html: `<b>${escHtml(nama)}</b> akan dihapus.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ef4444',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: `/budget-accounts/${id}`,
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' }
            })
                .done(function () {
                    loadAccountData();
                    Swal.fire({ icon: 'success', title: 'Dihapus', timer: 1000, showConfirmButton: false });
                })
                .fail(function (xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Tidak dapat menghapus.', 'error');
                });
        });
    });

    // Simpan
    $(document).on('click', '#accountModal-btn-save', function () {
        const id       = $('#accountModal-id').val();
        const idParent = $('#accountModal-id-parent').val();
        const nama     = $('#accountModal-nama').val().trim();
        const kode     = $('#accountModal-kode').val().trim().toUpperCase();

        if (!nama) return Swal.fire('Perhatian', 'Nama wajib diisi.', 'warning');
        if (!kode) return Swal.fire('Perhatian', 'Kode wajib diisi.', 'warning');

        const isEdit  = !!id;
        const url     = isEdit ? `/budget-accounts/${id}` : '/budget-accounts';
        const payload = {
            _token:     window.route.csrf,
            nama,
            kode,
            id_parent:  idParent || '',
            keterangan: $('#accountModal-keterangan').val(),
            is_aktif:   $('#accountModal-is_aktif').val() || '1',
        };
        if (isEdit) payload._method = 'PUT';

        $('#accountModal-btn-save').prop('disabled', true);
        $.post(url, payload)
            .done(function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('accountModal')).hide();
                loadAccountData();
                Swal.fire({ icon: 'success', title: 'Tersimpan', timer: 1200, showConfirmButton: false });
            })
            .fail(function (xhr) {
                const errs = xhr.responseJSON?.errors;
                const msg  = errs ? Object.values(errs).flat().join('<br>') : (xhr.responseJSON?.message || 'Terjadi kesalahan.');
                Swal.fire('Gagal', msg, 'error');
            })
            .always(function () { $('#accountModal-btn-save').prop('disabled', false); });
    });
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
