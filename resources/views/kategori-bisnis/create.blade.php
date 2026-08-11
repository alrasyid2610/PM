@extends('layouts.app')

@section('page-title', 'Kategori Bisnis')
@section('page-descrip', 'Kelola data kategori bisnis pelanggan')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('kategori-bisnis.index') }}">Kategori Bisnis</a>
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
    <form id="createKategoriBisnisForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-tags" color="icon-navy" title="Kategori Bisnis" subtitle="Data kategori bisnis pelanggan">
                <div class="row g-3">
                    <div class="col-md-9 col-12">
                        <label class="form-label required">Nama Kategori Bisnis</label>
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

        <x-form-actions back-route="{{ route('kategori-bisnis.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        $('select[name="is_aktif"]').select2({ placeholder: 'Pilih Status', width: '100%' });
    });

    submitCreateForm({
        formId: "#createKategoriBisnisForm",
        url: "{{ route('kategori-bisnis.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('kategori-bisnis.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
