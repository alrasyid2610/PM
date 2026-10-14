@extends('layouts.app')

@section('page-title', 'Bank Account')
@section('page-descrip', 'Kelola master data rekening bank')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('bank-accounts.index') }}">Bank Account</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M14 32L40 16l26 16" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <line x1="20" y1="36" x2="20" y2="56" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="34" y1="36" x2="34" y2="56" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="46" y1="36" x2="46" y2="56" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="60" y1="36" x2="60" y2="56" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="14" y1="62" x2="66" y2="62" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createBankAccountForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-building-columns" color="icon-navy" title="Bank Account" subtitle="Data rekening bank">
                <div class="row g-3">
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Bank</label>
                        <input type="text" name="bank" class="form-control" maxlength="100" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">No. Rekening</label>
                        <input type="text" name="no_rekening" class="form-control" maxlength="50" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Atas Nama</label>
                        <input type="text" name="atas_nama" class="form-control" maxlength="150" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_utama" id="create_is_utama" class="form-check-input" value="1">
                            <label class="form-check-label" for="create_is_utama">
                                Rekening utama <span class="text-muted">(otomatis terisi di keterangan penawaran SQ, tetap bisa diganti. Hanya 1 rekening yang bisa utama.)</span>
                            </label>
                        </div>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('bank-accounts.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    submitCreateForm({
        formId: "#createBankAccountForm",
        url: "{{ route('bank-accounts.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('bank-accounts.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
