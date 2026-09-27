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
            ['label' => 'Completed',  'value' => 'completed'],
            ['label' => 'Cancel',     'value' => 'cancel'],
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
<script src="{{ asset('assets/js/sales-quotation/index.js') }}?v={{ @filemtime(public_path('assets/js/sales-quotation/index.js')) }}"></script>
<script src="{{ asset('assets/js/sales-quotation/form.js') }}?v={{ @filemtime(public_path('assets/js/sales-quotation/form.js')) }}"></script>
@endsection
