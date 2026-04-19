@extends('layouts.app')

@section('page-title', 'Work Orders')
@section('page-descrip', 'Kelola data Work Orders')

@section('breadcrumb')
    <li class="breadcrumb-item" aria-current="page">
        <a href="{{ url('work-orders') }}">Work Orders</a>
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
    <form id="workOrderForm">
        @csrf

        <!-- SECTION 1: INFORMASI WORK ORDER -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-navy">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
                <div class="detail-section-title">Informasi Work Order</div>
                <div class="detail-section-sub">Data pekerjaan lapangan</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">
                    <div class="col-md-12 col-12">
                        <label class="form-label required">Sales Order</label>
                        <select name="id_sales_order" id="id_sales_order" class="form-select" style="width:100%" required></select>
                    </div>
                    <div class="col-md-12 col-12">
                        <label class="form-label required">Judul Order</label>
                        <input type="text" name="judul_order" class="form-control">
                    </div>
                    {{-- <div class="col-md-4 col-12">
                        <label class="form-label required">Tanggal SO</label>
                        <input type="date" name="tanggal_so" class="form-control" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control">
                    </div> --}}

                    <div class="col-md-5 col-12">
                        <label class="form-label required">Pelanggan</label>
                        <select name="id_pelanggan" class="form-select" required>
                            <option value="">Pilih Pelanggan</option>
                        </select>
                    </div>
                    <div class="col-md-5 col-12">
                        <label class="form-label required">Pelanggan Site</label>
                        <select name="id_site_pelanggan" class="form-select" required>
                            <option value="">Pilih Pelanggan Site</option>
                        </select>
                    </div>

                     <div class="col-md-2 col-12">
                        <label class="form-label">PIC Pekerjaan</label>
                        <select name="pic_pekerjaan"
                            id="pic_pekerjaan"
                            class="form-select">
                            <option value="">Pilih PIC</option>
                        </select>
                    </div>


                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" rows="4"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ url('work-orders') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Work Order
            </button>
        </div>

    </form>
</section>
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

        loadAllContacts();

        $("#id_sales_order").on('select2:clear', function (e) {
            clearForm();
        });

    });

     function loadAllContacts() {
        $.ajax({
            url: "{{ route('business-relation-contacts.select2') }}",
            method: "GET",
            data: { q: "" },
            success: function (response) {
                const selects = [
                    { id: "#pic_pekerjaan",         placeholder: "Pilih PIC"                  },
                ];

                selects.forEach(function (item) {
                    $(item.id).append(new Option("", ""));
                    $.each(response, function (index, opt) {
                        $(item.id).append(new Option(opt.text, opt.id));
                    });
                    $(item.id).select2({
                        placeholder: item.placeholder,
                        allowClear: true,
                    });
                });
            },
            error: function () {
                Notify.error('Gagal memuat data PIC');
            }
        });
    }


    function clearForm() {
        $('#workOrderForm')[0].reset();
        $('#id_sales_order').val(null).trigger('change');
        $('#id_pelanggan').val('').trigger('change');
        $('#id_site_pelanggan').val('').trigger('change');

        $("select[name='id_pelanggan']").val(null).trigger('change');
        $("select[name='id_site_pelanggan']").val(null).trigger('change');
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
                    return { results: data };
                },
                cache: true
            }
        });
    }


    function loadPelangganDetails() {
        console.log('Memuat data pelanggan...');

        $.ajax({
            url: "{{ route('api.get-data-br') }}",
            method: "GET",
            success: function(response) {
                dataPelanggan = response;
                $.each(dataPelanggan, function(index, item) {
                    $("select[name='id_pelanggan']").append(new Option(item.text, item.id));
                });
                $("select[name='id_pelanggan']").select2({
                    placeholder: "Pilih Pelanggan",
                    allowClear: true
                });
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
                $.each(dataPelanggan, function(index, item) {
                    $("select[name='id_site_pelanggan']").append(new Option(item.nama_lokasi, item.id_site));
                });
                $("select[name='id_site_pelanggan']").select2({
                    placeholder: "Pilih Site Pelanggan",
                    allowClear: true
                });
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
