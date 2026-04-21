@extends('layouts.app')

@section('content')
<style>
    .required::after {
        content: " *";
        color: red;
    }

</style>


<section class="section">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Tambah {{ Str::title(str_replace('-', ' ', request()->segment(1))); }}</h4>
                <p class="text-muted mb-0">
                    Tambahkan data {{ Str::title(str_replace('-', ' ', request()->segment(1))); }} baru.
                </p>
            </div>

            <a href="{{ url('work-orders') }}" class="btn btn-secondary btn-sm">
                Kembali
            </a>
        </div>

        <form id="boqForm">
            @csrf

            {{-- ============================= --}}
            {{-- INFORMASI SALES ORDER --}}
            {{-- ============================= --}}
            <div class="card mb-4">
                
                <div class="card-body">

                    {{-- ================= INFORMASI ORDER ================= --}}
                    <h6 class="fw-bold mb-3">Work Order</h6>
                    <div class="row mb-4">
                        <div class="col-md-12 col-lg-3 mb-3">
                            <label class="form-label required">Work Order</label>
                            <select name="id_work_order" id="id_work_order" class="form-select" style="width:100%" required>
                                {{-- <option value="">Pilih Work Order</option> --}}
                            </select>
                        </div>

                        <div class="col-md-12 col-lg-9 mb-3">
                            <label class="form-label required">Judul Order</label>
                            <input type="text" name="judul_order" class="form-control">
                        </div>

                    </div>

                    <h6 class="fw-bold">BOQ</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="boqTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="30%">Item Produk</th>
                                    <th width="15%">Satuan</th>
                                    <th width="20%">Harga Satuan</th>
                                    <th width="25%">Keterangan</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Row awal -->
                                <tr>
                                    <td>
                                        <input type="text" name="item_produk[]" class="form-control" required>
                                    </td>
                                    <td>
                                        <select name="satuan_produk[]" class="form-select">
                                            <option value="">Pilih Satuan</option>
                                            <option value="Hari">Hari</option>
                                            <option value="Orang">Orang</option>
                                            <option value="pcs">pcs</option>
                                            <option value="kg">kg</option>
                                            <option value="m">m</option>
                                            <option value="set">set</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="harga_satuan_produk[]" class="form-select">
                                            <option value="">Pilih Harga Satuan</option>
                                            <option value="10000">10.000</option>
                                            <option value="50000">50.000</option>
                                            <option value="100000">100.000</option>
                                            <option value="500000">500.000</option>
                                            <option value="1000000">1.000.000</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="keterangan[]" class="form-control">
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

                    <button type="button" id="btnAddItem" class="btn btn-primary btn-sm mt-2 disabled">
                        <i class="fa-solid fa-plus"></i> Tambah Item
                    </button>


                </div>


            </div>

            



            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                    Simpan Work Order
                </button>
            </div>

        </form>
    </div>
</section>

@endsection

@section('custom-script')
<script>
    $(document).ready(function () {

        $("#id_work_order").select2({
            placeholder: "Pilih atau ketik Work Order",
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: "{{ route('work-orders.select2') }}",
                dataType: "json",
                delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
                cache: true,
            },
        });

        $("#id_work_order").on("select2:select", function (e) {
            $("input[name='judul_order']").val(e.params.data.judul);
        });

        $("#id_work_order").on("select2:clear", function () {
            $("#boqForm")[0].reset();
            $("#id_work_order").val(null).trigger("change");
        });

        $("#btnAddItem").on("click", function () {
            const newRow = $("#boqRowTemplate").html();
            $("#boqTable tbody").append(newRow);
        });

        $("#boqTable").on("click", ".btn-remove", function () {
            if ($("#boqTable tbody tr").length > 1) {
                $(this).closest("tr").remove();
            } else {
                Notify.warning("Minimal harus ada 1 item.");
            }
        });
    });

    submitCreateForm({
        formId: "#boqForm",
        url: "{{ url('boq') }}",
        redirect: "{{ url('work-orders') }}",
    });
</script>

<template id="boqRowTemplate">
    <tr>
        <td><input type="text" name="item_produk[]" class="form-control" required></td>
        <td>
            <select name="satuan_produk[]" class="form-select">
                <option value="">Pilih Satuan</option>
                <option value="Hari">Hari</option>
                <option value="Orang">Orang</option>
                <option value="pcs">pcs</option>
                <option value="kg">kg</option>
                <option value="m">m</option>
                <option value="set">set</option>
            </select>
        </td>
        <td>
            <select name="harga_satuan_produk[]" class="form-select">
                <option value="">Pilih Harga Satuan</option>
                <option value="10000">10.000</option>
                <option value="50000">50.000</option>
                <option value="100000">100.000</option>
                <option value="500000">500.000</option>
                <option value="1000000">1.000.000</option>
            </select>
        </td>
        <td><input type="text" name="keterangan[]" class="form-control"></td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm btn-remove">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

