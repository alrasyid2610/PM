@extends('layouts.app')

@section('content')
<section class="section">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Tambah Commercial Building</h4>
                <p class="text-muted mb-0">
                    Tambahkan data gedung komersial baru.
                </p>
            </div>

            <a href="{{ url('commercial-buildings') }}" class="btn btn-secondary btn-sm">
                Kembali
            </a>
        </div>

        <form id="createCommercialBuildingForm">
            @csrf

            <div class="card mb-4">
                <div class="card-header">
                    <strong>Informasi Gedung</strong>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label required">Nama Gedung</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="text" name="website" class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label required">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Provinsi</label>
                            <input type="text" name="provinsi" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Kota / Kabupaten</label>
                            <input type="text" name="kota_kabupaten" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Kode Pos</label>
                            <input type="text" name="kode_pos" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pemilik</label>
                            <input type="text" name="pemilik" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pengurus</label>
                            <input type="text" name="pengurus" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="is_aktif" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Non Aktif</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ url('commercial-buildings') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary" id="btnSubmit">
                    Simpan Data
                </button>
            </div>

        </form>
    </div>
</section>
@endsection

@section('custom-script')
<script>
$('#createCommercialBuildingForm').on('submit', function (e) {
    e.preventDefault();

    const form = $(this);
    const btn  = $('#btnSubmit');

    btn.prop('disabled', true).text('Menyimpan...');

    Notify.confirm('Simpan Data?', function() {
        $.ajax({
            url: "{{ route('commercial-buildings.store') }}",
            type: "POST",
            data: form.serialize(),
            success: function (res) {
                Notify.success('Data berhasil disimpan!');
                window.location.href = "{{ url('commercial-buildings') }}";
            },
            error: function (xhr) {
                btn.prop('disabled', false).text('Simpan Data');
    
                if (xhr.status === 422) {
                    let msg = Object.values(xhr.responseJSON.errors)
                        .map(e => e[0])
                        .join('<br>');
    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal',
                        html: msg
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: xhr.responseJSON?.message ?? 'Terjadi kesalahan'
                    });
                }
            }
        });
    });
});
</script>
@endsection

