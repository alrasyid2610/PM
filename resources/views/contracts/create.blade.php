@extends('layouts.app')

@section('page-title', 'Contracts')
@section('page-descrip', 'Kelola data kontrak pelanggan')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('contracts.index') }}">Contracts</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="16" y="10" width="48" height="60" rx="4" stroke="white" stroke-width="3"/>
        <line x1="26" y1="26" x2="54" y2="26" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="26" y1="36" x2="54" y2="36" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="26" y1="46" x2="42" y2="46" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <path d="M46 56l4 4 8-8" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="contractForm" enctype="multipart/form-data" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-file-contract" color="icon-blue" title="Informasi Kontrak" subtitle="Detail data kontrak pelanggan">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">No Kontrak Client</label>
                        <input type="text" class="form-control" name="no_contract_client" placeholder="Nomor kontrak dari client">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Kontrak</label>
                        <input type="text" class="form-control fp-date" name="tanggal_kontrak" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="status">
                            <option value="draft">Draft</option>
                            <option value="aktif">Aktif</option>
                            <option value="selesai">Selesai</option>
                            <option value="batal">Batal</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="text" class="form-control fp-date" name="tanggal_mulai" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="text" class="form-control fp-date" name="tanggal_selesai" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Durasi (Bulan)</label>
                        <input type="number" class="form-control" name="durasi_bulan" min="1" placeholder="12">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nilai Kontrak (Rp)</label>
                        <input type="text" inputmode="numeric" class="form-control input-num-mask input-num-int" name="nilai_kontrak" placeholder="150,000,000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" name="catatan" rows="3" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <div class="col-12">
            <x-section-card icon="fa-building" color="icon-navy" title="Data Pelanggan">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Pelanggan</label>
                        <select class="form-select" name="id_business_relation" id="id_business_relation"></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">PIC Pelanggan</label>
                        <select class="form-select" name="id_pic_pelanggan" id="id_pic_pelanggan"></select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <div class="col-12">
            <x-section-card icon="fa-user-tie" color="icon-green" title="PIC Internal Pramatek">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">PIC Pramatek</label>
                        <select class="form-select" name="id_pic_pramatek" id="id_pic_pramatek"></select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <div class="col-12">
            <x-section-card icon="fa-paperclip" color="icon-teal" title="Attachment" subtitle="File pendukung kontrak">
                <input type="file" class="filepond" name="attachments[]" multiple>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('contracts.index') }}" submit-label="Simpan Contract" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    window.route = {
        select2BR:      "{{ route('business-relations.select2') }}",
        select2Contact: "{{ route('business-relation-contacts.select2') }}",
        select2User:    "{{ route('users.select2') }}",
        csrf:           "{{ csrf_token() }}",
    }

    function makeSelect2WithAdd(selector, url, placeholder, createUrl) {
        $(selector).select2({
            width: '100%',
            placeholder: placeholder,
            allowClear: true,
            ajax: {
                url: url,
                dataType: 'json',
                delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
                cache: true,
            },
            language: {
                noResults: function () {
                    return `<span>Tidak ditemukan. <a href="${createUrl}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`;
                },
            },
            escapeMarkup: function (m) { return m; },
        });
    }

    $(document).ready(function () {
        initNumericMask(document.body);
        initFpDate(document);

        createFileUploader(".filepond");
        $('#status').select2({ placeholder: '-- Pilih Status --', allowClear: true, width: '100%' });

        $('#id_business_relation').select2({
            width: '100%', placeholder: '-- Pilih Pelanggan --', allowClear: true, minimumInputLength: 2,
            ajax: { url: window.route.select2BR, delay: 300, dataType: 'json',
                    data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }) },
        });

        $('#id_pic_pelanggan').select2({
            width: '100%', placeholder: '-- Pilih PIC Pelanggan --', allowClear: true, minimumInputLength: 2,
            ajax: { url: window.route.select2Contact, delay: 300, dataType: 'json',
                    data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }) },
        });

        $('#id_pic_pramatek').select2({
            width: '100%', placeholder: '-- Pilih PIC Pramatek --', allowClear: true, minimumInputLength: 2,
            ajax: { url: window.route.select2User, delay: 300, dataType: 'json',
                    data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }) },
        });
    });

    submitCreateForm({
        formId:   "#contractForm",
        url:      "{{ route('contracts.store') }}",
        filepond: ".filepond",
        onSuccess: function (res) {
            window.location.href = "{{ route('contracts.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
