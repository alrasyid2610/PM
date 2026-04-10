@extends('layouts.app')

@section('page-title', 'Commercial Buildings')
@section('page-descrip', 'Kelola data Commercial Buildings')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('commercial-buildings.index') }}">Commercial Buildings</a>
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

