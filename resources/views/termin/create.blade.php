@extends(request('embed') ? 'layouts.embed' : 'layouts.app')

@section('page-title', 'Termin')
@section('page-descrip', 'Kelola data termin pembayaran proyek')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('termin.index') }}">Termin</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="12" y="10" width="56" height="60" rx="4" stroke="white" stroke-width="3"/>
        <path d="M24 28h32M24 40h32M24 52h20" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <path d="M52 48l6 6-6 6" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="terminForm" enctype="multipart/form-data" class="row g-3">
        @csrf

        <!-- SECTION 1: INFORMASI TERMIN -->
        <div class="col-12">
            <x-section-card icon="fa-file-invoice-dollar" color="icon-navy" title="Termin" subtitle="Data termin pembayaran proyek">
                <div class="row g-3">
                    <div class="col-md-8 col-12">
                        <label for="nama" class="form-label required">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    <div class="col-md-2 col-12">
                        <label for="persentase" class="form-label">Persentase (%)</label>
                        <input type="number" class="form-control" id="persentase" name="persentase"
                               step="0.01" min="0" max="100">
                    </div>
                    <div class="col-md-2 col-12">
                        <label for="status" class="form-label required">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="siap_kirim">Siap Kirim</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label">Down Payment</label>
                        <div class="form-check mt-1">
                            <input type="checkbox" class="form-check-input" id="is_dp" name="is_dp" value="1">
                            <label class="form-check-label" for="is_dp">DP</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="nilai" class="form-label required">Nilai (Rp)</label>
                        <input type="text" inputmode="numeric" class="form-control input-num-mask" id="nilai" name="nilai" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="tanggal" class="form-label required">Tanggal Selesai</label>
                        <input type="text" class="form-control fp-date" id="tanggal" name="tanggal" placeholder="Pilih tanggal" autocomplete="off" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="1"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label for="id_so" class="form-label">Sales Order</label>
                        <select class="form-select" id="id_so" name="id_so">
                            <option value="">— Tidak ada —</option>
                        </select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION 2: OUTPUT PEKERJAAN -->
        <div class="col-12" id="outputSelectionWrap" style="display:none;">
            <x-section-card icon="fa-file-circle-check" color="icon-teal" title="Output Pekerjaan" subtitle="Pilih output yang akan ditagihkan pada termin ini">
                <div id="outputSelectionContent">
                    <div class="text-center text-muted py-3">Pilih Sales Order terlebih dahulu</div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION 3: ATTACHMENT -->
        <div class="col-12">
            <x-section-card icon="fa-paperclip" color="icon-blue" title="Attachment" subtitle="File pendukung termin">
                <input type="file" class="filepond" name="attachments[]" multiple>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('termin.index') }}" submit-label="Simpan Termin" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    var preselectSoId = new URLSearchParams(window.location.search).get('id_so');

    $(document).ready(function () {
        initNumericMask(document);
        initFpDate(document);
        createFileUploader(".filepond");
        $('#status').select2({ placeholder: 'Pilih Status', width: '100%' });

        $('#id_so').select2({
            placeholder: '— Tidak ada —',
            allowClear: true,
            width: '100%',
            ajax: {
                url: '/sales-orders/select2',
                dataType: 'json',
                delay: 200,
                data: function (p) { return { q: p.term }; },
                processResults: function (d) { return { results: d }; },
                cache: true,
            },
        });

        if (preselectSoId) {
            $.get('/sales-orders/' + preselectSoId, function (so) {
                if (!so || !so.id_so) return;
                var opt = new Option(so.no_so + ' — ' + so.judul_order, so.id_so, true, true);
                $('#id_so').append(opt).trigger('change');
                $('#id_so').prop('disabled', true);
                $('<input>').attr({ type: 'hidden', name: 'id_so', value: so.id_so }).appendTo('#terminForm');
                checkDpStatus(so.id_so);
            });
        }

        function checkDpStatus(soId) {
            if (!soId) {
                $('#is_dp').prop('disabled', false);
                $('#dp-info').remove();
                return;
            }
            $.get('/termin/check-dp/' + soId, function (res) {
                if (res.has_dp) {
                    $('#is_dp').prop('checked', false).prop('disabled', true);
                    $('#status').prop('disabled', false).val('pending').trigger('change');
                    $('#status_hidden').remove();
                    if (!$('#dp-info').length) {
                        $('#is_dp').closest('.form-check').after(
                            '<small id="dp-info" class="text-danger d-block mt-1">' +
                            '<i class="fa-solid fa-circle-info me-1"></i>Sudah ada termin DP pada SO ini</small>'
                        );
                    }
                } else {
                    $('#is_dp').prop('disabled', false);
                    $('#dp-info').remove();
                }
            });
        }

        $('#is_dp').on('change', function () {
            if (this.checked) {
                $('#status').val('selesai').trigger('change').prop('disabled', true);
                if (!$('#status_hidden').length) {
                    $('<input>').attr({ type: 'hidden', id: 'status_hidden', name: 'status', value: 'selesai' }).appendTo('#terminForm');
                }
            } else {
                $('#status').prop('disabled', false).val('pending').trigger('change');
                $('#status_hidden').remove();
            }
        });

        $('#id_so').on('change', function () {
            var soId = $(this).val();
            checkDpStatus(soId);
            if (!soId) {
                $('#outputSelectionWrap').hide();
                $('#outputSelectionContent').html('');
                return;
            }
            $('#outputSelectionWrap').show();
            $('#outputSelectionContent').html('<div class="text-center py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');
            $.get('/termin/outputs-by-so/' + soId, function (data) {
                $('#outputSelectionContent').html(renderOutputSelection(data));
                filterOutputByTanggal();
            }).fail(function () {
                $('#outputSelectionContent').html('<div class="text-center text-danger py-3">Gagal memuat output pekerjaan</div>');
            });
        });
    });

    function renderOutputSelection(outputs) {
        if (!outputs.length) {
            return '<div class="text-center text-muted py-4">' +
                '<i class="fa-solid fa-inbox fa-2x d-block mb-2 opacity-25"></i>' +
                'Tidak ada output pekerjaan yang tersedia untuk SO ini</div>';
        }

        var TH = 'style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;padding:8px 12px;color:#64748b;font-weight:600;"';
        var TD = 'style="padding:8px 12px;vertical-align:middle;"';

        // Kelompokkan per WO
        var woGroups = new Map();
        outputs.forEach(function (item) {
            var key = item.no_wo || '—';
            if (!woGroups.has(key)) woGroups.set(key, []);
            woGroups.get(key).push(item);
        });

        var statusMap = {
            belum_siap: { label: 'Belum Siap', bg: '#fef2f2', color: '#dc2626', border: '#fecaca' },
            siap:       { label: 'Siap',        bg: '#f0fdf4', color: '#16a34a', border: '#bbf7d0' },
            terkirim:   { label: 'Terkirim',    bg: '#eff6ff', color: '#1d4ed8', border: '#bfdbfe' },
        };

        var accordionItems = '';
        woGroups.forEach(function (list, noWo) {
            var rows = list.map(function (item) {
                var st = statusMap[item.status] || statusMap['belum_siap'];
                var statusBadge = '<span style="font-size:11px;padding:2px 9px;border-radius:20px;white-space:nowrap;background:' + st.bg + ';color:' + st.color + ';border:1px solid ' + st.border + ';">' + st.label + '</span>';
                return '<tr>' +
                    '<td ' + TD + ' style="text-align:center;">' +
                        '<input type="checkbox" class="form-check-input output-check" ' +
                        'name="selected_outputs[]" value="' + item.id_output + '" ' +
                        'data-tgl-selesai="' + (item.tanggal_selesai ? item.tanggal_selesai.substring(0, 10) : '') + '">' +
                    '</td>' +
                    '<td ' + TD + '>' + escHtml(item.judul_output) + '</td>' +
                    '<td ' + TD + '>' + statusBadge + '</td>' +
                    '<td ' + TD + ' style="white-space:nowrap;color:#64748b;font-size:12px;">' + (item.tanggal_selesai ? item.tanggal_selesai.substring(0, 10) : '—') + '</td>' +
                '</tr>';
            }).join('');

            accordionItems +=
                '<div class="termin-output-accordion-item" style="border-bottom:1px solid #e2e8f0;">' +
                    '<div class="termin-output-accordion-header" style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f8fafc;cursor:pointer;user-select:none;">' +
                        '<i class="fa-solid fa-chevron-down" style="color:#1a56db;font-size:10px;transition:transform .2s;"></i>' +
                        '<i class="fa-solid fa-briefcase" style="color:#1a56db;font-size:12px;"></i>' +
                        '<span style="font-size:13px;font-weight:600;color:#1e293b;">' + escHtml(noWo) + '</span>' +
                        '<span style="font-size:11px;font-weight:600;padding:1px 8px;border-radius:20px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">' + list.length + ' output</span>' +
                    '</div>' +
                    '<div class="termin-output-accordion-body">' +
                        '<div class="table-responsive">' +
                        '<table class="table table-sm table-hover mb-0" style="font-size:13px;table-layout:fixed;width:100%;">' +
                        '<colgroup><col style="width:44px;"><col><col style="width:110px;"><col style="width:120px;"></colgroup>' +
                        '<thead style="background:#f8fafc;border-bottom:1px solid #e2e8f0;"><tr>' +
                        '<th ' + TH + '></th>' +
                        '<th ' + TH + '>Judul Output</th>' +
                        '<th ' + TH + '>Status</th>' +
                        '<th ' + TH + '>Tgl Selesai</th>' +
                        '</tr></thead>' +
                        '<tbody>' + rows + '</tbody>' +
                        '</table></div>' +
                    '</div>' +
                '</div>';
        });

        return '<p class="text-muted mb-2" style="font-size:11px;">' +
                '<i class="fa-solid fa-circle-info me-1"></i>' +
                'Apabila output tidak bisa dipilih, harap periksa kembali tanggal selesai output atau sesuaikan tanggal termin.' +
            '</p>' +
            '<div class="mb-2 d-flex align-items-center gap-2 flex-wrap">' +
                '<input type="checkbox" class="form-check-input" id="checkAllOutputs">' +
                '<label for="checkAllOutputs" class="form-label mb-0" style="font-size:12px;cursor:pointer;">Pilih Semua</label>' +
                '<span class="text-muted ms-2" id="outputCheckCount" style="font-size:11px;"></span>' +
                '<div class="ms-auto" style="position:relative;min-width:220px;">' +
                    '<span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;pointer-events:none;">' +
                        '<i class="fa-solid fa-magnifying-glass"></i>' +
                    '</span>' +
                    '<input type="text" id="outputSearchInput" placeholder="Cari judul output..." ' +
                        'style="width:100%;padding:5px 10px 5px 30px;font-size:12px;border:1px solid #e2e8f0;border-radius:6px;outline:none;">' +
                '</div>' +
            '</div>' +
            '<div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;" id="outputAccordionWrap">' +
            accordionItems +
            '</div>' +
            '<div id="outputSearchEmpty" style="display:none;" class="text-center text-muted py-4">' +
                '<i class="fa-solid fa-magnifying-glass fa-2x d-block mb-2 opacity-25"></i>Tidak ditemukan hasil pencarian</div>';
    }

    $(document).on('input', '#outputSearchInput', function () {
        var q = $(this).val().toLowerCase().trim();
        var anyVisible = false;

        $('.termin-output-accordion-item').each(function () {
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
                $item.find('.termin-output-accordion-body').show();
                $item.find('.fa-chevron-down').css('transform', 'rotate(0deg)');
            }
            if (visible) anyVisible = true;
        });

        $('#outputAccordionWrap').toggle(anyVisible);
        $('#outputSearchEmpty').toggle(!anyVisible);
    });

    $(document).on('click', '.termin-output-accordion-header', function () {
        var $body = $(this).next('.termin-output-accordion-body');
        var $chevron = $(this).find('.fa-chevron-down');
        var isOpen = $body.is(':visible');
        $body.slideToggle(150);
        $chevron.css('transform', isOpen ? 'rotate(-90deg)' : 'rotate(0deg)');
    });

    $(document).on('change', '.output-check', function () {
        var checked = this.checked;
        var $input = $(this).closest('tr').find('.judul-tagihan-input');
        if (checked) {
            $input.removeAttr('readonly').css({ background: '', color: '' });
        } else {
            $input.val('').attr('readonly', true).css({ background: '#f8fafc', color: '#94a3b8' });
        }
        var $visible = $('.termin-output-accordion-item:visible tbody tr:visible .output-check:not(:disabled)');
        var total    = $visible.length;
        var selected = $visible.filter(':checked').length;
        var totalAll = $('.output-check:checked').length;
        $('#outputCheckCount').text(totalAll + ' dari ' + $('.output-check:not(:disabled)').length + ' dipilih');
        $('#checkAllOutputs').prop('indeterminate', selected > 0 && selected < total);
        $('#checkAllOutputs').prop('checked', total > 0 && selected === total);
    });

    $(document).on('change', '#checkAllOutputs', function () {
        var checked = this.checked;
        $('.termin-output-accordion-item:visible tbody tr:visible .output-check:not(:disabled)')
            .prop('checked', checked).trigger('change');
    });

    function filterOutputByTanggal() {
        var tglTermin = $('#tanggal').val();
        $('.output-check').each(function () {
            var tglOutput = $(this).data('tgl-selesai');
            var shouldDisable = tglTermin && tglOutput && tglOutput > tglTermin;
            if (shouldDisable) {
                $(this).prop('checked', false).prop('disabled', true)
                    .closest('tr').css('opacity', '0.45');
            } else {
                $(this).prop('disabled', false)
                    .closest('tr').css('opacity', '');
            }
        });
        // sinkronkan counter & checkAll
        $('.output-check:checked').trigger('change');
        if (!$('.output-check:checked').length) {
            $('#outputCheckCount').text('');
            $('#checkAllOutputs').prop('checked', false).prop('indeterminate', false);
        }
    }

    $(document).on('change', '#tanggal', filterOutputByTanggal);

    $('#terminForm').on('submit', function () {
        var el = document.getElementById('nilai');
        if (el && el._cleave) el.value = el._cleave.getRawValue();
    });

    submitCreateForm({
        formId:   "#terminForm",
        url:      "{{ route('termin.store') }}",
        redirect: preselectSoId ? null : "{{ route('termin.index') }}",
        filepond: ".filepond",
        onSuccess: preselectSoId ? function () {
            localStorage.setItem('termin_created', JSON.stringify({ id_so: preselectSoId, ts: Date.now() }));
            var inIframe = window.self !== window.top;
            if (!inIframe) {
                if (window.opener && !window.opener.closed) window.opener.focus();
                setTimeout(function () { window.close(); }, 800);
            }
        } : null,
    });
</script>
@endsection
