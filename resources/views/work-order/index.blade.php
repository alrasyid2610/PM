@extends('layouts.app')
@section('content')
<x-crud-index
    title="List of Work Orders"
    :with-history="true"
    :with-selesai-filter="true"
    :search-fields="[
        ['label' => 'No. WO',          'value' => 'no_wo'],
        ['label' => 'No. SO',          'value' => 'no_so'],
        ['label' => 'Judul Pekerjaan', 'value' => 'judul'],
        ['label' => 'Pelanggan',       'value' => 'pelanggan'],
        ['label' => 'Status',          'value' => 'status', 'type' => 'select', 'options' => [
            ['label' => 'All',         'value' => 'all'],
            ['label' => 'On Progress', 'value' => 'on-progress'],
            ['label' => 'Completed',   'value' => 'completed'],
            ['label' => 'Deleted',     'value' => 'deleted'],
        ]],
    ]"
/>

{{-- Modal: Create FWO --}}
<div class="modal fade" id="modalCreateFwo" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="fa-solid fa-hard-hat me-2 text-primary"></i> Tambah Fieldwork Order</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    onclick="document.getElementById('iframeCreateFwo').src=''"></button>
            </div>
            <div class="modal-body p-0" style="min-height:500px;">
                <iframe id="iframeCreateFwo" src="" style="width:100%;height:75vh;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Create BOQ --}}
<div class="modal fade" id="modalCreateBoq" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="fa-solid fa-layer-group me-2 text-success"></i> Tambah BOQ</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    onclick="document.getElementById('iframeCreateBoq').src=''"></button>
            </div>
            <div class="modal-body p-0" style="min-height:500px;">
                <iframe id="iframeCreateBoq" src="" style="width:100%;height:75vh;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Output Pekerjaan --}}
<div class="modal fade" id="modalOutputForm" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="modalOutputFormTitle">
                    <i class="fa-solid fa-file-circle-check me-2 text-teal"></i> Tambah Output
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalOutputFormBody"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnSaveOutput" class="btn btn-sm btn-primary" data-no-disable>
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Copy WO --}}
<div class="modal fade" id="modalCopyWo" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-copy me-2 text-primary"></i> Salin Work Order
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalCopyWoBody">
                <div class="text-center py-4">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data WO...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnConfirmCopyWo" class="btn btn-primary btn-sm" disabled>
                    <i class="fa-solid fa-copy me-1"></i> Buat Salinan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Copy FWO --}}
<div class="modal fade" id="modalCopyFwo" tabindex="-1" aria-labelledby="modalCopyFwoLabel">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <h5 class="modal-title" id="modalCopyFwoLabel">
                    <i class="fa-solid fa-copy me-2 text-primary"></i> Salin Fieldwork Order
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalCopyFwoBody">
                <div class="text-center py-4">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data FWO...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnConfirmCopyFwo" class="btn btn-primary btn-sm" disabled>
                    <i class="fa-solid fa-copy me-1"></i> Buat Salinan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection


@section('custom-script')
<script>
    window.route = {
        data:          "{{ route('work-orders.data') }}",
        history:       "{{ url('work-orders') }}/",
        csrf:          "{{ csrf_token() }}",
        update:        "{{ url('work-orders') }}/",
        boqProgress:   "{{ url('work-orders') }}/",
        woDuplicate:   "{{ url('work-orders') }}/",
        woDetail:      "{{ url('work-orders') }}/",
        fwoDuplicate:  "{{ url('fieldworks') }}/",
        fwoDetail:     "{{ url('fieldworks') }}/",
        fwoBoqDetail:  "{{ url('fieldwork-boq/by-fwo') }}/",
        fwoBoqForCopy: "{{ url('fieldwork-boq/for-copy') }}/",
        usersSelect2:  "{{ route('users.select2') }}",
        tpSelect2:     "{{ route('testing-points.select2') }}",
        outputBase:    "{{ url('output-pekerjaan') }}",
    }

    window.datatableColumnRenderers = {
        status: function (data) {
            var map = {
                'on-progress': { bg: '#eff6ff', color: '#1d4ed8', border: '#bfdbfe', icon: 'fa-spinner',      label: 'On Progress' },
                completed:     { bg: '#f0fdf4', color: '#15803d', border: '#bbf7d0', icon: 'fa-circle-check', label: 'Completed' },
                deleted:    { bg: '#fef2f2', color: '#b91c1c', border: '#fecaca', icon: 'fa-trash',        label: 'Deleted' },
            };
            var s = map[data] || { bg: '#f1f5f9', color: '#475569', border: '#e2e8f0', icon: 'fa-circle', label: data || '-' };
            return '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:6px;background:' + s.bg + ';color:' + s.color + ';font-size:11px;font-weight:600;border:1px solid ' + s.border + ';white-space:nowrap;">'
                + '<i class="fa-solid ' + s.icon + '" style="font-size:10px;"></i> ' + s.label + '</span>';
        },
    };
</script>
<script src="{{ asset('assets/js/work-order/index.js') }}"></script>
<script src="{{ asset('assets/js/work-order/form.js') }}"></script>
@endsection