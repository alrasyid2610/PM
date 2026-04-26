@extends('layouts.app')

@section('page-title', 'Business Relation Contacts')
@section('page-descrip', 'Kelola data Business Relation Contacts')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('business-relation-contacts.index') }}">Business Relation Contacts</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Business Relation Contacts</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M28 8h4v28l-16 28h48L48 36V8h4" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M28 8h24" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <circle cx="32" cy="56" r="3" fill="white"/>
        <circle cx="44" cy="62" r="2" fill="white"/>
        <circle cx="38" cy="52" r="2" fill="white"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="createBusinesRelationContact">
        @csrf

        <x-section-card icon="fa-address-card" color="icon-green" title="Business Relation Contacts" subtitle="Data kontak PIC pelanggan">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label required">Bussines Relation Site</label>
                    <select id="id_br" name="id_br" class="form-select" required></select>
                </div>
                <div class="col-md-12">
                    <label class="form-label required">Nama PIC</label>
                    <input type="text" class="form-control" id="nama_pic" name="nama_pic" required>
                </div>
                <div class="col-md-3 col-12">
                    <label class="form-label required">No. Telp PIC</label>
                    <input type="text" class="form-control" id="nomor_telepon_pic" name="nomor_telepon_pic" required>
                </div>
                <div class="col-md-3 col-12">
                    <label class="form-label required">Email PIC</label>
                    <input type="text" class="form-control" id="email_pic" name="email_pic" required>
                </div>
                <div class="col-md-3 col-12">
                    <label class="form-label required">Lokasi PIC</label>
                    <input type="text" class="form-control" id="lokasi_pic" name="lokasi_pic" required>
                </div>
                <div class="col-md-3 col-12">
                    <label for="is_aktif" class="form-label required">Status</label>
                    <select class="form-select" id="is_aktif" name="is_aktif" required>
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </select>
                </div>
            </div>
        </x-section-card>

        <x-form-actions back-route="{{ route('business-relation-contacts.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection


@section('custom-script')
<script>
    $(document).ready(function () {
        $('#is_aktif').select2({ placeholder: 'Pilih Status', width: '100%' });

        $("#id_br").select2({
            placeholder: "Pilih Business Relation Site...",
            ajax: {
                url: "{{ route('business-relation-sites.select2') }}",
                dataType: "json",
                delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
                cache: true,
            },
            language: {
                noResults: function () {
                    return `<span>Tidak ditemukan. <a href="{{ route('business-relations.create') }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`;
                },
            },
            escapeMarkup: function (m) { return m; },
        });
    });

    submitCreateForm({
        formId: "#createBusinesRelationContact",
        url: "{{ route('business-relation-contacts.store') }}",
        redirect: "{{ route('business-relation-contacts.index') }}",
    });
</script>
@endsection
