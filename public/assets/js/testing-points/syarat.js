// ── Syarat Sample per Testing Point (tab "Syarat") ──────────────────────────────
// Syarat dipilih dari Master Syarat Sample (/syarat-sample), lalu angka isiannya (jumlah botol, volume, ...) diisi
// langsung di baris. Disimpan per baris saat angka diubah (tidak ikut tombol Edit/Simpan form Testing Point).
// Dipakai Lab Sample Reg (tampil saja; cek terpenuhi/tidak menyusul). Mengubah syarat tidak mereset approval.
let pointSyaratCache = [];

function pointSyaratUrl(suffix) {
    return window.route.paket + $('#pointSyaratWrap').data('id') + '/syarat' + (suffix || '');
}

function loadPointSyarat() {
    const $wrap = $('#pointSyaratWrap');
    if (!$wrap.length) return;
    $wrap.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...</div>');
    $.get(pointSyaratUrl())
        .done(function (rows) {
            pointSyaratCache = rows || [];
            renderPointSyarat();
        })
        .fail(function () { $wrap.html('<div class="text-center text-danger py-3">Gagal memuat syarat.</div>'); });
}

// Teks template → HTML dengan kolom angka di tempat tiap isian ({kunci:angka=default}).
function syaratTeksHtml(row, canEdit) {
    const isian = {};
    (row.isian || []).forEach(function (i) { isian[i.key] = i; });
    let out = '', last = 0, m;
    const re = /\{([a-z0-9_]+):([a-z]+)(?:=([^{}]*))?\}/g;
    while ((m = re.exec(row.template))) {
        out += escHtml(row.template.slice(last, m.index));
        const i = isian[m[1]] || {};
        const val = i.value !== null && i.value !== undefined ? i.value : '';
        const ph = i.default !== null && i.default !== undefined ? i.default : '0';
        out += `<input type="text" inputmode="numeric" class="form-control form-control-sm d-inline-block text-center syarat-isian"
                    style="width:78px;margin:0 4px;" data-key="${escHtml(m[1])}" value="${escHtml(val)}" placeholder="${escHtml(ph)}"
                    ${canEdit ? '' : 'disabled'} data-no-disable>`;
        last = re.lastIndex;
    }
    return out + escHtml(row.template.slice(last));
}

function renderPointSyarat() {
    const $wrap = $('#pointSyaratWrap');
    const canEdit = can('testing-points', 'can_update');
    if (!pointSyaratCache.length) {
        $wrap.html(`<div class="text-center text-muted py-4">
            <i class="fa-solid fa-clipboard-check fa-2x d-block mb-2 opacity-25"></i>
            Belum ada syarat sample.${canEdit ? ' Klik <b>+ Syarat</b> untuk memilih dari Master Syarat Sample.' : ''}
        </div>`);
        return;
    }

    const rows = pointSyaratCache.map(function (r, i) {
        return `<tr data-id="${r.id_tp_syarat}">
            <td style="width:40px;color:#94a3b8;">${i + 1}</td>
            <td>${syaratTeksHtml(r, canEdit)}${r.aktif ? '' : ' <span class="badge" style="font-size:10px;background:#f1f5f9;color:#475569;">Master nonaktif</span>'}</td>
            <td class="text-center" style="width:60px;">
                ${canEdit ? `<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-syarat-hapus" data-id="${r.id_tp_syarat}" data-no-disable title="Hapus syarat">
                    <i class="fa-solid fa-trash" style="color:#dc2626;"></i></button>` : ''}
            </td>
        </tr>`;
    }).join('');

    $wrap.html(`<div class="table-responsive"><table class="pm-table">
        <thead><tr><th style="width:40px;">No</th><th>Syarat</th><th style="width:60px;"></th></tr></thead>
        <tbody>${rows}</tbody>
    </table></div>`);
}

// Simpan nilai isian satu baris (dipanggil saat angka diubah)
function simpanSyaratNilai($tr) {
    const nilai = {};
    $tr.find('.syarat-isian').each(function () { nilai[$(this).data('key')] = $(this).val().trim(); });
    $.ajax({
        url: pointSyaratUrl('/' + $tr.data('id')),
        method: 'POST',
        data: { _token: window.route.csrf, _method: 'PUT', nilai: nilai },
        success: function () {
            const row = pointSyaratCache.find(function (r) { return Number(r.id_tp_syarat) === Number($tr.data('id')); });
            if (row) row.isian.forEach(function (i) { i.value = nilai[i.key] === '' ? null : nilai[i.key]; });
            if (window.Notify) Notify.toast('Syarat disimpan');
        },
        error: function (xhr) { Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan syarat.'); loadPointSyarat(); },
    });
}

$(document).on('change', '#pointSyaratWrap .syarat-isian', function () {
    const v = $(this).val().trim();
    if (v !== '' && !/^\d+$/.test(v)) { Notify.warning('Isi dengan angka bulat.'); $(this).val(''); }
    simpanSyaratNilai($(this).closest('tr'));
});

// + Syarat: pilih dari master (yang aktif dan belum dipakai Testing Point ini)
$(document).on('click', '.btn-syarat-add', function () {
    $.get('/syarat-sample/select2').done(function (rows) {
        const dipakai = new Set(pointSyaratCache.map(function (r) { return Number(r.id_syarat); }));
        const pilihan = {};
        (rows || []).filter(function (r) { return !dipakai.has(Number(r.id)); }).forEach(function (r) { pilihan[r.id] = r.text; });

        if (!Object.keys(pilihan).length) {
            Swal.fire('Tidak ada pilihan', (rows || []).length
                ? 'Semua syarat di master sudah dipakai Testing Point ini.'
                : 'Master Syarat Sample masih kosong. Tambahkan dulu di menu Syarat Sample.', 'info');
            return;
        }

        Swal.fire({
            title: 'Tambah syarat sample',
            input: 'select',
            inputOptions: pilihan,
            inputPlaceholder: 'Pilih syarat',
            showCancelButton: true,
            confirmButtonText: 'Tambah',
            cancelButtonText: 'Batal',
            inputValidator: function (v) { return v ? null : 'Pilih syarat dari daftar'; },
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.post(pointSyaratUrl(), { _token: window.route.csrf, id_syarat: r.value })
                .done(function () { Notify.success('Syarat ditambahkan. Isi angkanya di baris syarat.'); loadPointSyarat(); })
                .fail(function (xhr) { Notify.error(xhr.responseJSON?.message || 'Gagal menambah syarat.'); });
        });
    });
});

$(document).on('click', '.btn-syarat-hapus', function () {
    const id = $(this).data('id');
    Notify.confirmDelete('Hapus syarat ini dari Testing Point?', function () {
        $.ajax({
            url: pointSyaratUrl('/' + id),
            method: 'POST',
            data: { _token: window.route.csrf, _method: 'DELETE' },
            success: function () { Notify.success('Syarat dihapus'); loadPointSyarat(); },
            error: function (xhr) { Notify.error(xhr.responseJSON?.message || 'Gagal menghapus syarat.'); },
        });
    });
});
