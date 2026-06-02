@extends('layouts.app')

@section('page-title', 'Sales Orders')
@section('page-descrip', 'Kelola data Sales Orders')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Sales Orders</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M28 8h4v28l-16 28h48L48 36V8h4" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M28 8h24" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <circle cx="32" cy="56" r="3" fill="white"/>
        <circle cx="44" cy="62" r="2" fill="white"/>
        <circle cx="38" cy="52" r="2" fill="white"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Sales Orders"
    create-route="sales-orders.create"
    :with-history="true"
/>

{{-- Modal iframe: Create Termin --}}
<div class="modal fade" id="modalCreateTermin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content" style="height:90vh;">
            <div class="modal-header py-2 px-3" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <span class="fw-semibold" style="font-size:14px;">
                    <i class="fa-solid fa-file-invoice-dollar me-2" style="color:#7c3aed;"></i>
                    Tambah Termin
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="overflow:hidden;">
                <iframe id="iframeCreateTermin" src="" frameborder="0"
                    style="width:100%;height:100%;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- Modal iframe: Create WO --}}
<div class="modal fade" id="modalCreateWo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content" style="height:90vh;">
            <div class="modal-header py-2 px-3" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <span class="fw-semibold" style="font-size:14px;">
                    <i class="fa-solid fa-briefcase me-2" style="color:#1a56db;"></i>
                    Tambah Work Order
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="overflow:hidden;">
                <iframe id="iframeCreateWo" src="" frameborder="0"
                    style="width:100%;height:100%;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@section('custom-script')
<script>
    window.route = {
        data:       "{{ route('sales-orders.data') }}",
        history:    "{{ url('sales-orders') }}/",
        csrf:       "{{ csrf_token() }}",
        update:     "{{ url('sales-orders') }}/",
        woProgress:   "{{ url('sales-orders') }}/",
        woDuplicate:  "{{ url('work-orders') }}/",
        terminBySo:   "{{ url('termin/by-so') }}/",
        siteSelect2:  "{{ url('business-relations/sites/select2') }}",
    }
</script>
<script src="{{ asset('assets/js/sales-order/index.js') }}"></script>
<script src="{{ asset('assets/js/sales-order/form.js') }}"></script>
@endsection