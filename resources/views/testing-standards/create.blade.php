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
    <div class="container-fluid">
        <form id="testingStandardForm" enctype="multipart/form-data">
            @csrf

            <div class="card mb-4">
                <div class="card-body">

                    <div class="mb-3">
                        <label for="nomor" class="form-label required">Nomor</label>
                        <input
                            type="text"
                            class="form-control"
                            id="nomor"
                            name="nomor"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="judul" class="form-label required">Judul</label>
                        <input
                            type="text"
                            class="form-control"
                            id="judul"
                            name="judul"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="is_aktif" class="form-label required">Status</label>
                        <select
                            class="form-select"
                            id="is_aktif"
                            name="is_aktif"
                            required>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Attachment</label>

                        <input
                            type="file"
                            class="filepond"
                            name="attachments[]"
                            multiple>
                    </div>

                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                    Simpan Testing Standard
                </button>

                <a href="{{ route('testing-standards.index') }}"
                    class="btn btn-secondary btn-sm">
                    Batal
                </a>
            </div>

        </form>

    </div>
</section>
@endsection
@section('custom-script')
<script>
    $(document).ready(function() {

        FilePond.create(document.querySelector('.filepond'), {

            allowMultiple: true,

            acceptedFileTypes: [
                'image/*',
                'application/pdf',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ],

            labelIdle: 'Drag & Drop file atau <span class="filepond--label-action">Browse</span>'

        });

    });


    $('#testingStandardForm').submit(function(e) {

        e.preventDefault();

        Notify.confirm('Simpan Data?', function() {

            let form = document.getElementById('testingStandardForm');
            let formData = new FormData(form);
            FilePond
                .find(document.querySelector('.filepond'))
                .getFiles()
                .forEach(fileItem => {

                    formData.append('attachments[]', fileItem.file);

                });

            $.ajax({
                url: "{{ route('testing-standards.store') }}",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Notify.success('Testing standard berhasil disimpan');
                },
                error: function() {
                    Notify.error('Gagal menyimpan testing standard');
                }

            });

        });

    });
</script>
@endsection