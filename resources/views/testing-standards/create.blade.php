@extends('layouts.app')

@section('page-title', 'Testing Standards')
@section('page-descrip', 'Kelola data Standards pengujian laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('testing-standards.index') }}">Testing Standards</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
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
<section class="section">
    <form id="testingStandardForm" enctype="multipart/form-data" class="row g-3">
        @csrf

        <!-- SECTION 1: INFORMASI STANDARD -->
        <div class="col-12">
            <x-section-card icon="fa-book" color="icon-navy" title="Testing Standards" subtitle="Data standar pengujian laboratorium">
                <div class="row g-3">
                    <div class="col-md-4 col-12">
                        <label for="nomor" class="form-label required">Nomor</label>
                        <input type="text" class="form-control" id="nomor" name="nomor" required>
                    </div>
                    <div class="col-md-6 col-12">
                        <label for="judul" class="form-label required">Judul</label>
                        <input type="text" class="form-control" id="judul" name="judul" required>
                    </div>
                    <div class="col-md-2 col-12">
                        <label for="is_aktif" class="form-label required">Status</label>
                        <select class="form-select" id="is_aktif" name="is_aktif" required>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION 2: ATTACHMENT -->
        <div class="col-12">
            <x-section-card icon="fa-paperclip" color="icon-blue" title="Attachment" subtitle="File pendukung standard">
                <input type="file" class="filepond" name="attachments[]" multiple>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('testing-standards.index') }}" submit-label="Simpan Testing Standard" />

    </form>
</section>
@endsection
@section('custom-script')
<script>
    $(document).ready(function () {
        createFileUploader(".filepond");
        $('#is_aktif').select2({ placeholder: 'Pilih Status', width: '100%' });
    });

    submitCreateForm({
        formId: "#testingStandardForm",
        url: "{{ route('testing-standards.store') }}",
        redirect: "{{ route('testing-standards.index') }}",
        filepond: ".filepond",
    });
</script>
@endsection
