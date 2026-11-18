@extends('layouts.app')

@section('page-title', 'Master Label Budget')
@section('page-descrip', 'Tambah label Budget Plan')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('budget-label.index') }}">Master Label Budget</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M10 40V14h26l34 34-26 26L10 40z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <circle cx="24" cy="28" r="4" stroke="white" stroke-width="3"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createBudgetLabelForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-tags" color="icon-navy" title="Label Budget" subtitle="Label yang bisa dipilih di Budget Plan WO, FWO, SQ WO, dan SQ FWO">
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label required">Label</label>
                        <input type="text" name="nama" class="form-control" required maxlength="255" placeholder="mis. Bulanan Internal" autofocus>
                        <small class="text-muted">Periksa ejaan — label ini dipilih (bukan diketik) di Budget Plan, jadi bentuk bakunya ditentukan di sini.</small>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="urutan" class="form-control" min="0" max="9999" value="0">
                        <small class="text-muted">Urutan di pilihan (kecil = di atas).</small>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label">Status</label>
                        <select name="is_aktif" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('budget-label.index') }}" submit-label="Simpan Label" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        $('select[name="is_aktif"]').select2({ width: '100%', minimumResultsForSearch: Infinity });
    });

    submitCreateForm({
        formId: "#createBudgetLabelForm",
        url: "{{ route('budget-label.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('budget-label.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
