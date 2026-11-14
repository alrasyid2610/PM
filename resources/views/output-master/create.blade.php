@extends('layouts.app')

@section('page-title', 'Master Output')
@section('page-descrip', 'Tambah judul Output Pekerjaan / Output Other')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('output-master.index') }}">Master Output</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M22 8h28l14 14v50H22V8z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M50 8v14h14" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M32 40h24M32 50h24M32 60h16" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createOutputMasterForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-file-lines" color="icon-navy" title="Judul Output" subtitle="Judul yang bisa dipilih di Output Pekerjaan atau Output Other">
                <div class="row g-3">
                    <div class="col-md-7 col-12">
                        <label class="form-label required">Judul</label>
                        <input type="text" name="judul" class="form-control" required maxlength="255" placeholder="mis. LHU Air" autofocus>
                        <small class="text-muted">Periksa ejaan — judul ini akan dipilih (bukan diketik) di Output, jadi bentuk bakunya ditentukan di sini.</small>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label required">Tipe</label>
                        <select name="tipe" class="form-select" required>
                            <option value="pekerjaan">Output Pekerjaan</option>
                            <option value="other">Output Other</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label">Status</label>
                        <select name="is_aktif" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="col-12" id="omLhuWrap">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_lhu" id="om_is_lhu" class="form-check-input" value="1">
                            <label class="form-check-label" for="om_is_lhu">Judul ini adalah <b>LHU</b> (Laporan Hasil Uji) — saklar LHU di Output Pekerjaan otomatis aktif saat judul ini dipilih</label>
                        </div>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('output-master.index') }}" submit-label="Simpan Judul" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        $('select[name="tipe"]').select2({ width: '100%', minimumResultsForSearch: Infinity });
        $('select[name="is_aktif"]').select2({ width: '100%', minimumResultsForSearch: Infinity });

        // Penanda LHU hanya berlaku untuk Output Pekerjaan
        function syncLhu() {
            const pekerjaan = $('select[name="tipe"]').val() === 'pekerjaan';
            $('#omLhuWrap').toggleClass('d-none', !pekerjaan);
            if (!pekerjaan) $('#om_is_lhu').prop('checked', false);
        }
        $('select[name="tipe"]').on('change', syncLhu);
        syncLhu();
    });

    submitCreateForm({
        formId: "#createOutputMasterForm",
        url: "{{ route('output-master.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('output-master.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
