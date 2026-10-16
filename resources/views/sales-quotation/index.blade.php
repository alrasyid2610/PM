@extends('layouts.app')

@section('page-title', 'Sales Quotations')
@section('page-descrip', 'Kelola data Sales Quotation (penawaran)')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Sales Quotations</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 10h28l12 12v48a2 2 0 0 1-2 2H20a2 2 0 0 1-2-2V12a2 2 0 0 1 2-2z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M48 10v12h12" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M26 40h28M26 48h28M26 56h18" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Sales Quotations"
    create-route="sales-quotations.create"
    :with-history="true"
    :search-fields="[
        ['label' => 'No. SQ',          'value' => 'no_sq'],
        ['label' => 'Judul Order',     'value' => 'judul'],
        ['label' => 'Pelanggan',       'value' => 'pelanggan'],
        ['label' => 'Status',          'value' => 'status', 'type' => 'select', 'options' => [
            ['label' => 'All',        'value' => 'all'],
            ['label' => 'Draft',      'value' => 'draft'],
            ['label' => 'Final',      'value' => 'final'],
            ['label' => 'Win',        'value' => 'win'],
            ['label' => 'Lose',       'value' => 'lose'],
            ['label' => 'Void',       'value' => 'void'],
            ['label' => 'Deleted',    'value' => 'deleted'],
        ]],
    ]"
/>

{{-- Modal iframe: Create SQ Work Order (pola sama dengan "+ WO" di Sales Order) --}}
<div class="modal fade" id="modalCreateSqWo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content" style="height:90vh;">
            <div class="modal-header py-2 px-3" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <span class="fw-semibold" style="font-size:14px;">
                    <i class="fa-solid fa-briefcase me-2" style="color:#1a56db;"></i>
                    Tambah SQ Work Order
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    onclick="document.getElementById('iframeCreateSqWo').src=''"></button>
            </div>
            <div class="modal-body p-0" style="overflow:hidden;position:relative;">
                <div id="loaderCreateSqWo" class="iframe-loading-overlay">
                    <i class="fa-solid fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
                    <span>Memuat form...</span>
                </div>
                <iframe id="iframeCreateSqWo" src="" frameborder="0"
                    style="width:100%;height:100%;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- Modal Bandingkan SQ --}}
<style>
    #modalCompareSq .cmp-card { background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;flex:1 1 200px;min-width:200px; }
    #modalCompareSq .cmp-card .lbl { font-size:11px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:#64748b; }
    #modalCompareSq .cmp-card .val { font-size:20px;font-weight:700;color:#0f172a;margin-top:2px; }
    #modalCompareSq .cmp-card .sub { font-size:11px;color:#94a3b8; }
    #modalCompareSq .cmp-pill { display:inline-block;font-size:11px;font-weight:700;padding:1px 8px;border-radius:20px; }
    #modalCompareSq .cmp-up { background:#f0fdf4;color:#15803d; }
    #modalCompareSq .cmp-down { background:#fef2f2;color:#b91c1c; }
    #modalCompareSq .cmp-flat { background:#f1f5f9;color:#64748b; }
    #modalCompareSq .cmp-st { display:inline-block;font-size:10px;font-weight:700;padding:1px 8px;border-radius:20px;white-space:nowrap; }
    #modalCompareSq .cmp-st.baru { background:#dcfce7;color:#15803d; }
    #modalCompareSq .cmp-st.dihapus { background:#fee2e2;color:#b91c1c; }
    #modalCompareSq .cmp-st.berubah { background:#fef9c3;color:#a16207; }
    #modalCompareSq .cmp-st.sama { background:#f1f5f9;color:#64748b; }
    #modalCompareSq .cmp-sec { margin-top:14px; }
    #modalCompareSq .cmp-sec-title { font-size:12px;font-weight:700;color:#334155;margin-bottom:4px; }
    #modalCompareSq .cmp-wo { border:1px solid #e2e8f0;border-radius:10px;margin-top:14px;overflow:hidden; }
    #modalCompareSq .cmp-wo-head { background:#f8fafc;padding:9px 14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;border-bottom:1px solid #e2e8f0; }
    #modalCompareSq .cmp-wo-body { padding:4px 14px 12px; }
    #modalCompareSq table.cmp-t { width:100%;font-size:12px;border-collapse:collapse; }
    #modalCompareSq table.cmp-t th { font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:#64748b;padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:left; }
    #modalCompareSq table.cmp-t td { padding:7px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top; }
    #modalCompareSq .r { text-align:right;white-space:nowrap; }
    #modalCompareSq .cmp-from { color:#94a3b8;text-decoration:line-through; }
    #modalCompareSq.cmp-hide-same .cmp-same { display:none !important; }
</style>
<div class="modal fade" id="modalCompareSq" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 px-3" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <span class="fw-semibold" style="font-size:14px;">
                    <i class="fa-solid fa-code-compare me-2" style="color:#6d28d9;"></i>
                    Bandingkan <span id="compareSqNo"></span>
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-8">
                        <label class="form-label mb-1" style="font-size:12px;">Bandingkan dengan (SQ pembanding)</label>
                        <select id="compareSqSelect" class="form-select"></select>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="compareSqShowSame">
                            <label class="form-check-label" for="compareSqShowSame" style="font-size:12px;">Tampilkan yang tidak berubah</label>
                        </div>
                        <button type="button" class="pm-btn-pill pm-btn-pill--blue" id="btnCompareSqPdf" disabled>
                            <i class="fa-solid fa-file-pdf" style="font-size:10px;"></i> Cetak PDF
                        </button>
                    </div>
                </div>
                <div id="compareSqResult"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Convert SQ → SO --}}
<div class="modal fade" id="modalConvertSq" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 px-3" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <span class="fw-semibold" style="font-size:14px;">
                    <i class="fa-solid fa-right-left me-2" style="color:#1a56db;"></i>
                    Terbitkan Sales Order dari <span id="convertSqNo"></span>
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="convertSqBody">
                <div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat...</div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="pm-btn-pill" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="pm-btn-pill pm-btn-pill--blue" id="btnConvertSqSubmit" disabled>
                    <i class="fa-solid fa-right-left" style="font-size:10px;"></i> Terbitkan SO
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:    "{{ route('sales-quotations.data') }}",
        history: "{{ url('sales-quotations') }}/",
        csrf:    "{{ csrf_token() }}",
        update:  "{{ url('sales-quotations') }}/",
    }
</script>
{{-- Dragula: drag-and-drop urutan poin di tab Keterangan (pola sama Testing Points) --}}
<link href="{{ asset('assets/vendor/dragula/dragula.min.css') }}" rel="stylesheet">
<script src="{{ asset('assets/vendor/dragula/dragula.min.js') }}"></script>
<script src="{{ asset('assets/js/sales-quotation/index.js') }}?v={{ @filemtime(public_path('assets/js/sales-quotation/index.js')) }}"></script>
<script src="{{ asset('assets/js/sales-quotation/form.js') }}?v={{ @filemtime(public_path('assets/js/sales-quotation/form.js')) }}"></script>
@endsection
