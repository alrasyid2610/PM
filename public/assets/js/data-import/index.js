// Menu Import Data: pilih jenis + site → template → upload → pratinjau (belum menyimpan) → proses.
// Aturan tiap jenis data ada di server (App\Support\DataImport); halaman ini hanya alur umum.

var importToken = null; // token file pratinjau yang menunggu diproses

var optionCache = {}; // source => Promise daftar opsi (dimuat sekali per sumber, difilter di klien — pola Select2 proyek)

function loadOptions(source) {
    if (!optionCache[source]) {
        optionCache[source] = $.get(window.route.options + '/' + encodeURIComponent(source));
    }
    return optionCache[source];
}

$(document).ready(function () {
    $('#importJenis').select2({ placeholder: 'Pilih jenis data', width: '100%', allowClear: true });

    $('#importJenis').on('change', function () {
        const def = (window.importJenis || {})[$(this).val()];
        $('#importJenisNote').text(def && def.note ? def.note : '').toggle(!!(def && def.note));
        renderContexts(def);
        resetPreview();
        refreshState();
    });

    $(document).on('change', '.import-ctx', function () { resetPreview(); refreshState(); });
    $('#importFile').on('change', function () { resetPreview(); refreshState(); });
    $('#btnPreview').on('click', previewFile);
    $(document).on('click', '#btnProcess', processImport);
    $(document).on('click', '#btnCancelPreview', function () {
        $('#importFile').val('');
        resetPreview();
        refreshState();
    });
});

// Pilihan tambahan sesuai definisi jenis data (mis. Site) — jenis tanpa konteks tidak menampilkan apa pun
function renderContexts(def) {
    const $wrap = $('#importContextWrap').empty();
    const ctxs = (def && def.contexts) || [];
    $wrap.toggle(ctxs.length > 0);

    ctxs.forEach(function (c) {
        const $col = $('<div class="mb-2"></div>');
        $col.append('<label class="form-label required">' + escHtml(c.label) + '</label>');
        const $sel = $('<select class="form-select import-ctx" data-ctx-key="' + escHtml(c.key) + '"><option value=""></option></select>');
        $col.append($sel);
        $wrap.append($col);

        $sel.select2({ placeholder: 'Pilih ' + c.label.toLowerCase(), width: '100%', allowClear: true });
        loadOptions(c.source).done(function (list) {
            list.forEach(function (o) { $sel.append(new Option(o.text, o.id, false, false)); });
            $sel.val(null).trigger('change.select2');
        }).fail(function () { Notify.error('Gagal memuat daftar ' + c.label.toLowerCase() + '.'); });
    });
}

// {jenis, ctx:{key:nilai}, lengkap:bool}
function selected() {
    const ctx = {};
    let lengkap = !!$('#importJenis').val();
    $('.import-ctx').each(function () {
        const v = $(this).val();
        ctx[$(this).data('ctx-key')] = v;
        if (!v) lengkap = false;
    });
    return { jenis: $('#importJenis').val(), ctx: ctx, lengkap: lengkap };
}

function refreshState() {
    const s = selected();
    const ready = s.lengkap;

    $('#btnDownloadTemplate')
        .toggleClass('disabled', !ready)
        .attr('aria-disabled', !ready)
        .attr('href', ready ? window.route.template + '?' + $.param({ jenis: s.jenis, ctx: s.ctx }) : '#');

    $('#importFile').prop('disabled', !ready);
    $('#btnPreview').prop('disabled', !(ready && $('#importFile')[0].files.length));
}

function resetPreview() {
    importToken = null;
    $('#importPreviewWrap, #importResultWrap').hide();
    $('#importPreviewBody, #importResultBody').empty();
}

function errMsg(xhr, fallback) {
    const j = xhr.responseJSON;
    if (j && j.errors) return Object.values(j.errors).flat().join(' ');
    return (j && j.message) || fallback;
}

// ── Pratinjau ────────────────────────────────────────────────────────────────
function previewFile() {
    const s = selected();
    const file = $('#importFile')[0].files[0];
    if (!s.lengkap) return Notify.warning('Lengkapi pilihan data terlebih dahulu.');
    if (!file) return Notify.warning('Pilih file Excel terlebih dahulu.');

    const fd = new FormData();
    fd.append('_token', window.route.csrf);
    fd.append('jenis', s.jenis);
    Object.keys(s.ctx).forEach(function (k) { fd.append('ctx[' + k + ']', s.ctx[k]); });
    fd.append('file', file);

    const $btn = $('#btnPreview');
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Memeriksa...');
    resetPreview();

    $.ajax({ url: window.route.preview, method: 'POST', data: fd, processData: false, contentType: false })
        .done(function (res) {
            importToken = res.token;
            renderPreview(res);
        })
        .fail(function (xhr) { Notify.error(errMsg(xhr, 'File gagal diperiksa.')); })
        .always(function () {
            $btn.html('<i class="fa-solid fa-magnifying-glass-chart me-1"></i> Periksa File');
            refreshState();
        });
}

function statusBadge(st) {
    const map = {
        valid: ['#dcfce7', '#166534', 'Siap diimpor'],
        skip:  ['#fef3c7', '#92400e', 'Dilewati'],
        error: ['#fee2e2', '#991b1b', 'Error'],
    }[st];
    return '<span class="badge rounded-pill" style="background:' + map[0] + ';color:' + map[1] + ';">' + map[2] + '</span>';
}

function renderPreview(res) {
    const c = res.count;
    const rows = res.rows.map(function (r) {
        return '<tr>' +
            '<td style="text-align:center;color:#9ca3af;">' + r.no + '</td>' +
            '<td>' + escHtml(r.a) + '</td>' +
            '<td>' + escHtml(r.b) + '</td>' +
            '<td>' + statusBadge(r.status) + '</td>' +
            '<td style="font-size:12px;color:#64748b;">' + escHtml(r.message || '') + '</td>' +
        '</tr>';
    }).join('');

    const aksi = c.valid > 0
        ? '<button type="button" id="btnProcess" class="btn btn-primary btn-sm"><i class="fa-solid fa-upload me-1"></i> Proses Import (' + c.valid + ' baris)</button>'
        : '<span class="text-muted" style="font-size:13px;">Tidak ada baris yang bisa diimpor. Perbaiki file lalu periksa ulang.</span>';

    $('#importPreviewBody').html(
        '<div class="d-flex align-items-center gap-2 flex-wrap mb-3">' +
            '<span style="font-weight:600;">' + res.total + ' baris terbaca</span>' +
            '<span class="badge rounded-pill" style="background:#dcfce7;color:#166534;">Siap diimpor: ' + c.valid + '</span>' +
            '<span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;">Dilewati (duplikat): ' + c.skip + '</span>' +
            '<span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;">Error: ' + c.error + '</span>' +
        '</div>' +
        '<div class="table-responsive" style="max-height:420px;overflow:auto;border:1px solid #e5e7eb;border-radius:8px;">' +
            '<table class="pm-table" style="margin:0;"><thead><tr>' +
                '<th style="width:70px;text-align:center;">BARIS</th><th>' + escHtml((res.labels[0] || '').toUpperCase()) + '</th><th>' + escHtml((res.labels[1] || '').toUpperCase()) + '</th><th style="width:130px;">STATUS</th><th>KETERANGAN</th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table>' +
        '</div>' +
        (res.dipotong ? '<div class="text-muted mt-2" style="font-size:12px;">Menampilkan 300 baris pertama; seluruh ' + res.total + ' baris tetap diperiksa dan diproses.</div>' : '') +
        '<div class="d-flex align-items-center gap-2 mt-3">' + aksi +
            '<button type="button" id="btnCancelPreview" class="btn btn-light btn-sm">Batal</button></div>'
    );
    $('#importPreviewWrap').show();
}

// ── Proses ───────────────────────────────────────────────────────────────────
function processImport() {
    const s = selected();
    if (!importToken) return;

    Swal.fire({
        title: 'Proses import sekarang?',
        html: 'Baris yang <strong>siap diimpor</strong> akan disimpan. Baris duplikat atau error dilewati dan dilaporkan di akhir.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Proses',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then(function (r) {
        if (!r.isConfirmed) return;

        const $btn = $('#btnProcess');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...');

        $.post(window.route.process, { _token: window.route.csrf, jenis: s.jenis, ctx: s.ctx, token: importToken })
            .done(function (res) {
                importToken = null;
                $('#importFile').val('');
                $('#importPreviewWrap').hide();
                renderResult(res);
                refreshState();
                Notify.success('Import selesai');
            })
            .fail(function (xhr) {
                Notify.error(errMsg(xhr, 'Import gagal.'));
                $btn.prop('disabled', false).html('<i class="fa-solid fa-upload me-1"></i> Proses Import');
            });
    });
}

function renderResult(res) {
    const s = res.summary;
    const download = res.result_file
        ? '<a href="' + window.route.result + '/' + encodeURIComponent(res.result_file) + '" class="btn btn-outline-success btn-sm ms-auto">' +
              '<i class="fa-solid fa-file-excel me-1"></i> Download Hasil (dengan kolom Status)</a>'
        : '';

    const catatan = (res.catatan || []).length
        ? '<div class="mt-3" style="font-size:13px;font-weight:600;">Baris yang dilewati / error:</div>' +
          '<ul class="mb-0 mt-1" style="padding-left:18px;font-size:12px;color:#92400e;">' +
          res.catatan.map(function (c) { return '<li>' + escHtml(c) + '</li>'; }).join('') +
          (res.catatan_total > res.catatan.length ? '<li>…dan ' + (res.catatan_total - res.catatan.length) + ' lainnya (lihat file hasil)</li>' : '') +
          '</ul>'
        : '';

    $('#importResultBody').html(
        '<div class="d-flex align-items-center gap-2 flex-wrap">' +
            '<span class="badge rounded-pill" style="background:#dcfce7;color:#166534;">Ditambahkan: ' + s.inserted + '</span>' +
            '<span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;">Dilewati: ' + s.skipped + '</span>' +
            '<span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;">Error: ' + s.errors + '</span>' +
            download +
        '</div>' + catatan
    );
    $('#importResultWrap').show();
}
