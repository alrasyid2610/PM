@extends('layouts.app')

@section('page-title', 'Import Data Lab')
@section('page-descrip', 'Import data master Lab Testing per modul dari file Excel')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Import Data Lab</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 12h28l12 12v44H20V12z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M48 12v12h12" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M40 38v20M32 50l8 8 8-8" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <div class="row g-3">

        <div class="col-12">
            <x-section-card icon="fa-file-import" color="icon-navy" title="Import Data Lab" subtitle="Pilih 1 modul, download template modul itu, isi, lalu upload — diproses per modul supaya risikonya kecil">
                <div class="row g-3">
                    <div class="col-12" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;font-size:13px;color:#374151;">
                        <strong>Urutan yang disarankan</strong> (kalau modulnya punya relasi ke modul lain, modul induknya harus sudah ada datanya duluan):
                        <ol class="mb-0 mt-1" style="padding-left:18px;">
                            <li>Testing Units, Testing Parameters, Kelompok Matriks Sample, Testing Standards — bebas urutan, berdiri sendiri</li>
                            <li>Matriks Sample — butuh Kelompok Matriks Sample</li>
                            <li>Testing Points — butuh Testing Standards + Matriks Sample</li>
                            <li>Testing Items — butuh Testing Points + Testing Parameters + Testing Units</li>
                        </ol>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label required">Pilih Modul</label>
                        <select id="importModul" class="form-select">
                            <option value="">-- Pilih Modul --</option>
                            @foreach($modules as $m)
                                <option value="{{ $m['key'] }}">{{ $m['label'] }}</option>
                            @endforeach
                        </select>
                        <div id="importModulNote" class="form-text text-warning" style="display:none;"></div>
                    </div>
                    <div class="col-md-6 col-12 d-flex align-items-end">
                        <a href="#" id="btnDownloadTemplate" class="btn btn-outline-primary btn-sm disabled" tabindex="-1" aria-disabled="true">
                            <i class="fa-solid fa-download me-1"></i> Download Template Modul Ini
                        </a>
                    </div>

                    <div class="col-12">
                        <label class="form-label required">Upload File Excel Terisi</label>
                        <input type="file" id="importFile" class="form-control" accept=".xlsx,.xls" disabled>
                    </div>
                    <div class="col-12">
                        <button type="button" id="btnImport" class="btn btn-primary btn-sm" disabled>
                            <i class="fa-solid fa-upload me-1"></i> Proses Import Modul Ini
                        </button>
                    </div>
                </div>
            </x-section-card>
        </div>

        <div class="col-12" id="importSummaryWrap" style="display:none;">
            <x-section-card icon="fa-list-check" color="icon-blue" title="Hasil Import">
                <div id="importSummaryBody"></div>
            </x-section-card>
        </div>

    </div>
</section>
@endsection

@section('custom-script')
<script>
    const MODULE_NOTES = {
        @foreach($modules as $m)
            "{{ $m['key'] }}": {{ \Illuminate\Support\Js::from($m['note'] ?? null) }},
        @endforeach
    };

    $('#importModul').on('change', function () {
        const modul = $(this).val();
        const hasModul = !!modul;

        $('#importFile, #btnImport').prop('disabled', !hasModul);
        $('#btnDownloadTemplate').toggleClass('disabled', !hasModul).attr('aria-disabled', !hasModul);
        $('#btnDownloadTemplate').attr('href', hasModul ? "{{ route('lab-data-import.template') }}?modul=" + modul : '#');

        const note = MODULE_NOTES[modul];
        if (note) {
            $('#importModulNote').text('⚠ ' + note).show();
        } else {
            $('#importModulNote').hide();
        }

        $('#importSummaryWrap').hide();
        $('#importFile').val('');
    });

    $('#btnImport').on('click', function () {
        const modul = $('#importModul').val();
        const fileEl = document.getElementById('importFile');
        if (!modul) { Notify.warning('Pilih modul terlebih dahulu'); return; }
        if (!fileEl.files.length) { Notify.warning('Pilih file Excel terlebih dahulu'); return; }

        const $btn = $(this);
        const fd = new FormData();
        fd.append('_token', "{{ csrf_token() }}");
        fd.append('modul', modul);
        fd.append('file', fileEl.files[0]);

        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...');
        $('#importSummaryWrap').hide();

        $.ajax({
            url: "{{ route('lab-data-import.import') }}",
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
        })
            .done(function (res) {
                if (!res.success) {
                    Notify.error(res.message || 'Import gagal');
                    return;
                }
                Notify.success('Import selesai');
                renderSummary($('#importModul option:selected').text(), res.result, res.result_file);
                fileEl.value = '';
            })
            .fail(function (xhr) {
                Notify.error(xhr.responseJSON?.message || 'Import gagal');
            })
            .always(function () {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-upload me-1"></i> Proses Import Modul Ini');
            });
    });

    function renderSummary(label, s, resultFile) {
        const detailHtml = s.errors && s.errors.length
            ? '<ul class="mb-0 mt-2" style="padding-left:16px;font-size:12px;color:#b45309;">' +
              s.errors.slice(0, 20).map(e => `<li>${escHtml(e)}</li>`).join('') +
              (s.errors.length > 20 ? `<li>...dan ${s.errors.length - 20} lainnya</li>` : '') +
              '</ul>'
            : '';

        const downloadBtn = resultFile
            ? `<a href="{{ url('lab-data-import/result') }}/${encodeURIComponent(resultFile)}" class="btn btn-outline-success btn-sm ms-auto">
                   <i class="fa-solid fa-file-excel me-1"></i> Download Hasil (dengan kolom Status)
               </a>`
            : '';

        const html = `<div class="d-flex align-items-center gap-3 flex-wrap mb-1">
                <span style="font-weight:600;">${escHtml(label)}</span>
                <span class="badge rounded-pill" style="background:#dcfce7;color:#166534;">Ditambahkan: ${s.inserted}</span>
                <span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;">Dilewati: ${s.skipped}</span>
                ${downloadBtn}
            </div>${detailHtml}`;

        $('#importSummaryBody').html(html);
        $('#importSummaryWrap').show();
    }
</script>
@endsection
