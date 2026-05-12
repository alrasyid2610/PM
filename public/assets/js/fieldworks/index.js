let page;
let currentFwoData  = null;
let fwoBoqData      = [];
let fwoBoqViewHtml  = null;
let fwoBoqSnapshot  = null;
let addedBoqIds     = new Set();
let selectedBoq     = null;

// ── Init ───────────────────────────────────────────────────────────────────────
$(document).ready(function () {

    // Select2 untuk modal (di-init tanpa URL dulu — URL disetel saat modal buka)
    $('#selectFwoBoq').select2({
        dropdownParent: $('#modalAddFwoBoq'),
        placeholder: 'Ketik nama Testing Point...',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: function () {
                return window.route.boqSelect2ByWo + (currentFwoData?.id_wo ?? 0) + '?id_fwo=' + (currentFwoData?.id_fwo ?? 0);
            },
            dataType: 'json',
            delay: 250,
            data: p => ({ q: p.term }),
            processResults: d => ({ results: d }),
            cache: false,
        },
    });

    $('#selectFwoBoq').on('select2:select', function (e) {
        const d = e.params.data;
        if (addedBoqIds.has(String(d.id))) {
            Notify.warning('BOQ section ini sudah ditambahkan.');
            $(this).val(null).trigger('change');
            selectedBoq = null;
            resetFwoBoqModal();
            return;
        }
        selectedBoq = d;
        loadFwoBoqSectionPreview(d.id);
    });

    $('#selectFwoBoq').on('select2:clear', function () {
        selectedBoq = null;
        resetFwoBoqModal();
        $('#btnConfirmFwoBoq').prop('disabled', true);
    });

    // Reset modal saat ditutup
    $('#modalAddFwoBoq').on('hidden.bs.modal', function () {
        selectedBoq = null;
        $('#selectFwoBoq').val(null).trigger('change');
        resetFwoBoqModal();
    });

    // Konfirmasi tambah section
    $('#btnConfirmFwoBoq').on('click', function () {
        if (!selectedBoq) return;
        const qty = $('#fwoBoqQtyInput').val() ? parseInt($('#fwoBoqQtyInput').val()) : null;
        const ket = $('#fwoBoqKetInput').val() || null;

        // client-side qty validation: pakai remaining_qty (sisa dari FWO lain)
        const maxAllowed = selectedBoq.remaining_qty ?? selectedBoq.qty_boq ?? null;
        if (qty && maxAllowed !== null && qty > maxAllowed) {
            const boqHint = selectedBoq.qty_boq ? ` (maks BOQ: ${selectedBoq.qty_boq})` : ` (maks: ${maxAllowed})`;
            Notify.warning('Qty tidak boleh melebihi batas' + boqHint);
            return;
        }

        addFwoBoqSection({
            id_boq:           selectedBoq.id,
            id_testing_point: selectedBoq.id_testing_point,
            point_name:       selectedBoq.text,
            qty:              qty,
            boq_qty:          selectedBoq.qty_boq,
            remaining_qty:    selectedBoq.remaining_qty,
            satuan:           selectedBoq.satuan,
            keterangan:       ket,
            items:            window._fwoBoqPreviewItems ?? [],
        });
        bootstrap.Modal.getInstance('#modalAddFwoBoq').hide();
    });

    // Tombol Edit BOQ (delegasi)
    $(document).on('click', '.btn-fwo-boq-edit', function () {
        enterFwoBoqEditMode();
    });

    // Hapus section (delegasi)
    $(document).on('click', '.btn-remove-fwo-boq', function () {
        const boqId = String($(this).closest('.fwo-boq-section').data('boq-id'));
        addedBoqIds.delete(boqId);
        $(this).closest('.fwo-boq-section').remove();
        checkFwoBoqEmpty();
    });

    page = new CrudPageController({
        primaryKey: 'id_fwo',
        renderForm: renderFwoForm,
        initSelect: function () {
            $('#detail_id_wo').on('select2:select', function (e) {
                $('input[name="judul_pekerjaan"]').val(e.params.data.judul || '');
            });
        },
        afterLoad: function (res) {
            currentFwoData = res;
            loadFwoBoqList(res.id_fwo);
        },
    });
});

// ── Load & render view mode ────────────────────────────────────────────────────
function loadFwoBoqList(id_fwo) {
    $('#fwoBoqContent').html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>'
    );

    $.get(window.route.fwoBoqByFwo + id_fwo, function (data) {
        fwoBoqData = data ?? [];
        $('#fwoBoqContent').html(renderFwoBoqView(fwoBoqData));
    }).fail(function () {
        $('#fwoBoqContent').html(
            '<div class="text-center text-danger py-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat data</div>'
        );
    });
}

// ── Enter BOQ edit mode ────────────────────────────────────────────────────────
function enterFwoBoqEditMode() {
    fwoBoqViewHtml = $('#fwoBoqContent').html();
    addedBoqIds.clear();

    let editHtml = renderFwoBoqEditBar();
    editHtml += '<div id="fwoBoqSections"></div>';
    editHtml += '<div id="fwoBoqEmpty" style="display:none;">' +
        '<div class="card"><div class="card-body text-center text-muted py-4">' +
        '<i class="fa-solid fa-layer-group fa-2x mb-2 d-block opacity-25"></i>' +
        'Belum ada section. Klik <strong>+ Tambah Section</strong> untuk menambahkan.' +
        '</div></div></div>';

    $('#fwoBoqContent').html(editHtml);

    // Pre-load existing sections
    if (fwoBoqData.length > 0) {
        fwoBoqData.forEach(function (sec) {
            addFwoBoqSection(sec);
        });
    } else {
        $('#fwoBoqEmpty').show();
    }

    fwoBoqSnapshot = JSON.stringify(collectFwoBoqSections());

    // Bind tombol dalam edit mode
    $('#btnCancelFwoBoq').on('click', function () {
        exitFwoBoqEditMode();
    });

    $('#btnSaveFwoBoq').on('click', function () {
        saveFwoBoq(currentFwoData.id_fwo);
    });

    $('#btnAddFwoBoqSection').on('click', function () {
        selectedBoq = null;
        resetFwoBoqModal();
        $('#selectFwoBoq').val(null).trigger('change');
        new bootstrap.Modal('#modalAddFwoBoq').show();
    });

    // Ubah tombol "Edit BOQ" jadi visual aktif
    $('.btn-fwo-boq-edit')
        .addClass('editing')
        .html('<i class="fa-solid fa-times"></i> Batal Edit');
}

function exitFwoBoqEditMode() {
    addedBoqIds.clear();
    fwoBoqSnapshot = null;
    $('#fwoBoqContent').html(fwoBoqViewHtml);
    fwoBoqViewHtml = null;
    // Kembalikan tombol
    $('.btn-fwo-boq-edit')
        .removeClass('editing')
        .html('<i class="fa-solid fa-pen"></i> Edit BOQ');
}

// ── Save BOQ ───────────────────────────────────────────────────────────────────
function saveFwoBoq(id_fwo) {
    const sections = collectFwoBoqSections();
    if (!sections.length) { Notify.warning('Tambahkan minimal 1 section BOQ'); return; }

    if (JSON.stringify(sections) === fwoBoqSnapshot) {
        Notify.warning('Tidak ada perubahan data');
        return;
    }

    // Client-side qty validation: pakai remaining_qty dari data attribute
    for (const sec of sections) {
        const $sec     = $(`.fwo-boq-section[data-boq-id="${sec.id_boq}"]`);
        const rawRem   = $sec.data('remaining-qty');
        const rawBoq   = $sec.data('boq-qty');
        const maxAllow = rawRem !== '' && rawRem !== undefined ? parseInt(rawRem) : null;
        const boqTotal = rawBoq !== '' && rawBoq !== undefined ? parseInt(rawBoq) : null;
        if (sec.qty && maxAllow !== null && sec.qty > maxAllow) {
            const ptName = $sec.find('.fw-semibold').first().text();
            const hint   = boqTotal ? ` (maks BOQ: ${boqTotal})` : ` (maks: ${maxAllow})`;
            Notify.warning(`Qty section "${ptName}" melebihi batas${hint}`);
            return;
        }
    }

    Notify.confirm('Simpan Fieldwork BOQ?', function () {
        $('#btnSaveFwoBoq').prop('disabled', true)
            .html('<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: window.route.fwoBoqUpdate + id_fwo,
            method: 'PUT',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': window.route.csrf },
            data: JSON.stringify({ sections }),
            success: function () {
                Notify.success('Fieldwork BOQ berhasil diperbarui');
                addedBoqIds.clear();
                fwoBoqSnapshot = null;
                fwoBoqViewHtml = null;
                $('.btn-fwo-boq-edit').removeClass('editing').html('<i class="fa-solid fa-pen"></i> Edit BOQ');
                loadFwoBoqList(id_fwo);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan Fieldwork BOQ');
                $('#btnSaveFwoBoq').prop('disabled', false)
                    .html('<i class="fa-solid fa-check me-1"></i> Simpan BOQ');
            },
        });
    });
}

// ── Section management ─────────────────────────────────────────────────────────
function addFwoBoqSection(sec) {
    const html = renderFwoBoqSectionEdit(sec);
    const $el  = $(html);
    $el.attr('data-boq-qty', sec.boq_qty ?? '');
    $('#fwoBoqEmpty').hide();
    $('#fwoBoqSections').append($el);
    initNumericMask($el);
    addedBoqIds.add(String(sec.id_boq));
}

function checkFwoBoqEmpty() {
    if ($('.fwo-boq-section').length === 0) $('#fwoBoqEmpty').show();
}

function collectFwoBoqSections() {
    const sections = [];
    $('.fwo-boq-section').each(function () {
        const $sec = $(this);
        sections.push({
            id_boq:     parseInt($sec.data('boq-id')),
            qty:        rawNumVal($sec.find('.input-fwo-qty')[0]),
            keterangan: $sec.find('.input-fwo-ket').val() || null,
        });
    });
    return sections;
}

// ── Modal helpers ──────────────────────────────────────────────────────────────
function loadFwoBoqSectionPreview(id_boq) {
    resetFwoBoqModal();
    $('#fwoBoqModalLoading').removeClass('d-none');

    const sectionUrl = window.route.boqSectionItems + id_boq + '/section-items?id_fwo=' + (currentFwoData?.id_fwo ?? 0);
    $.get(sectionUrl, function (res) {
        $('#fwoBoqModalLoading').addClass('d-none');
        const items = res.items ?? [];

        if (!items.length) {
            $('#fwoBoqModalEmpty').removeClass('d-none');
        } else {
            $('#fwoBoqModalItemsList').html(
                items.map((item, j) => renderFwoBoqModalItem(item, j)).join('')
            );
        }

        // Pakai remaining_qty (sisa setelah FWO lain) sebagai batas, bukan qty_boq total
        const maxVal = res.remaining_qty ?? res.qty_boq ?? null;
        if (maxVal !== null) {
            const satuan = res.satuan ? ' ' + res.satuan : '';
            $('#fwoBoqMaxHint').html(`<span class="text-danger small">(sisa ${maxVal}${satuan})</span>`);
            $('#fwoBoqQtyInput').attr('max', maxVal);
        }

        // Simpan remaining_qty ke selectedBoq untuk validasi saat konfirmasi
        if (selectedBoq) {
            selectedBoq.remaining_qty = res.remaining_qty ?? res.qty_boq ?? null;
        }

        window._fwoBoqPreviewItems = items;
        $('#fwoBoqModalPreview').removeClass('d-none');
        $('#btnConfirmFwoBoq').prop('disabled', false);
    }).fail(function () {
        $('#fwoBoqModalLoading').addClass('d-none');
        $('#fwoBoqModalEmpty').removeClass('d-none');
    });
}

function resetFwoBoqModal() {
    $('#fwoBoqModalLoading, #fwoBoqModalEmpty, #fwoBoqModalPreview').addClass('d-none');
    $('#fwoBoqModalItemsList').empty();
    $('#fwoBoqQtyInput').val('').removeAttr('max');
    $('#fwoBoqKetInput').val('');
    $('#fwoBoqMaxHint').html('');
    $('#btnConfirmFwoBoq').prop('disabled', true);
    window._fwoBoqPreviewItems = [];
}
