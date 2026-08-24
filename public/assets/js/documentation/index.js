let page;

$(document).ready(function () {
    // Edit/Simpan/Batal untuk konten Quill — dibind di $(document) (bukan
    // #detailContent), karena sistem toggle edit bawaan (bindEditToggle)
    // memanggil .off("click", ".btn-edit-context") TANPA namespace tiap kali
    // detail panel di-render ulang — itu ikut menghapus handler apapun yang
    // dipasang di #detailContent untuk selector yang sama, walau pakai
    // namespace berbeda. Dibind di $(document) supaya tidak collide (pola
    // yang sama dipakai .btn-delete-record di bawah).
    //
    // Penting: karena native event bubbling, handler bawaan yang dipasang di
    // #detailContent (lebih dekat ke tombol) SELALU jalan duluan sebelum
    // handler di sini (dipasang di document, lebih jauh) — jadi pas kode di
    // bawah ini jalan, class "editing" & tombol Simpan SUDAH di-toggle oleh
    // sistem bawaan. Makanya deteksi masuk-edit-atau-tidak pakai keberadaan
    // tombol Simpan (elemen baru yang ditambahkan sistem bawaan), bukan class.
    $(document).on('click', '.btn-edit-context', function () {
        if (!$(this).closest('#detailContent').length) return;
        const entering = $('#detailContent .btn-save-context').length > 0;

        if (entering) {
            const html = $('#documentation-view').html() || '';
            $('#documentation-view').hide();
            $('#documentation-editor-edit').show();

            if (window._docEditQuill) {
                window._docEditQuill.root.innerHTML = html;
            } else {
                window._docEditQuill = initDocumentationEditor('#documentation-editor-edit', html);
            }
        } else {
            $('#documentation-editor-edit').hide();
            $('#documentation-view').show();
        }
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirmDelete('Hapus Dokumentasi ini?', function () {
            $.ajax({
                url: window.route.update + id,
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' },
                success: function (res) {
                    Notify.success(res.message || 'Data berhasil dihapus');
                    setTimeout(function () { window.location.href = window.location.pathname; }, 1000);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                },
            });
        });
    });

    page = new CrudPageController({
        primaryKey: "id_documentation",
        renderForm: renderForm,

        initSelect: function (res) {
            // Quill instance lama (kalau ada, dari record sebelumnya) sudah tidak
            // valid begitu #detailContent di-render ulang — buang referensinya
            // supaya tidak nyangkut ke DOM node yang sudah dibuang.
            window._docEditQuill = null;

            try {
                initTagsSelect2(res.tags || []);
            } catch (e) {
                console.error('Gagal inisialisasi Tags select2:', e);
            }
        },
    });
});

function initTagsSelect2(currentTags) {
    const $sel = $('#detail_tags');
    if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
    $sel.empty();

    (currentTags || []).forEach(function (t) {
        $sel.append(new Option(t, t, true, true));
    });

    $sel.select2({
        width: '100%',
        tags: true,
        placeholder: 'Pilih atau ketik tag baru',
        dropdownParent: $('#detailContent'),
        ajax: {
            url: window.route.tagSelect2,
            delay: 200,
            dataType: 'json',
            data: (p) => ({ q: p.term ?? '' }),
            processResults: (d) => ({ results: d }),
        },
    });
}
