@extends('layouts.app')

@section('page-title', 'Personnel')
@section('page-descrip', 'Kelola data personel lapangan (PIC, teknisi sampling, dsb)')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('personnel.index') }}">Personnel</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="30" cy="26" r="10" stroke="white" stroke-width="3"/>
        <circle cx="54" cy="30" r="8" stroke="white" stroke-width="3"/>
        <path d="M12 62c0-11.046 8.059-20 18-20s18 8.954 18 20" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <path d="M44 62c0-8.837 6.268-16 14-16s14 7.163 14 16" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createPersonnelForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-people-group" color="icon-navy" title="Personnel" subtitle="Data personel lapangan — PIC, teknisi sampling, dsb (tidak bisa login ke sistem)">
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label required">Nama</label>
                        <input type="text" name="nama" class="form-control" required maxlength="255">
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" maxlength="30">
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Status</label>
                        <select name="is_aktif" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Non Aktif</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Opsional"></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('personnel.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        $('select[name="is_aktif"]').select2({ placeholder: 'Pilih Status', width: '100%' });
    });

    submitCreateForm({
        formId: "#createPersonnelForm",
        url: "{{ route('personnel.store') }}",
        onSuccess: function (res) {
            window.location.href = "{{ route('personnel.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
