@extends('layouts.app')

@section('page-title', 'Dokumentasi')
@section('page-descrip', 'Panduan & dokumentasi penggunaan aplikasi per modul')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Dokumentasi</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 12h30l10 10v46H20V12z" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M50 12v10h10" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M28 38h24M28 48h24M28 58h16" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Documentation"
    create-route="documentations.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('documentations.data') }}",
        update: "{{ url('documentations') }}/",
        history: "{{ url('documentations') }}/",
        uploadImage: "{{ route('documentations.upload-image') }}",
        tagSelect2: "{{ route('documentations.tags.select2') }}",
        csrf: "{{ csrf_token() }}"
    }

    window.documentationModuleOptions = @json($moduleOptions);

    window.datatableHeaderLabels = {
        pembuat: 'Dibuat Oleh',
        is_published: 'Status',
        created_at: 'Tanggal',
    };
</script>
<script src="{{ asset('assets/vendor/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/js/documentation/form.js') }}"></script>
<script src="{{ asset('assets/js/documentation/index.js') }}"></script>
@endsection

@section('style')
<link href="{{ asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
<style>
    /* Quill mengosongkan margin p/h1-h6/list/blockquote di dalam .ql-editor
       (quill.snow.css) karena di dalam editor spasi antar baris diatur lewat
       baris kosong yang diketik user sendiri. Saat konten ditampilkan
       read-only di #documentation-view (bukan instance editor aktif),
       perilaku itu bikin semua heading/paragraf menempel tanpa jarak — jadi
       spacing blog yang wajar perlu di-set ulang khusus untuk tampilan ini. */
    #documentation-view.ql-editor h1,
    #documentation-view.ql-editor h2,
    #documentation-view.ql-editor h3,
    #documentation-view.ql-editor h4,
    #documentation-view.ql-editor h5,
    #documentation-view.ql-editor h6 {
        margin: 1.5em 0 0.6em;
    }
    #documentation-view.ql-editor h1:first-child,
    #documentation-view.ql-editor h2:first-child,
    #documentation-view.ql-editor h3:first-child {
        margin-top: 0;
    }
    #documentation-view.ql-editor p,
    #documentation-view.ql-editor ol,
    #documentation-view.ql-editor ul,
    #documentation-view.ql-editor pre,
    #documentation-view.ql-editor blockquote {
        margin: 0 0 1em;
    }
    #documentation-view.ql-editor li {
        margin-bottom: 0.25em;
    }
</style>
@endsection
