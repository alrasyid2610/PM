@extends('layouts.app')
@section('page-title', 'Create Testing Matriks Samples')
@section('page-descrip', 'Kelola data Matriks Samples pengujian laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
          <a href="{{route('testing-matriks-samples.index')}}">Testing Matriks Samples</a>
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
    <form id="testingMatriksSampleForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-vials" color="icon-teal" title="Testing Matriks Samples" subtitle="Data matriks sampel pengujian">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label required">Kelompok Matriks Sample</label>
                        <select id="id_testing_kelompok_matriks_sample"
                                name="id_testing_kelompok_matriks_sample"
                                class="form-select"
                                required>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Kode</label>
                        <input type="text" class="form-control" name="kode" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Judul Indonesia</label>
                        <input type="text" class="form-control" name="judul_indonesia" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Judul Inggris</label>
                        <input type="text" class="form-control" name="judul_inggris" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="3"></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('testing-matriks-samples.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        $("#id_testing_kelompok_matriks_sample").select2({
            placeholder: "Pilih Kelompok...",
            ajax: {
                url: "{{ route('testing-kelompok-matriks-samples.select2') }}",
                dataType: "json",
                delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
                cache: true,
            },
            language: {
                noResults: function () {
                    return `<span>Tidak ditemukan. <a href="{{ route('testing-kelompok-matriks-samples.create') }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`;
                },
            },
            escapeMarkup: function (m) { return m; },
        });
    });

    submitCreateForm({
        formId: "#testingMatriksSampleForm",
        url: "{{ route('testing-matriks-samples.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('testing-matriks-samples.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
