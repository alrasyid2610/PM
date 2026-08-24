@extends('layouts.app')

@section('page-title', 'Create Documentation')
@section('page-descrip', 'Tulis dokumentasi/panduan modul baru')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">
        <a href="{{ route('documentations.index') }}">Dokumentasi</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 12h30l10 10v46H20V12z" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M50 12v10h10" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M28 38h24M28 48h24M28 58h16" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="documentationForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-book" color="icon-navy" title="Informasi Dokumentasi" subtitle="Judul, modul terkait, dan tag">
                <div class="row g-3">
                    <div class="col-md-8 col-12">
                        <label for="judul" class="form-label required">Judul</label>
                        <input type="text" class="form-control" id="judul" name="judul" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="modul" class="form-label required">Modul</label>
                        <select name="modul" id="modul" class="form-select select2" required>
                            <option value=""></option>
                            @foreach($moduleOptions as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label for="tags" class="form-label">Tags</label>
                        <select name="tags[]" id="tags" class="form-select" multiple></select>
                        <small class="text-muted">Ketik untuk buat tag baru, atau pilih dari yang sudah ada</small>
                    </div>
                    <div class="col-md-12">
                        <label for="ringkasan" class="form-label">Ringkasan</label>
                        <input type="text" class="form-control" id="ringkasan" name="ringkasan" placeholder="Ringkasan singkat untuk daftar (opsional)">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="is_published" name="is_published">
                            <option value="1" selected>Published</option>
                            <option value="0">Draft</option>
                        </select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <div class="col-12">
            <x-section-card icon="fa-pen-to-square" color="icon-green" title="Konten" subtitle="Isi dokumentasi lengkap">
                <div id="documentation-editor" style="height:420px;background:#fff;"></div>
                <textarea name="konten" id="konten-hidden" style="display:none;"></textarea>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('documentations.index') }}" submit-label="Simpan Dokumentasi" />

    </form>
</section>
@endsection

@section('style')
<link href="{{ asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
@endsection

@section('custom-script')
<script src="{{ asset('assets/vendor/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/js/documentation/form.js') }}"></script>
<script>
    window.route = {
        store: "{{ route('documentations.store') }}",
        uploadImage: "{{ route('documentations.upload-image') }}",
        tagSelect2: "{{ route('documentations.tags.select2') }}",
        csrf: "{{ csrf_token() }}",
    };

    $(document).ready(function () {
        $('#modul').select2({ placeholder: 'Pilih Modul', width: '100%' });

        $('#tags').select2({
            width: '100%',
            tags: true,
            placeholder: 'Pilih atau ketik tag baru',
            ajax: {
                url: window.route.tagSelect2,
                delay: 200,
                dataType: 'json',
                data: (p) => ({ q: p.term ?? '' }),
                processResults: (d) => ({ results: d }),
            },
        });
    });

    const quill = initDocumentationEditor('#documentation-editor');

    $('#documentationForm').on('submit', function () {
        $('#konten-hidden').val(quill.root.innerHTML);
    });

    submitCreateForm({
        formId: "#documentationForm",
        url: "{{ route('documentations.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('documentations.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
