let page;
let currentTerminData = null;
const TH_STYLE = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;padding:8px 12px;color:#64748b;font-weight:600;"';
const TD_STYLE = 'style="padding:8px 12px;vertical-align:middle;"';

// ── Tab switch: show/hide action buttons ──────────────────────────────────────
$(document).on('shown.bs.tab', '#terminDetailTabs button[data-bs-toggle="tab"]', function (e) {
    const target = $(e.target).data('bs-target');
    $('#terminTabActionsInfo, #terminTabActionsOutput').addClass('d-none');
    if (target === '#tabTerminInfo')   $('#terminTabActionsInfo').removeClass('d-none');
    if (target === '#tabTerminOutput') $('#terminTabActionsOutput').removeClass('d-none');
});

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_termin",
        renderForm: function (res) { currentTerminData = res; return renderForm(res); },
        initSelect: function () {
            initNumericMask(document.getElementById('detailContent'));
            $("#detail_status").select2({
                width: "100%",
                dropdownParent: $("#detailContent"),
            });
        },
        onSave: function (id) {
            var el = document.getElementById('detail_nilai');
            if (el && el._cleave) el.value = el._cleave.getRawValue();
            submitCrudForm({ id: id, reload: page.loadDetail.bind(page), filepond: page.pondEdit });
        },
        useAttachment: true,
    });

    // Hapus output dari termin — langsung save via API
    $(document).on('click', '.btn-remove-output-termin', function () {
        var $row = $(this).closest('tr.assigned-output-row');
        var idOutput = $(this).data('id');
        var terminId = page.selectedRow.id;

        Notify.confirm('Hapus output ini dari tagihan?', function () {
            $.ajax({
                url: window.route.update + terminId + '/remove-output',
                method: 'POST',
                data: { _token: window.route.csrf, id_output: idOutput },
                success: function () {
                    $row.remove();
                    if ($('#assignedOutputsTbody tr').length === 0) {
                        $('#assignedOutputsTableWrap').hide();
                        $('#noAssignedOutputMsg').show();
                    }
                    Notify.success('Output berhasil dihapus dari tagihan');
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Gagal menghapus output');
                },
            });
        });
    });

    // Tambah output ke termin (edit form) — load picker
    $(document).on('click', '#btnAddOutputTermin', function () {
        var soId = $(this).data('so-id');
        if (!soId) return;
        var $wrap = $('#addOutputTerminWrap');
        if ($wrap.find('#outputPickerTable').length) {
            $wrap.html('');
            return;
        }
        $wrap.html('<div class="text-center py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');
        $.get(window.route.outputsBySo + soId, function (data) {
            if (!data.length) {
                $wrap.html('<div class="text-center text-muted py-3 mt-2" style="font-size:12px;">Tidak ada output tersedia</div>');
                return;
            }

            var TH = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;padding:8px 12px;color:#64748b;font-weight:600;"';
            var TD = 'style="padding:8px 12px;vertical-align:middle;"';

            // Kelompokkan per WO
            var woGroups = new Map();
            data.forEach(function (item) {
                var key = item.no_wo || '—';
                if (!woGroups.has(key)) woGroups.set(key, []);
                woGroups.get(key).push(item);
            });

            var accordionItems = '';
            woGroups.forEach(function (list, noWo) {
                var rows = list.map(function (item) {
                    return '<tr>' +
                        '<td ' + TD + ' style="text-align:center;">' +
                            '<input type="checkbox" class="form-check-input output-pick-check"' +
                            ' data-id="' + item.id_output + '"' +
                            ' data-wo="' + escHtml(item.no_wo) + '"' +
                            ' data-judul="' + escHtml(item.judul_output) + '">' +
                        '</td>' +
                        '<td ' + TD + '>' + escHtml(item.judul_output) + '</td>' +
                    '</tr>';
                }).join('');

                accordionItems +=
                    '<div class="output-pick-accordion-item" style="border-bottom:1px solid #e2e8f0;">' +
                        '<div class="output-pick-accordion-header" style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f8fafc;cursor:pointer;user-select:none;">' +
                            '<i class="fa-solid fa-chevron-down" style="color:#1a56db;font-size:10px;transition:transform .2s;"></i>' +
                            '<i class="fa-solid fa-briefcase" style="color:#1a56db;font-size:12px;"></i>' +
                            '<span style="font-size:13px;font-weight:600;color:#1e293b;">' + escHtml(noWo) + '</span>' +
                            '<span style="font-size:11px;font-weight:600;padding:1px 8px;border-radius:20px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">' + list.length + ' output</span>' +
                        '</div>' +
                        '<div class="output-pick-accordion-body">' +
                            '<div class="table-responsive">' +
                            '<table class="table table-sm table-hover mb-0" style="font-size:13px;table-layout:fixed;width:100%;">' +
                            '<colgroup><col style="width:44px;"><col style="width:100%;"></colgroup>' +
                            '<thead style="background:#f8fafc;border-bottom:1px solid #e2e8f0;"><tr>' +
                            '<th ' + TH + '></th>' +
                            '<th ' + TH + '>Judul Output</th>' +
                            '</tr></thead>' +
                            '<tbody>' + rows + '</tbody>' +
                            '</table></div>' +
                        '</div>' +
                    '</div>';
            });

            $wrap.html(
                '<div class="mt-3 p-3" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">' +
                '<div class="d-flex align-items-center gap-2 flex-wrap mb-2">' +
                    '<div class="fw-semibold" style="font-size:12px;color:#374151;"><i class="fa-solid fa-plus me-1" style="color:#0f766e;"></i>Pilih Output Tambahan</div>' +
                    '<input type="checkbox" class="form-check-input ms-2" id="outputPickCheckAll">' +
                    '<label for="outputPickCheckAll" class="form-label mb-0" style="font-size:12px;cursor:pointer;">Pilih Semua</label>' +
                    '<span class="text-muted ms-1" id="outputPickCount" style="font-size:11px;"></span>' +
                    '<div class="ms-auto" style="position:relative;min-width:220px;">' +
                        '<span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;pointer-events:none;">' +
                            '<i class="fa-solid fa-magnifying-glass"></i>' +
                        '</span>' +
                        '<input type="text" id="outputPickSearch" placeholder="Cari judul output..." ' +
                            'style="width:100%;padding:5px 10px 5px 30px;font-size:12px;border:1px solid #e2e8f0;border-radius:6px;outline:none;">' +
                    '</div>' +
                '</div>' +
                '<div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:12px;" id="outputPickAccordion">' +
                accordionItems +
                '</div>' +
                '<div id="outputPickSearchEmpty" style="display:none;" class="text-center text-muted py-3 mb-2">' +
                    '<i class="fa-solid fa-magnifying-glass fa-2x d-block mb-2 opacity-25"></i>Tidak ditemukan hasil pencarian</div>' +
                '<button type="button" id="btnConfirmAddOutput" class="btn btn-sm btn-primary" data-no-disable>' +
                '<i class="fa-solid fa-check me-1"></i>Tambahkan Terpilih</button>' +
                '<button type="button" id="btnCancelAddOutput" class="btn btn-sm btn-outline-secondary ms-2" data-no-disable>Batal</button>' +
                '</div>'
            );
        }).fail(function () {
            $wrap.html('<div class="text-center text-danger py-3 mt-2" style="font-size:12px;">Gagal memuat output</div>');
        });
    });

    $(document).on('click', '#btnCancelAddOutput', function () {
        $('#addOutputTerminWrap').html('');
    });

    $(document).on('click', '#btnRefreshOutputTermin', function () {
        var $btn = $(this);
        var terminId = page.selectedRow.id;
        if (!terminId) return;
        $btn.prop('disabled', true).find('i').addClass('fa-spin');
        $.get(window.route.update + terminId + '/detail', function (res) {
            $('#outputTerminContent').html(renderAssignedOutputsInner(res.assigned_outputs || []));
            $btn.prop('disabled', false).find('i').removeClass('fa-spin');
        }).fail(function () {
            $btn.prop('disabled', false).find('i').removeClass('fa-spin');
            Notify.error('Gagal memuat data output');
        });
    });

    // Siap Kirim
    $(document).on('click', '#btnSiapKirimTermin', function () {
        var terminId = $(this).data('termin-id');
        var $btn = $(this);
        Swal.fire({
            title: 'Tandai Siap Kirim?',
            html: 'Status termin akan berubah menjadi <strong>Siap Kirim</strong>.<br><span style="font-size:13px;color:#6b7280;">Status tidak dapat dikembalikan.</span>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-paper-plane me-1"></i> Ya, Siap Kirim',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin" style="font-size:10px;"></i> Memproses...');
            $.ajax({
                url: window.route.update + terminId + '/siap-kirim',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': window.route.csrf },
                success: function () {
                    Notify.success('Termin berhasil ditandai Siap Kirim');
                    page.loadDetail(terminId);
                },
                error: function () {
                    Notify.error('Gagal mengubah status termin');
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Siap Kirim');
                },
            });
        });
    });

    // Selesaikan Termin
    $(document).on('click', '#btnSelesaikanTermin', function () {
        var terminId = $(this).data('termin-id');
        var $btn = $(this);
        Swal.fire({
            title: 'Selesaikan Termin?',
            html: 'Status termin akan berubah menjadi <strong>Selesai</strong>.<br><span style="font-size:13px;color:#6b7280;">Status tidak dapat dikembalikan.</span>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-circle-check me-1"></i> Ya, Selesaikan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin" style="font-size:10px;"></i> Memproses...');
            $.ajax({
                url: window.route.update + terminId + '/selesai',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': window.route.csrf },
                success: function () {
                    Notify.success('Termin berhasil diselesaikan');
                    page.loadDetail(terminId);
                },
                error: function () {
                    Notify.error('Gagal mengubah status termin');
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-circle-check me-1"></i> Selesaikan');
                },
            });
        });
    });

    // Accordion toggle untuk output picker
    $(document).on('click', '.output-pick-accordion-header', function () {
        var $body = $(this).next('.output-pick-accordion-body');
        var $chevron = $(this).find('.fa-chevron-down');
        var isOpen = $body.is(':visible');
        $body.slideToggle(150);
        $chevron.css('transform', isOpen ? 'rotate(-90deg)' : 'rotate(0deg)');
    });

    // Search di output picker
    $(document).on('input', '#outputPickSearch', function () {
        var q = $(this).val().toLowerCase().trim();
        var anyVisible = false;
        $('.output-pick-accordion-item').each(function () {
            var $item = $(this);
            var matchCount = 0;
            $item.find('tbody tr').each(function () {
                var text = $(this).find('td:nth-child(2)').text().toLowerCase();
                var match = !q || text.includes(q);
                $(this).toggle(match);
                if (match) matchCount++;
            });
            var visible = matchCount > 0 || !q;
            $item.toggle(visible);
            if (visible && q) {
                $item.find('.output-pick-accordion-body').show();
                $item.find('.fa-chevron-down').css('transform', 'rotate(0deg)');
            }
            if (visible) anyVisible = true;
        });
        $('#outputPickAccordion').toggle(anyVisible);
        $('#outputPickSearchEmpty').toggle(!anyVisible);
    });

    // Pilih Semua output pick (hanya visible)
    $(document).on('change', '#outputPickCheckAll', function () {
        var checked = this.checked;
        $('.output-pick-accordion-item:visible tbody tr:visible .output-pick-check')
            .prop('checked', checked).trigger('change');
    });

    // Update counter output pick
    $(document).on('change', '.output-pick-check', function () {
        var $visible = $('.output-pick-accordion-item:visible tbody tr:visible .output-pick-check');
        var totalVis = $visible.length;
        var selVis   = $visible.filter(':checked').length;
        var selAll   = $('.output-pick-check:checked').length;
        $('#outputPickCount').text(selAll ? selAll + ' dipilih' : '');
        $('#outputPickCheckAll').prop('indeterminate', selVis > 0 && selVis < totalVis);
        $('#outputPickCheckAll').prop('checked', totalVis > 0 && selVis === totalVis);
    });

    $(document).on('click', '#btnConfirmAddOutput', function () {
        var $checked = $('.output-pick-check:checked');
        if (!$checked.length) {
            Notify.warning('Pilih minimal satu output');
            return;
        }

        var ids = [];
        $checked.each(function () { ids.push($(this).data('id')); });

        var terminId = page.selectedRow.id;
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: window.route.update + terminId + '/add-output',
            method: 'POST',
            data: { _token: window.route.csrf, ids: ids },
            success: function () {
                var $tbody = $('#assignedOutputsTbody');
                $checked.each(function () {
                    var id    = $(this).data('id');
                    var wo    = $(this).data('wo');
                    var judul = $(this).data('judul');
                    if ($('input[name="selected_outputs[]"][value="' + id + '"]').length) return;
                    $tbody.append(
                        '<tr class="assigned-output-row" data-id="' + id + '">' +
                        '<input type="hidden" name="selected_outputs[]" value="' + id + '">' +
                        '<td ' + TD_STYLE + ' style="font-size:12px;color:#64748b;">' + escHtml(wo) + '</td>' +
                        '<td ' + TD_STYLE + '>' + escHtml(judul) + '</td>' +
                        '<td ' + TD_STYLE + '>' +
                            '<input type="text" name="judul_tagihan[' + id + ']" class="form-control form-control-sm disabled" ' +
                            'placeholder="Sama seperti judul output" maxlength="255">' +
                        '</td>' +
                        '<td ' + TD_STYLE + ' style="text-align:right;">' +
                            '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-output-termin" ' +
                            'data-id="' + id + '" data-no-disable style="font-size:11px;">' +
                            '<i class="fa-solid fa-trash"></i></button>' +
                        '</td></tr>'
                    );
                });
                $('#assignedOutputsTableWrap').show();
                $('#noAssignedOutputMsg').hide();
                $('#addOutputTerminWrap').html('');
                Notify.success('Output berhasil ditambahkan ke tagihan');
            },
            error: function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Gagal menyimpan output');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i>Tambahkan Terpilih');
            },
        });
    });

    $(document).on('click', '.btn-delete-record', function () {
        const id = $(this).data('id');
        Notify.confirm('Hapus Termin?', function () {
            $.ajax({
                url: window.route.update + id,
                method: 'POST',
                data: { _token: window.route.csrf, _method: 'DELETE' },
                success: function (res) {
                    Notify.success(res.message || 'Data berhasil dihapus');
                    const soId = currentTerminData && currentTerminData.id_so;
                    if (soId) {
                        window.location.href = '/sales-orders?open=' + soId;
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
