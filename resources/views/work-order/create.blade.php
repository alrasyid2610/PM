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

        <form id="workOrderForm">
            @csrf

            {{-- ============================= --}}
            {{-- INFORMASI SALES ORDER --}}
            {{-- ============================= --}}
            <div class="card mb-4">
                
                <div class="card-body">

                    {{-- ================= INFORMASI ORDER ================= --}}
                    <h6 class="fw-bold mb-3">Sales Order</h6>
                    <div class="row mb-4">
                        <div class="col-md-12 col-lg-3 mb-3">
                            <label class="form-label required">Sales Order</label>
                            <select name="id_sales_order" id="id_sales_order" class="form-select" style="width:100%" required>
                                {{-- <option value="">Pilih Sales Order</option> --}}
                            </select>
                        </div>

                        <div class="col-md-12 col-lg-3 mb-3">
                            <label class="form-label required">Tanggal SO</label>
                            <input type="date" name="tanggal_so" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4 col-lg-3 mb-3">
                            <label class="form-label required">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control">
                        </div>

                        <div class="col-md-4 col-lg-3 mb-3">
                            <label class="form-label required">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control">
                        </div>

                        <div class="col-md-12 col-lg-9 mb-3">
                            <label class="form-label required">Judul Order</label>
                            <input type="text" name="judul_order" class="form-control">
                        </div>

                        <div class="col-md-12 col-lg-3 mb-3">
                            <label class="form-label">PIC Pekerjaan</label>
                            <input type="text" name="pic_pekerjaan" class="form-control">
                        </div>

                        <div class="col-md-12 col-lg-6 mb-3">
                            <label class="form-label required">Pelanggan</label>
                            <select name="id_pelanggan" class="form-select" required>
                                <option value="">Pilih Pelanggan</option>
                            </select>
                        </div>

                        <div class="col-md-12 col-lg-6 mb-3">
                            <label class="form-label required">Pelanggan Site</label>
                            <select name="id_site_pelanggan" class="form-select" required>
                                <option value="">Pilih Pelanggan Site</option>
                            </select>
                        </div>
                        
                    </div>

                    <h6 class="fw-bold">PO</h6>
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-4 col-lg-2">
                            <label class="form-label">Tidak Ada PO</label>
                            <select name="tidak_ada_po" class="form-select">
                                <option value="" selected>Pilih</option>
                                <option value="1">Ada PO</option>
                                <option value="0">Tidak Ada PO</option>
                            </select>
                        </div>

                        <div class="col-md-4 col-lg-4">
                            <label class="form-label">Tanggal PO</label>
                            <input type="date" name="tanggal_po" class="form-control">
                        </div>

                        <div class="col-md-4 col-lg-6 mb-3">
                            <label class="form-label">No PO</label>
                            <input type="text" name="no_po" class="form-control">
                        </div>

                    </div>

                    <h6 class="fw-bold">Lainnya</h6>
                    <div class="row mb-4">
                        <div class="col-md-12 col-lg-12 mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" id="keterangan" cols="30" rows="6" class="form-control"></textarea>
                        </div>
                    </div>

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


{{-- <div class="modal fade" id="modalSalesOrder" tabindex="-1" aria-labelledby="modalSalesOrderLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"> <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSalesOrderLabel">Cari Sales Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Masukkan Nomor SO atau Nama Customer..." id="inputQuerySO">
          <button class="btn btn-outline-secondary" type="button" id="btnFilterSO">Cari</button>
        </div>

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>No. SO</th>
                <th>Tanggal</th>
                <th>Customer</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="resultSalesOrder">
              <tr>
                <td colspan="4" class="text-center text-muted">Belum ada data.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div> --}}
@endsection

@section('custom-script')
<script>

    var dataPelanggan = '';
    var dataSO = '';

    
    
    $(document).ready(function() {
        
        loadPelangganDetails();
        initBrSelect2();


        $('#id_sales_order').on('select2:select', function (e) {
            var data = e.params.data;

            getSO(data).then(function(response) {
                dataSO = response;

                console.log('Data sales order berhasil dimuat:', dataSO);
                
                if(dataSO) {
                    // Isi field tanggal_so
                    $("input[name='tanggal_so']").val(dataSO.tanggal_so);
                    $("input[name='judul_order']").val(dataSO.judul_order);
                    $("input[name='tanggal_mulai']").val(dataSO.tanggal_mulai);
                    $("input[name='tanggal_selesai']").val(dataSO.tanggal_selesai);
                    $("select[name='tidak_ada_po']").val(dataSO.tidak_ada_po);
                    $("input[name='tanggal_po']").val(dataSO.tanggal_po);
                    $("input[name='no_po']").val(dataSO.no_po);
                    $("select[name='id_pelanggan']").val(dataSO.id_pelanggan).trigger('change');
                    $("select[name='id_site_pelanggan']").val(dataSO.id_site_pelanggan).trigger('change');
                }
                
            });
        });


        $("#id_sales_order").on('select2:clear', function (e) {
            clearForm();
        });

    });


    function clearForm() {
        $('#workOrderForm')[0].reset();
        $('#id_sales_order').val(null).trigger('change');
        $('#id_pelanggan').val(null).trigger('change');
        $('#id_site_pelanggan').val(null).trigger('change');
    }


    function getSO(data) {
        return $.ajax({
                url: "{{ url('sales-orders') }}/" + data.id,
                type: "GET",
                success: function(response) {
                    result = response;
                },
                error: function(xhr) {
                    Notify.error('Gagal mengambil data sales order');
                }
            })
    }

    
    function initBrSelect2() {
        $('#id_sales_order').select2({
            placeholder: 'Pilih atau ketik Sales Order',
            // theme: 'bootstrap-5', // Tambahkan jika pakai Bootstrap 5 agar rapi
            // tags: true,
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: "{{ route('sales-orders.select2') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    // Jika backend sudah mengirim 'id' dan 'text', ini sudah cukup:
                    return { results: data };
                },
                cache: true
            }
        });
    }


    function loadPelangganDetails() {

        // Console log untuk memastikan fungsi dipanggil
        console.log('Memuat data pelanggan...');
        
        $.ajax({
            url: "{{ route('api.get-data-br') }}",
            method: "GET",
            success: function(response) {
                dataPelanggan = response;
                console.log('Data pelanggan busines relation berhasil dimuat dan select2 diisi.');
                console.log('init select2');
                // Populate select2 for pelanggan
                $.each(dataPelanggan, function(index, item) {
                    $("select[name='id_pelanggan']")
                        .append(new Option(item.text, item.id));
                });
                // Initialize select2 for delivery and payment
                // Baru init select2 TANPA data:
                 $("select[name='id_pelanggan']").select2({
                    placeholder: "Pilih Pelanggan",
                    allowClear: true
                });

                console.log('Select2 berhasil diinisialisasi dengan data pelanggan.');
            },
            error: function(xhr) {
                Notify.error('Gagal memuat detail pelanggan');
            }
        });
        
        
        $.ajax({
            url: "{{ route('api.get-data-site') }}",
            method: "GET",
            success: function(response) {
                dataPelanggan = response;
                console.log('Data pelanggan berhasil dimuat dan select2 diisi.');

                console.log('init select2', response);
                
                // Populate select2 for pelanggan
                // Populate select2 for pelanggan
                $.each(dataPelanggan, function(index, item) {
                    $("select[name='id_site_pelanggan']")
                        .append(new Option(item.nama_lokasi, item.id_site));
                });

                // Initialize select2 for delivery and payment
                // Baru init select2 TANPA data:
                 $("select[name='id_site_pelanggan']").select2({
                    placeholder: "Pilih Pelanggan",
                    allowClear: true
                });
                
                console.log('Select2 berhasil diinisialisasi dengan data pelanggan.');
            },
            error: function(xhr) {
                Notify.error('Gagal memuat detail pelanggan');
            }
        });

    }

    $('#workOrderForm').submit(function(e) {
        e.preventDefault();
        
        Notify.confirm('Simpan Data?', function() {
            $.ajax({
                url: "{{ url('work-orders') }}",
                method: "POST",
                data: $('#workOrderForm').serialize(),
                success: function(response) {
                    Notify.success('Work order berhasil disimpan');
                    // window.location.href = "{{ url('work-orders') }}";
                },
                error: function(xhr) {
                    Notify.error('Gagal menyimpan work order');
                }
            });
        });

    });
    
</script>
@endsection

