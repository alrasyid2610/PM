@extends('layouts.app')

@section('page-title', 'Create Testing Parameters')
@section('page-descrip', 'Kelola data Parameters laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">
        <a href="{{ route('testing-parameters.index') }}">Testing Parameters</a>
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Edit Testing Parameter</h4>
                <p class="text-muted mb-0">Edit data testing parameter.</p>
            </div>
            <a href="{{ route('testing-parameters.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
        <form id="testingParameterForm" method="POST" action="{{ route('testing-parameters.update', $item->id_testing_parameter) }}">
            @csrf
            @method('PUT')
            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="kelompok" class="form-label">Kelompok</label>
                        <input type="text" class="form-control" id="kelompok" name="kelompok" value="{{ $item->kelompok }}">
                    </div>
                    <div class="mb-3">
                        <label for="kode" class="form-label required">Kode</label>
                        <input type="text" class="form-control" id="kode" name="kode" value="{{ $item->kode }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="judul_indonesia" class="form-label required">Judul Indonesia</label>
                        <input type="text" class="form-control" id="judul_indonesia" name="judul_indonesia" value="{{ $item->judul_indonesia }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="judul_inggris" class="form-label">Judul Inggris</label>
                        <input type="text" class="form-control" id="judul_inggris" name="judul_inggris" value="{{ $item->judul_inggris }}">
                    </div>
                    <div class="mb-3">
                        <label for="rumus_empiris" class="form-label">Rumus Empiris</label>
                        <input type="text" class="form-control" id="rumus_empiris" name="rumus_empiris" value="{{ $item->rumus_empiris }}">
                    </div>
                    <div class="mb-3">
                        <label for="judul_iupac" class="form-label">Judul IUPAC</label>
                        <input type="text" class="form-control" id="judul_iupac" name="judul_iupac" value="{{ $item->judul_iupac }}">
                    </div>
                    <div class="mb-3">
                        <label for="referensi" class="form-label">Referensi</label>
                        <input type="text" class="form-control" id="referensi" name="referensi" value="{{ $item->referensi }}">
                    </div>
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan">{{ $item->keterangan }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="attachment" class="form-label">Attachment</label>
                        <input type="text" class="form-control" id="attachment" name="attachment" value="{{ $item->attachment }}">
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('testing-parameters.index') }}" class="btn btn-secondary btn-sm">Batal</a>
            </div>
        </form>
    </div>
</section>
@endsection
