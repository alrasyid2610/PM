// ── File Preview (general / public) ─────────────────────────────────────────
// Engine preview lampiran yang dipakai lintas modul (budget realisasi, output,
// sample, kontrak, dll). Pakai begitu saja:
//
//   ${renderFilePreviewButton(a.attachments)}       // tombol "Lihat" (kosong kalau tidak ada file)
//   openFilePreview(['fwo-budget-actuals/x.pdf'])   // buka langsung
//
// Path relatif terhadap /storage/ (sama seperti kolom attachments di DB).
// Image → <img>, PDF → <iframe>, lainnya → tombol unduh (tidak bisa dipreview).
//
// Diload global dari layouts/app.blade.php — tidak perlu include ulang.

const FILE_PREVIEW_IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const FILE_PREVIEW_PDF_EXT   = ['pdf'];

function filePreviewExt(path) {
    return String(path || '').split('.').pop().toLowerCase();
}

function filePreviewKind(path) {
    const ext = filePreviewExt(path);
    if (FILE_PREVIEW_IMAGE_EXT.includes(ext)) return 'image';
    if (FILE_PREVIEW_PDF_EXT.includes(ext))   return 'pdf';
    return 'other';
}

function filePreviewUrl(path) {
    return '/storage/' + String(path || '').replace(/^\/+/, '');
}

// Nama file untuk tampilan: buang folder dan awalan upload
// (`{table}_{timestamp}_`), sisakan nama asli. Path di storage tidak berubah.
function filePreviewName(path) {
    const base = String(path || '').split('/').pop();
    return base.replace(/^[a-z_\-]+_\d+_/i, '');
}

// Terima array path, atau string JSON (kolom attachments dari DB), atau null.
function normalizeAttachmentList(files) {
    if (!files) return [];
    if (Array.isArray(files)) return files.filter(Boolean);
    try {
        const parsed = JSON.parse(files);
        return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
    } catch (_) {
        return [];
    }
}

// Tombol kecil "Lihat" (atau kosong). Daftar file disimpan di data-files
// (JSON, di-escape) lalu dibaca handler global di bawah.
function renderFilePreviewButton(files, opts) {
    const list = normalizeAttachmentList(files);
    if (!list.length) return '<span class="text-muted" style="font-size:11px;">—</span>';

    const title = (opts && opts.title) || 'Preview Dokumen';
    const payload = escHtml(JSON.stringify(list));
    const label = list.length > 1 ? `Lihat (${list.length})` : 'Lihat';
    return `<button type="button" class="btn btn-sm btn-outline-secondary btn-file-preview py-0 px-2"
        style="font-size:11px;white-space:nowrap;" data-files="${payload}" data-title="${escHtml(title)}" data-no-disable>
        <i class="fa-solid fa-eye me-1"></i>${label}
    </button>`;
}

// Modal preview — dibuat sekali di <body> saat pertama dibutuhkan.
function ensureFilePreviewModal() {
    if (document.getElementById('filePreviewModal')) return;

    const html = `
    <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
            <div class="modal-content" style="height:88vh;">
                <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e2e8f0;">
                    <h6 class="modal-title mb-0" id="filePreviewModalTitle">Preview Dokumen</h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0 d-flex" style="min-height:0;">
                    <div id="filePreviewList" style="width:220px;flex-shrink:0;border-right:1px solid #e2e8f0;overflow-y:auto;padding:8px;"></div>
                    <div id="filePreviewStage" style="flex:1;min-width:0;display:flex;align-items:center;justify-content:center;background:#f8fafc;overflow:auto;"></div>
                </div>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', html);
}

function renderFilePreviewStage(path) {
    const url = filePreviewUrl(path);
    const kind = filePreviewKind(path);

    if (kind === 'image') {
        return `<img src="${escHtml(url)}" alt="${escHtml(filePreviewName(path))}"
            style="max-width:100%;max-height:100%;object-fit:contain;padding:12px;">`;
    }
    if (kind === 'pdf') {
        return `<iframe src="${escHtml(url)}" title="${escHtml(filePreviewName(path))}"
            style="width:100%;height:100%;border:0;"></iframe>`;
    }
    return `<div class="text-center text-muted p-4" style="font-size:13px;">
        <i class="fa-solid fa-file fa-2x d-block mb-2 opacity-50"></i>
        Format ini tidak bisa dipreview.<br>
        <a href="${escHtml(url)}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
            <i class="fa-solid fa-download me-1"></i> Buka / Unduh
        </a>
    </div>`;
}

function openFilePreview(files, opts) {
    const list = normalizeAttachmentList(files);
    if (!list.length) return;

    ensureFilePreviewModal();
    $('#filePreviewModalTitle').text((opts && opts.title) || 'Preview Dokumen');

    let activeIdx = 0;
    function show(idx) {
        activeIdx = idx;
        $('#filePreviewStage').html(renderFilePreviewStage(list[idx]));
        $('#filePreviewList .fp-item').removeClass('active')
            .eq(idx).addClass('active');
    }

    $('#filePreviewList').html(list.map(function (path, i) {
        const icon = { image: 'fa-image', pdf: 'fa-file-pdf', other: 'fa-file' }[filePreviewKind(path)];
        return `<button type="button" class="fp-item btn btn-sm w-100 text-start mb-1 d-flex align-items-center gap-2"
            data-idx="${i}" style="font-size:11px;border:1px solid #e2e8f0;">
            <i class="fa-solid ${icon}"></i>
            <span class="text-truncate">${escHtml(filePreviewName(path))}</span>
        </button>`;
    }).join(''));

    $('#filePreviewList').off('click.filePreview').on('click.filePreview', '.fp-item', function () {
        show(Number($(this).data('idx')));
    });

    show(activeIdx);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('filePreviewModal')).show();
}

// Handler global: tombol .btn-file-preview di mana pun (termasuk di dalam modal lain).
$(document).on('click', '.btn-file-preview', function (e) {
    e.preventDefault();
    const files = $(this).data('files');
    openFilePreview(files, { title: $(this).data('title') });
});
