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
    <form id="salesOrderForm" class="row g-3">
        @csrf

        <!-- SECTION 1: INFORMASI ORDER -->
        <div class="col-12">
            <x-section-card icon="fa-file-lines" color="icon-navy" title="Informasi Order" subtitle="Data utama sales order">
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
            </x-section-card>
        </div>

        <!-- SECTION 2: PURCHASE ORDER -->
        <div class="col-12">
            <x-section-card icon="fa-receipt" color="icon-amber" title="Purchase Order (PO)" subtitle="Referensi PO dari pelanggan">
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
            </x-section-card>
        </div>

        <!-- SECTION 3: DATA PELANGGAN -->
        <div class="col-12">
            <x-section-card icon="fa-building-user" color="icon-blue" title="Data Pelanggan" subtitle="Billing, Delivery & Payment">
    
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
    
            </x-section-card>
        </div>

        <!-- SECTION 4: PIC INTERNAL -->
        <div class="col-12">
            <x-section-card icon="fa-users" color="icon-green" title="PIC Internal" subtitle="Penanggung jawab dari Pramatek">
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
            </x-section-card>
        </div>

        <!-- SECTION 5: STATUS & KETERANGAN -->
        <div class="col-12">
            <x-section-card icon="fa-circle-info" color="icon-purple" title="Status & Keterangan">
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
            </x-section-card>
        </div>

        <!-- SECTION 6: PERIOD JADWAL -->
        <div class="col-12">
            <x-section-card icon="fa-calendar-days" color="icon-navy" title="Period Jadwal" subtitle="Jadwal kunjungan per lokasi (opsional)">
                <div id="createPeriodList" class="mb-2"></div>

                <button type="button" id="btnAddCreatePeriod" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Period
                </button>

                <div id="createPeriodForm" class="mt-3 d-none p-3" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                    <div class="small fw-semibold text-muted mb-3">
                        <i class="fa-solid fa-calendar-plus me-1"></i> Data Period
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm text-muted mb-1">Nama Period <span class="text-danger">*</span></label>
                            <input type="text" id="createPeriodNama" class="form-control form-control-sm" placeholder="cth: Maintenance Semester 1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label form-label-sm text-muted mb-1">Lokasi (Site) <span class="text-muted fst-italic" style="font-size:10px;">opsional</span></label>
                            <select id="createPeriodSite" style="width:100%"></select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label form-label-sm text-muted mb-1">Tanggal Mulai</label>
                            <input type="date" id="createPeriodMulai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label form-label-sm text-muted mb-1">Tanggal Selesai</label>
                            <input type="date" id="createPeriodSelesai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm text-muted mb-1">Frekuensi</label>
                            <select id="createPeriodInterval" class="form-select form-select-sm">
                                <option value="">Pilih...</option>
                                <option value="1">Bulanan</option>
                                <option value="2">Bimulanan</option>
                                <option value="3">Triwulan</option>
                                <option value="4">Caturwulan</option>
                                <option value="6">Semester</option>
                                <option value="12">Annual</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label form-label-sm text-muted mb-1">Keterangan</label>
                            <input type="text" id="createPeriodKet" class="form-control form-control-sm" placeholder="opsional">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" id="btnCancelCreatePeriod" class="btn btn-sm btn-outline-secondary">Batal</button>
                        <button type="button" id="btnSaveCreatePeriod" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-check me-1"></i> Tambahkan
                        </button>
                    </div>
                </div>

                <input type="hidden" name="periods_json" id="periodsJson" value="[]">
            </x-section-card>
        </div>

        <!-- ACTION BUTTONS -->
        <x-form-actions back-route="{{ url('sales-orders') }}" submit-label="Simpan Sales Order" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    var dataPelanggan = '';

    $(document).ready(function () {
        $('select[name="id_office"]').select2({ placeholder: 'Pilih Office', allowClear: true, width: '100%' });

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

    // ── Period Jadwal ────────────────────────────────────────────────────────────
    var INTERVAL_LABELS = {1:'Bulanan',2:'Bimulanan',3:'Triwulan',4:'Caturwulan',6:'Semester',12:'Annual'};
    var soCreatePeriods = [];

    function escPeriod(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function renderCreatePeriodList() {
        var $list = $('#createPeriodList');
        if (!soCreatePeriods.length) { $list.html(''); return; }
        $list.html(soCreatePeriods.map(function(p, idx) {
            var freqLabel = INTERVAL_LABELS[p.interval_bulan] || (p.interval_bulan ? p.interval_bulan + ' bln' : '—');
            var jadwal    = (p.tanggal_mulai ? p.tanggal_mulai.substring(0, 7) : '—') +
                            ' s/d ' +
                            (p.tanggal_selesai ? p.tanggal_selesai.substring(0, 7) : '—');
            return '<div class="d-flex align-items-center gap-3 py-2 px-3 mb-2" style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;">' +
                '<i class="fa-solid fa-calendar-days" style="color:#1a5fbe;font-size:13px;"></i>' +
                '<div class="flex-grow-1">' +
                    '<div class="small fw-semibold">' + escPeriod(p.nama_period) + '</div>' +
                    '<div class="small text-muted">' +
                        jadwal + ' &middot; ' + escPeriod(freqLabel) +
                        (p.site_name ? ' &middot; <i class="fa-solid fa-location-dot" style="font-size:10px;"></i> ' + escPeriod(p.site_name) : '') +
                    '</div>' +
                    (p.keterangan ? '<div class="small text-muted fst-italic">' + escPeriod(p.keterangan) + '</div>' : '') +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-create-period" data-idx="' + idx + '" style="font-size:11px;">' +
                    '<i class="fa-solid fa-times"></i>' +
                '</button>' +
            '</div>';
        }).join(''));
        $('#periodsJson').val(JSON.stringify(soCreatePeriods));
    }

    function initCreatePeriodSiteSelect2() {
        if ($('#createPeriodSite').hasClass('select2-hidden-accessible')) return;
        $('#createPeriodSite').select2({
            dropdownParent: $('#createPeriodForm'),
            placeholder: 'Ketik nama lokasi...',
            allowClear: true,
            ajax: {
                url: "{{ url('business-relations/sites/select2') }}",
                dataType: 'json',
                delay: 200,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data }; },
            },
        });
    }

    $('#btnAddCreatePeriod').on('click', function() {
        $('#createPeriodForm').removeClass('d-none');
        $(this).addClass('d-none');
        initCreatePeriodSiteSelect2();
    });

    $('#btnCancelCreatePeriod').on('click', function() {
        $('#createPeriodForm').addClass('d-none');
        $('#btnAddCreatePeriod').removeClass('d-none');
        $('#createPeriodNama').val('');
        $('#createPeriodSite').val(null).trigger('change');
        $('#createPeriodMulai, #createPeriodSelesai, #createPeriodKet').val('');
        $('#createPeriodInterval').val('');
    });

    $('#btnSaveCreatePeriod').on('click', function() {
        var namaPeriod = $('#createPeriodNama').val().trim();
        if (!namaPeriod) { Notify.error('Nama period wajib diisi'); return; }

        var siteId   = $('#createPeriodSite').val();
        var siteName = siteId ? $('#createPeriodSite option:selected').text() : '';

        soCreatePeriods.push({
            nama_period:     namaPeriod,
            id_site:         siteId   || null,
            site_name:       siteName || null,
            tanggal_mulai:   $('#createPeriodMulai').val()    || null,
            tanggal_selesai: $('#createPeriodSelesai').val()  || null,
            interval_bulan:  $('#createPeriodInterval').val() ? parseInt($('#createPeriodInterval').val()) : null,
            keterangan:      $('#createPeriodKet').val()      || null,
        });
        renderCreatePeriodList();

        $('#createPeriodNama').val('');
        $('#createPeriodSite').val(null).trigger('change');
        $('#createPeriodMulai, #createPeriodSelesai, #createPeriodKet').val('');
        $('#createPeriodInterval').val('');
        $('#createPeriodForm').addClass('d-none');
        $('#btnAddCreatePeriod').removeClass('d-none');
    });

    $(document).on('click', '.btn-remove-create-period', function() {
        var idx = parseInt($(this).data('idx'));
        soCreatePeriods.splice(idx, 1);
        renderCreatePeriodList();
    });
    // ─────────────────────────────────────────────────────────────────────────────

    submitCreateForm({
        formId: "#salesOrderForm",
        url: "{{ url('sales-orders') }}",
        redirect: "{{ url('sales-orders') }}",
    });
</script>
@endsection