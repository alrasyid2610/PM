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
                data: (p) => ({ q: p.term, with_site: 1 }), processResults: (d) => ({ results: d }), cache: false,
            },
            escapeMarkup: (m) => m,
        });
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
