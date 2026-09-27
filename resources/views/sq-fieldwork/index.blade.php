@extends('layouts.app')

@section('page-title', 'SQ Fieldworks')
@section('page-descrip', 'Kelola Fieldwork estimasi Sales Quotation')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">SQ Fieldworks</li>
@endsection

@section('style')
<style>
    /* SQ berstatus Final/Completed/Cancel — sembunyikan semua aksi ubah */
    .sq-locked .btn-kelola-sq-fwo-boq, .sq-locked .btn-sq-fwo-budget-add,
    .sq-locked .btn-edit-sq-fwo-budget, .sq-locked .btn-remove-sq-fwo-budget { display: none !important; }
</style>
@endsection

@section('content')
<x-crud-index
    title="List of SQ Fieldworks"
    :with-history="true"
    :search-fields="[
        ['label' => 'No. FWO',          'value' => 'no_sq_fwo'],
        ['label' => 'No. WO',           'value' => 'no_sq_wo'],
        ['label' => 'No. SQ',           'value' => 'no_sq'],
        ['label' => 'Judul Pekerjaan',  'value' => 'judul'],
    ]"
/>

{{-- Modal iframe: Create SQ Fieldwork (pola sama dengan "+ WO" di SQ) --}}
<div class="modal fade" id="modalCreateSqFwo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="height:90vh;">
            <div class="modal-header py-2 px-3" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <span class="fw-semibold" style="font-size:14px;">
                    <i class="fa-solid fa-helmet-safety me-2" style="color:#1a56db;"></i>
                    Tambah SQ Fieldwork
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    onclick="document.getElementById('iframeCreateSqFwo').src=''"></button>
            </div>
            <div class="modal-body p-0" style="overflow:hidden;position:relative;">
                <div id="loaderCreateSqFwo" class="iframe-loading-overlay">
                    <i class="fa-solid fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
                    <span>Memuat form...</span>
                </div>
                <iframe id="iframeCreateSqFwo" src="" frameborder="0"
                    style="width:100%;height:100%;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('sq-fieldworks.data') }}",
        history: "{{ url('sq-fieldworks') }}/",
        csrf: "{{ csrf_token() }}",
        update: "{{ url('sq-fieldworks') }}/",
    }
</script>
<script src="{{ asset('assets/js/sq-fieldwork/index.js') }}?v={{ @filemtime(public_path('assets/js/sq-fieldwork/index.js')) }}"></script>
<script src="{{ asset('assets/js/sq-fieldwork/form.js') }}?v={{ @filemtime(public_path('assets/js/sq-fieldwork/form.js')) }}"></script>
@endsection
