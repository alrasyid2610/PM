let page;
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
        renderForm: renderForm,
        initSelect: function () {
            $("#detail_status").select2({
                width: "100%",
                dropdownParent: $("#detailContent"),
            });
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
            var rows = data.map(function (item) {
                return '<tr>' +
                    '<td ' + TD_STYLE + ' style="width:40px;text-align:center;">' +
                        '<input type="checkbox" class="form-check-input output-pick-check" data-id="' + item.id_output + '" data-wo="' + escHtml(item.no_wo) + '" data-judul="' + escHtml(item.judul_output) + '">' +
                    '</td>' +
                    '<td ' + TD_STYLE + ' style="font-size:12px;color:#64748b;">' + escHtml(item.no_wo) + '</td>' +
                    '<td ' + TD_STYLE + '>' + escHtml(item.judul_output) + '</td>' +
                '</tr>';
            }).join('');
            $wrap.html(
                '<div class="mt-3 p-3" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">' +
                '<div class="fw-semibold mb-2" style="font-size:12px;color:#374151;"><i class="fa-solid fa-plus me-1" style="color:#0f766e;"></i>Pilih Output Tambahan</div>' +
                '<div class="table-responsive"><table class="table table-sm table-hover mb-2" id="outputPickerTable" style="font-size:13px;">' +
                '<thead style="background:#f8fafc;border-bottom:1px solid #e2e8f0;"><tr>' +
                '<th ' + TH_STYLE + ' style="width:40px;"></th>' +
                '<th ' + TH_STYLE + ' style="min-width:110px;">No WO</th>' +
                '<th ' + TH_STYLE + ' style="min-width:200px;">Judul Output</th>' +
                '</tr></thead><tbody>' + rows + '</tbody></table></div>' +
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
                    $('#detailContent').html('');
                    page.selectedRow.id = null;
                    if ($.fn.DataTable.isDataTable('#masterTable')) {
                        $('#masterTable').DataTable().ajax.reload(null, false);
                    }
                },
                error: function (xhr) {
                    Notify.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                },
            });
        });
    });
});
