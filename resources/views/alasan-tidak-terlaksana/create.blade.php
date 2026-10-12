@extends('layouts.app')

@section('page-title', 'Alasan Tidak Terlaksana')
@section('page-descrip', 'Kelola master alasan titik sample FWO tidak dapat diambil')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('alasan-tidak-terlaksana.index') }}">Alasan Tidak Terlaksana</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="40" cy="40" r="24" stroke="white" stroke-width="3"/>
        <line x1="23" y1="57" x2="57" y2="23" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createAlasanForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-ban" color="icon-navy" title="Alasan Tidak Terlaksana" subtitle="Alasan titik sample tidak dapat diambil">
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label required">Alasan</label>
                        <input type="text" name="nama" class="form-control" maxlength="100" required>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label">Keterangan</label>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="wajib_keterangan" id="create_wajib_keterangan" class="form-check-input" value="1">
                            <label class="form-check-label" for="create_wajib_keterangan">Keterangan wajib diisi saat alasan ini dipilih</label>
                        </div>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('alasan-tidak-terlaksana.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    submitCreateForm({
        formId: "#createAlasanForm",
        url: "{{ route('alasan-tidak-terlaksana.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('alasan-tidak-terlaksana.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
