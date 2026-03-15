@extends('layouts.app')

@section('content')
<section class="section">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Tambah Testing Standard</h4>
                <p class="text-muted mb-0">
                    Tambahkan data testing standard baru.
                </p>
            </div>

            <a href="{{ route('testing-standards.index') }}"
                class="btn btn-secondary btn-sm">
                Kembali
            </a>
        </div>

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