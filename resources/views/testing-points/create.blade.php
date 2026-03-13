@extends('layouts.app')

@section('content')
<section class="section">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Tambah Testing Point</h4>
                <p class="text-muted mb-0">
                    Tambahkan data testing point baru.
                </p>
            </div>

            <a href="{{ route('testing-points.index') }}"
               class="btn btn-secondary btn-sm">
                Kembali
            </a>
        </div>

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
                        <input type="text"
                               class="form-control"
                               name="attachment"
                               placeholder="Nama file / path file">
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

    Notify.confirm('Simpan Data?', function() {

        $.ajax({
            url: "{{ route('testing-points.store') }}",
            method: "POST",
            data: $('#testingPointForm').serialize(),

            success: function(response) {
                Notify.success('Testing point berhasil disimpan');
                // Optional redirect
                // window.location.href = "{{ route('testing-points.index') }}";
            },

            error: function(xhr) {
                Notify.error('Gagal menyimpan testing point');
            }
        });

    });

});
</script>
@endsection