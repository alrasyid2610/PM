@extends('layouts.app')

@section('page-title', 'Kepemilikan')
@section('page-descrip', 'Kelola data kepemilikan badan usaha')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('kepemilikan.index') }}">Kepemilikan</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M40 10 14 26v6h52v-6L40 10z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <line x1="18" y1="36" x2="18" y2="60" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="32" y1="36" x2="32" y2="60" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="48" y1="36" x2="48" y2="60" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="62" y1="36" x2="62" y2="60" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="14" y1="66" x2="66" y2="66" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createKepemilikanForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-landmark" color="icon-navy" title="Kepemilikan" subtitle="Data kepemilikan badan usaha">
                <div class="row g-3">
                    <div class="col-md-9 col-12">
                        <label class="form-label required">Nama Kepemilikan</label>
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

        <x-form-actions back-route="{{ route('kepemilikan.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        $('select[name="is_aktif"]').select2({ placeholder: 'Pilih Status', width: '100%' });
    });

    submitCreateForm({
        formId: "#createKepemilikanForm",
        url: "{{ route('kepemilikan.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('kepemilikan.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
