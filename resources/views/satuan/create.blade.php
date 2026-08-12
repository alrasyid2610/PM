@extends('layouts.app')

@section('page-title', 'Satuan')
@section('page-descrip', 'Kelola data satuan BOQ')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('satuan.index') }}">Satuan</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M14 30h52v20H14z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <line x1="24" y1="30" x2="24" y2="38" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="34" y1="30" x2="34" y2="38" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="44" y1="30" x2="44" y2="38" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="54" y1="30" x2="54" y2="38" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createSatuanForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-ruler-combined" color="icon-navy" title="Satuan" subtitle="Data satuan untuk item BOQ">
                <div class="row g-3">
                    <div class="col-md-9 col-12">
                        <label class="form-label required">Nama Satuan</label>
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

        <x-form-actions back-route="{{ route('satuan.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        $('select[name="is_aktif"]').select2({ placeholder: 'Pilih Status', width: '100%' });
    });

    submitCreateForm({
        formId: "#createSatuanForm",
        url: "{{ route('satuan.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('satuan.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
