@extends('layouts.app')
@section('content')
<section class="section">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Tambah Testing Parameter</h4>
                <p class="text-muted mb-0">Tambahkan data testing parameter baru.</p>
            </div>
            <a href="{{ route('testing-parameters.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
        <form id="testingParameterForm" enctype="multipart/form-data">
            @csrf
            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="kelompok" class="form-label">Kelompok</label>
                        <select name="kelompok" id="kelompok" class="form-select select2">
                            <option value="Fisika">Fisika</option>
                            <option value="Kimia Logam">Kimia Logam</option>
                            <option value="Kimia Non Logam">Kimia Non Logam</option>
                            <option value="Kimia Organik">Kimia Organik</option>
                            <option value="Mikrobiologi">Mikrobiologi</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="kode" class="form-label required">Kode</label>
                        <input type="text" class="form-control scientific-input" id="kode" name="kode" required>
                    </div>
                    <div class="mb-3">
                        <label for="judul_indonesia" class="form-label required">Judul Indonesia</label>
                        <input type="text" class="form-control" id="judul_indonesia" name="judul_indonesia" required>
                    </div>
                    <div class="mb-3">
                        <label for="judul_inggris" class="form-label">Judul Inggris</label>
                        <input type="text" class="form-control" id="judul_inggris" name="judul_inggris">
                    </div>
                    <div class="mb-3">
                        <label for="rumus_empiris" class="form-label">Rumus Empiris</label>
                        <input type="text" class="form-control" id="rumus_empiris" name="rumus_empiris">
                    </div>
                    <div class="mb-3">
                        <label for="judul_iupac" class="form-label">Judul IUPAC</label>
                        <input type="text" class="form-control" id="judul_iupac" name="judul_iupac">
                    </div>
                    <div class="mb-3">
                        <label for="referensi" class="form-label">Referensi</label>
                        <input type="text" class="form-control" id="referensi" name="referensi">
                    </div>
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan"></textarea>
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
                <button type="submit" class="btn btn-primary">Simpan Testing Parameter</button>
                <a href="{{ route('testing-parameters.index') }}" class="btn btn-secondary btn-sm">Batal</a>
            </div>
        </form>
    </div>
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