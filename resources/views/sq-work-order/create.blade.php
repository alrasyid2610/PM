@extends(request('embed') ? 'layouts.embed' : 'layouts.app')

@section('page-title', 'SQ Work Orders')
@section('page-descrip', 'Tambahkan Work Order estimasi')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('sq-work-orders') }}">SQ Work Orders</a></li>
    <li class="breadcrumb-item active" aria-current="page">Tambah</li>
@endsection

@section('content')

{{-- STICKY SQ INFO BANNER --}}
<div id="sqInfoBanner" style="display:none;position:sticky;top:0;z-index:100;background:#fff;border-bottom:2px solid #e2e8f0;padding:10px 16px;margin:-8px -12px 16px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
    <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:13px;">
        <div style="display:flex;align-items:center;gap:6px;min-width:0;">
            <i class="fa-solid fa-file-signature" style="color:#1a56db;font-size:11px;flex-shrink:0;"></i>
            <span id="sqBannerNoSq" style="font-weight:700;color:#1a56db;white-space:nowrap;"></span>
        </div>
        <div style="width:1px;height:16px;background:#e2e8f0;flex-shrink:0;"></div>
        <div style="display:flex;align-items:center;gap:6px;min-width:0;flex:1;">
            <i class="fa-solid fa-file-lines" style="color:#374151;font-size:11px;flex-shrink:0;"></i>
            <span id="sqBannerJudul" style="color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
        </div>
    </div>
</div>

<section class="section">
    <form id="sqWorkOrderForm" class="row g-3">
        @csrf
        <input type="hidden" name="id_sq" id="id_sq">

        <div class="col-12">
            <x-section-card icon="fa-briefcase" color="icon-navy" title="Informasi Work Order" subtitle="Data pekerjaan estimasi">
                <div class="row g-3">
                    <div class="col-md-12 col-12">
                        <label class="form-label">Judul Pekerjaan</label>
                        <input type="text" name="judul_pekerjaan" class="form-control">
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label required">Hari Mulai (ke-)</label>
                        <input type="number" min="1" name="hari_mulai" class="form-control" value="1" required>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Durasi (hari)</label>
                        <input type="number" min="1" name="durasi_hari" class="form-control">
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Frekuensi</label>
                        <select name="interval_bulan" id="interval_bulan" class="form-select">
                            <option value="">— Tidak ada —</option>
                            <option value="1">Bulanan</option>
                            <option value="2">Bimulanan</option>
                            <option value="3">Triwulan</option>
                            <option value="4">Caturwulan</option>
                            <option value="6">Semester</option>
                            <option value="12">Annual</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12" id="noUrutWrap" style="display:none;">
                        <label class="form-label required">Urutan ke-</label>
                        <input type="number" name="no_urut_period" id="no_urut_period" class="form-control" min="1" placeholder="Auto">
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

        <x-form-actions back-route="{{ url('sq-work-orders') }}" submit-label="Simpan SQ Work Order" />
    </form>
</section>
@endsection

@section('custom-script')
<script>
    var preselectSqId = new URLSearchParams(window.location.search).get('id_sq');

    $(document).ready(function () {
        if (preselectSqId) {
            $('#id_sq').val(preselectSqId);
            $.get("{{ url('sales-quotations') }}/" + preselectSqId, function (sq) {
                $('#sqBannerNoSq').text((sq.no_sq ?? '—') + (sq.revisi > 0 ? ' Rev.' + sq.revisi : ''));
                $('#sqBannerJudul').text(sq.judul_order ?? '—');
                $('#sqInfoBanner').show();

                // Auto-fill dari SQ: Judul Pekerjaan = Judul Order, Site = Site Pemesan
                if (sq.judul_order && !$('input[name="judul_pekerjaan"]').val()) {
                    $('input[name="judul_pekerjaan"]').val(sq.judul_order);
                }
                if (sq.id_site_pelanggan && !$('select[name="id_site_pelanggan_pekerjaan"]').val()) {
                    $('select[name="id_site_pelanggan_pekerjaan"]')
                        .append(new Option(sq.nama_site_pelanggan || ('#' + sq.id_site_pelanggan), sq.id_site_pelanggan, true, true))
                        .trigger('change');
                }
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

    // Urutan ke- otomatis = jumlah WO SQ ini dgn frekuensi sama + 1 (pola sama Create WO)
    var sqWoExisting = [];
    if (preselectSqId) {
        $.get("{{ url('sq-work-orders') }}/" + preselectSqId + "/list", function (rows) { sqWoExisting = rows || []; });
    }
    $(document).on('change', '#interval_bulan', function () {
        var interval = $(this).val();
        $('#noUrutWrap').toggle(!!interval);
        if (!interval) { $('#no_urut_period').val(''); return; }
        var count = sqWoExisting.filter(function (w) { return String(w.interval_bulan) === String(interval); }).length;
        $('#no_urut_period').val(count + 1);
    });

    submitCreateForm({
        formId: '#sqWorkOrderForm',
        url: "{{ url('sq-work-orders') }}",
        redirect: preselectSqId ? null : "{{ url('sq-work-orders') }}",
        onSuccess: preselectSqId ? function (res) {
            localStorage.setItem('sq_wo_created', JSON.stringify({ id_sq: preselectSqId, id_sq_wo: res.id, ts: Date.now() }));
            var inIframe = window.self !== window.top;
            if (!inIframe) {
                if (window.opener && !window.opener.closed) window.opener.focus();
                setTimeout(function () { window.close(); }, 800);
            }
        } : null,
    });
</script>
@endsection
