@extends('layouts.app')

@section('page-title', 'Business Estates')
@section('page-descrip', 'Kelola data Business Estates')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('business-estates.index') }}">Business Estates</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
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
    <form method="POST" id="editBusinessEstateForm">
        @csrf
        @method('PUT')

        <input type="hidden" name="id_bestate" value="{{ $bestate->id_bestate }}">

        <x-section-card icon="fa-industry" color="icon-navy" title="Informasi Business Estate" subtitle="Data kawasan industri">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label required">Nama</label>
                    <input type="text" name="nama" class="form-control" value="{{ old('nama', $bestate->nama) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kode</label>
                    <input type="text" name="kode" class="form-control" value="{{ old('kode', $bestate->kode) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="is_aktif" class="form-select">
                        <option value="1" {{ $bestate->is_aktif == 1 ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ $bestate->is_aktif == 0 ? 'selected' : '' }}>Non Aktif</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2">{{ old('alamat', $bestate->alamat) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Provinsi</label>
                    <select name="provinsi" class="form-select wilayah-provinsi"
                        data-value="{{ old('provinsi', $bestate->provinsi) }}"></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kota / Kabupaten</label>
                    <select name="kota_kabupaten" class="form-select wilayah-kota"
                        data-value="{{ old('kota_kabupaten', $bestate->kota_kabupaten) }}"></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Website</label>
                    <input type="text" name="website" class="form-control" value="{{ old('website', $bestate->website) }}">
                </div>
            </div>
        </x-section-card>

        <x-section-card icon="fa-users" color="icon-green" title="Penanggung Jawab" subtitle="Data pemilik & pengurus kawasan">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Pemilik</label>
                    <input type="text" name="pemilik" class="form-control" value="{{ old('pemilik', $bestate->pemilik) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Pengurus</label>
                    <input type="text" name="pengurus" class="form-control" value="{{ old('pengurus', $bestate->pengurus) }}">
                </div>
            </div>
        </x-section-card>

        <x-form-actions back-route="{{ route('business-estates.index') }}" submit-label="Simpan Perubahan" submit-id="btnSubmit" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
$(document).ready(function () {

    WilayahEngine.init('body');
    $('select[name="is_aktif"]').select2({ placeholder: 'Pilih Status', width: '100%' });

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
                        let msg = Object.values(errors).map(e => e[0]).join('<br>');
                        Swal.fire({ icon: 'error', title: 'Validasi Gagal', html: msg });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan sistem' });
                    }
                }
            });
        });
    });

});
</script>
@endsection
