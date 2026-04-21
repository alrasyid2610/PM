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
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-blue">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div class="detail-section-title">Commercial Building</div>
                <div class="detail-section-sub">Data gedung komersial</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label required">Nama Gedung</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="col-md-4 col-12">
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
                        <input type="text" name="provinsi" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kota / Kabupaten</label>
                        <input type="text" name="kota_kabupaten" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Kode Pos</label>
                        <input type="text" name="kode_pos" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: PENANGGUNG JAWAB -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-green">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="detail-section-title">Penanggung Jawab</div>
                <div class="detail-section-sub">Data pemilik & pengurus gedung</div>
            </div>
            <div class="detail-section-body">
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
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ url('commercial-buildings') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary" id="btnSubmit">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data
            </button>
        </div>

    </form>
</section>
@endsection

@section('custom-script')
<script>
    submitCreateForm({
        formId: "#createCommercialBuildingForm",
        url: "{{ route('commercial-buildings.store') }}",
        redirect: "{{ route('commercial-buildings.index') }}",
    });
</script>
@endsection
