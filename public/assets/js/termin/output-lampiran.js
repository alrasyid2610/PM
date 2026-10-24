// Lampiran output di modul Termin — tampilan sama dengan tab Output WO:
// satu tombol "Lihat" / "Lihat (n)" (core/filePreview.js, daftar file di modal preview),
// ditambah link Drive bila ada. Dipakai di daftar output yang ditagihkan dan di picker (edit & create).
function terminOutputLampiran(item) {
    var files = [];
    try {
        files = typeof item.attachments === 'string' ? JSON.parse(item.attachments || '[]') : (item.attachments || []);
    } catch (e) { files = []; }

    var html = '';
    if (files && files.length) {
        // Ringkas: satu tombol "Lihat" / "Lihat (n)"; daftar file ada di modal preview
        html = renderFilePreviewButton(files, { title: (item.judul_output || 'Output') + ' — Lampiran' });
    }

    if (item.link_drive) {
        html += (html ? ' ' : '') + '<a href="' + escHtml(item.link_drive) + '" target="_blank" rel="noopener" style="font-size:11px;color:#1a56db;white-space:nowrap;">' +
            '<i class="fa-brands fa-google-drive me-1"></i>Drive</a>';
    }

    return html || '<span class="text-muted" style="font-size:11px;">—</span>';
}
