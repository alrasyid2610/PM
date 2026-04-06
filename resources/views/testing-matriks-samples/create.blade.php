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
    <div class="container-fluid">
        <form id="testingMatriksSampleForm">
            @csrf

            <div class="card mb-4">
                <div class="card-body">

                    {{-- FK Kelompok --}}
                    <div class="mb-3">
                        <label class="form-label required">
                            Kelompok Matriks Sample
                        </label>

                        <select id="id_testing_kelompok_matriks_sample"
                                name="id_testing_kelompok_matriks_sample"
                                class="form-select"
                                required>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Kode</label>
                        <input type="text"
                               class="form-control"
                               name="kode"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Judul Indonesia</label>
                        <input type="text"
                               class="form-control"
                               name="judul_indonesia"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Judul Inggris</label>
                        <input type="text"
                               class="form-control"
                               name="judul_inggris"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control"
                                  name="keterangan"></textarea>
                    </div>

                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                    Simpan Data
                </button>

                <a href="{{ route('testing-matriks-samples.index') }}"
                   class="btn btn-secondary btn-sm">
                    Batal
                </a>
            </div>

        </form>

    </div>
</section>
@endsection

@section('custom-script')
<script>

$(document).ready(function() {

    // SELECT2 FK
    $('#id_testing_kelompok_matriks_sample').select2({
        placeholder: 'Pilih Kelompok...',
        ajax: {
            url: "{{ route('testing-kelompok-matriks-samples.select2') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

});


$('#testingMatriksSampleForm').submit(function(e) {

    e.preventDefault();

    Notify.confirm('Simpan Data?', function() {

        $.ajax({
            url: "{{ route('testing-matriks-samples.store') }}",
            method: "POST",
            data: $('#testingMatriksSampleForm').serialize(),

            success: function(response) {
                Notify.success('Data berhasil disimpan');
                // optional redirect
                // window.location.href = "{{ route('testing-matriks-samples.index') }}";
            },

            error: function(xhr) {
                Notify.error('Gagal menyimpan data');
            }
        });

    });

});
</script>
@endsection