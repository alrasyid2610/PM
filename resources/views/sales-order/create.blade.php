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
                        <input type="text" name="tanggal_so" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off" required>
                    </div>
                    <div class="col-md-9 col-12">
                        <label class="form-label required">Judul Order</label>
                        <input type="text" name="judul_order" class="form-control" required>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="text" name="tanggal_mulai" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="text" name="tanggal_selesai" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Office</label>
                        <select name="id_office" class="form-select">
                            <option value="">Pilih Office</option>
                            <option value="1">Pramatek Jakarta</option>
                            <option value="2">Pramatek Bandung</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label">Sales Contract</label>
                        <select name="id_sc" id="id_sc" class="form-select">
                            <option value=""></option>
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
                        <input type="text" name="tanggal_po" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off">
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
                        <i class="fa-solid fa-file-invoice me-1"></i> Data Pemesan
                    </div>
                    <div class="detail-party-label">
                        <i class="fa-solid fa-truck me-1"></i> Data Pengiriman
                    </div>
                    <div class="detail-party-label">
                        <i class="fa-solid fa-money-bill me-1"></i> Data Pembayaran
                    </div>
                </div>
    
                <!-- Pelanggan -->
                <div class="row g-3 mb-1">
                    <div class="col-12 d-md-none">
                        <div class="detail-mobile-section-label">
                            <i class="fa-solid fa-file-invoice me-1"></i> Data Pemesan
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
                            <i class="fa-solid fa-truck me-1"></i> Data Pengiriman
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
                            <i class="fa-solid fa-money-bill me-1"></i> Data Pembayaran
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
                    <div class="col-md-12 col-12">
                        <label class="form-label">Keterangan Status</label>
                        <textarea name="keterangan_status" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Cara Pembayaran</label>
                        <textarea name="cara_pembayaran" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ url('sales-orders') }}" submit-label="Simpan Sales Order" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    var dataPelanggan = '';

    $(document).ready(function () {
        initFpDate(document);
        $('select[name="id_office"]').select2({ placeholder: 'Pilih Office', allowClear: true, width: '100%' });

        $('#id_sc').select2({
            width: '100%',
            placeholder: 'Cari no. kontrak atau nama pelanggan...',
            allowClear: true,
            ajax: {
                url: "{{ url('contracts/select2') }}",
                dataType: 'json',
                delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
                cache: true,
            },
            language: {
                noResults: function () {
                    return `<span>Tidak ditemukan. <a href="{{ route('contracts.create') }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`;
                },
            },
            escapeMarkup: function (m) { return m; },
        });

        loadPelangganDetails();
        initPicInternal();

        // Daftar PIC selalu ikut union Perusahaan yang sedang dipilih di ketiga
        // kategori — dibaca ulang tiap select2 dibuka/diketik, jadi tidak perlu
        // di-reinit manual saat salah satu Perusahaan berubah.
        initPicSelect('#id_pic_pelanggan', 'Pilih PIC');
        initPicSelect('#id_pic_pelanggan_delivery', 'Pilih PIC');
        initPicSelect('#id_pic_pelanggan_payment', 'Pilih PIC');

        // Begitu Perusahaan & Site Pemesan sudah lengkap → salin ke Pengiriman
        // & Pembayaran (PIC ikut kalau sudah dipilih juga). Hanya mengisi field
        // yang MASIH KOSONG di kategori tujuan, tidak menimpa yang sudah diisi manual.
        ['#id_pelanggan', 'select[name="id_site_pelanggan"]', '#id_pic_pelanggan'].forEach(function (sel) {
            $(sel).on('select2:select', syncPemesanToOthers);
        });

        // Ganti Perusahaan di suatu kategori → kosongkan PIC kategori itu
        // (PIC lama sudah tentu tidak valid lagi untuk Perusahaan yang baru)
        const companyPicPairs = [
            ['#id_pelanggan', '#id_pic_pelanggan'],
            ['select[name="id_pelanggan_delivery"]', '#id_pic_pelanggan_delivery'],
            ['select[name="id_pelanggan_payment"]', '#id_pic_pelanggan_payment'],
        ];
        companyPicPairs.forEach(function (pair) {
            $(pair[0]).on('select2:select select2:clear', function () {
                $(pair[1]).val(null).trigger('change');
            });
        });
    });

    // Ambil {id, text} dari select2 yang sedang terpilih. null kalau belum diisi.
    function getSelect2SelectedData(selector) {
        const $el = $(selector);
        if (!$el.length || !$el.val()) return null;
        const data = $el.select2('data');
        if (!data || !data.length) return null;
        return { id: $el.val(), text: data[0].text };
    }

    // Begitu Perusahaan & Site Pemesan sudah lengkap, salin ke Pengiriman &
    // Pembayaran yang masih kosong (Perusahaan, Site, dan PIC kalau sudah ada).
    function syncPemesanToOthers() {
        const company = getSelect2SelectedData('#id_pelanggan');
        const site    = getSelect2SelectedData('select[name="id_site_pelanggan"]');
        if (!company || !site) return;

        const pic = getSelect2SelectedData('#id_pic_pelanggan');

        const targets = [
            { company: 'select[name="id_pelanggan_delivery"]', site: 'select[name="id_site_pelanggan_delivery"]', pic: '#id_pic_pelanggan_delivery' },
            { company: 'select[name="id_pelanggan_payment"]',  site: 'select[name="id_site_pelanggan_payment"]',  pic: '#id_pic_pelanggan_payment' },
        ];

        targets.forEach(function (t) {
            const $c = $(t.company);
            if ($c.length && !$c.val()) {
                $c.append(new Option(company.text, company.id, true, true)).trigger('change');
            }
            const $s = $(t.site);
            if ($s.length && !$s.val()) {
                $s.append(new Option(site.text, site.id, true, true)).trigger('change');
            }
            if (pic) {
                const $p = $(t.pic);
                if ($p.length && !$p.val()) {
                    $p.append(new Option(pic.text, pic.id, true, true)).trigger('change');
                }
            }
        });
    }

    // PIC yang muncul = union dari semua Perusahaan yang sudah dipilih
    // di ketiga kategori (Pemesan, Pengiriman, Pembayaran) — bukan cuma
    // perusahaan di kategorinya sendiri.
    function getSelectedCompanyIds() {
        return [
            $('#id_pelanggan').val(),
            $('select[name="id_pelanggan_delivery"]').val(),
            $('select[name="id_pelanggan_payment"]').val(),
        ].filter(function (v) { return !!v; });
    }

    function initPicSelect(selector, placeholder) {
        const $el = $(selector);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }

        $el.select2({
            placeholder: placeholder,
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('business-relation-contacts.select2') }}",
                dataType: 'json',
                delay: 200,
                data: function (params) {
                    return { q: params.term || '', id_br: getSelectedCompanyIds(), with_site: 1 };
                },
                processResults: function (data) {
                    return { results: data };
                },
                cache: false,
            },
            language: {
                noResults: function () {
                    return '<span>Tidak ditemukan. <a href="{{ route("business-relation-contacts.create") }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>';
                },
            },
            escapeMarkup: function (m) { return m; },
        });
    }

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

    function initPicInternal() {
        const selects = [
            { id: "#pic_input",               placeholder: "Pilih PIC Input"           },
            { id: "#pic_order",               placeholder: "Pilih PIC Order"           },
            { id: "#pic_marketing_internal",  placeholder: "Pilih Marketing Internal"  },
            { id: "#pic_marketing_eksternal", placeholder: "Pilih Marketing Eksternal" },
        ];

        selects.forEach(function (item) {
            $(item.id).select2({
                placeholder: item.placeholder,
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: "{{ route('users.select2') }}",
                    dataType: 'json',
                    delay: 200,
                    data: function (params) { return { q: params.term || '' }; },
                    processResults: function (data) { return { results: data }; },
                    cache: true,
                },
            });
        });
    }

    submitCreateForm({
        formId: "#salesOrderForm",
        url: "{{ url('sales-orders') }}",
        redirect: "{{ url('sales-orders') }}",
    });
</script>
@endsection