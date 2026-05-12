@extends('layouts.app')

@section('page-title', 'Fieldwork')
@section('page-descrip', 'Tambah data kegiatan pekerjaan lapangan')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ route('fieldworks.index') }}">Fieldwork</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Tambah</li>
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="10" y="30" width="60" height="40" rx="4" stroke="white" stroke-width="3"/>
        <path d="M28 30V22a12 12 0 0 1 24 0v8" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <circle cx="40" cy="50" r="5" fill="white"/>
        <path d="M40 55v8" stroke="white" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endsection

@section('content')
<section class="section">
    <form id="fieldworkForm" class="row g-3">
        @csrf

        <div class="col-12">
            <x-section-card icon="fa-hard-hat" color="icon-amber" title="Informasi Fieldwork" subtitle="Data kegiatan pekerjaan lapangan">
                <div class="row g-3">

                    <div class="col-md-12">
                        <label class="form-label required">Work Order</label>
                        <select name="id_wo" id="create_id_wo" class="form-select" required>
                            <option value="">-- Pilih Work Order --</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label required">Judul Pekerjaan</label>
                        <input type="text" name="judul_pekerjaan" class="form-control" required maxlength="500"
                            placeholder="Judul kegiatan fieldwork">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">Site Pelanggan</label>
                        <select name="id_site_pelanggan_pekerjaan" id="create_id_site" class="form-select" required>
                            <option value="">-- Pilih Site --</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">PIC Pelanggan</label>
                        <select name="id_pic_pelanggan_pekerjaan" id="create_id_pic" class="form-select" required>
                            <option value="">-- Pilih PIC --</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Waktu Kedatangan</label>
                        <input type="datetime-local" name="waktu_kedatangan" class="form-control">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Opsional"></textarea>
                    </div>

                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('fieldworks.index') }}" submit-label="Simpan Fieldwork" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    function noResultsAdd(createUrl) {
        return {
            language: {
                noResults: function () {
                    return `<span>Tidak ditemukan. <a href="${createUrl}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`;
                },
            },
            escapeMarkup: function (m) { return m; },
        };
    }

    $(document).ready(function () {
        $('#create_id_wo').select2({
            width: '100%',
            placeholder: 'Ketik No WO...',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('work-orders.select2') }}",
                dataType: 'json',
                delay: 250,
                data: p => ({ q: p.term }),
                processResults: d => ({ results: d }),
                cache: true,
            },
        });

        $('#create_id_wo').on('select2:select', function (e) {
            $('input[name="judul_pekerjaan"]').val(e.params.data.judul || '');
        });

        // Auto-fill WO dari query param ?id_wo=
        const preId = new URLSearchParams(window.location.search).get('id_wo');
        if (preId) {
            $.get("{{ url('work-orders') }}/" + preId + "/detail", function (res) {
                if (!res) return;
                const label = (res.no_wo ?? '') + (res.judul_pekerjaan ? ' - ' + res.judul_pekerjaan : '');
                const opt   = new Option(label, res.id_wo, true, true);
                $('#create_id_wo').append(opt).trigger('change');
                $('input[name="judul_pekerjaan"]').val(res.judul_pekerjaan ?? '');
            });
        }

        $('#create_id_site').select2({
            width: '100%',
            placeholder: 'Ketik nama site...',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('business-relation-sites.select2') }}",
                dataType: 'json',
                delay: 250,
                data: p => ({ q: p.term }),
                processResults: d => ({ results: d }),
                cache: true,
            },
            ...noResultsAdd('/business-relations/create'),
        });

        $('#create_id_pic').select2({
            width: '100%',
            placeholder: 'Ketik nama PIC...',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('business-relation-contacts.select2') }}",
                dataType: 'json',
                delay: 250,
                data: p => ({ q: p.term }),
                processResults: d => ({ results: d }),
                cache: true,
            },
            ...noResultsAdd('/business-relation-contacts/create'),
        });
    });

    submitCreateForm({
        formId: '#fieldworkForm',
        url: "{{ route('fieldworks.store') }}",
        redirect: "{{ route('fieldworks.index') }}",
    });
</script>
@endsection
