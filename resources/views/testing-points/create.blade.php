@extends('layouts.app')

@section('page-title', 'Testing Points')
@section('page-descrip', 'Kelola data Testing Points pengujian laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('testing-points.index') }}">Testing Points</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
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
    <form id="testingPointForm" enctype="multipart/form-data" class="row g-3">
        @csrf

        <!-- SECTION 1: INFORMASI POINT -->
        <div class="col-12">
            <x-section-card icon="fa-map-pin" color="icon-navy" title="Testing Points" subtitle="Data titik pengujian laboratorium">
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label required">Testing Standard</label>
                        <select id="id_testing_standard"
                            name="id_testing_standard"
                            class="form-select"
                            required></select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label required">Testing Matriks Sample</label>
                        <select id="id_testing_matriks_sample"
                            name="id_testing_matriks_sample"
                            class="form-select"
                            required></select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label required">Nama</label>
                        <input type="text" class="form-control" name="nama" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Nomor Halaman</label>
                        <input type="text" class="form-control" name="nomor_halaman">
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label required">Status</label>
                        <select name="is_aktif" class="form-select" required>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" rows="3"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="3"></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION 2: ATTACHMENT -->
        <div class="col-12">
            <x-section-card icon="fa-paperclip" color="icon-blue" title="Attachment" subtitle="File pendukung testing point">
                <input type="file" class="filepond" name="attachments[]" multiple>
            </x-section-card>
        </div>

        <!-- SECTION 3: TESTING ITEMS -->
        <div class="col-12">
            <x-section-card icon="fa-table-list" color="icon-green" title="Testing Items" subtitle="Detail item pengujian per point">
                <x-slot name="actions">
                    <button type="button" class="btn btn-primary btn-sm btn-add-row ms-2">
                        <i class="fa-solid fa-plus me-1"></i> Tambah Baris
                    </button>
                </x-slot>
                <div class="dynamic-table-wrapper">
                    <div class="table-responsive">
                        <table id="Table" class="table table-bordered table-sm dynamic-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="white-space:nowrap;width:40px">No</th>
                                    <th style="min-width:240px">Judul Indonesia</th>
                                    <th style="min-width:240px">Judul Inggris</th>
                                    <th style="min-width:160px">Parameter</th>
                                    <th style="min-width:140px">Unit</th>
                                    <th style="min-width:120px">Nilai</th>
                                    <th style="min-width:140px">Keterangan</th>
                                    <th style="white-space:nowrap;width:60px">Status</th>
                                    <th style="white-space:nowrap;width:60px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <input type="hidden" name="id_testing_item[]" value="">
                                    <td class="row-number"></td>
                                    <td>
                                        <input type="text" name="judul_indonesia[]" class="form-control form-control-sm">
                                    </td>
                                    <td>
                                        <input type="text" name="judul_inggris[]" class="form-control form-control-sm">
                                    </td>
                                    <td>
                                        <select name="parameter[]" class="form-control form-control-sm parameter-select"></select>
                                    </td>
                                    <td>
                                        <select name="unit[]" class="form-control form-control-sm unit-select"></select>
                                    </td>
                                    <td>
                                        <input type="text" name="nilai[]" class="form-control form-control-sm">
                                    </td>
                                    <td>
                                        <input type="text" name="keterangan[]" class="form-control form-control-sm">
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" name="status[]" value="1">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm btn-remove">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ route('testing-points.index') }}" submit-label="Simpan Data" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        createFileUploader(".filepond");

        $('select[name="is_aktif"]').select2({ placeholder: 'Pilih Status', width: '100%' });

        $("#id_testing_standard").select2({
            width: '100%',
            placeholder: "Pilih Testing Standard...",
            allowClear: true,
            ajax: {
                url: "{{ route('testing-standards.select2') }}",
                dataType: "json",
                delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
                cache: true,
            },
            language: {
                noResults: function () {
                    return `<span>Tidak ditemukan. <a href="{{ route('testing-standards.create') }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`;
                },
            },
            escapeMarkup: function (m) { return m; },
        });

        $("#id_testing_matriks_sample").select2({
            width: '100%',
            placeholder: "Pilih Matriks Sample...",
            allowClear: true,
            ajax: {
                url: "{{ route('testing-matriks-samples.select2') }}",
                dataType: "json",
                delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
                cache: true,
            },
            language: {
                noResults: function () {
                    return `<span>Tidak ditemukan. <a href="{{ route('testing-matriks-samples.create') }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`;
                },
            },
            escapeMarkup: function (m) { return m; },
        });

        function initRowSelect2(row) {
            $(row).find('.parameter-select').select2({
                width: '100%',
                placeholder: 'Pilih Parameter...',
                allowClear: true,
                ajax: {
                    url: "{{ route('testing-parameters.select2') }}",
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({ q: params.term }),
                    processResults: (data) => ({ results: data }),
                    cache: true,
                },
                language: { noResults: () => `<span>Tidak ditemukan. <a href="{{ route('testing-parameters.create') }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>` },
                escapeMarkup: (m) => m,
                dropdownParent: $(row).find('td').eq(3),
            });
            $(row).find('.unit-select').select2({
                width: '100%',
                placeholder: 'Pilih Unit...',
                allowClear: true,
                ajax: {
                    url: "{{ route('testing-units.select2') }}",
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({ q: params.term }),
                    processResults: (data) => ({ results: data }),
                    cache: true,
                },
                language: { noResults: () => `<span>Tidak ditemukan. <a href="{{ route('testing-units.create') }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>` },
                escapeMarkup: (m) => m,
                dropdownParent: $(row).find('td').eq(4),
            });
        }

        // Init first row
        $("#Table tbody tr").each(function () { initRowSelect2(this); });

        $(".btn-add-row").on("click", function () {
            let newRow = `
                <tr>
                    <input type="hidden" name="id_testing_item[]" value="">
                    <td class="row-number"></td>
                    <td><input type="text" name="judul_indonesia[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="judul_inggris[]" class="form-control form-control-sm"></td>
                    <td><select name="parameter[]" class="form-control form-control-sm parameter-select"></select></td>
                    <td><select name="unit[]" class="form-control form-control-sm unit-select"></select></td>
                    <td><input type="text" name="nilai[]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="keterangan[]" class="form-control form-control-sm"></td>
                    <td class="text-center"><input type="checkbox" name="status[]" value="1"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm btn-remove">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            const $newRow = $(newRow);
            $("#Table tbody").append($newRow);
            initRowSelect2($newRow[0]);
            updateRowNumbers();
        });

        $(document).on("click", ".btn-remove", function () {
            $(this).closest("tr").remove();
            updateRowNumbers();
        });

        function updateRowNumbers() {
            $("#Table tbody tr").each(function (i) {
                $(this).find(".row-number").text(i + 1);
            });
        }

        updateRowNumbers();
    });

    submitCreateForm({
        formId: "#testingPointForm",
        url: "{{ route('testing-points.store') }}",
        filepond: ".filepond",
        onSuccess: function (res) {
            window.location.href = "{{ route('testing-points.index') }}?open=" + res.id;
        },
    });
</script>
@endsection
