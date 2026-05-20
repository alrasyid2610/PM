@extends('layouts.app')
@section('content')
<x-crud-index
    title="List of Work Orders"
    :with-history="true"
/>

{{-- Modal: Copy FWO --}}
<div class="modal fade" id="modalCopyFwo" tabindex="-1" aria-labelledby="modalCopyFwoLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
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
        fwoDuplicate:  "{{ url('fieldworks') }}/",
        fwoDetail:     "{{ url('fieldworks') }}/",
        fwoBoqDetail:  "{{ url('fieldwork-boq/by-fwo') }}/",
        usersSelect2:  "{{ route('users.select2') }}",
    }
</script>
<script src="{{ asset('assets/js/work-order/index.js') }}"></script>
<script src="{{ asset('assets/js/work-order/form.js') }}"></script>
<script src="{{ asset('assets/js/work-order/period.js') }}"></script>
@endsection