// ─── QUILL EDITOR HELPER (dipakai create.blade.php & detail panel) ─────────

function initDocumentationEditor(selector, initialHtml) {
    const quill = new Quill(selector, {
        theme: 'snow',
        modules: {
            toolbar: {
                container: [
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ color: [] }, { background: [] }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ align: [] }],
                    ['link', 'image'],
                    ['clean'],
                ],
                handlers: {
                    image: function () {
                        const input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', 'image/*');
                        input.click();

                        input.onchange = () => {
                            const file = input.files[0];
                            if (!file) return;

                            const range = quill.getSelection(true);
                            quill.insertText(range.index, 'Mengunggah gambar...', { italic: true });

                            const fd = new FormData();
                            fd.append('image', file);
                            fd.append('_token', window.route.csrf);

                            $.ajax({
                                url: window.route.uploadImage,
                                method: 'POST',
                                data: fd,
                                processData: false,
                                contentType: false,
                            })
                                .done(function (res) {
                                    quill.deleteText(range.index, 'Mengunggah gambar...'.length);
                                    quill.insertEmbed(range.index, 'image', res.url);
                                    quill.setSelection(range.index + 1);
                                })
                                .fail(function () {
                                    quill.deleteText(range.index, 'Mengunggah gambar...'.length);
                                    Notify.error('Gagal mengunggah gambar');
                                });
                        };
                    },
                },
            },
        },
    });

    if (initialHtml) {
        quill.root.innerHTML = initialHtml;
    }

    // Sync sekali di awal — supaya kalau user langsung klik Simpan tanpa
    // mengubah apapun, #konten-hidden tidak kosong (text-change belum tentu
    // sempat kepicu kalau tidak ada perubahan sama sekali).
    $('#konten-hidden').val(quill.root.innerHTML);

    // Sinkronkan textarea tersembunyi (#konten-hidden) setiap kali konten
    // berubah — BUKAN cuma pas klik Simpan. Ini sengaja supaya tidak
    // bergantung urutan handler klik ".btn-save-context" (handler bawaan yang
    // langsung submit form ada di #detailContent, lebih dekat ke tombol jadi
    // selalu jalan lebih dulu dari handler manapun yang dipasang di document
    // — kalau sync konten cuma dilakukan di situ, hasilnya keburu ketinggalan).
    quill.on('text-change', function () {
        $('#konten-hidden').val(quill.root.innerHTML);
    });

    return quill;
}

// ─── DETAIL PANEL ───────────────────────────────────────────────────────────

function renderForm(res) {
    const tagsHtml = (res.tags || []).map(function (t) {
        return `<span class="pm-badge" style="background:#eef2ff;color:#4338ca;font-size:11px;margin-right:4px;">${escHtml(t)}</span>`;
    }).join('') || '<span class="text-muted" style="font-size:12px;">Belum ada tag</span>';

    return `
<form id="detailForm">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="_token" value="${window.route.csrf}">
    <input type="hidden" name="id_documentation" value="${res.id_documentation}">

    ${formGroup.actionBar({
        number: escHtml(res.judul ?? '—'),
        createdAt: escHtml(res.created_at ?? '—'),
        updatedAt: escHtml(res.updated_at ?? '—'),
        deleteId: res.id_documentation,
        editText: 'Edit Dokumentasi',
        noWrap: true,
        subtitle: `
            <div class="d-flex align-items-center gap-2 detail-date" style="margin:0;">
                <span class="pm-badge" style="background:#e0f2fe;color:#0369a1;font-size:11px;font-weight:600;">${escHtml(res.modul ?? '—')}</span>
                ${res.is_published
                    ? '<span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:11px;font-weight:600;">Published</span>'
                    : '<span class="badge rounded-pill" style="background:#f1f5f9;color:#64748b;font-size:11px;font-weight:600;">Draft</span>'}
                <span>Dibuat oleh ${escHtml(res.pembuat ?? '—')}</span>
            </div>
        `,
    })}

    <div class="pm-tab-card">
        <div class="pm-tab-header">
            <ul class="pm-tab-nav" id="docDetailTabs" role="tablist">
                <li role="presentation">
                    <button class="pm-tab-btn active" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tabDocInfo">
                        <i class="fa-solid fa-book me-1" style="color:#1a3a6e;font-size:11px;"></i>
                        Dokumentasi
                    </button>
                </li>
            </ul>
            <div class="pm-tab-actions">
                <div id="docTabActionsInfo" class="d-flex align-items-center gap-2"></div>
            </div>
        </div>
        <div class="pm-tab-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabDocInfo" role="tabpanel">
                    <div class="row g-3">
                        ${formGroup.sectionCard(
                            { icon: 'fa-book', color: 'icon-navy', title: 'Informasi Dokumentasi', subtitle: 'Judul, modul, dan tag' },
                            `<div class="row g-3 form-1">
                                ${formGroup.text("judul", "Judul", res.judul, true, { className: "col-md-8" })}
                                ${formGroup.select("modul", "Modul", res.modul,
                                    (window.documentationModuleOptions || []).map(m => ({ value: m, label: m })),
                                    { className: "col-md-4", required: true }
                                )}
                                <div class="col-md-12">
                                    <label class="form-label">Tags</label>
                                    <select name="tags[]" id="detail_tags" class="form-select" multiple></select>
                                </div>
                                ${formGroup.text("ringkasan", "Ringkasan", res.ringkasan, false, { className: "col-md-8" })}
                                ${formGroup.select("is_published", "Status", res.is_published ? 1 : 0,
                                    [{ value: 1, label: "Published" }, { value: 0, label: "Draft" }],
                                    { className: "col-md-4" }
                                )}
                            </div>`
                        )}

                        ${formGroup.sectionCard(
                            { icon: 'fa-pen-to-square', color: 'icon-green', title: 'Konten', subtitle: 'Isi dokumentasi lengkap' },
                            `<div class="form-2">
                                <div id="documentation-view" class="ql-editor" style="min-height:200px;padding:0;">${res.konten || ''}</div>
                                <div id="documentation-editor-edit" style="display:none;height:420px;background:#fff;"></div>
                                <textarea name="konten" id="konten-hidden" style="display:none;"></textarea>
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
