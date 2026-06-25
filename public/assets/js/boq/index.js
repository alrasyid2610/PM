let page;
let currentBoqData  = null;
let viewSnapshot    = null;
let itemsSnapshot   = null;
let addedPointIds   = new Set();
let selectedPoint   = null;
let editingSectionId = null;
let isViewModeEdit  = false;

// ── Per-item edit helpers ──────────────────────────────────────────────────────
function renderBoqEditBody(sec) {
    return `
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <label class="form-label form-label-sm text-muted mb-1">Item Produk Alternatif</label>
                <input type="text" class="form-control form-control-sm boq-edit-item-produk"
                    value="${escHtml(sec.item_produk_alternate ?? '')}">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm text-muted mb-1">Qty</label>
                <input type="text" inputmode="numeric" class="form-control form-control-sm boq-edit-qty input-num-mask input-num-int"
                    value="${sec.qty ?? ''}">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm text-muted mb-1">Satuan</label>
                <select class="form-select form-select-sm boq-edit-satuan">
                    <option value="">— Pilih —</option>
                    <option value="PCS" ${sec.satuan === 'PCS' ? 'selected' : ''}>PCS</option>
                    <option value="Titik" ${sec.satuan === 'Titik' ? 'selected' : ''}>Titik</option>
                    <option value="Set" ${sec.satuan === 'Set' ? 'selected' : ''}>Set</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm text-muted mb-1">Harga (Rp)</label>
                <input type="text" inputmode="numeric" class="form-control form-control-sm boq-edit-harga input-num-mask"
                    value="${sec.harga ?? ''}">
            </div>
            <div class="col-md-12">
                <div class="boq-edit-total-line text-end" style="font-size:12px;color:#64748b;min-height:18px;margin-bottom:2px;"></div>
            </div>
            <div class="col-md-12">
                <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                <input type="text" class="form-control form-control-sm boq-edit-ket"
                    value="${escHtml(sec.keterangan ?? '')}">
            </div>
        </div>
        <div class="text-muted small fw-semibold mb-2">
            <i class="fa-solid fa-list-check me-1"></i> Items (${(sec.items ?? []).length})
        </div>
        <div>${renderBoqSubItems(sec.items ?? [])}</div>`;
}

function updateBoqItemTotal($container) {
    const qty   = rawNumVal($container.find('.boq-edit-qty')[0]) || 0;
    const harga = rawNumVal($container.find('.boq-edit-harga')[0]) || 0;
    const $line = $container.find('.boq-edit-total-line');
    if (qty && harga) {
        $line.html(
            Number(qty).toLocaleString('en-US') + ' qty &times; Rp ' + Number(harga).toLocaleString('en-US') +
            ' = <strong style="color:#1d4ed8;">Rp ' + Number(qty * harga).toLocaleString('en-US') + '</strong>'
        );
    } else {
        $line.html('');
    }
}

// ── CrudPageController init ────────────────────────────────────────────────────
$(document).ready(function () {

    // Select2 untuk Testing Point di dalam modal (init sekali)
    $('#selectTestingPoint').select2({
        dropdownParent: $('#modalAddSection'),
        placeholder: 'Ketik nama item...',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: window.route.testingPointSelect2,
            dataType: 'json',
            delay: 250,
            data: p => ({ q: p.term }),
            processResults: d => ({ results: d }),
            cache: true,
        },
    });

    $('#selectTestingPoint').on('select2:select', function (e) {
        const id = String(e.params.data.id);
        if (!editingSectionId && addedPointIds.has(id)) {
            Notify.warning('Testing Point ini sudah ditambahkan. Gunakan tombol Edit pada item tersebut.');
            $(this).val(null).trigger('change');
            selectedPoint = null;
            resetModalItems();
            return;
        }
        selectedPoint = { id: e.params.data.id, text: e.params.data.text };
        const preChecked = editingSectionId ? getCurrentItemIds(editingSectionId) : null;
        loadModalItems(e.params.data.id, preChecked);
    });

    $('#selectTestingPoint').on('select2:clear', function () {
        selectedPoint = null;
        resetModalItems();
        $('#btnConfirmSection').prop('disabled', true);
    });

    // Reset modal saat ditutup
    $('#modalAddSection').on('hidden.bs.modal', function () {
        editingSectionId = null;
        isViewModeEdit   = false;
        selectedPoint    = null;
        $('#selectTestingPoint').val(null).trigger('change').prop('disabled', false);
        resetModalItems();
        $('#modalSectionTitle').html('<i class="fa-solid fa-layer-group me-2 text-primary"></i> Tambah Item');
        $('#btnConfirmText').text('Tambah Item');
    });

    // Pilih / Hapus semua — hanya item yang terlihat
    $('#btnCheckAll').on('click', function (e) {
        e.preventDefault();
        $('#modalItemsList .modal-item-row:not(.d-none) .item-check').prop('checked', true);
        updateConfirmBtn();
    });
    $('#btnUncheckAll').on('click', function (e) {
        e.preventDefault();
        $('#modalItemsList .modal-item-row:not(.d-none) .item-check').prop('checked', false);
        updateConfirmBtn();
    });

    // Search filter
    $('#modalItemSearch').on('input', function () {
        const term = $(this).val().toLowerCase();
        let visible = 0;
        $('#modalItemsList .modal-item-row').each(function () {
            const item  = $(this).find('.item-check').data('item') || {};
            const text  = ((item.judul_indonesia || '') + ' ' + (item.judul_inggris || '')).toLowerCase();
            const match = !term || text.includes(term);
            $(this).toggleClass('d-none', !match);
            if (match) visible++;
        });
        $('#modalSearchEmpty').toggleClass('d-none', visible > 0);
    });

    // Konfirmasi section (add / edit)
    $('#btnConfirmSection').on('click', function () {
        const checkedItems = [];
        $('#modalItemsList .item-check:checked').each(function () {
            checkedItems.push($(this).data('item'));
        });
        if (!checkedItems.length) return;

        if (isViewModeEdit && editingSectionId) {
            // Save langsung ke server — update items section ini saja
            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

            const newItemIds = checkedItems.map(it => it.id_testing_item);
            const sections = (currentBoqData.sections ?? []).map(function (s) {
                const itemIds = String(s.id_testing_point) === String(editingSectionId)
                    ? newItemIds
                    : (s.items ?? []).map(it => it.id_testing_item);
                return {
                    id_testing_point:      s.id_testing_point,
                    item_produk_alternate: s.item_produk_alternate ?? null,
                    qty:                   s.qty ?? null,
                    satuan:                s.satuan ?? null,
                    harga:                 s.harga ?? null,
                    keterangan:            s.keterangan ?? null,
                    items:                 itemIds,
                };
            });

            $.ajax({
                url: window.route.update + currentBoqData.id_wo,
                method: 'PUT',
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': window.route.csrf },
                data: JSON.stringify({ sections: sections }),
                success: function () {
                    Notify.success('BOQ berhasil diperbarui');
                    bootstrap.Modal.getInstance('#modalAddSection').hide();
                    page.loadDetail(currentBoqData.id_wo);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan BOQ');
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> <span id="btnConfirmText">Simpan Perubahan</span>');
                },
            });
        } else if (editingSectionId) {
            updateSection(editingSectionId, checkedItems);
            bootstrap.Modal.getInstance('#modalAddSection').hide();
        } else {
            addSection(selectedPoint.id, selectedPoint.text, checkedItems);
            bootstrap.Modal.getInstance('#modalAddSection').hide();
        }
    });

    // Tombol edit section (delegasi — section hanya ada saat edit mode)
    $(document).on('click', '.btn-edit-section', function () {
        const $sec  = $(this).closest('.boq-section');
        const ptId  = String($sec.data('point-id'));
        const ptTxt = $sec.find('.section-point-name').text().trim();

        editingSectionId = ptId;
        selectedPoint    = { id: ptId, text: ptTxt };

        const opt = new Option(ptTxt, ptId, true, true);
        $('#selectTestingPoint').empty().append(opt).trigger('change');
        $('#selectTestingPoint').prop('disabled', true);

        $('#modalSectionTitle').html('<i class="fa-solid fa-pen me-2 text-warning"></i> Edit Item');
        $('#btnConfirmText').text('Simpan Perubahan');

        resetModalItems();
        loadModalItems(ptId, getCurrentItemIds(ptId));
        new bootstrap.Modal('#modalAddSection').show();
    });

    // Hapus section (delegasi)
    $(document).on('click', '.btn-remove-section', function () {
        const ptId = String($(this).closest('.boq-section').data('point-id'));
        addedPointIds.delete(ptId);
        $(this).closest('.boq-section').remove();
        checkEmpty();
    });

    // Accordion toggle — skip jika klik pada tombol aksi
    $(document).on('click', '.pm-accordion-header', function (e) {
        if ($(e.target).closest('.boq-item-view-actions, .boq-item-edit-actions').length) return;
        const $header = $(this);
        const $body   = $header.next('.pm-accordion-collapse');
        const isOpen  = $header.attr('aria-expanded') === 'true';
        $header.attr('aria-expanded', !isOpen);
        $body.slideToggle(150);
    });

    // Per-item Edit — buka modal dengan items terload
    $(document).on('click', '.btn-boq-item-edit', function (e) {
        e.stopPropagation();
        const idx = $(this).data('section-idx');
        const sec = currentBoqData && currentBoqData.sections[idx];
        if (!sec) return;

        const ptId  = String(sec.id_testing_point);
        const ptTxt = sec.point_name ?? ptId;

        editingSectionId = ptId;
        isViewModeEdit   = true;

        const opt = new Option(ptTxt, ptId, true, true);
        $('#selectTestingPoint').empty().append(opt).trigger('change');
        $('#selectTestingPoint').prop('disabled', true);

        $('#modalSectionTitle').html('<i class="fa-solid fa-pen me-2 text-warning"></i> Edit Item');
        $('#btnConfirmText').text('Simpan Perubahan');

        resetModalItems();
        loadModalItems(ptId, new Set(sec.items.map(it => String(it.id_testing_item))));
        new bootstrap.Modal('#modalAddSection').show();
    });

    // Per-item Cancel
    $(document).on('click', '.btn-boq-item-cancel', function (e) {
        e.stopPropagation();
        const idx   = $(this).data('section-idx');
        const sec   = currentBoqData && currentBoqData.sections[idx];
        if (!sec) return;

        const $item = $(this).closest('.pm-accordion-item');
        $item.find('.boq-item-edit-actions').removeClass('active');
        $item.find('.boq-item-view-actions').show();
        $item.find('.pm-accordion-body').html(renderBoqViewBody(sec));
    });

    // Per-item Save
    $(document).on('click', '.btn-boq-item-save', function (e) {
        e.stopPropagation();
        const idx   = $(this).data('section-idx');
        const $item = $(this).closest('.pm-accordion-item');
        const $body = $item.find('.pm-accordion-collapse');
        const $btn  = $(this);

        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

        const qtyEl   = $body.find('.boq-edit-qty')[0];
        const hargaEl = $body.find('.boq-edit-harga')[0];

        // Update section di currentBoqData
        const sec = currentBoqData.sections[idx];
        sec.item_produk_alternate = $body.find('.boq-edit-item-produk').val() || null;
        sec.qty     = rawNumVal(qtyEl) || null;
        sec.satuan  = $body.find('.boq-edit-satuan').val() || null;
        sec.harga   = rawNumVal(hargaEl) || null;
        sec.keterangan = $body.find('.boq-edit-ket').val() || null;

        const sections = currentBoqData.sections.map(function (s) {
            return {
                id_testing_point:      s.id_testing_point,
                item_produk_alternate: s.item_produk_alternate,
                qty:                   s.qty,
                satuan:                s.satuan,
                harga:                 s.harga,
                keterangan:            s.keterangan,
                items:                 (s.items ?? []).map(it => it.id_testing_item),
            };
        });

        $.ajax({
            url: window.route.update + currentBoqData.id_wo,
            method: 'PUT',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': window.route.csrf },
            data: JSON.stringify({ sections }),
            success: function () {
                Notify.success('Item berhasil diperbarui');
                page.loadDetail(currentBoqData.id_wo);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-check" style="font-size:9px;"></i> Simpan');
            },
        });
    });

    // Live total pada edit mode
    $(document).on('input', '.boq-edit-qty, .boq-edit-harga', function () {
        updateBoqItemTotal($(this).closest('.pm-accordion-collapse'));
    });

    // Search accordion BOQ items
    $(document).on('input', '#boqAccordionSearch', function () {
        const term = $(this).val().toLowerCase().trim();
        let visible = 0;
        $('.pm-accordion-item').each(function () {
            const match = !term || ($(this).data('search-text') || '').includes(term);
            $(this).toggle(match);
            if (match) visible++;
        });
        $('#btnClearBoqSearch').toggleClass('d-none', !term);
        $('#boqAccordionEmpty').toggleClass('d-none', visible > 0);
        $('#boqAccordion').toggle(visible > 0);
    });

    $(document).on('click', '#btnClearBoqSearch', function () {
        $('#boqAccordionSearch').val('').trigger('input');
    });

    // Tombol edit BOQ (muncul di view mode)
    $(document).on('click', '.btn-edit-context', function () {
        if (currentBoqData) enterBoqEditMode(currentBoqData);
    });

    page = new CrudPageController({
        primaryKey: 'id_wo',
        renderForm: renderBoqForm,
        noEditBind: true,
        afterLoad: function (res) {
            currentBoqData = res;
        },
    });

    const openId = new URLSearchParams(window.location.search).get('open');
    if (openId) {
        page.loadDetail(parseInt(openId));
        const detailTab = document.querySelector('#detail-tab');
        if (detailTab) new bootstrap.Tab(detailTab).show();
    }
});

// ── Enter edit mode ────────────────────────────────────────────────────────────
function enterBoqEditMode(res) {
    addedPointIds.clear();
    selectedPoint    = null;
    editingSectionId = null;

    // Simpan snapshot konten tab BOQ saja
    const $tabBoq = $('#tabBoqItems');
    viewSnapshot = $tabBoq.html();

    // Ganti konten tab BOQ dengan editor
    $tabBoq.html(renderBoqEditContent());

    // Aktifkan tab BOQ
    const boqTabBtn = document.querySelector('[data-bs-target="#tabBoqItems"]');
    if (boqTabBtn) new bootstrap.Tab(boqTabBtn).show();

    // Ganti tombol Edit → Batal + Simpan di action bar
    const $editBtn = $('#detailContent').find('.btn-edit-context');
    $editBtn.replaceWith(`
        <button type="button" id="btnCancelEdit" class="btn-action-edit editing ms-0">
            <i class="fa-solid fa-times"></i> Batal
        </button>
        <button type="button" id="btnSaveBoq" class="btn-action-save">
            <i class="fa-solid fa-check"></i> Simpan
        </button>
    `);

    // Pre-load sections yang sudah ada
    const sections = res.sections ?? [];
    if (sections.length > 0) {
        sections.forEach(function (sec) {
            addSection(sec.id_testing_point, sec.point_name, sec.items);
            const $sec = $(`.boq-section[data-point-id="${sec.id_testing_point}"]`);
            $sec.find('.input-item-produk').val(sec.item_produk_alternate || '');
            const qtyEl   = $sec.find('.input-qty')[0];
            const hargaEl = $sec.find('.input-harga')[0];
            if (qtyEl && qtyEl._cleave)   qtyEl._cleave.setRawValue(sec.qty || '');
            else $sec.find('.input-qty').val(sec.qty || '');
            if (hargaEl && hargaEl._cleave) hargaEl._cleave.setRawValue(sec.harga || '');
            else $sec.find('.input-harga').val(sec.harga || '');
            $sec.find('.input-satuan').val(sec.satuan || '');
            $sec.find('.input-ket').val(sec.keterangan || '');
            updateSectionTotal($sec);
        });
    } else {
        $('#boqEmpty').show();
    }

    itemsSnapshot = JSON.stringify(collectSections());

    // Batal: restore dari snapshot tanpa AJAX
    $('#btnCancelEdit').one('click', function () {
        $tabBoq.html(viewSnapshot);
        viewSnapshot = null;
        const $actionBtns = $('#detailContent').find('.detail-action-bar').children().last();
        $actionBtns.html(formGroup.editButton('Edit BOQ'));
    });

    $('#btnSaveBoq').one('click', function () {
        saveBoq(res.id_wo);
    });

    $('#btnAddSection').on('click', function () {
        editingSectionId = null;
        resetModalItems();
        $('#selectTestingPoint').val(null).trigger('change').prop('disabled', false);
        selectedPoint = null;
        new bootstrap.Modal('#modalAddSection').show();
    });
}

// ── Save BOQ via PUT ───────────────────────────────────────────────────────────
function saveBoq(id_wo) {
    const sections = collectSections();
    if (!sections.length) { Notify.warning('Tambahkan minimal 1 item BOQ'); return; }

    if (JSON.stringify(sections) === itemsSnapshot) {
        Notify.warning('Tidak ada perubahan data');
        return;
    }

    Notify.confirm('Simpan perubahan BOQ?', function () {
        $('#btnSaveBoq').prop('disabled', true)
            .html('<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: window.route.update + id_wo,
            method: 'PUT',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': window.route.csrf },
            data: JSON.stringify({ sections: sections }),
            success: function () {
                Notify.success('BOQ berhasil diperbarui');
                page.loadDetail(id_wo);
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan BOQ');
                $('#btnSaveBoq').prop('disabled', false)
                    .html('<i class="fa-solid fa-check"></i> Simpan');
            },
        });
    });
}

// ── Live total per section ─────────────────────────────────────────────────────
function updateSectionTotal($sec) {
    const qty   = rawNumVal($sec.find('.input-qty')[0]) || 0;
    const harga = rawNumVal($sec.find('.input-harga')[0]) || 0;
    const $line = $sec.find('.section-total-line');
    if (qty && harga) {
        $line.html(
            Number(qty).toLocaleString('en-US') + ' qty &times; Rp ' + Number(harga).toLocaleString('en-US') +
            ' = <strong style="color:#1d4ed8;">Rp ' + Number(qty * harga).toLocaleString('en-US') + '</strong>'
        );
    } else {
        $line.html('');
    }
}

$(document).on('input', '.input-qty, .input-harga', function () {
    updateSectionTotal($(this).closest('.boq-section'));
});

// ── Section management ─────────────────────────────────────────────────────────
function addSection(pointId, pointText, items) {
    $('#boqEmpty').hide();
    const $el = $(renderSectionCard(pointId, pointText, items));
    $('#boqSections').append($el);
    initNumericMask($el);
    addedPointIds.add(String(pointId));
}

function updateSection(pointId, items) {
    const $sec = $(`.boq-section[data-point-id="${pointId}"]`);
    $sec.find('.boq-items').html(
        items.map((item, i) => renderItem(pointId, item, i + 1)).join('')
    );
    $sec.find('.section-item-count').text(items.length + ' item');
}

function checkEmpty() {
    if ($('.boq-section').length === 0) {
        $('#boqEmpty').show();
    }
}

function collectSections() {
    const sections = [];
    $('.boq-section').each(function () {
        const $sec  = $(this);
        const items = [];
        $sec.find('.boq-item').each(function () {
            items.push(parseInt($(this).data('item-id')));
        });
        sections.push({
            id_testing_point:      parseInt($sec.data('point-id')),
            item_produk_alternate: $sec.find('.input-item-produk').val() || null,
            qty:                   rawNumVal($sec.find('.input-qty')[0]),
            satuan:                $sec.find('.input-satuan').val() || null,
            harga:                 rawNumVal($sec.find('.input-harga')[0]),
            keterangan:            $sec.find('.input-ket').val() || null,
            items:                 items,
        });
    });
    return sections;
}

function getCurrentItemIds(pointId) {
    const ids = new Set();
    $(`.boq-section[data-point-id="${pointId}"] .boq-item`).each(function () {
        ids.add(String($(this).data('item-id')));
    });
    return ids;
}

// ── Modal helpers ──────────────────────────────────────────────────────────────
function loadModalItems(pointId, preCheckedIds = null) {
    resetModalItems();
    $('#modalLoading').removeClass('d-none');

    $.get(window.route.testingItemsByPoint + pointId, function (res) {
        $('#modalLoading').addClass('d-none');
        const items = res.data ?? [];

        if (!items.length) {
            $('#modalEmpty').removeClass('d-none');
            return;
        }

        let html = '';
        items.forEach(function (item) {
            const unit     = item.kode_unit || '—';
            const nilai    = item.nilai ?? '—';
            const checked  = preCheckedIds ? preCheckedIds.has(String(item.id_testing_item)) : true;
            const safeItem = JSON.stringify(item).replace(/'/g, '&#39;');
            html += `
                <div class="modal-item-row d-flex align-items-center gap-3">
                    <input type="checkbox" class="form-check-input item-check flex-shrink-0 mt-0"
                        id="mitem_${item.id_testing_item}" ${checked ? 'checked' : ''}
                        data-item='${safeItem}'>
                    <label class="d-flex justify-content-between align-items-center w-100 gap-2"
                        for="mitem_${item.id_testing_item}" style="cursor:pointer; margin:0;">
                        <div>
                            <span class="fw-semibold">${escBoq(item.judul_indonesia ?? '—')}</span>
                            <span class="text-muted ms-1 small">/ ${escBoq(item.judul_inggris ?? '—')}</span>
                        </div>
                        <span class="item-meta-badge flex-shrink-0">${escBoq(unit)} · ${escBoq(String(nilai))}</span>
                    </label>
                </div>`;
        });

        $('#modalItemsList').html(html);
        $('#modalItemsWrap').removeClass('d-none');
        $('#modalItemsList').on('change', '.item-check', updateConfirmBtn);
        updateConfirmBtn();
    }).fail(function () {
        $('#modalLoading').addClass('d-none');
        $('#modalEmpty').removeClass('d-none');
    });
}

function resetModalItems() {
    $('#modalLoading, #modalEmpty, #modalItemsWrap, #modalSearchEmpty').addClass('d-none');
    $('#modalItemsList').empty();
    $('#modalItemSearch').val('');
    $('#btnConfirmSection').prop('disabled', true);
}

function updateConfirmBtn() {
    $('#btnConfirmSection').prop('disabled',
        $('#modalItemsList .item-check:checked').length === 0
    );
}
