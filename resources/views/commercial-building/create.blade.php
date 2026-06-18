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
    <form id="createCommercialBuildingForm">
        @csrf

        <!-- SECTION 1: INFORMASI GEDUNG -->
        <x-section-card icon="fa-building" color="icon-blue" title="Commercial Building" subtitle="Data gedung komersial">
            <div class="row g-3">
                <div class="col-md-5 col-12">
                    <label class="form-label required">Nama Gedung</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
                <div class="col-md-2 col-12">
                    <label class="form-label">Kode</label>
                    <input type="text" name="kode" class="form-control">
                </div>
                <div class="col-md-3 col-12">
                    <label class="form-label">Website</label>
                    <input type="text" name="website" class="form-control">
                </div>
                <div class="col-md-2 col-12">
                    <label class="form-label">Status</label>
                    <select name="is_aktif" class="form-select">
                        <option value="1">Aktif</option>
                        <option value="0">Non Aktif</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label required">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="3" required></textarea>
                </div>
                <div class="col-md-4 col-12">
                    <label class="form-label">Provinsi</label>
                    <select name="provinsi" class="form-select wilayah-provinsi" data-value=""></select>
                </div>
                <div class="col-md-4 col-12">
                    <label class="form-label">Kota / Kabupaten</label>
                    <select name="kota_kabupaten" class="form-select wilayah-kota" data-value=""></select>
                </div>
                <div class="col-md-4 col-12">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" name="kode_pos" class="form-control">
                </div>
            </div>
        </x-section-card>

        <!-- SECTION 2: PENANGGUNG JAWAB -->
        <x-section-card icon="fa-users" color="icon-green" title="Penanggung Jawab" subtitle="Data pemilik & pengurus gedung">
            <div class="row g-3">
                <div class="col-md-6 col-12">
                    <label class="form-label">Pemilik</label>
                    <input type="text" name="pemilik" class="form-control">
                </div>
                <div class="col-md-6 col-12">
                    <label class="form-label">Pengurus</label>
                    <input type="text" name="pengurus" class="form-control">
                </div>
            </div>
        </x-section-card>

        <x-form-actions back-route="{{ url('commercial-buildings') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        WilayahEngine.init('body');
        $('select[name="is_aktif"]').select2({ placeholder: 'Pilih Status', width: '100%' });
    });

    submitCreateForm({
        formId: "#createCommercialBuildingForm",
        url: "{{ route('commercial-buildings.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('commercial-buildings.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
