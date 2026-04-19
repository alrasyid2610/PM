@extends('layouts.app')

@section('page-title', 'Create Testing Parameters')
@section('page-descrip', 'Kelola data Parameters laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">
        <a href="{{ route('testing-parameters.index') }}">Testing Parameters</a>
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
    <form id="testingParameterForm" enctype="multipart/form-data">
        @csrf

        <!-- SECTION 1: INFORMASI PARAMETER -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-amber">
                    <i class="fa-solid fa-flask"></i>
                </div>
                <div class="detail-section-title">Informasi Parameter</div>
                <div class="detail-section-sub">Data parameter pengujian laboratorium</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">
                    <div class="col-md-4 col-12">
                        <label for="kelompok" class="form-label">Kelompok</label>
                        <select name="kelompok" id="kelompok" class="form-select select2">
                            <option value="Fisika">Fisika</option>
                            <option value="Kimia Logam">Kimia Logam</option>
                            <option value="Kimia Non Logam">Kimia Non Logam</option>
                            <option value="Kimia Organik">Kimia Organik</option>
                            <option value="Mikrobiologi">Mikrobiologi</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="kode" class="form-label required">Kode</label>
                        <input type="text" class="form-control scientific-input" id="kode" name="kode" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="judul_indonesia" class="form-label required">Judul Indonesia</label>
                        <input type="text" class="form-control" id="judul_indonesia" name="judul_indonesia" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="judul_inggris" class="form-label">Judul Inggris</label>
                        <input type="text" class="form-control" id="judul_inggris" name="judul_inggris">
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="rumus_empiris" class="form-label">Rumus Empiris</label>
                        <input type="text" class="form-control" id="rumus_empiris" name="rumus_empiris">
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="judul_iupac" class="form-label">Judul IUPAC</label>
                        <input type="text" class="form-control" id="judul_iupac" name="judul_iupac">
                    </div>
                    <div class="col-md-12">
                        <label for="referensi" class="form-label">Referensi</label>
                        <input type="text" class="form-control" id="referensi" name="referensi">
                    </div>
                    <div class="col-md-12">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: ATTACHMENT -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-blue">
                    <i class="fa-solid fa-paperclip"></i>
                </div>
                <div class="detail-section-title">Attachment</div>
                <div class="detail-section-sub">File pendukung parameter</div>
            </div>
            <div class="detail-section-body">
                <input type="file" class="filepond" name="attachments[]" multiple>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('testing-parameters.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Testing Parameter
            </button>
        </div>

    </form>
</section>

@endsection
@section('custom-script')
<script>
    $(document).ready(function() {
        $('.select2').select2()

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

    $('#testingParameterForm').submit(function(e) {
        e.preventDefault();
        Notify.confirm('Simpan Data?', function() {

            let form = document.getElementById('testingParameterForm');
            let formData = new FormData(form);
            FilePond.find(document.querySelector('.filepond'))
                .getFiles()
                .forEach(fileItem => {
                    formData.append('attachments[]', fileItem.file);
                });

            $.ajax({
                url: "{{ route('testing-parameters.store') }}",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Notify.success('Testing parameter berhasil disimpan');
                    // window.location.href = "{{ route('testing-parameters.index') }}";
                },
                error: function(xhr) {
                    Notify.error('Gagal menyimpan testing parameter');
                }
            });
        });
    });
</script>
@endsection
