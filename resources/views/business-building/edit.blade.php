@extends('layouts.app')

@section('content')
<style>
    .required::after {
        content: " *";
        color: red;
    }
</style>

<section class="section">
    <div class="container-fluid">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Edit Commercial Building</h4>
                <p class="text-muted mb-0">
                    Perbarui data commercial building yang sudah ada.
                </p>
            </div>

            <a href="{{ route('commercial-buildings.index') }}"
               class="btn btn-secondary btn-sm">
                Kembali
            </a>
        </div>

        {{-- FORM --}}
        <form method="POST" id="editCommercialBuildingForm">
            @csrf
            @method('PUT')

            <input type="hidden"
                   name="id_building"
                   value="{{ $building->id_building }}">

            <div class="card mb-4">
                <div class="card-header">
                    <strong>Informasi Commercial Building</strong>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        {{-- Nama --}}
                        <div class="col-md-6">
                            <label class="form-label required">Nama</label>
                            <input type="text"
                                   name="nama"
                                   class="form-control"
                                   value="{{ old('nama', $building->nama) }}"
                                   required>
                        </div>

                        {{-- Status --}}
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="is_aktif" class="form-select">
                                <option value="1" {{ $building->is_aktif == 1 ? 'selected' : '' }}>
                                    Aktif
                                </option>
                                <option value="0" {{ $building->is_aktif == 0 ? 'selected' : '' }}>
                                    Non Aktif
                                </option>
                            </select>
                        </div>

                        {{-- Website --}}
                        <div class="col-md-3">
                            <label class="form-label">Website</label>
                            <input type="text"
                                   name="website"
                                   class="form-control"
                                   value="{{ old('website', $building->website) }}">
                        </div>

                        {{-- Alamat --}}
                        <div class="col-md-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat"
                                      class="form-control"
                                      rows="2">{{ old('alamat', $building->alamat) }}</textarea>
                        </div>

                        {{-- Provinsi --}}
                        <div class="col-md-4">
                            <label class="form-label">Provinsi</label>
                            <input type="text"
                                   name="provinsi"
                                   class="form-control"
                                   value="{{ old('provinsi', $building->provinsi) }}">
                        </div>

                        {{-- Kota / Kabupaten --}}
                        <div class="col-md-4">
                            <label class="form-label">Kota / Kabupaten</label>
                            <input type="text"
                                   name="kota_kabupaten"
                                   class="form-control"
                                   value="{{ old('kota_kabupaten', $building->kota_kabupaten) }}">
                        </div>

                        {{-- Kode Pos --}}
                        <div class="col-md-4">
                            <label class="form-label">Kode Pos</label>
                            <input type="text"
                                   name="kode_pos"
                                   class="form-control"
                                   value="{{ old('kode_pos', $building->kode_pos) }}">
                        </div>

                        {{-- Pemilik --}}
                        <div class="col-md-6">
                            <label class="form-label">Pemilik</label>
                            <input type="text"
                                   name="pemilik"
                                   class="form-control"
                                   value="{{ old('pemilik', $building->pemilik) }}">
                        </div>

                        {{-- Pengurus --}}
                        <div class="col-md-6">
                            <label class="form-label">Pengurus</label>
                            <input type="text"
                                   name="pengurus"
                                   class="form-control"
                                   value="{{ old('pengurus', $building->pengurus) }}">
                        </div>

                    </div>
                </div>
            </div>

            {{-- ACTION --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('commercial-buildings.index') }}"
                   class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit"
                        id="btnSubmit"
                        class="btn btn-primary">
                    Simpan Perubahan
                </button>
            </div>

        </form>
    </div>
</section>
@endsection

@section('custom-script')
<script>
$(document).ready(function () {

    const form = $('#editCommercialBuildingForm');
    const btn  = $('#btnSubmit');
    const id   = $('input[name="id_building"]').val();

    form.on('submit', function (e) {
        e.preventDefault();

        

        btn.prop('disabled', true).text('Menyimpan...');

        Notify.confirm('Simpan Data?', function() {
            $.ajax({
                url: "{{ route('commercial-buildings.update', ':id') }}".replace(':id', id),
                type: "POST",
                data: form.serialize(),
                success: function (res) {
    
                    Notify.success('Data berhasil disimpan!');
                    window.location.href =
                            "{{ route('commercial-buildings.index') }}";
                },
                error: function (xhr) {
    
                    btn.prop('disabled', false).text('Simpan Perubahan');
    
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let msg = Object.values(errors)
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
                            text: xhr.responseJSON?.message ?? 'Terjadi kesalahan sistem'
                        });
                    }
                }
            });
        });
        
    });

});
</script>
@endsection

