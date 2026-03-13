@extends('layouts.app')
@section('content')
<section class="section">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Tambah Testing Unit</h4>
                <p class="text-muted mb-0">Tambahkan data testing unit baru.</p>
            </div>
            <a href="{{ route('testing-units.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
        <form id="testingUnitForm">
            @csrf
            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="kode" class="form-label required">Kode</label>
                        <input type="text" class="form-control" id="kode" name="kode" required>
                    </div>
                    <div class="mb-3">
                        <label for="judul_indonesia" class="form-label required">Judul Indonesia</label>
                        <input type="text" class="form-control scientific-input" id="judul_indonesia" name="judul_indonesia" required>
                    </div>
                    <div class="mb-3">
                        <label for="judul_inggris" class="form-label required">Judul Inggris</label>
                        <input type="text" class="form-control scientific-input" id="judul_inggris" name="judul_inggris" required>
                    </div>
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan"></textarea>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                    Simpan Testing Unit
                </button>
                <a href="{{ route('testing-units.index') }}" class="btn btn-secondary btn-sm">Batal</a>
            </div>
        </form>
    </div>
</section>
@endsection

@section('custom-script')
<script>
    $('#testingUnitForm').submit(function(e) {
        e.preventDefault();
        Notify.confirm('Simpan Data?', function() {
            $.ajax({
                url: "{{ route('testing-units.store') }}",
                method: "POST",
                data: $('#testingUnitForm').serialize(),
                success: function(response) {
                    Notify.success('Testing unit berhasil disimpan');
                    // window.location.href = "{{ route('testing-units.index') }}";
                },
                error: function(xhr) {
                    Notify.error('Gagal menyimpan testing unit');
                }
            });
        });
    });
</script>
@endsection
