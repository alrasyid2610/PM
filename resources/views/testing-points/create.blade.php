@extends('layouts.app')

@section('page-title', 'Testing Points')
@section('page-descrip', 'Kelola data Testing Points pengujian laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('testing-points.index') }}">Testing Points</a>
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
        <form id="testingPointForm">
            @csrf

            <div class="card mb-4">
                <div class="card-body">

                    {{-- FK Testing Standard --}}
                    <div class="mb-3">
                        <label class="form-label required">Testing Standard</label>
                        <select id="id_testing_standard"
                            name="id_testing_standard"
                            class="form-select"
                            required></select>
                    </div>

                    {{-- FK Matriks Sample --}}
                    <div class="mb-3">
                        <label class="form-label required">Testing Matriks Sample</label>
                        <select id="id_testing_matriks_sample"
                            name="id_testing_matriks_sample"
                            class="form-select"
                            required></select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Nama</label>
                        <input type="text"
                            class="form-control"
                            name="nama"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control"
                            name="deskripsi"
                            rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nomor Halaman</label>
                        <input type="text"
                            class="form-control"
                            name="nomor_halaman">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Attachment</label>

                        <input
                            type="file"
                            class="filepond"
                            name="attachments[]"
                            multiple>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Status</label>
                        <select name="is_aktif"
                            class="form-select"
                            required>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control"
                            name="keterangan"
                            rows="3"></textarea>
                    </div>

                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                    Simpan Data
                </button>

                <a href="{{ route('testing-points.index') }}"
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

        // Select2 Testing Standard
        $('#id_testing_standard').select2({
            placeholder: 'Pilih Testing Standard...',
            ajax: {
                url: "{{ route('testing-standards.data') }}",
                dataType: 'json',
                processResults: function(data) {
                    return {
                        results: data.data.map(item => ({
                            id: item.id_testing_standard,
                            text: item.nomor + ' - ' + item.judul
                        }))
                    };
                }
            }
        });

        // Select2 Matriks Sample
        $('#id_testing_matriks_sample').select2({
            placeholder: 'Pilih Matriks Sample...',
            ajax: {
                url: "{{ route('testing-matriks-samples.data') }}",
                dataType: 'json',
                processResults: function(data) {
                    return {
                        results: data.data.map(item => ({
                            id: item.id_testing_matriks_sample,
                            text: item.kode + ' - ' + item.judul_indonesia
                        }))
                    };
                }
            }
        });

    });


    $('#testingPointForm').submit(function(e) {
        e.preventDefault();
        let form = document.getElementById('testingPointForm');
        let formData = new FormData(form);
        FilePond
            .find(document.querySelector('.filepond'))
            .getFiles()
            .forEach(fileItem => {

                formData.append('attachments[]', fileItem.file);

            });

        Notify.confirm('Simpan Data?', function() {

            $.ajax({
                url: "{{ route('testing-points.store') }}",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Notify.success('Testing point berhasil disimpan');
                },

                error: function(xhr) {
                    Notify.error('Gagal menyimpan testing point');
                }
            });

        });

    });
</script>
@endsection