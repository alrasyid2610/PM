@extends('layouts.app')

@section('page-title', 'Sub Kategori Bisnis')
@section('page-descrip', 'Kelola data sub kategori bisnis pelanggan')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('sub-kategori-bisnis.index') }}">Sub Kategori Bisnis</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M14 24a4 4 0 0 1 4-4h18l8 8h18a4 4 0 0 1 4 4v24a4 4 0 0 1-4 4H18a4 4 0 0 1-4-4V24z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createSubKategoriBisnisForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-tag" color="icon-navy" title="Sub Kategori Bisnis" subtitle="Data sub kategori bisnis pelanggan">
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label required">Kategori Bisnis</label>
                        <select name="id_kategori_bisnis" id="id_kategori_bisnis" class="form-select" required></select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label required">Nama Sub Kategori Bisnis</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Status</label>
                        <select name="is_aktif" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Non Aktif</option>
                        </select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('sub-kategori-bisnis.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        $('select[name="is_aktif"]').select2({ placeholder: 'Pilih Status', width: '100%' });

        $('#id_kategori_bisnis').select2({
            placeholder: 'Pilih Kategori Bisnis',
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('kategori-bisnis.select2') }}",
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term ?? '' }),
                processResults: data => ({ results: data }),
                cache: true,
            },
            language: {
                noResults: () => `<span>Tidak ditemukan. <a href="{{ route('kategori-bisnis.create') }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`,
            },
            escapeMarkup: (m) => m,
        });
    });

    submitCreateForm({
        formId: "#createSubKategoriBisnisForm",
        url: "{{ route('sub-kategori-bisnis.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('sub-kategori-bisnis.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
