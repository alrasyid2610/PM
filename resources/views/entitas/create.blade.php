@extends('layouts.app')

@section('page-title', 'Entitas')
@section('page-descrip', 'Kelola data entitas badan usaha')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('entitas.index') }}">Entitas</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="16" y="10" width="48" height="60" rx="4" stroke="white" stroke-width="3"/>
        <line x1="26" y1="26" x2="54" y2="26" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="26" y1="36" x2="54" y2="36" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="26" y1="46" x2="42" y2="46" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createEntitasForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-sitemap" color="icon-navy" title="Entitas" subtitle="Data entitas badan usaha">
                <div class="row g-3">
                    <div class="col-md-9 col-12">
                        <label class="form-label required">Nama Entitas</label>
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

        <x-form-actions back-route="{{ route('entitas.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        $('select[name="is_aktif"]').select2({ placeholder: 'Pilih Status', width: '100%' });
    });

    submitCreateForm({
        formId: "#createEntitasForm",
        url: "{{ route('entitas.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('entitas.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
