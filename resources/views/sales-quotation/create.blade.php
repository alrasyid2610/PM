@extends('layouts.app')

@section('page-title', 'Tambah Sales Quotation')
@section('page-descrip', 'Tambahkan data sales quotation baru')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ url('sales-quotations') }}">Sales Quotations</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Tambah</li>
@endsection

@section('content')
<section class="section">
    <form id="salesQuotationForm" class="row g-3">
        @csrf

        <!-- SECTION 1: INFORMASI QUOTATION -->
        <div class="col-12">
            <x-section-card icon="fa-file-lines" color="icon-navy" title="Informasi Quotation" subtitle="Data utama sales quotation">
                <div class="row g-3">
                    <div class="col-md-3 col-12">
                        <label class="form-label required">Tanggal SQ</label>
                        <input type="text" name="tanggal_sq" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off" required>
                    </div>
                    <div class="col-md-9 col-12">
                        <label class="form-label required">Judul Order</label>
                        <input type="text" name="judul_order" class="form-control" required>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Berlaku Sampai</label>
                        <input type="text" name="berlaku_sampai" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Rencana Mulai <span class="text-muted">(acuan hari ke-1)</span></label>
                        <input type="text" name="rencana_mulai" class="form-control fp-date" placeholder="Pilih tanggal" autocomplete="off">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label">Office</label>
                        <select name="id_office" class="form-select"></select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION 2: DATA PELANGGAN -->
        <div class="col-12">
            <x-section-card icon="fa-building-user" color="icon-blue" title="Data Pelanggan" subtitle="Billing, Delivery & Payment">

                <div class="detail-party-header d-none d-md-grid">
                    <div class="detail-party-label"><i class="fa-solid fa-file-invoice me-1"></i> Data Pemesan</div>
                    <div class="detail-party-label"><i class="fa-solid fa-truck me-1"></i> Data Pengiriman</div>
                    <div class="detail-party-label"><i class="fa-solid fa-money-bill me-1"></i> Data Pembayaran</div>
                </div>

                <div class="row g-3 mb-1">
                    <div class="col-12 d-md-none">
                        <div class="detail-mobile-section-label"><i class="fa-solid fa-file-invoice me-1"></i> Data Pemesan</div>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Perusahaan</label>
                        <select name="id_pelanggan" id="id_pelanggan" class="form-select" required>
                            <option value="">Pilih Pelanggan</option>
                        </select>
                    </div>
                    <div class="col-12 d-md-none">
                        <div class="detail-mobile-section-label"><i class="fa-solid fa-truck me-1"></i> Data Pengiriman</div>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Perusahaan</label>
                        <select name="id_pelanggan_delivery" class="form-select" required>
                            <option value="">Pilih Pelanggan</option>
                        </select>
                    </div>
                    <div class="col-12 d-md-none">
                        <div class="detail-mobile-section-label"><i class="fa-solid fa-money-bill me-1"></i> Data Pembayaran</div>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Perusahaan</label>
                        <select name="id_pelanggan_payment" class="form-select" required>
                            <option value="">Pilih Pelanggan</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-1">
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Site</label>
                        <select name="id_site_pelanggan" id="id_site_pelanggan" class="form-select" required>
                            <option value="">Pilih Site</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Site</label>
                        <select name="id_site_pelanggan_delivery" id="id_site_pelanggan_delivery" class="form-select" required>
                            <option value="">Pilih Site</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Site</label>
                        <select name="id_site_pelanggan_payment" id="id_site_pelanggan_payment_select" class="form-select" required>
                            <option value="">Pilih Site</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4 col-12">
                        <label class="form-label required">PIC</label>
                        <select name="id_pic_pelanggan" id="id_pic_pelanggan" class="form-select" required>
                            <option value="">Pilih PIC</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">PIC</label>
                        <select name="id_pic_pelanggan_delivery" id="id_pic_pelanggan_delivery" class="form-select" required>
                            <option value="">Pilih PIC</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">PIC</label>
                        <select name="id_pic_pelanggan_payment" id="id_pic_pelanggan_payment" class="form-select" required>
                            <option value="">Pilih PIC</option>
                        </select>
                    </div>
                </div>

            </x-section-card>
        </div>

        <!-- SECTION 3: PIC INTERNAL -->
        <div class="col-12">
            <x-section-card icon="fa-users" color="icon-green" title="PIC Internal" subtitle="Penanggung jawab dari Pramatek">
                <div class="row g-3">
                    <div class="col-md-4 col-12">
                        <label class="form-label required">PIC Input</label>
                        <select name="pic_input" id="pic_input" class="form-select" required>
                            <option value="">Pilih PIC Input</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label required">Marketing Internal</label>
                        <select name="pic_marketing_internal" id="pic_marketing_internal" class="form-select" required>
                            <option value="">Pilih Marketing Internal</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="form-label">Marketing Eksternal</label>
                        <select name="pic_marketing_eksternal" id="pic_marketing_eksternal" class="form-select">
                            <option value="">Pilih Marketing Eksternal</option>
                        </select>
                    </div>
                </div>
            </x-section-card>
        </div>

        <!-- SECTION 4: KETERANGAN -->
        <div class="col-12">
            <x-section-card icon="fa-circle-info" color="icon-purple" title="Keterangan">
                <div class="row g-3">
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

        <x-form-actions back-route="{{ url('sales-quotations') }}" submit-label="Simpan Sales Quotation" />

    </form>
</section>
@endsection

@section('custom-script')
<script>
    var dataPelanggan = '';
    var allSites = [];

    $(document).ready(function () {
        initFpDate(document);
        $('select[name="id_office"]').select2({
            width: '100%',
            placeholder: 'Pilih Office',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('office.select2') }}",
                dataType: 'json',
                delay: 200,
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data }),
                cache: true,
            },
            language: {
                noResults: function () {
                    return `<span>Tidak ditemukan. <a href="{{ route('office.create') }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`;
                },
            },
            escapeMarkup: function (m) { return m; },
        });

        loadPelangganDetails();
        initPicInternal();

        initPicSelect('#id_pic_pelanggan', 'Pilih PIC');
        initPicSelect('#id_pic_pelanggan_delivery', 'Pilih PIC');
        initPicSelect('#id_pic_pelanggan_payment', 'Pilih PIC');

        ['#id_pelanggan', 'select[name="id_site_pelanggan"]', '#id_pic_pelanggan'].forEach(function (sel) {
            $(sel).on('select2:select', syncPemesanToOthers);
        });

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

        const companySitePairs = [
            ['#id_pelanggan', "select[name='id_site_pelanggan']"],
            ['select[name="id_pelanggan_delivery"]', "select[name='id_site_pelanggan_delivery']"],
            ['select[name="id_pelanggan_payment"]', "select[name='id_site_pelanggan_payment']"],
        ];
        companySitePairs.forEach(function (pair) {
            $(pair[0]).on('select2:select select2:clear', function () {
                populateSiteOptions(pair[1], $(pair[0]).val());
            });
        });
    });

    function getSelect2SelectedData(selector) {
        const $el = $(selector);
        if (!$el.length || !$el.val()) return null;
        const data = $el.select2('data');
        if (!data || !data.length) return null;
        return { id: $el.val(), text: data[0].text };
    }

    function syncPemesanToOthers() {
        const company = getSelect2SelectedData('#id_pelanggan');
        const site = getSelect2SelectedData('select[name="id_site_pelanggan"]');
        if (!company || !site) return;

        const pic = getSelect2SelectedData('#id_pic_pelanggan');

        const targets = [
            { company: 'select[name="id_pelanggan_delivery"]', site: 'select[name="id_site_pelanggan_delivery"]', pic: '#id_pic_pelanggan_delivery' },
            { company: 'select[name="id_pelanggan_payment"]',  site: 'select[name="id_site_pelanggan_payment"]',  pic: '#id_pic_pelanggan_payment' },
        ];

        targets.forEach(function (t) {
            const $c = $(t.company);
            if ($c.length && !$c.val()) $c.append(new Option(company.text, company.id, true, true)).trigger('change');
            const $s = $(t.site);
            if ($s.length && !$s.val()) $s.append(new Option(site.text, site.id, true, true)).trigger('change');
            if (pic) {
                const $p = $(t.pic);
                if ($p.length && !$p.val()) $p.append(new Option(pic.text, pic.id, true, true)).trigger('change');
            }
        });
    }

    function getSelectedCompanyIds() {
        return [
            $('#id_pelanggan').val(),
            $('select[name="id_pelanggan_delivery"]').val(),
            $('select[name="id_pelanggan_payment"]').val(),
        ].filter(function (v) { return !!v; });
    }

    function initPicSelect(selector, placeholder) {
        const $el = $(selector);
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

        $el.select2({
            placeholder: placeholder,
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('business-relation-contacts.select2') }}",
                dataType: 'json',
                delay: 200,
                data: function (params) { return { q: params.term || '', id_br: getSelectedCompanyIds(), with_site: 1 }; },
                processResults: function (data) { return { results: data }; },
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
            method: 'GET',
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
                    $(sel).select2({ placeholder: 'Pilih Pelanggan', allowClear: true });
                });
            },
            error: function () { Notify.error('Gagal memuat data pelanggan'); }
        });

        $.ajax({
            url: "{{ route('api.get-data-site') }}",
            method: 'GET',
            success: function (response) {
                allSites = response;
                const pairs = [
                    ['#id_pelanggan', "select[name='id_site_pelanggan']"],
                    ['select[name="id_pelanggan_delivery"]', "select[name='id_site_pelanggan_delivery']"],
                    ['select[name="id_pelanggan_payment"]', "select[name='id_site_pelanggan_payment']"],
                ];
                pairs.forEach(function (pair) {
                    $(pair[1]).select2({ placeholder: 'Pilih Site', allowClear: true });
                    populateSiteOptions(pair[1], $(pair[0]).val());
                });
            },
            error: function () { Notify.error('Gagal memuat data site'); }
        });
    }

    function populateSiteOptions(siteSelector, id_br) {
        const $el = $(siteSelector);
        const currentVal = $el.val();

        const filtered = id_br
            ? allSites.filter(function (s) { return String(s.id_br) === String(id_br); })
            : allSites;

        $el.empty();
        $.each(filtered, function (index, item) {
            $el.append(new Option(item.nama_lokasi, item.id_site));
        });

        const stillValid = filtered.some(function (s) { return String(s.id_site) === String(currentVal); });
        $el.val(stillValid ? currentVal : null).trigger('change');
    }

    function initPicInternal() {
        const selects = [
            { id: '#pic_input', placeholder: 'Pilih PIC Input' },
            { id: '#pic_marketing_internal', placeholder: 'Pilih Marketing Internal' },
            { id: '#pic_marketing_eksternal', placeholder: 'Pilih Marketing Eksternal' },
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

    $('#salesQuotationForm').on('submit', function (e) {
        const requiredFields = [
            ['input[name="tanggal_sq"]', 'Tanggal SQ'],
            ['input[name="judul_order"]', 'Judul Order'],
            ['select[name="id_pelanggan"]', 'Perusahaan (Data Pemesan)'],
            ['select[name="id_site_pelanggan"]', 'Site (Data Pemesan)'],
            ['select[name="id_pic_pelanggan"]', 'PIC (Data Pemesan)'],
            ['select[name="id_pelanggan_delivery"]', 'Perusahaan (Data Pengiriman)'],
            ['select[name="id_site_pelanggan_delivery"]', 'Site (Data Pengiriman)'],
            ['select[name="id_pic_pelanggan_delivery"]', 'PIC (Data Pengiriman)'],
            ['select[name="id_pelanggan_payment"]', 'Perusahaan (Data Pembayaran)'],
            ['select[name="id_site_pelanggan_payment"]', 'Site (Data Pembayaran)'],
            ['select[name="id_pic_pelanggan_payment"]', 'PIC (Data Pembayaran)'],
            ['select[name="pic_input"]', 'PIC Input'],
            ['select[name="pic_marketing_internal"]', 'Marketing Internal'],
        ];

        const missing = requiredFields
            .filter(([sel]) => !$(sel).val())
            .map(([, label]) => label);

        if (missing.length) {
            e.preventDefault();
            e.stopImmediatePropagation();
            Notify.error('Field berikut wajib diisi:<br>' + missing.map((m) => '• ' + m).join('<br>'));
            return false;
        }
    });

    submitCreateForm({
        formId: '#salesQuotationForm',
        url: "{{ url('sales-quotations') }}",
        onSuccess: function (res) {
            window.location.href = "{{ url('sales-quotations') }}" + (res.id_sq ? '?open=' + res.id_sq : '');
        },
    });
</script>
@endsection
