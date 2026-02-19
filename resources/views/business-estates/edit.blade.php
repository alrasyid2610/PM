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
                <h4 class="mb-1">Edit Business Estate</h4>
                <p class="text-muted mb-0">
                    Perbarui data business estate yang sudah ada.
                </p>
            </div>

            <a href="{{ route('business-estates.index') }}" class="btn btn-secondary btn-sm">
                Kembali
            </a>
        </div>

        {{-- FORM --}}
        <form method="POST" id="editBusinessEstateForm">
            @csrf
            @method('PUT')

            <input type="hidden" name="id_bestate" value="{{ $bestate->id_bestate }}">

            <div class="card mb-4">
                <div class="card-header">
                    <strong>Informasi Business Estate</strong>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        {{-- Nama --}}
                        <div class="col-md-6">
                            <label class="form-label required">Nama</label>
                            <input type="text"
                                   name="nama"
                                   class="form-control"
                                   value="{{ old('nama', $bestate->nama) }}"
                                   required>
                        </div>

                        {{-- Kode --}}
                        <div class="col-md-3">
                            <label class="form-label">Kode</label>
                            <input type="text"
                                   name="kode"
                                   class="form-control"
                                   value="{{ old('kode', $bestate->kode) }}">
                        </div>

                        {{-- Status --}}
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="is_aktif" class="form-select">
                                <option value="1" {{ $bestate->is_aktif == 1 ? 'selected' : '' }}>
                                    Aktif
                                </option>
                                <option value="0" {{ $bestate->is_aktif == 0 ? 'selected' : '' }}>
                                    Non Aktif
                                </option>
                            </select>
                        </div>

                        {{-- Alamat --}}
                        <div class="col-md-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat"
                                      class="form-control"
                                      rows="2">{{ old('alamat', $bestate->alamat) }}</textarea>
                        </div>

                        {{-- Provinsi --}}
                        <div class="col-md-4">
                            <label class="form-label">Provinsi</label>
                            <input type="text"
                                   name="provinsi"
                                   class="form-control"
                                   value="{{ old('provinsi', $bestate->provinsi) }}">
                        </div>

                        {{-- Kota / Kabupaten --}}
                        <div class="col-md-4">
                            <label class="form-label">Kota / Kabupaten</label>
                            <input type="text"
                                   name="kota_kabupaten"
                                   class="form-control"
                                   value="{{ old('kota_kabupaten', $bestate->kota_kabupaten) }}">
                        </div>

                        {{-- Website --}}
                        <div class="col-md-4">
                            <label class="form-label">Website</label>
                            <input type="text"
                                   name="website"
                                   class="form-control"
                                   value="{{ old('website', $bestate->website) }}">
                        </div>

                        {{-- Pemilik --}}
                        <div class="col-md-6">
                            <label class="form-label">Pemilik</label>
                            <input type="text"
                                   name="pemilik"
                                   class="form-control"
                                   value="{{ old('pemilik', $bestate->pemilik) }}">
                        </div>

                        {{-- Pengurus --}}
                        <div class="col-md-6">
                            <label class="form-label">Pengurus</label>
                            <input type="text"
                                   name="pengurus"
                                   class="form-control"
                                   value="{{ old('pengurus', $bestate->pengurus) }}">
                        </div>

                    </div>
                </div>
            </div>

            {{-- ACTION --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('business-estates.index') }}"
                   class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
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

    const form = $('#editBusinessEstateForm');
    const btn  = $('#btnSubmit');
    const id   = $('input[name="id_bestate"]').val();

    form.on('submit', function (e) {
        e.preventDefault();

        btn.prop('disabled', true).text('Menyimpan...');

        Notify.confirm('Simpan Data?', function() {
            $.ajax({
                url: "{{ route('business-estates.update', ':id') }}".replace(':id', id),
                type: "POST",
                data: form.serialize(),
                success: function (res) {

                    Notify.success('Data berhasil disimpan!');
                    window.location.href = "{{ route('business-estates.index') }}";
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
