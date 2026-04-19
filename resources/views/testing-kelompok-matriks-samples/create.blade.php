@extends('layouts.app')
@section('page-title', 'Testing Kelompok Matriks Samples')
@section('page-descrip', 'Kelola data Kelompok Matriks Samples pengujian laboratorium')

@section('breadcrumb')
<li class="breadcrumb-item" aria-current="page">
    <a href="{{ route('testing-kelompok-matriks-samples.index') }}">Testing Kelompok Matriks Samples</a>
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
    <form id="testingKelompokMatriksSampleForm">
        @csrf

        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-teal">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div class="detail-section-title">Kelompok Matriks Samples</div>
                <div class="detail-section-sub">Data kelompok matriks sampel</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">
                    <div class="col-md-4 col-12">
                        <label for="kode" class="form-label required">Kode</label>
                        <input type="text" class="form-control" id="kode" name="kode" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="judul_indonesia" class="form-label required">Judul Indonesia</label>
                        <input type="text" class="form-control" id="judul_indonesia" name="judul_indonesia" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label for="judul_inggris" class="form-label required">Judul Inggris</label>
                        <input type="text" class="form-control" id="judul_inggris" name="judul_inggris" required>
                    </div>
                    <div class="col-md-12">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('testing-kelompok-matriks-samples.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data
            </button>
        </div>

    </form>
</section>
@endsection

@section('custom-script')
<script>
    submitCreateForm({
        formId: "#testingKelompokMatriksSampleForm",
        url: "{{ route('testing-kelompok-matriks-samples.store') }}",
        redirect: "{{ route('testing-kelompok-matriks-samples.index') }}",
    });
</script>
@endsection
