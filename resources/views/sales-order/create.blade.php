@extends('layouts.app')

@section('page-title', 'Tambah Sales Order')
@section('page-descrip', 'Tambahkan data sales order baru')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ url('sales-orders') }}">Sales Orders</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Tambah</li>
@endsection

@section('content')
<section class="section">
    <form id="salesOrderForm">
        @csrf

        <!-- SECTION 1: INFORMASI ORDER -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-navy">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div class="detail-section-title">Informasi Order</div>
                <div class="detail-section-sub">Data utama sales order</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">
                    <div class="col-md-3 col-12">
                        <label class="form-label required">Tanggal SO</label>
                        <input type="date" name="tanggal_so" class="form-control" required>
                    </div>
                    <div class="col-md-9 col-12">
                        <label class="form-label required">Judul Order</label>
                        <input type="text" name="judul_order" class="form-control" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Office</label>
                        <select name="id_office" class="form-select">
                            <option value="">Pilih Office</option>
                            <option value="1">Pramatek Jakarta</option>
                            <option value="2">Pramatek Bandung</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: PURCHASE ORDER -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-amber">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div class="detail-section-title">Purchase Order (PO)</div>
                <div class="detail-section-sub">Referensi PO dari pelanggan</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="tidak_ada_po"
                                id="tidak_ada_po"
                                class="form-check-input"
                                value="1">
                            <label class="form-check-label" for="tidak_ada_po">
                                Ada PO
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Tanggal PO</label>
                        <input type="date" name="tanggal_po" class="form-control">
                    </div>
                    <div class="col-md-9 col-12">
                        <label class="form-label">No PO</label>
                        <input type="text" name="no_po" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3: DATA PELANGGAN -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-blue">
                    <i class="fa-solid fa-building-user"></i>
                </div>
                <div class="detail-section-title">Data Pelanggan</div>
                <div class="detail-section-sub">Billing, Delivery & Payment</div>
            </div>
            <div class="detail-section-body">

                <!-- Party header desktop -->
                <div class="detail-party-header d-none d-md-grid">
                    <div class="detail-party-label">
                        <i class="fa-solid fa-file-invoice me-1"></i> Billing (Pemesan)
                    </div>
                    <div class="detail-party-label">
                        <i class="fa-solid fa-truck me-1"></i> Delivery (Pengiriman)
                    </div>
                    <div class="detail-party-label">
                        <i class="fa-solid fa-money-bill me-1"></i> Payment (Pembayaran)
                    </div>
                </div>

                <!-- Pelanggan -->
                <div class="row g-3 mb-1">
                    <div class="col-12 d-md-none">
                        <div class="detail-mobile-section-label">
                            <i class="fa-solid fa-file-invoice me-1"></i> Billing (Pemesan)
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Perusahaan</label>
                        <select name="id_pelanggan" id="id_pelanggan" class="form-select" required>
                            <option value="">Pilih Pelanggan</option>
                        </select>
                    </div>
                    <div class="col-12 d-md-none">
                        <div class="detail-mobile-section-label">
                            <i class="fa-solid fa-truck me-1"></i> Delivery (Pengiriman)
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Perusahaan</label>
                        <select name="id_pelanggan_delivery" class="form-select">
                            <option value="">Pilih Pelanggan</option>
                        </select>
                    </div>
                    <div class="col-12 d-md-none">
                        <div class="detail-mobile-section-label">
                            <i class="fa-solid fa-money-bill me-1"></i> Payment (Pembayaran)
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Perusahaan</label>
                        <select name="id_pelanggan_payment" class="form-select">
                            <option value="">Pilih Pelanggan</option>
                        </select>
                    </div>
                </div>

                <!-- Site -->
                <div class="row g-3 mb-1">
                    <div class="col-md-4 col-12">
                        <label class="form-label">Site</label>
                        <select name="id_site_pelanggan" id="id_site_pelanggan" class="form-select">
                            <option value="">Pilih Site</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Site</label>
                        <select name="id_site_pelanggan_delivery" id="id_site_pelanggan_delivery" class="form-select">
                            <option value="">Pilih Site</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Site</label>
                        <select name="id_site_pelanggan_payment" id="id_site_pelanggan_payment_select" class="form-select">
                            <option value="">Pilih Site</option>
                        </select>
                    </div>
                </div>

                <!-- PIC — Section 3 -->
                <div class="row g-3">
                    <div class="col-md-4 col-12">
                        <label class="form-label">PIC</label>
                        <select name="id_pic_pelanggan"
                            id="id_pic_pelanggan"
                            class="form-select">
                            <option value="">Pilih PIC</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">PIC</label>
                        <select name="id_pic_pelanggan_delivery"
                            id="id_pic_pelanggan_delivery"
                            class="form-select">
                            <option value="">Pilih PIC</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">PIC</label>
                        <select name="id_pic_pelanggan_payment"
                            id="id_pic_pelanggan_payment"
                            class="form-select">
                            <option value="">Pilih PIC</option>
                        </select>
                    </div>
                </div>

            </div>
        </div>

        <!-- SECTION 4: PIC INTERNAL -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-green">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="detail-section-title">PIC Internal</div>
                <div class="detail-section-sub">Penanggung jawab dari Pramatek</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">
                    <div class="col-md-3 col-12">
                        <label class="form-label">PIC Input</label>
                        <select name="pic_input" id="pic_input" class="form-select">
                            <option value="">Pilih PIC Input</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">PIC Order</label>
                        <select name="pic_order" id="pic_order" class="form-select">
                            <option value="">Pilih PIC Order</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Marketing Internal</label>
                        <select name="pic_marketing_internal" id="pic_marketing_internal" class="form-select">
                            <option value="">Pilih Marketing Internal</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Marketing Eksternal</label>
                        <select name="pic_marketing_eksternal" id="pic_marketing_eksternal" class="form-select">
                            <option value="">Pilih Marketing Eksternal</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 5: STATUS & KETERANGAN -->
        <div class="detail-section-card mb-3">
            <div class="detail-section-header">
                <div class="detail-section-icon icon-purple">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div class="detail-section-title">Status & Keterangan</div>
            </div>
            <div class="detail-section-body">
                <div class="row g-3">
                    <div class="col-md-3 col-12">
                        <label class="form-label">Status</label>
                        <input type="text" name="status" class="form-control" value="Draft" readonly>
                    </div>
                    <div class="col-md-9 col-12">
                        <label class="form-label">Keterangan Status</label>
                        <textarea name="keterangan_status" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ url('sales-orders') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Sales Order
            </button>
        </div>

    </form>
</section>
@endsection

@section('custom-script')
<script>
    var dataPelanggan = '';

    $(document).ready(function () {
        loadPelangganDetails();
        //  loadPICInternal();
        loadAllContacts();
    });

    function loadPelangganDetails() {
        $.ajax({
            url: "{{ route('api.get-data-br') }}",
            method: "GET",
            success: function (response) {
                dataPelanggan = response;

                const selects = [
                    "select[name='id_pelanggan']",
                    "select[name='id_pelanggan_delivery']",
                    "select[name='id_pelanggan_payment']",
                ];

                selects.forEach(function (sel) {
                    $.each(dataPelanggan, function (index, item) {
                        $(sel).append(new Option(item.text, item.id));
                    });
                    $(sel).select2({ placeholder: "Pilih Pelanggan", allowClear: true });
                });
            },
            error: function () {
                Notify.error('Gagal memuat data pelanggan');
            }
        });

        $.ajax({
            url: "{{ route('api.get-data-site') }}",
            method: "GET",
            success: function (response) {
                const selects = [
                    "select[name='id_site_pelanggan']",
                    "select[name='id_site_pelanggan_delivery']",
                    "select[name='id_site_pelanggan_payment']",
                ];

                selects.forEach(function (sel) {
                    $.each(response, function (index, item) {
                        $(sel).append(new Option(item.nama_lokasi, item.id_site));
                    });
                    $(sel).select2({ placeholder: "Pilih Site", allowClear: true });
                });
            },
            error: function () {
                Notify.error('Gagal memuat data site');
            }
        });
    }

    function loadPICInternal() {
        $.ajax({
            url: "{{ route('business-relation-contacts.select2') }}",
            method: "GET",
            data: { q: "" }, // ← kirim kosong agar return semua data
            success: function (response) {
                const selects = [
                    { id: "#pic_input",               placeholder: "Pilih PIC Input"            },
                    { id: "#pic_order",               placeholder: "Pilih PIC Order"            },
                    { id: "#pic_marketing_internal",  placeholder: "Pilih Marketing Internal"   },
                    { id: "#pic_marketing_eksternal", placeholder: "Pilih Marketing Eksternal"  },
                ];

                selects.forEach(function (item) {
                    // Populate options
                    $.each(response, function (index, opt) {
                        $(item.id).append(new Option(opt.text, opt.id));
                    });

                    // Init select2 — filter di client, tidak ada ajax
                    $(item.id).select2({
                        placeholder: item.placeholder,
                        allowClear: true,
                    });
                });
            },
            error: function () {
                Notify.error('Gagal memuat data PIC internal');
            }
        });
    }

    function loadAllContacts() {
        $.ajax({
            url: "{{ route('business-relation-contacts.select2') }}",
            method: "GET",
            data: { q: "" },
            success: function (response) {
                const selects = [
                    { id: "#id_pic_pelanggan",         placeholder: "Pilih PIC"                  },
                    { id: "#id_pic_pelanggan_delivery", placeholder: "Pilih PIC"                  },
                    { id: "#id_pic_pelanggan_payment",  placeholder: "Pilih PIC"                  },
                    { id: "#pic_input",                 placeholder: "Pilih PIC Input"            },
                    { id: "#pic_order",                 placeholder: "Pilih PIC Order"            },
                    { id: "#pic_marketing_internal",    placeholder: "Pilih Marketing Internal"   },
                    { id: "#pic_marketing_eksternal",   placeholder: "Pilih Marketing Eksternal"  },
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

    submitCreateForm({
        formId: "#salesOrderForm",
        url: "{{ url('sales-orders') }}",
        redirect: "{{ url('sales-orders') }}",
    });
</script>
@endsection