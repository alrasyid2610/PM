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
                <h4 class="mb-1">Tambah Sales Order</h4>
                <p class="text-muted mb-0">
                    Tambahkan data sales order baru.
                </p>
            </div>

            <a href="{{ url('sales-orders') }}" class="btn btn-secondary btn-sm">
                Kembali
            </a>
        </div>

        <form id="salesOrderForm">
            @csrf

            {{-- ============================= --}}
            {{-- INFORMASI SALES ORDER --}}
            {{-- ============================= --}}
            <div class="card mb-4">
                

                
                <div class="card-body">

                    {{-- ================= INFORMASI ORDER ================= --}}
                    <h6 class="fw-bold mb-3">Informasi Order</h6>

                    {{-- <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label required">No SO</label>
                        <div class="col-md-10">
                            <input type="text" name="no_so" class="form-control" required>
                        </div>
                    </div> --}}

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label required">Tanggal SO</label>
                        <div class="col-md-10">
                            <input type="date" name="tanggal_so" class="form-control" required>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">Judul Order</label>
                        <div class="col-md-10">
                            <input type="text" name="judul_order" class="form-control">
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-lg-2 col-form-label">Tidak Ada PO</label>
                        <div class="col-md-2">
                            <select name="tidak_ada_po" class="form-select">
                                <option value="1">Ada PO</option>
                                <option value="0">Tidak Ada PO</option>
                            </select>
                        </div>

                        <label class="form-label col-md-2 col-form-label">No PO</label>
                        <div class="col-md-4">
                            <input type="text" name="no_po" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">Tanggal PO</label>
                        <div class="col-md-4">
                            <input type="date" name="tanggal_po" class="form-control">
                        </div>
                    </div>

                    <hr>

                    {{-- ================= DELIVERY ================= --}}
                    <h6 class="fw-bold mb-3">Delivery</h6>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">Tanggal Mulai</label>
                        <div class="col-md-4">
                            <input type="date" name="tanggal_mulai" class="form-control">
                        </div>

                        <label class="form-label col-md-2 col-form-label">Tanggal Selesai</label>
                        <div class="col-md-4">
                            <input type="date" name="tanggal_selesai" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">Office</label>
                        <div class="col-md-4">
                            <input type="number" name="id_office" class="form-control">
                        </div>
                    </div>

                    <hr>

                    {{-- ================= PELANGGAN ================= --}}
                    <h6 class="fw-bold mb-3">Pelanggan</h6>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label required">Pelanggan</label>
                        <div class="col-md-4">
                            <select name="id_pelanggan" id="id_pelanggan" class="form-select" required>
                                <option value="">Pilih Pelanggan</option>
                            </select>
                        </div>

                        <label class="form-label col-md-2 col-form-label">Site</label>
                        <div class="col-md-4">
                            {{-- <input type="number" name="id_site_pelanggan" class="form-control"> --}}
                            <select name="id_site_pelanggan" id="id_site_pelanggan" class="form-select">
                                <option value="">Pilih Site</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">PIC</label>
                        <div class="col-md-4">
                            <select name="id_pic_pelanggan" id="id_pic_pelanggan" class="form-select">
                                <option value="">Pilih PIC</option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    {{-- ================= Delivery ================= --}}
                    <h6 class="fw-bold mb-3">Delivery</h6>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">Pelanggan</label>
                        <div class="col-md-4">
                            <select name="id_pelanggan_delivery" class="form-select" required>
                                <option value="">Pilih Pelanggan</option>
                            </select>
                        </div>

                        <label class="form-label col-md-2 col-form-label">Site</label>
                        <div class="col-md-4">
                            {{-- <input type="number" name="id_site_pelanggan_delivery" class="form-control"> --}}

                            <select name="id_site_pelanggan_delivery" id="id_site_pelanggan_delivery" class="form-select">
                                <option value="">Pilih Site</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">PIC</label>
                        <div class="col-md-4">
                            <select name="id_pic_pelanggan_delivery" id="id_pic_pelanggan_delivery" class="form-select">
                                <option value="">Pilih PIC</option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    {{-- ================= PAYMENT ================= --}}
                    <h6 class="fw-bold mb-3">Payment</h6>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">Pelanggan</label>
                        <div class="col-md-4">
                            <select name="id_pelanggan_payment" class="form-select" required>
                                <option value="">Pilih Pelanggan</option>
                            </select>
                        </div>

                        <label class="form-label col-md-2 col-form-label">Site</label>
                        <div class="col-md-4">
                            {{-- <input type="number" name="id_site_pelanggan_payment" class="form-control"> --}}
                            <select name="id_site_pelanggan_payment" id="id_site_pelanggan_payment_select" class="form-select">
                                <option value="">Pilih Site</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">PIC</label>
                        <div class="col-md-4">
                            <select name="id_pic_pelanggan_payment" id="id_pic_pelanggan_payment" class="form-select">
                                <option value="">Pilih PIC</option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    {{-- ================= PIC INTERNAL ================= --}}
                    <h6 class="fw-bold mb-3">PIC Internal</h6>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">PIC Input</label>
                        <div class="col-md-4">
                            <input type="text" name="pic_input" class="form-control">
                        </div>

                        <label class="form-label col-md-2 col-form-label">PIC Order</label>
                        <div class="col-md-4">
                            <input type="text" name="pic_order" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">Marketing Internal</label>
                        <div class="col-md-4">
                            <input type="text" name="pic_marketing_internal" class="form-control">
                        </div>

                        <label class="form-label col-md-2 col-form-label">Marketing Eksternal</label>
                        <div class="col-md-4">
                            <input type="text" name="pic_marketing_eksternal" class="form-control">
                        </div>
                    </div>

                    <hr>

                    {{-- ================= STATUS ================= --}}
                    <h6 class="fw-bold mb-3">Status</h6>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">Status</label>
                        <div class="col-md-4">
                            <input type="text" name="status" class="form-control" value="Draft">
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">Keterangan Status</label>
                        <div class="col-md-10">
                            <textarea name="keterangan_status" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="form-label col-md-2 col-form-label">Keterangan</label>
                        <div class="col-md-10">
                            <textarea name="keterangan" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                </div>


            </div>

            



            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                    Simpan Sales Order
                </button>
            </div>

        </form>
    </div>
</section>
@endsection

@section('custom-script')
<script>

    var dataPelanggan = '';

    $(document).ready(function() {
        loadPelangganDetails();

        $("#id_pelanggan").on('select2:select', function(e) {
            var data = e.params.data;
            console.log('Data pelanggan yang dipilih:', data);
            console.log(data.id);
            
            $("select[name='id_pelanggan_delivery']")
                .val(data.id)
                .trigger('change');

            $("select[name='id_pelanggan_payment']")
                .val(data.id)
                .trigger('change');
            
            console.log($("select[name='id_pelanggan_delivery']").find("option[value='" + data.id + "']").length);
                
            $.ajax({
                url: "{{ url('/api/get-contact-site') }}" + '/' + data.id, // Kirim ID site yang dipilih
                method: "GET",
                success: function(response) {
                    console.log('Data kontak pelanggan berhasil dimuat:', response);
                    $("#id_pic_pelanggan").empty().append('<option value="">Pilih PIC</option>'); // Reset options PIC pelanggan
                    $.each(response, function(index, contact) {
                        $("#id_pic_pelanggan").append(new Option(contact.nama_pic, contact.id_contact));
                    });

                    $("#id_pic_pelanggan").select2({
                        placeholder: "Pilih PIC",
                        allowClear: true
                    });

                    

                    // Lakukan sesuatu dengan data kontak, misalnya tampilkan di form
                },
                error: function(xhr) {
                    Notify.error('Gagal memuat kontak pelanggan');
                }
            })
        });
        
        
    });

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

                $.each(dataPelanggan, function(index, item) {
                    $("select[name='id_pelanggan_delivery']")
                        .append(new Option(item.text, item.id));
                });

                $.each(dataPelanggan, function(index, item) {
                    $("select[name='id_pelanggan_payment']")
                        .append(new Option(item.text, item.id));
                });

                // Initialize select2 for delivery and payment
                // Baru init select2 TANPA data:
                 $("select[name='id_pelanggan']").select2({
                    placeholder: "Pilih Pelanggan",
                    allowClear: true
                });

                 $("select[name='id_pelanggan_delivery']").select2({
                    placeholder: "Pilih Pelanggan",
                    allowClear: true
                });

                 $("select[name='id_pelanggan_payment']").select2({
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

                $.each(dataPelanggan, function(index, item) {
                    $("select[name='id_site_pelanggan_delivery']")
                        .append(new Option(item.nama_lokasi, item.id_site));
                });

                $.each(dataPelanggan, function(index, item) {
                    $("select[name='id_site_pelanggan_payment']")
                        .append(new Option(item.nama_lokasi, item.id_site));
                });

                // Initialize select2 for delivery and payment
                // Baru init select2 TANPA data:
                 $("select[name='id_site_pelanggan']").select2({
                    placeholder: "Pilih Pelanggan",
                    allowClear: true
                });

                 $("select[name='id_site_pelanggan_delivery']").select2({
                    placeholder: "Pilih Pelanggan",
                    allowClear: true
                });

                 $("select[name='id_site_pelanggan_payment']").select2({
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

    
    
    
    
    $('#salesOrderForm').submit(function(e) {
        e.preventDefault();
        
        Notify.confirm('Simpan Data?', function() {
            $.ajax({
                url: "{{ url('sales-orders') }}",
                method: "POST",
                data: $('#salesOrderForm').serialize(),
                success: function(response) {
                    Notify.success('Sales order berhasil disimpan');
                    // window.location.href = "{{ url('sales-orders') }}";
                },
                error: function(xhr) {
                    Notify.error('Gagal menyimpan sales order');
                }
            });
        });

    });

</script>
@endsection

