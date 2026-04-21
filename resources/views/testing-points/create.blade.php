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
    <form id="testingPointForm" enctype="multipart/form-data">
        @csrf

        <!-- SECTION 1: INFORMASI POINT -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-navy">
                    <i class="fa-solid fa-map-pin"></i>
                </div>
                <div class="detail-section-title">Testing Points</div>
                <div class="detail-section-sub">Data titik pengujian laboratorium</div>
            </div>
            <div class="detail-section-body">
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
            </div>
        </div>

        <!-- SECTION 2: ATTACHMENT -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-blue">
                    <i class="fa-solid fa-paperclip"></i>
                </div>
                <div class="detail-section-title">Attachment</div>
                <div class="detail-section-sub">File pendukung testing point</div>
            </div>
            <div class="detail-section-body">
                <input type="file" class="filepond" name="attachments[]" multiple>
            </div>
        </div>

        <!-- SECTION 3: TESTING ITEMS -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-green">
                    <i class="fa-solid fa-table-list"></i>
                </div>
                <div class="detail-section-title">Testing Items</div>
                <div class="detail-section-sub">Detail item pengujian per point</div>
                <button type="button" class="btn btn-primary btn-sm btn-add-row ms-2">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Baris
                </button>
            </div>
            <div class="detail-section-body p-0">
                <div class="dynamic-table-wrapper">
                    <div class="table-responsive">
                        <table id="Table" class="table table-bordered table-sm dynamic-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%">No</th>
                                    <th width="20%">Judul Indonesia</th>
                                    <th width="20%">Judul Inggris</th>
                                    <th width="12%">Parameter</th>
                                    <th width="10%">Unit</th>
                                    <th width="10%">Nilai</th>
                                    <th width="12%">Keterangan</th>
                                    <th width="8%">Status</th>
                                    <th width="5%">Aksi</th>
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
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('testing-points.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data
            </button>
        </div>

    </form>
</section>
@endsection

@section('custom-script')
<script>
    $(document).ready(function () {
        createFileUploader(".filepond");

        $("#id_testing_standard").select2({
            placeholder: "Pilih Testing Standard...",
            ajax: {
                url: "{{ route('testing-standards.data') }}",
                dataType: "json",
                processResults: (data) => ({
                    results: data.data.map((item) => ({
                        id: item.id_testing_standard,
                        text: item.nomor + " - " + item.judul,
                    })),
                }),
            },
        });

        $("#id_testing_matriks_sample").select2({
            placeholder: "Pilih Matriks Sample...",
            ajax: {
                url: "{{ route('testing-matriks-samples.data') }}",
                dataType: "json",
                processResults: (data) => ({
                    results: data.data.map((item) => ({
                        id: item.id_testing_matriks_sample,
                        text: item.kode + " - " + item.judul_indonesia,
                    })),
                }),
            },
        });

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
            $("#Table tbody").append(newRow);
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
        redirect: "{{ route('testing-points.index') }}",
        filepond: ".filepond",
    });
</script>
@endsection
