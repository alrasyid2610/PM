let page;
let currentFwoData        = null;
let fwoBoqData            = [];
let fwoBoqViewHtml        = null;
let fwoBoqSnapshot        = null;
let addedBoqIds           = new Set();
let selectedBoq           = null;
let currentPersonelData   = [];
let personelViewHtml      = null;
let personelEditIdx       = 0;
let fwoBoqDirectMode      = false;
let fwoAttachmentData     = [];
let fwoAttachmentViewHtml = null;
let fwoAttPondInstances   = [];
let fwoAttGroupIdx        = 0;

// ── Tab switch: show/hide action buttons ──────────────────────────────────────
$(document).on('shown.bs.tab', '#fwoDetailTabs button[data-bs-toggle="tab"]', function (e) {
    const target = $(e.target).data('bs-target');
    $('#fwoTabActionsInfo, #fwoTabActionsPersonel, #fwoTabActionsBoq').addClass('d-none');
    if (target === '#tabFwoInfo')       $('#fwoTabActionsInfo').removeClass('d-none');
    if (target === '#tabFwoPersonel')   $('#fwoTabActionsPersonel').removeClass('d-none');
    if (target === '#tabFwoBoq')        $('#fwoTabActionsBoq').removeClass('d-none');
    if (target === '#tabFwoAttachment') $('#fwoTabActionsAttachment').removeClass('d-none');
});

// ── Init ───────────────────────────────────────────────────────────────────────
window.datatableColumnRenderers = {
    status: function (data) {
        if (data === 'completed') {
            return '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:6px;background:#f0fdf4;color:#15803d;font-size:11px;font-weight:600;border:1px solid #bbf7d0;white-space:nowrap;">'
                + '<i class="fa-solid fa-circle-check" style="font-size:10px;"></i> Completed</span>';
        }
        return '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:6px;background:#fffbeb;color:#b45309;font-size:11px;font-weight:600;border:1px solid #fde68a;white-space:nowrap;">'
            + '<i class="fa-solid fa-hourglass-half" style="font-size:10px;"></i> Planned</span>';
    },
};

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
            processResults: function (d) {
                return {
                    results: d.map(function (item) {
                        return addedBoqIds.has(String(item.id))
                            ? Object.assign({}, item, { disabled: true })
                            : item;
                    }),
                };
            },
            cache: false,
        },
        templateResult: function (item) {
            if (!item.id) return item.text;
            if (addedBoqIds.has(String(item.id))) {
                const $el = $('<span>').css({ display: 'flex', alignItems: 'center', gap: '8px' });
                $('<i>').addClass('fa-solid fa-circle-check').css({ color: '#22c55e', fontSize: '13px', flexShrink: '0' }).appendTo($el);
                $('<span>').text(item.text).css({ textDecoration: 'line-through', color: '#94a3b8' }).appendTo($el);
                $('<span>').text('Sudah ditambahkan').css({
                    fontSize: '11px', background: '#f0fdf4', color: '#16a34a',
                    borderRadius: '10px', padding: '1px 8px', border: '1px solid #bbf7d0',
                    flexShrink: '0',
                }).appendTo($el);
                return $el;
            }
            return item.text;
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

    // Tombol tambah BOQ langsung (bulk mode — tampil semua BOQ sekaligus)
    $(document).on('click', '#btnAddFwoBoqDirect', function () {
        openBulkBoqModal();
    });

    // Reset modal lama saat ditutup
    $('#modalAddFwoBoq').on('hidden.bs.modal', function () {
        selectedBoq = null;
        fwoBoqDirectMode = false;
        $('#selectFwoBoq').val(null).trigger('change');
        resetFwoBoqModal();
    });

    // Simpan bulk BOQ
    $(document).on('click', '#btnSaveBulkBoq', function () {
        saveBulkBoq($(this));
    });

    // Hapus satu item BOQ dari view mode
    $(document).on('click', '.btn-fwo-boq-delete', function () {
        const boqId   = String($(this).data('boq-id'));
        const ptName  = $(this).closest('tr').find('td:nth-child(2)').text().trim();

        Swal.fire({
            icon:              'warning',
            title:             'Hapus Item BOQ?',
            html:              '<strong>' + escHtml(ptName) + '</strong>',
            showCancelButton:  true,
            confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Hapus',
            cancelButtonText:  'Batal',
            confirmButtonColor: '#dc2626',
            cancelButtonColor:  '#6b7280',
            reverseButtons:    true,
        }).then(function (result) {
            if (!result.isConfirmed) return;

            const remaining = (fwoBoqData || []).filter(function (s) {
                return String(s.id_boq) !== boqId;
            });

            if (!remaining.length) {
                Notify.warning('Tidak dapat menghapus — minimal harus ada 1 item BOQ');
                return;
            }

            const sections = remaining.map(function (s) {
                return { id_boq: s.id_boq, qty: s.qty, keterangan: s.keterangan };
            });

            $.ajax({
                url:         window.route.fwoBoqUpdate + currentFwoData.id_fwo,
                method:      'PUT',
                contentType: 'application/json',
                headers:     { 'X-CSRF-TOKEN': window.route.csrf },
                data:        JSON.stringify({ sections }),
                success: function () {
                    Notify.success('Item BOQ berhasil dihapus');
                    loadFwoBoqList(currentFwoData.id_fwo);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Gagal menghapus item BOQ');
                },
            });
        });
    });

    // Hapus file existing dalam edit mode attachment
    $(document).on('click', '.btn-remove-att-existing', function () {
        $(this).closest('.att-existing-file').remove();
    });

    // Hapus grup attachment
    $(document).on('click', '.btn-remove-att-group', function () {
        const $group = $(this).closest('.fwo-att-group');
        const idx    = $group.data('idx');
        const pond   = fwoAttPondInstances[idx];
        if (pond) { pond.destroy(); delete fwoAttPondInstances[idx]; }
        $group.remove();
    });

    // Eye toggle untuk detail items di bulk modal
    $(document).on('click', '.btn-bulk-eye', function () {
        const $btn     = $(this);
        const id_boq   = $btn.data('boq-id');
        const $detail  = $btn.siblings('.bulk-boq-items-detail');
        const $icon    = $btn.find('i');

        if ($detail.is(':visible')) {
            $detail.hide();
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            return;
        }

        if ($detail.data('loaded')) {
            $detail.show();
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            return;
        }

        $detail.html('<span class="text-muted small"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</span>').show();
        $icon.removeClass('fa-eye').addClass('fa-eye-slash');

        $.get(window.route.boqSectionItems + id_boq + '/section-items?id_fwo=' + (currentFwoData?.id_fwo ?? 0), function (res) {
            const items = res.items ?? [];
            if (!items.length) {
                $detail.html('<span class="text-muted small">Tidak ada item</span>');
            } else {
                $detail.html(
                    '<div style="border-left:3px solid #e2e8f0;padding-left:8px;margin-top:4px;">' +
                    items.map(function (item, j) {
                        return `<div class="text-muted small py-1" style="${j > 0 ? 'border-top:1px solid #f1f5f9;' : ''}">
                            <span class="fw-semibold text-dark">${j + 1}.</span>
                            ${escHtml(item.judul_indonesia ?? '—')}
                            <span class="text-muted">/ ${escHtml(item.judul_inggris ?? '—')}</span>
                            <span class="item-meta-badge ms-1">${escHtml(item.kode_unit || '—')} · ${escHtml(String(item.nilai ?? '—'))}</span>
                        </div>`;
                    }).join('') +
                    '</div>'
                );
            }
            $detail.data('loaded', true);
        }).fail(function () {
            $detail.html('<span class="text-danger small">Gagal memuat</span>');
        });
    });

    // Konfirmasi tambah section
    $('#btnConfirmFwoBoq').on('click', function () {
        if (!selectedBoq) return;
        const qty = $('#fwoBoqQtyInput').val() ? parseInt($('#fwoBoqQtyInput').val()) : null;
        const ket = $('#fwoBoqKetInput').val() || null;

        const maxAllowed = selectedBoq.remaining_qty ?? selectedBoq.qty_boq ?? null;
        if (qty && maxAllowed !== null && qty > maxAllowed) {
            const boqHint = selectedBoq.qty_boq ? ` (maks BOQ: ${selectedBoq.qty_boq})` : ` (maks: ${maxAllowed})`;
            Notify.warning('Qty tidak boleh melebihi batas' + boqHint);
            return;
        }

        if (fwoBoqDirectMode) {
            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

            const existingSections = (fwoBoqData || []).map(function (s) {
                return { id_boq: s.id_boq, qty: s.qty, keterangan: s.keterangan };
            });
            const allSections = existingSections.concat([{
                id_boq:     selectedBoq.id,
                qty:        qty,
                keterangan: ket,
            }]);

            $.ajax({
                url:         window.route.fwoBoqUpdate + currentFwoData.id_fwo,
                method:      'PUT',
                contentType: 'application/json',
                headers:     { 'X-CSRF-TOKEN': window.route.csrf },
                data:        JSON.stringify({ sections: allSections }),
                success: function () {
                    Notify.success('BOQ berhasil ditambahkan');
                    bootstrap.Modal.getInstance('#modalAddFwoBoq').hide();
                    loadFwoBoqList(currentFwoData.id_fwo);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Gagal menambahkan BOQ');
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Tambah Item');
                },
            });
        } else {
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
        }
    });

    // Toggle items di modal tambah BOQ
    $(document).on('click', '#btnToggleModalItems', function () {
        const $list = $('#fwoBoqModalItemsList');
        const $icon = $(this).find('i');
        const isVisible = $list.is(':visible');
        $list.toggle(!isVisible);
        $icon.toggleClass('fa-eye', isVisible).toggleClass('fa-eye-slash', !isVisible);
    });

    // Toggle items di section edit mode
    $(document).on('click', '.btn-toggle-boq-items', function () {
        const $items = $(this).closest('.card-body').find('.fwo-boq-items');
        const $icon  = $(this).find('i');
        const isVisible = $items.is(':visible');
        $items.toggle(!isVisible);
        $icon.toggleClass('fa-eye', isVisible).toggleClass('fa-eye-slash', !isVisible);
    });

    // Hapus baris personel (delegasi)
    $(document).on('click', '.btn-remove-personel-row', function () {
        $(this).closest('.personel-edit-row').remove();
        syncPersonelEditEmpty();
    });

    // Hapus section (delegasi)
    $(document).on('click', '.btn-remove-fwo-boq', function () {
        const boqId = String($(this).closest('.fwo-boq-section').data('boq-id'));
        addedBoqIds.delete(boqId);
        $(this).closest('.fwo-boq-section').remove();
        checkFwoBoqEmpty();
    });

    // Selesaikan FWO
    $(document).on('click', '#btnCompleteFwo', function () {
        const fwoId = $(this).data('fwo-id');
        const $btn  = $(this);
        Swal.fire({
            title: 'Selesaikan FWO?',
            html:  'Status akan berubah menjadi <strong>Completed</strong>.<br><span style="font-size:13px;color:#6b7280;">Status tidak dapat dikembalikan ke Planned.</span>',
            icon:  'question',
            showCancelButton:    true,
            confirmButtonText:   '<i class="fa-solid fa-circle-check me-1"></i> Ya, Selesaikan',
            cancelButtonText:    'Batal',
            confirmButtonColor:  '#16a34a',
            cancelButtonColor:   '#6b7280',
            reverseButtons:      true,
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin" style="font-size:10px;"></i> Memproses...');
            $.ajax({
                url:     window.route.fwoComplete + fwoId + '/complete',
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': window.route.csrf },
                success: function () {
                    page.loadDetail(page.selectedRow.id);
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Gagal menyelesaikan FWO');
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-circle-check" style="font-size:10px;"></i> Selesaikan');
                },
            });
        });
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
            currentFwoData      = res;
            currentPersonelData = res.personels || [];
            try {
                fwoAttachmentData = res.attachments
                    ? (typeof res.attachments === 'string' ? JSON.parse(res.attachments) : res.attachments)
                    : [];
            } catch (e) { fwoAttachmentData = []; }
            $('#fwoPersonelContent').html(renderPersonelView(currentPersonelData));
            $('#fwoAttachmentContent').html(renderFwoAttachmentView(fwoAttachmentData));
            loadFwoBoqList(res.id_fwo);
        },
    });

    page.bindEditBehaviour = function () {
        bindEditToggle({
            container: '#detailContent',
            onEditStart: function () {
                enterPersonelEditMode();
                enterFwoBoqEditMode();
                enterFwoAttachmentEditMode();
            },
            onEditCancel: function () {
                exitPersonelEditMode();
                exitFwoBoqEditMode();
                exitFwoAttachmentEditMode();
            },
            onSave: function () {
                saveAll(page.selectedRow.id);
            },
        });
    };

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirm('Hapus Fieldwork?', function () {
            $.ajax({
                url: window.route.update + id,
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' },
                success: function (res) {
                    Notify.success(res.message || 'Data berhasil dihapus');
                    const woId = currentFwoData && currentFwoData.id_wo;
                    if (woId) {
                        window.location.href = '/work-orders?open=' + woId;
                    } else {
                        $('#detailContent').html('');
                        page.selectedRow.id = null;
                        if ($.fn.DataTable.isDataTable('#masterTable')) {
                            $('#masterTable').DataTable().ajax.reload(null, false);
                        }
                    }
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                },
            });
        });
    });
});

// ── Personel edit mode ─────────────────────────────────────────────────────────
function enterPersonelEditMode() {
    personelViewHtml = $('#fwoPersonelContent').html();
    personelEditIdx  = 0;

    const editBar = `<div class="d-flex justify-content-start align-items-center mb-3">
        <button type="button" id="btnAddPersonelRow" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Tambah Personel
        </button>
    </div>`;

    const emptyMsg = `<div id="personelEditEmpty" style="display:none;text-align:center;padding:16px;border:1px dashed #e2e8f0;border-radius:8px;color:#94a3b8;font-size:13px;">
        Belum ada personel. Klik <strong>+ Tambah Personel</strong>.
    </div>`;

    $('#fwoPersonelContent').html(editBar + '<div id="personelEditRows"></div>' + emptyMsg);

    // Pre-load existing personels
    if (currentPersonelData.length > 0) {
        currentPersonelData.forEach(function (p) {
            addPersonelEditRow({ id: p.id_user, text: p.user_name }, p.role);
        });
    } else {
        syncPersonelEditEmpty();
    }

    $('#btnAddPersonelRow').on('click', function () {
        addPersonelEditRow(null, '');
    });
}

function exitPersonelEditMode() {
    personelEditIdx  = 0;
    personelViewHtml && $('#fwoPersonelContent').html(personelViewHtml);
    personelViewHtml = null;
}

function addPersonelEditRow(userData, roleVal) {
    const idx = personelEditIdx++;
    const roleOptions = ['Leader', 'Driver', 'Anggota'].map(function (r) {
        return `<option value="${r}" ${roleVal === r ? 'selected' : ''}>${r}</option>`;
    }).join('');

    const row = $(`
        <div class="personel-edit-row d-flex align-items-end gap-2 mb-2" data-idx="${idx}">
            <div style="flex:1;min-width:0;">
                <label class="form-label form-label-sm text-muted mb-1">Personel</label>
                <select class="form-select personel-edit-user" data-idx="${idx}"></select>
            </div>
            <div style="width:160px;flex-shrink:0;">
                <label class="form-label form-label-sm text-muted mb-1">Role</label>
                <select class="form-select personel-edit-role">
                    <option value="">— Pilih Role —</option>
                    ${roleOptions}
                </select>
            </div>
            <div style="flex-shrink:0;padding-bottom:2px;">
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-personel-row" title="Hapus">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>
    `);

    $('#personelEditRows').append(row);

    const $select = row.find('.personel-edit-user');
    $select.select2({
        width: '100%',
        placeholder: 'Ketik nama personel...',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: window.route.userSelect2,
            dataType: 'json',
            delay: 200,
            data: p => ({ q: p.term }),
            processResults: d => ({ results: d }),
            cache: true,
        },
    });

    row.find('.personel-edit-role').select2({
        width: '100%',
        minimumResultsForSearch: Infinity,
    });

    if (userData) {
        const opt = new Option(userData.text, userData.id, true, true);
        $select.append(opt).trigger('change');
    }

    syncPersonelEditEmpty();
}

function syncPersonelEditEmpty() {
    const has = $('#personelEditRows .personel-edit-row').length > 0;
    $('#personelEditEmpty').toggle(!has);
}

function collectPersonelRows() {
    const rows = [];
    $('#personelEditRows .personel-edit-row').each(function () {
        const id_user = $(this).find('.personel-edit-user').val();
        const role    = $(this).find('.personel-edit-role').val() || null;
        if (id_user) rows.push({ id_user: parseInt(id_user), role });
    });
    return rows;
}

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
        'Belum ada item. Klik <strong>+ Tambah Item</strong> untuk menambahkan.' +
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

    $('#btnAddFwoBoqSection').on('click', function () {
        fwoBoqDirectMode = false;
        selectedBoq = null;
        resetFwoBoqModal();
        $('#selectFwoBoq').val(null).trigger('change');
        new bootstrap.Modal('#modalAddFwoBoq').show();
    });
}

function exitFwoBoqEditMode() {
    addedBoqIds.clear();
    fwoBoqSnapshot = null;
    $('#fwoBoqContent').html(fwoBoqViewHtml);
    fwoBoqViewHtml = null;
}

// ── Save all (FWO info + personel + BOQ) ──────────────────────────────────────
function saveAll(id_fwo) {
    const formData    = $('#detailForm').serialize();
    const personels   = collectPersonelRows();
    const boqSections = collectFwoBoqSections();

    for (const sec of boqSections) {
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

    Notify.confirm('Simpan semua perubahan?', function () {
        const $saveBtn = $('.btn-save-context');
        $saveBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url:    window.route.update + id_fwo,
            method: 'POST',
            data:   formData,
            success: function () {
                $.ajax({
                    url:         window.route.personelUpdate + id_fwo + '/personels',
                    method:      'PUT',
                    contentType: 'application/json',
                    headers:     { 'X-CSRF-TOKEN': window.route.csrf },
                    data:        JSON.stringify({ personels }),
                    success: function () {
                        $.ajax({
                            url:         window.route.fwoBoqUpdate + id_fwo,
                            method:      'PUT',
                            contentType: 'application/json',
                            headers:     { 'X-CSRF-TOKEN': window.route.csrf },
                            data:        JSON.stringify({ sections: boqSections }),
                            success: function () {
                                saveAttachments(id_fwo, function () {
                                    Notify.success('Data berhasil disimpan');
                                    page.loadDetail(id_fwo);
                                });
                            },
                            error: function (xhr) {
                                Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan Fieldwork BOQ');
                                $saveBtn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Simpan');
                            },
                        });
                    },
                    error: function (xhr) {
                        Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan personel');
                        $saveBtn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Simpan');
                    },
                });
            },
            error: function (xhr) {
                const errs = xhr.responseJSON?.errors;
                const msg  = errs
                    ? Object.values(errs).flat().join(' ')
                    : (xhr.responseJSON?.message || 'Gagal menyimpan data fieldwork');
                Notify.error(msg);
                $saveBtn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Simpan');
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

// ── Bulk BOQ modal (direct mode) ───────────────────────────────────────────────
function openBulkBoqModal() {
    $('#bulkBoqLoading').removeClass('d-none');
    $('#bulkBoqEmpty, #bulkBoqList').addClass('d-none');
    $('#btnSaveBulkBoq').prop('disabled', true);
    new bootstrap.Modal('#modalBulkAddFwoBoq').show();

    $.get(
        window.route.boqSelect2ByWo + (currentFwoData?.id_wo ?? 0) +
        '?id_fwo=' + (currentFwoData?.id_fwo ?? 0),
        function (data) {
            $('#bulkBoqLoading').addClass('d-none');
            if (!data || !data.length) {
                $('#bulkBoqEmpty').removeClass('d-none');
                return;
            }
            $('#bulkBoqList').html(renderBulkBoqList(data)).removeClass('d-none');
            $('#btnSaveBulkBoq').prop('disabled', false);
        }
    ).fail(function () {
        $('#bulkBoqLoading').addClass('d-none');
        $('#bulkBoqEmpty').removeClass('d-none');
    });
}

function renderBulkBoqList(boqItems) {
    const TH = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;padding:8px 12px;color:#64748b;font-weight:600;"';
    const TD = 'style="padding:8px 10px;vertical-align:middle;"';

    const added    = boqItems.filter(function (item) {
        return (fwoBoqData || []).some(function (s) { return String(s.id_boq) === String(item.id); });
    });
    const notAdded = boqItems.filter(function (item) {
        return !(fwoBoqData || []).some(function (s) { return String(s.id_boq) === String(item.id); });
    });

    function buildRow(item, num) {
        const existing    = (fwoBoqData || []).find(function (s) { return String(s.id_boq) === String(item.id); });
        const existingQty = existing ? (existing.qty ?? '') : '';
        const existingKet = existing ? (existing.keterangan ?? '') : '';
        const satuan      = item.satuan ? ' ' + escHtml(item.satuan) : '';
        const sisaColor   = (item.remaining_qty > 0) ? '#1d4ed8' : '#dc2626';

        return `<tr>
            <td ${TD} style="padding:8px 12px;color:#94a3b8;text-align:center;font-size:12px;">${num}</td>
            <td ${TD} style="padding:8px 12px;color:#1e293b;font-weight:500;">
                ${escHtml(item.text ?? '—')}
                <button type="button" class="btn-bulk-eye"
                    data-boq-id="${item.id}" title="Lihat detail items"
                    style="background:none;border:none;padding:0 0 0 4px;cursor:pointer;color:#94a3b8;font-size:12px;vertical-align:middle;line-height:1;">
                    <i class="fa-solid fa-eye"></i>
                </button>
                <div class="bulk-boq-items-detail mt-1" style="display:none;"></div>
            </td>
            <td ${TD} style="padding:8px 12px;color:#475569;white-space:nowrap;">${item.qty_boq ?? '—'}${satuan}</td>
            <td ${TD} style="padding:8px 12px;white-space:nowrap;">
                <span style="color:${sisaColor};font-weight:600;">${item.remaining_qty ?? '—'}${satuan}</span>
            </td>
            <td ${TD} style="padding:8px 10px;width:110px;">
                <input type="number" class="form-control form-control-sm bulk-boq-qty"
                    data-boq-id="${item.id}"
                    data-max="${item.remaining_qty ?? ''}"
                    min="0" placeholder="0" value="${escHtml(String(existingQty))}">
            </td>
            <td ${TD} style="padding:8px 10px;">
                <input type="text" class="form-control form-control-sm bulk-boq-ket"
                    placeholder="opsional" value="${escHtml(existingKet)}">
            </td>
        </tr>`;
    }

    const addedRows    = added.map(function (item, i) { return buildRow(item, i + 1); }).join('');
    const notAddedRows = notAdded.map(function (item, i) { return buildRow(item, added.length + i + 1); }).join('');

    const dividerRow = (added.length && notAdded.length)
        ? `<tr>
            <td colspan="6" style="padding:4px 0;">
                <hr style="margin:4px 12px;border-color:#e2e8f0;">
                <span style="display:block;text-align:center;color:#94a3b8;font-size:11px;font-weight:600;letter-spacing:.4px;margin-bottom:4px;">
                    + Tambahkan item lainnya
                </span>
            </td>
           </tr>`
        : '';

    return `<div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                <tr>
                    <th ${TH} style="width:40px;text-align:center;">#</th>
                    <th ${TH}>Item BOQ</th>
                    <th ${TH}>Qty Kontrak</th>
                    <th ${TH}>Sisa</th>
                    <th ${TH} style="width:110px;">Qty FWO</th>
                    <th ${TH}>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                ${addedRows}
                ${dividerRow}
                ${notAddedRows}
            </tbody>
        </table>
    </div>`;
}

function saveBulkBoq($btn) {
    const sections = [];
    let hasError   = false;

    $('#bulkBoqList .bulk-boq-qty').each(function () {
        const qty = parseInt($(this).val()) || 0;
        if (qty <= 0) return;

        const maxRaw  = $(this).data('max');
        const max     = maxRaw !== '' && maxRaw !== undefined ? parseInt(maxRaw) : null;
        if (max !== null && qty > max) {
            const namaItem = $(this).closest('tr').find('td:nth-child(2)').clone().find('.btn-bulk-eye, .bulk-boq-items-detail').remove().end().text().trim();
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                html: '<strong>' + escHtml(namaItem) + '</strong><br><span style="font-size:14px;">Qty melebihi sisa yang tersedia (maks: ' + max + ')</span>',
            });
            hasError = true;
            return false;
        }

        const id_boq = parseInt($(this).data('boq-id'));
        const ket    = $(this).closest('tr').find('.bulk-boq-ket').val() || null;
        sections.push({ id_boq, qty, keterangan: ket });
    });

    if (hasError) return;
    if (!sections.length) {
        Notify.warning('Isi minimal 1 item dengan Qty lebih dari 0');
        return;
    }

    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

    $.ajax({
        url:         window.route.fwoBoqUpdate + currentFwoData.id_fwo,
        method:      'PUT',
        contentType: 'application/json',
        headers:     { 'X-CSRF-TOKEN': window.route.csrf },
        data:        JSON.stringify({ sections }),
        success: function () {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan');
            Notify.success('BOQ berhasil disimpan');
            bootstrap.Modal.getInstance('#modalBulkAddFwoBoq').hide();
            loadFwoBoqList(currentFwoData.id_fwo);
        },
        error: function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan BOQ');
            $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan');
        },
    });
}

function resetFwoBoqModal() {
    $('#fwoBoqModalLoading, #fwoBoqModalEmpty, #fwoBoqModalPreview').addClass('d-none');
    $('#fwoBoqModalItemsList').empty().hide();
    $('#btnToggleModalItems').find('i').removeClass('fa-eye-slash').addClass('fa-eye');
    $('#fwoBoqQtyInput').val('').removeAttr('max');
    $('#fwoBoqKetInput').val('');
    $('#fwoBoqMaxHint').html('');
    $('#btnConfirmFwoBoq').prop('disabled', true);
    window._fwoBoqPreviewItems = [];
}

// ── Attachment edit mode ───────────────────────────────────────────────────────
function enterFwoAttachmentEditMode() {
    fwoAttachmentViewHtml = $('#fwoAttachmentContent').html();
    fwoAttPondInstances   = [];
    fwoAttGroupIdx        = 0;

    let html = renderFwoAttachmentEditBar();
    html += '<div id="fwoAttGroups"></div>';
    html += '<div id="fwoAttEmpty" class="text-center text-muted py-4" style="display:none;">' +
        '<i class="fa-solid fa-paperclip fa-2x d-block mb-2 opacity-25"></i>' +
        'Klik <strong>+ Tambah Tipe Dokumen</strong> untuk menambahkan.' +
        '</div>';

    $('#fwoAttachmentContent').html(html);

    if (fwoAttachmentData.length > 0) {
        fwoAttachmentData.forEach(function (group) { addAttachmentGroup(group); });
    } else {
        $('#fwoAttEmpty').show();
    }

    $(document).on('click.attgroup', '#btnAddAttachmentGroup', function () {
        addAttachmentGroup({ type: FWO_ATTACHMENT_TYPES[0], files: [] });
    });
}

function addAttachmentGroup(group) {
    $('#fwoAttEmpty').hide();
    const idx  = fwoAttGroupIdx++;
    const html = renderFwoAttachmentGroupEdit(group, idx);
    const $el  = $(html);
    $('#fwoAttGroups').append($el);

    const pond = FilePond.create($el.find('.fwo-att-filepond')[0], {
        allowMultiple: true,
        labelIdle: 'Drag & Drop file atau <span class="filepond--label-action">Browse</span>',
        acceptedFileTypes: ['image/*', 'application/pdf',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    });
    fwoAttPondInstances[idx] = pond;
}

function exitFwoAttachmentEditMode() {
    Object.values(fwoAttPondInstances).forEach(function (p) { try { p.destroy(); } catch (e) {} });
    fwoAttPondInstances = [];
    fwoAttGroupIdx      = 0;
    $(document).off('click.attgroup');
    if (fwoAttachmentViewHtml !== null) {
        $('#fwoAttachmentContent').html(fwoAttachmentViewHtml);
        fwoAttachmentViewHtml = null;
    }
}

function collectFwoAttachmentData() {
    const groups = [];
    $('#fwoAttGroups .fwo-att-group').each(function () {
        const idx      = $(this).data('idx');
        const type     = $(this).find('.fwo-att-type').val();
        const existing = $(this).find('.fwo-att-existing').map(function () { return $(this).val(); }).get();
        const pond     = fwoAttPondInstances[idx];
        const newFiles = pond ? pond.getFiles().map(function (f) { return f.file; }) : [];
        groups.push({ type, existing, newFiles });
    });
    return groups;
}

function saveAttachments(id_fwo, callback) {
    const groups = collectFwoAttachmentData();

    const hasNew      = groups.some(function (g) { return g.newFiles.length > 0; });
    const hasExisting = groups.some(function (g) { return g.existing.length > 0; });

    if (!hasNew && !hasExisting && !fwoAttachmentData.length) {
        callback();
        return;
    }

    const fd = new FormData();
    fd.append('_token', window.route.csrf);
    fd.append('_method', 'POST');

    groups.forEach(function (group, i) {
        fd.append('groups[' + i + '][type]', group.type);
        group.existing.forEach(function (path, j) {
            fd.append('groups[' + i + '][existing][' + j + ']', path);
        });
        group.newFiles.forEach(function (file, j) {
            fd.append('groups[' + i + '][files][' + j + ']', file);
        });
    });

    $.ajax({
        url:         window.route.fwoAttachments + id_fwo + '/attachments',
        method:      'POST',
        data:        fd,
        processData: false,
        contentType: false,
        success:     function () { callback(); },
        error:       function (xhr) {
            Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan attachment');
        },
    });
}
