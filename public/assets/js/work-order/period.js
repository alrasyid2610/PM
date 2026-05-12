// ── WO Period Section ────────────────────────────────────────────────────────
// Dipanggil setelah renderForm selesai di-mount ke DOM.
// id_wo   : integer
// id_so   : integer
// id_period: integer|null (dari res saat ini)
// res     : objek lengkap dari WO show()

function loadWoPeriodContent(id_wo, id_so, id_period) {
    var $wrap = $('#woPeriodContent');
    $wrap.html('<div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');

    $.get('/wo-periods/by-so/' + id_so, function (periods) {
        var current = null;
        if (id_period) {
            for (var i = 0; i < periods.length; i++) {
                if (periods[i].id_period == id_period) { current = periods[i]; break; }
            }
        }
        $wrap.html(renderWoPeriodView(current, id_wo));
    }).fail(function () {
        $wrap.html('<div class="text-muted small py-2 text-center text-danger">Gagal memuat data period</div>');
    });
}

function renderWoPeriodView(period, id_wo) {
    if (!period) {
        return '<div class="text-center text-muted py-3">' +
            '<i class="fa-solid fa-calendar-xmark fa-lg d-block mb-2 opacity-25"></i>' +
            '<span class="small">Belum ada period jadwal</span></div>';
    }

    var tglMulai   = period.tanggal_mulai   ? period.tanggal_mulai.substring(0, 7)   : '—';
    var tglSelesai = period.tanggal_selesai ? period.tanggal_selesai.substring(0, 7) : '—';
    var interval   = period.interval_bulan  ? 'tiap ' + period.interval_bulan + ' bulan' : '';

    var woListHtml = '';
    if (period.wos && period.wos.length) {
        woListHtml = '<div class="mt-3">' +
            '<div class="small text-muted fw-semibold mb-1"><i class="fa-solid fa-briefcase me-1"></i>WO dalam period ini (' + period.wos.length + ')</div>' +
            period.wos.map(function (wo) {
                var isThis = wo.id_wo == id_wo;
                return '<div class="d-flex align-items-center gap-2 py-1" style="border-bottom:1px solid #f1f5f9;">' +
                    '<i class="fa-solid fa-circle-dot" style="font-size:8px;color:#9ca3af;"></i>' +
                    (isThis
                        ? '<span class="small fw-semibold" style="color:var(--primary-700);">' + escWo(wo.no_wo) + ' <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:10px;">ini</span></span>'
                        : '<a href="/work-orders?open=' + wo.id_wo + '" target="_blank" class="small fw-semibold text-decoration-none" style="color:var(--primary-500);">' + escWo(wo.no_wo) + '</a>') +
                    '<span class="small text-muted">' + escWo(wo.judul_pekerjaan ?? '') + '</span>' +
                    '</div>';
            }).join('') +
            '</div>';
    } else {
        woListHtml = '<div class="mt-2 small text-muted">Belum ada WO lain dalam period ini</div>';
    }

    return '<div class="d-flex flex-wrap gap-3">' +
        '<div style="min-width:140px;">' +
            '<div class="small text-muted mb-1">Lokasi</div>' +
            '<div class="small fw-semibold">' + escWo(period.nama_site ?? '—') + '</div>' +
        '</div>' +
        '<div style="min-width:140px;">' +
            '<div class="small text-muted mb-1">Jadwal</div>' +
            '<div class="small fw-semibold">' + tglMulai + ' s/d ' + tglSelesai + '</div>' +
        '</div>' +
        (interval ? '<div>' +
            '<div class="small text-muted mb-1">Frekuensi</div>' +
            '<div class="small fw-semibold">' + escWo(interval) + '</div>' +
        '</div>' : '') +
    '</div>' + woListHtml;
}

// ── Edit mode: render form inline ───────────────────────────────────────────
function renderWoPeriodEditForm(id_wo, id_so, id_period) {
    return '<div id="woPeriodEditWrap">' +
        // Row 1: pilih period existing
        '<div class="mb-3">' +
            '<label class="form-label form-label-sm text-muted mb-1">Pilih Period yang Ada</label>' +
            '<div class="d-flex gap-2">' +
                '<select id="woPeriodSelect2" style="width:100%"></select>' +
            '</div>' +
        '</div>' +

        // Divider
        '<div class="d-flex align-items-center gap-2 mb-3">' +
            '<hr style="flex:1;margin:0;">' +
            '<span class="small text-muted">atau</span>' +
            '<hr style="flex:1;margin:0;">' +
        '</div>' +

        // Toggle create new period
        '<div id="woPeriodCreateToggle">' +
            '<button type="button" id="btnShowCreatePeriod" class="btn btn-outline-primary btn-sm">' +
                '<i class="fa-solid fa-plus me-1"></i> Buat Period Baru' +
            '</button>' +
        '</div>' +

        // Create new period form (hidden)
        '<div id="woPeriodCreateForm" class="d-none mt-3 p-3" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">' +
            '<div class="small fw-semibold text-muted mb-2"><i class="fa-solid fa-calendar-plus me-1"></i>Data Period Baru</div>' +
            '<div class="row g-2">' +
                '<div class="col-md-12">' +
                    '<label class="form-label form-label-sm text-muted mb-1">Lokasi (Site)</label>' +
                    '<select id="newPeriodSite" style="width:100%"></select>' +
                '</div>' +
                '<div class="col-md-5">' +
                    '<label class="form-label form-label-sm text-muted mb-1">Tanggal Mulai</label>' +
                    '<input type="date" id="newPeriodMulai" class="form-control form-control-sm">' +
                '</div>' +
                '<div class="col-md-5">' +
                    '<label class="form-label form-label-sm text-muted mb-1">Tanggal Selesai</label>' +
                    '<input type="date" id="newPeriodSelesai" class="form-control form-control-sm">' +
                '</div>' +
                '<div class="col-md-2">' +
                    '<label class="form-label form-label-sm text-muted mb-1">Interval (bln)</label>' +
                    '<input type="number" id="newPeriodInterval" class="form-control form-control-sm" min="1" placeholder="2">' +
                '</div>' +
                '<div class="col-md-12">' +
                    '<label class="form-label form-label-sm text-muted mb-1">Keterangan</label>' +
                    '<input type="text" id="newPeriodKet" class="form-control form-control-sm" placeholder="opsional">' +
                '</div>' +
            '</div>' +
            '<div class="d-flex justify-content-end gap-2 mt-3">' +
                '<button type="button" id="btnCancelCreatePeriod" class="btn btn-outline-secondary btn-sm">Batal</button>' +
                '<button type="button" id="btnSaveCreatePeriod" class="btn btn-primary btn-sm">' +
                    '<i class="fa-solid fa-check me-1"></i> Simpan Period' +
                '</button>' +
            '</div>' +
        '</div>' +

        // Action bar
        '<div class="d-flex justify-content-end gap-2 mt-3">' +
            '<button type="button" id="btnCancelPeriodEdit" class="btn btn-outline-secondary btn-sm">Batal</button>' +
            '<button type="button" id="btnSavePeriodAssign" class="btn btn-primary btn-sm">' +
                '<i class="fa-solid fa-check me-1"></i> Simpan' +
            '</button>' +
        '</div>' +
    '</div>';
}

// ── Init event handlers for period section ──────────────────────────────────
function initWoPeriodSection(id_wo, id_so, id_period) {

    // ── Ubah Period button → switch to edit mode ─────────────────────────────
    $(document).on('click', '#btnUbahPeriod', function () {
        var $content = $('#woPeriodContent');
        var $btn     = $('#btnUbahPeriod');
        $btn.addClass('d-none');
        $content.html(renderWoPeriodEditForm(id_wo, id_so, id_period));

        // Init Select2 for picking existing period
        $('#woPeriodSelect2').select2({
            placeholder: 'Pilih period yang sudah ada...',
            allowClear: true,
            ajax: {
                url: '/wo-periods/select2',
                dataType: 'json',
                delay: 200,
                data: function (params) {
                    return { q: params.term, id_so: id_so };
                },
            },
        });

        // Pre-select if already has period
        if (id_period) {
            $.get('/wo-periods/select2?id_so=' + id_so, function (results) {
                var match = results.find(function (r) { return r.id == id_period; });
                if (match) {
                    var opt = new Option(match.text, match.id, true, true);
                    $('#woPeriodSelect2').append(opt).trigger('change');
                }
            });
        }

        // Init select2 for new period site
        $('#newPeriodSite').select2({
            dropdownParent: $('#woPeriodCreateForm'),
            placeholder: 'Pilih lokasi...',
            ajax: {
                url: '/business-relations/sites/select2',
                dataType: 'json',
                delay: 200,
                data: function (params) { return { q: params.term }; },
            },
        });

        // Toggle create form
        $(document).on('click', '#btnShowCreatePeriod', function () {
            $('#woPeriodCreateForm').removeClass('d-none');
            $('#btnShowCreatePeriod').addClass('d-none');
        });
        $(document).on('click', '#btnCancelCreatePeriod', function () {
            $('#woPeriodCreateForm').addClass('d-none');
            $('#btnShowCreatePeriod').removeClass('d-none');
        });

        // Save new period
        $(document).on('click', '#btnSaveCreatePeriod', function () {
            var $btn = $(this);
            var id_site = $('#newPeriodSite').val();
            if (!id_site) { alert('Lokasi wajib dipilih'); return; }

            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');
            $.ajax({
                url: '/wo-periods',
                method: 'POST',
                data: {
                    _token: window.route.csrf,
                    id_so: id_so,
                    id_site: id_site,
                    tanggal_mulai: $('#newPeriodMulai').val() || null,
                    tanggal_selesai: $('#newPeriodSelesai').val() || null,
                    interval_bulan: $('#newPeriodInterval').val() || null,
                    keterangan: $('#newPeriodKet').val() || null,
                },
                success: function (res) {
                    // Auto-select the new period
                    var opt = new Option(
                        (res.data.nama_site ?? 'Lokasi') +
                        (res.data.tanggal_mulai ? ' · ' + res.data.tanggal_mulai.substring(0, 7) : '') +
                        (res.data.interval_bulan ? ' · tiap ' + res.data.interval_bulan + ' bln' : ''),
                        res.data.id_period, true, true
                    );
                    $('#woPeriodSelect2').append(opt).trigger('change');
                    $('#woPeriodCreateForm').addClass('d-none');
                    $('#btnShowCreatePeriod').removeClass('d-none');
                },
                error: function () { alert('Gagal membuat period'); },
                complete: function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Simpan Period'); },
            });
        });

        // Cancel edit
        $(document).on('click', '#btnCancelPeriodEdit', function () {
            $('#btnUbahPeriod').removeClass('d-none');
            loadWoPeriodContent(id_wo, id_so, id_period);
        });

        // Save assignment
        $(document).on('click', '#btnSavePeriodAssign', function () {
            var $btn  = $(this);
            var newId = $('#woPeriodSelect2').val() || null;

            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>');
            $.ajax({
                url: '/work-orders/' + id_wo + '/period',
                method: 'PUT',
                data: { _token: window.route.csrf, id_period: newId },
                success: function () {
                    id_period = newId ? parseInt(newId) : null;
                    $('#btnUbahPeriod').removeClass('d-none');
                    loadWoPeriodContent(id_wo, id_so, id_period);
                },
                error: function () { alert('Gagal menyimpan period'); },
                complete: function () { $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Simpan'); },
            });
        });
    });
}
