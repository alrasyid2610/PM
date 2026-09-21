@extends('layouts.app')

@section('page-title', 'Office')
@section('page-descrip', 'Kelola data Office Pramatek')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('office.index') }}">Office</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 68V16h28l12 12v40H20z" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M48 16v12h12" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <line x1="28" y1="34" x2="44" y2="34" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="28" y1="44" x2="52" y2="44" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="28" y1="54" x2="52" y2="54" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createOfficeForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-building" color="icon-navy" title="Office" subtitle="Data kantor Pramatek">
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label required">Nama Office</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label">Alamat</label>
                        <input type="text" name="alamat" class="form-control">
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('office.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    submitCreateForm({
        formId: "#createOfficeForm",
        url: "{{ route('office.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('office.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
