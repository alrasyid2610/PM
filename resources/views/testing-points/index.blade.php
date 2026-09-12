@extends('layouts.app')

@section('page-title', 'Testing Points')
@section('page-descrip', 'Kelola data Testing Points pengujian laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Testing Points</li>
    {{-- <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('testing-units.index') }}">Testing Units</a>
    </li> --}}
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
<x-crud-index title="List of Testing Points" create-route="testing-points.create" :with-history="true" />

{{-- Modal: Salin Testing Point --}}
<div class="modal fade" id="modalCloneTestingPoint" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title"><i class="fa-solid fa-copy me-2 text-primary"></i> Salin Testing Point</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalCloneTpBody">
                <div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data...</div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnConfirmCloneTp" class="btn btn-primary btn-sm" disabled>
                    <i class="fa-solid fa-copy me-1"></i> Buat Salinan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('custom-script')
{{-- Dragula: drag-and-drop reorder baris Testing Items (lihat tableForm.js) --}}
<link href="{{ asset('assets/vendor/dragula/dragula.min.css') }}" rel="stylesheet">
<script src="{{ asset('assets/vendor/dragula/dragula.min.js') }}"></script>

{{-- Catatan: #row-template yang benar-benar dipakai di-render inline oleh
     renderForm() di testing-points/form.js (di dalam #detailContent, dimuat
     lewat AJAX). Template statis yang dulu ada di sini sudah tidak
     dipakai/basi (duplikat id="row-template" & sudah ketinggalan struktur —
     tidak ada kolom drag handle/hidden status[]) — dihapus 2026-09-09 supaya
     tidak jadi duplikat ID yang membingungkan di kemudian hari. --}}
<script>
    window.route = {
        data: "{{ route('testing-points.data') }}",
        update: "{{ url('testing-points') }}/",
        deleteAttachment: "{{ route('testing-points.delete-attachment') }}",
        history: "{{ url('testing-points') }}/",
        csrf: "{{ csrf_token() }}",

        // Clone Testing Point
        itemsByPoint:     "{{ url('testing-items/by-point') }}/",
        select2Standard:  "{{ route('testing-standards.select2') }}",
        createStandard:   "{{ route('testing-standards.create') }}",
        select2Matriks:   "{{ route('testing-matriks-samples.select2') }}",
        createMatriks:    "{{ route('testing-matriks-samples.create') }}",
        select2Parameter: "{{ route('testing-parameters.select2') }}",
        createParameter:  "{{ route('testing-parameters.create') }}",
        select2Unit:      "{{ route('testing-units.select2') }}",
        createUnit:       "{{ route('testing-units.create') }}",
    }
</script>

<script src="{{ asset('assets/js/testing-points/form.js') }}"></script>
<script src="{{ asset('assets/js/testing-points/index.js') }}"></script>
@endsection