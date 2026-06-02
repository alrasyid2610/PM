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
                    <div class="col-md-3 col-12">
                        <label for="nomor" class="form-label required">Nomor</label>
                        <input type="text" class="form-control" id="nomor" name="nomor" required>
                    </div>
                    <div class="col-md-5 col-12">
                        <label for="nama" class="form-label required">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    <div class="col-md-2 col-12">
                        <label for="persentase" class="form-label required">Persentase (%)</label>
                        <input type="number" class="form-control" id="persentase" name="persentase"
                               step="0.01" min="0" max="100" required>
                    </div>
                    <div class="col-md-2 col-12">
                        <label for="status" class="form-label required">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="proses">Proses</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="nilai" class="form-label required">Nilai (Rp)</label>
                        <input type="number" class="form-control" id="nilai" name="nilai"
                               step="1" min="0" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="tanggal" class="form-label required">Tanggal</label>
                        <input type="date" class="form-control" id="tanggal" name="tanggal" required>
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
            });
        }

        $('#id_so').on('change', function () {
            var soId = $(this).val();
            if (!soId) {
                $('#outputSelectionWrap').hide();
                $('#outputSelectionContent').html('');
                return;
            }
            $('#outputSelectionWrap').show();
            $('#outputSelectionContent').html('<div class="text-center py-3"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>');
            $.get('/termin/outputs-by-so/' + soId, function (data) {
                $('#outputSelectionContent').html(renderOutputSelection(data));
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

        var rows = outputs.map(function (item) {
            return '<tr>' +
                '<td ' + TD + ' style="width:40px;text-align:center;">' +
                    '<input type="checkbox" class="form-check-input output-check" ' +
                    'name="selected_outputs[]" value="' + item.id_output + '">' +
                '</td>' +
                '<td ' + TD + ' style="font-size:12px;color:#64748b;white-space:nowrap;">' + escHtml(item.no_wo) + '</td>' +
                '<td ' + TD + '>' + escHtml(item.judul_output) + '</td>' +
                '<td ' + TD + '>' +
                    '<input type="text" name="judul_tagihan[' + item.id_output + ']" ' +
                    'class="form-control form-control-sm judul-tagihan-input" ' +
                    'placeholder="Sama seperti judul output" maxlength="255" readonly ' +
                    'style="background:#f8fafc;color:#94a3b8;">' +
                '</td>' +
            '</tr>';
        }).join('');

        return '<div class="mb-2 d-flex align-items-center gap-2">' +
            '<input type="checkbox" class="form-check-input" id="checkAllOutputs">' +
            '<label for="checkAllOutputs" class="form-label mb-0" style="font-size:12px;cursor:pointer;">Pilih Semua</label>' +
            '<span class="text-muted ms-2" id="outputCheckCount" style="font-size:11px;"></span>' +
            '</div>' +
            '<div class="table-responsive">' +
            '<table class="table table-sm table-hover mb-0" style="font-size:13px;">' +
            '<thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;"><tr>' +
            '<th ' + TH + ' style="width:40px;"></th>' +
            '<th ' + TH + ' style="min-width:110px;">No WO</th>' +
            '<th ' + TH + ' style="min-width:200px;">Judul Output</th>' +
            '<th ' + TH + ' style="min-width:220px;">Judul Tagihan <span style="font-size:10px;color:#94a3b8;">(opsional)</span></th>' +
            '</tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '</table></div>';
    }

    $(document).on('change', '.output-check', function () {
        var checked = this.checked;
        var $input = $(this).closest('tr').find('.judul-tagihan-input');
        if (checked) {
            $input.removeAttr('readonly').css({ background: '', color: '' });
        } else {
            $input.val('').attr('readonly', true).css({ background: '#f8fafc', color: '#94a3b8' });
        }
        var total = $('.output-check').length;
        var selected = $('.output-check:checked').length;
        $('#outputCheckCount').text(selected + ' dari ' + total + ' dipilih');
        $('#checkAllOutputs').prop('indeterminate', selected > 0 && selected < total);
        $('#checkAllOutputs').prop('checked', selected === total);
    });

    $(document).on('change', '#checkAllOutputs', function () {
        $('.output-check').prop('checked', this.checked).trigger('change');
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
