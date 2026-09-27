@extends(request('embed') ? 'layouts.embed' : 'layouts.app')

@section('page-title', 'SQ Fieldworks')
@section('page-descrip', 'Tambahkan Fieldwork estimasi')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('sq-fieldworks') }}">SQ Fieldworks</a></li>
    <li class="breadcrumb-item active" aria-current="page">Tambah</li>
@endsection

@section('content')

{{-- STICKY WO INFO BANNER --}}
<div id="sqWoInfoBanner" style="display:none;position:sticky;top:0;z-index:100;background:#fff;border-bottom:2px solid #e2e8f0;padding:10px 16px;margin:-8px -12px 16px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
    <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:13px;">
        <div style="display:flex;align-items:center;gap:6px;min-width:0;">
            <i class="fa-solid fa-briefcase" style="color:#1a56db;font-size:11px;flex-shrink:0;"></i>
            <span id="sqWoBannerNoSqWo" style="font-weight:700;color:#1a56db;white-space:nowrap;"></span>
        </div>
        <div style="width:1px;height:16px;background:#e2e8f0;flex-shrink:0;"></div>
        <div style="display:flex;align-items:center;gap:6px;min-width:0;flex:1;">
            <i class="fa-solid fa-file-lines" style="color:#374151;font-size:11px;flex-shrink:0;"></i>
            <span id="sqWoBannerJudul" style="color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
        </div>
    </div>
</div>

<section class="section">
    <form id="sqFieldworkForm" class="row g-3">
        @csrf
        <input type="hidden" name="id_sq_wo" id="id_sq_wo">

        <div class="col-12">
            <x-section-card icon="fa-helmet-safety" color="icon-navy" title="Informasi Fieldwork" subtitle="Data pekerjaan lapangan estimasi">
                <div class="row g-3">
                    <div class="col-md-12 col-12">
                        <label class="form-label">Judul Pekerjaan</label>
                        <input type="text" name="judul_pekerjaan" class="form-control">
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label required">Hari Ke-</label>
                        <input type="number" min="1" name="hari_ke" class="form-control" value="1" required>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Durasi (hari)</label>
                        <input type="number" min="1" name="durasi_hari" class="form-control" value="1">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label">Site Pekerjaan</label>
                        <select name="id_site_pelanggan_pekerjaan" class="form-select">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label">PIC Pekerjaan</label>
                        <select name="id_pic_pelanggan_pekerjaan" class="form-select">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ url('sq-fieldworks') }}" submit-label="Simpan SQ Fieldwork" />
    </form>
</section>
@endsection

@section('custom-script')
<script>
    var preselectSqWoId = new URLSearchParams(window.location.search).get('id_sq_wo');
    var sqWoData = null;

    $(document).ready(function () {
        if (preselectSqWoId) {
            $('#id_sq_wo').val(preselectSqWoId);
            $.get("{{ url('sq-work-orders') }}/" + preselectSqWoId, function (wo) {
                sqWoData = wo;
                $('#sqWoBannerNoSqWo').text(wo.no_sq_wo ?? '—');
                $('#sqWoBannerJudul').text(wo.judul_pekerjaan ?? '—');
                $('#sqWoInfoBanner').show();

                // Auto-fill dari WO: Judul Pekerjaan & Site Pekerjaan
                if (wo.judul_pekerjaan && !$('input[name="judul_pekerjaan"]').val()) {
                    $('input[name="judul_pekerjaan"]').val(wo.judul_pekerjaan);
                }
                if (wo.id_site_pelanggan_pekerjaan && !$('select[name="id_site_pelanggan_pekerjaan"]').val()) {
                    $('select[name="id_site_pelanggan_pekerjaan"]')
                        .append(new Option(wo.nama_site_pelanggan_pekerjaan || ('#' + wo.id_site_pelanggan_pekerjaan), wo.id_site_pelanggan_pekerjaan, true, true))
                        .trigger('change');
                }
                $('input[name="hari_ke"]').val(wo.hari_mulai || 1);
            });
        }

        $('select[name="id_site_pelanggan_pekerjaan"]').select2({
            width: '100%', placeholder: 'Pilih Site', allowClear: true, minimumInputLength: 0,
            ajax: {
                url: "{{ url('business-relations/sites/select2') }}",
                dataType: 'json', delay: 250,
                data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: false,
            },
            escapeMarkup: (m) => m,
        });

        $('select[name="id_pic_pelanggan_pekerjaan"]').select2({
            width: '100%', placeholder: 'Pilih PIC', allowClear: true, minimumInputLength: 0,
            ajax: {
                url: "{{ route('business-relation-contacts.select2') }}",
                dataType: 'json', delay: 250,
                data: (p) => ({ q: p.term, with_site: 1, id_site: $('select[name="id_site_pelanggan_pekerjaan"]').val() || '' }), processResults: (d) => ({ results: d }), cache: false,
            },
            language: {
                noResults: () => '<span>Tidak ditemukan. <a href="/business-relation-contacts/create" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>',
            },
            escapeMarkup: (m) => m,
        });
    });

    // Ganti Site → PIC lama belum tentu milik Site baru
    $(document).on('change', 'select[name="id_site_pelanggan_pekerjaan"]', function () {
        $('select[name="id_pic_pelanggan_pekerjaan"]').val(null).trigger('change');
    });

    submitCreateForm({
        formId: '#sqFieldworkForm',
        url: "{{ url('sq-fieldworks') }}",
        redirect: preselectSqWoId ? null : "{{ url('sq-fieldworks') }}",
        onSuccess: preselectSqWoId ? function (res) {
            localStorage.setItem('sq_fwo_created', JSON.stringify({ id_sq_wo: preselectSqWoId, id_sq_fwo: res.id, ts: Date.now() }));
            var inIframe = window.self !== window.top;
            if (!inIframe) {
                if (window.opener && !window.opener.closed) window.opener.focus();
                setTimeout(function () { window.close(); }, 800);
            }
        } : null,
    });
</script>
@endsection
