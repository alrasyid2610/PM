@extends('layouts.app')

@section('content')
<section class="section">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Tambah Testing Item</h4>
                <p class="text-muted mb-0">
                    Tambahkan data testing item baru.
                </p>
            </div>

            <a href="{{ route('testing-items.index') }}"
                class="btn btn-secondary btn-sm">
                Kembali
            </a>
        </div>

        <form id="testingItemForm">
            @csrf

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">

                        <h6 class="font-bold">Data Poin</h6>
                        <div class="mb-3">
                            <label class="form-label required">Testing Point</label>
                            <select id="id_testing_point"
                                name="id_testing_point"
                                class="form-select"
                                required></select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Testing Point Deskripsi</label>
                            <p type="text"
                                step="any"
                                class="form-control"
                                id="testing_poin_deskripsi"
                                name="testing_poin_deskripsi" disabled>-</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Nomor Halaman</label>
                            <p type="text"
                                step="any"
                                class="form-control"
                                id="testing_poin_nomor_halaman"
                                name="testing_poin_nomor_halaman" disabled>-</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Keterangan</label>
                            <textarea class="form-control" id="testing_poin_keterangan" name="testing_poin_keterangan" disabled></textarea>
                        </div>

                        <h6 class="font-bold">Data Matriks Sample</h6>
                        <div class="mb-3 col-12 col-lg-4">
                            <label class="form-label required">Sample Kelompok Judul</label>
                            <p type="text"
                                step="any"
                                class="form-control"
                                id="kelompok_matriks_sample_judul_indonesia"
                                name="kelompok_matriks_sample_judul_indonesia" disabled>-</p>
                        </div>

                        <div class="mb-3 col-12 col-lg-4">
                            <label class="form-label required">Sample Kode</label>
                            <p type="text"
                                step="any"
                                class="form-control"
                                id="matriks_sample_kode"
                                name="matriks_sample_kode" disabled>-</p>
                        </div>

                        <div class="mb-3 col-12 col-lg-4">
                            <label class="form-label required">Sample Judul</label>
                            {{-- <select id="id_testing_matriks_sample"
                                    name="id_testing_matriks_sample"
                                    class="form-select"
                                    required></select> --}}

                            <p type="text"
                                step="any"
                                class="form-control"
                                id="matriks_sample_judul_indonesia"
                                name="matriks_sample_judul_indonesia" disabled>-</p>
                        </div>


                        <h6 class="font-bold">Data Standard</h6>
                        <div class="mb-3 col-12 col-lg-6">
                            <label class="form-label required">Matrik Standard Nomor</label>
                            <p type="text"
                                step="any"
                                class="form-control"
                                id="matrik_standard_nomor"
                                name="matrik_standard_nomor" disabled>-</p>
                        </div>

                        <div class="mb-3 col-12 col-lg-6">
                            <label class="form-label required">Matrik Standard Judul</label>
                            <p type="text"
                                step="any"
                                class="form-control"
                                id="matrik_standard_judul"
                                name="matrik_standard_judul" disabled>-</p>
                        </div>




                    </div>
                    {{-- End Row --}}

                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="dynamic-table-wrapper">

                        <button type="button" class="btn btn-primary btn-sm btn-add-row mb-2">
                            Tambah Baris
                        </button>

                        <div class="table-responsive">
                            <table id="itemsTable" class="table table-bordered table-sm dynamic-table">

                                <thead class="table-light">
                                    <tr>
                                        <th width="3%">No</th>
                                        <th width="25%">Judul Indonesia</th>
                                        <th width="25%">Judul Inggris</th>
                                        <th width="10%">Parameter</th>
                                        <th width="10%">Unit</th>
                                        <th width="10%">Nilai</th>
                                        <th width="10%">Keterangan</th>
                                        <th width="5%">Status</th>
                                        <th width="5%">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <tr>

                                        <td class="row-number"></td>

                                        <td>
                                            <input type="text" name="judul_indonesia[]" class="form-control">
                                        </td>

                                        <td>
                                            <input type="text" name="judul_inggris[]" class="form-control">
                                        </td>

                                        <td>
                                            <select name="parameter[]" class="form-control parameter-select"></select>
                                        </td>

                                        <td>
                                            <select name="unit[]" class="form-control unit-select"></select>
                                        </td>

                                        <td>
                                            <input type="text" name="nilai[]" class="form-control">
                                        </td>

                                        <td>
                                            <input type="text" name="keterangan[]" class="form-control">
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

            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                    Simpan Data
                </button>

                <a href="{{ route('testing-items.index') }}"
                    class="btn btn-secondary btn-sm">
                    Batal
                </a>
            </div>

        </form>

    </div>
</section>
@endsection

@section('custom-script')
<script type="text/template" id="row-template">

    <tr>

    <td class="row-number"></td>

    <td>
        <input type="text" name="judul_indonesia[]" class="form-control">
    </td>

    <td>
        <input type="text" name="judul_inggris[]" class="form-control">
    </td>

    <td>
        <select name="parameter[]" class="form-control parameter-select"></select>
    </td>

    <td>
        <select name="unit[]" class="form-control unit-select"></select>
    </td>

    <td>
        <input type="text" name="nilai[]" class="form-control">
    </td>

    <td>
        <input type="text" name="keterangan[]" class="form-control">
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

</script>



<script>
    let parameterData = [];
    let unitData = [];

    function loadMasterData() {

        $.getJSON("/testing-parameters/data", function(res) {

            parameterData = res.data.map(function(item) {
                return {
                    id: item.id_testing_parameter,
                    text: item.judul_indonesia
                };
            });

        });

        $.getJSON("/testing-units/data", function(res) {

            unitData = res.data.map(function(item) {
                return {
                    id: item.id_testing_unit,
                    text: item.kode + " - " + item.judul_indonesia
                };
            });

        });

    }


    $(document).ready(function() {
        setDynamicFormState(true);

        // Testing Matrik Sample
        $('#id_testing_matriks_sample').select2({
            placeholder: 'Pilih Testing Point...',
            allowClear: true,
            ajax: {
                url: "/testing-matriks-samples/data",
                dataType: 'json',
                processResults: function(data) {
                    return {
                        results: data.data.map(item => ({
                            id: item.id_testing_matriks_sample,
                            text: item.judul_indonesia,
                            kelompok: item.kelompok_matrik_judul_indonesia
                        }))
                    };
                }
            }
        });

        $('#id_testing_matriks_sample').on('select2:select', function(e) {
            let data = e.params.data;
            $('p[name="kelompok_matrik_judul_indonesia"]').text(data.kelompok);
        });

        let itemsTable = new DynamicTable({
            table: "#itemsTable",
            autoNumber: true
        });


        // Testing Point
        $('#id_testing_point').select2({
            placeholder: 'Pilih Testing Point...',
            allowClear: true,
            ajax: {
                url: "/testing-points/data",
                dataType: 'json',
                processResults: function(data) {
                    return {
                        results: data.data.map(item => (

                            {
                                id: item.id_testing_point,
                                text: item.nama + ' - ' + item.testing_poin_deskripsi,
                                nama_poin: item.nama,
                                testing_poin_nomor_halaman: item.testing_poin_nomor_halaman,
                                testing_poin_deskripsi: item.testing_poin_deskripsi,
                                testing_poin_keterangan: item.testing_poin_keterangan,
                                matrik_standard_nomor: item.matrik_standard_nomor,
                                matrik_standard_judul: item.matrik_standard_judul,
                                kelompok_matriks_sample_judul_indonesia: item.kelompok_matriks_sample_judul_indonesia,
                                matriks_sample_kode: item.matriks_sample_kode,
                                matriks_sample_judul_indonesia: item.matriks_sample_judul_indonesia,
                            }
                        ))
                    };
                }
            }
        });

        $('#id_testing_point').on('select2:select', function(e) {
            let data = e.params.data;

            let id = data.id;
            $.get('/testing-items/by-point/' + id, function(res) {
                itemsTable.loadData(res.data);
            });

            fillFormFromObject(data);
            setDynamicFormState(false);
        });

        $('#id_testing_point').on('select2:clear', function() {
            clearSiteField();
            setDynamicFormState(true);
        });

    });


    $('#testingItemForm').submit(function(e) {

        e.preventDefault();

        Notify.confirm('Simpan Data?', function() {

            $.ajax({
                url: "{{ route('testing-items.store') }}",
                method: "POST",
                data: $('#testingItemForm').serialize(),

                success: function(response) {
                    Notify.success('Testing item berhasil disimpan');
                    // optional redirect
                    // window.location.href = "{{ route('testing-items.index') }}";
                },

                error: function(xhr) {
                    Notify.error('Gagal menyimpan testing item');
                }
            });

        });

    });


    function clearSiteField() {
        const fields = [
            'id_testing_point', 'testing_poin_deskripsi', 'testing_poin_nomor_halaman',
            'testing_poin_keterangan', 'kelompok_matriks_sample_judul_indonesia', 'matriks_sample_kode',
            'matriks_sample_judul_indonesia', 'matrik_standard_nomor', 'matrik_standard_judul'
        ];

        fields.forEach(name => {
            $(`[name="${name}"]`).val('');
            $(`p[name="${name}"]`).text('-');
        });
    }

    function setDynamicFormState(disabled = true) {

        let table = $('.dynamic-table-wrapper');

        table.find('input, select, button').prop('disabled', disabled);

        // khusus select2 harus trigger ulang
        table.find('.parameter-select, .unit-select')
            .prop('disabled', disabled)
            .trigger('change.select2');

    }
</script>
@endsection