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
    <form id="workOrderForm" class="row g-3">
        @csrf

        <!-- SECTION 1: INFORMASI WORK ORDER -->
        <div class="col-12">
            <x-section-card icon="fa-briefcase" color="icon-navy" title="Informasi Work Order" subtitle="Data pekerjaan lapangan">
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

                    <div class="col-md-12 col-12" id="periodFieldWrap" style="display:none;">
                        <label class="form-label">
                            <i class="fa-solid fa-calendar-days me-1 text-primary"></i>
                            Assign ke Period
                        </label>
                        <select name="id_period" id="id_period" class="form-select">
                            <option value="">— Tidak assign ke period —</option>
                        </select>
                        <div class="form-text text-muted" id="periodFieldHint"></div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" rows="4"></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions back-route="{{ url('work-orders') }}" submit-label="Simpan Work Order" />

    </form>
</section>
@endsection

@section('custom-script')
<script>

    var dataPelanggan = '';
    var dataSO = '';
    var preselectSoId = new URLSearchParams(window.location.search).get('id_so');

    var INTERVAL_LABELS = {1:'Bulanan',2:'Bimulanan',3:'Triwulan',4:'Caturwulan',6:'Semester',12:'Annual'};

    function calcExpected(p) {
        if (!p.tanggal_mulai || !p.tanggal_selesai || !p.interval_bulan) return null;
        var months = Math.round(
            (new Date(p.tanggal_selesai) - new Date(p.tanggal_mulai)) / (1000 * 60 * 60 * 24 * 30)
        );
        return Math.floor(months / p.interval_bulan);
    }

    function loadPeriodsForSo(id_so) {
        $.get("{{ url('wo-periods/by-so') }}/" + id_so, function(periods) {
            var $wrap   = $('#periodFieldWrap');
            var $select = $('#id_period');

            $select.find('option:not(:first)').remove();

            if (!periods || !periods.length) {
                $wrap.slideUp(200);
                if ($select.hasClass('select2-hidden-accessible')) $select.val('').trigger('change');
                return;
            }

            var availableCount = 0;
            periods.forEach(function(p) {
                var assigned = p.wos ? p.wos.length : 0;
                var expected = calcExpected(p);
                var isFull   = expected !== null && assigned >= expected;

                var label = p.nama_period || '—';
                if (p.nama_site)      label += ' · ' + p.nama_site;
                if (p.tanggal_mulai)  label += ' · ' + p.tanggal_mulai.substring(0, 7);
                if (p.interval_bulan) label += ' · ' + (INTERVAL_LABELS[p.interval_bulan] || p.interval_bulan + ' bln');

                if (isFull) {
                    label += ' — Penuh (' + assigned + '/' + expected + ')';
                    var $opt = $(new Option(label, p.id_period));
                    $opt.prop('disabled', true);
                    $select.append($opt);
                } else {
                    var slot = expected !== null ? ' (' + assigned + '/' + expected + ' slot)' : '';
                    $select.append(new Option(label + slot, p.id_period));
                    availableCount++;
                }
            });

            if (availableCount === 0) {
                $('#periodFieldHint').html(
                    '<i class="fa-solid fa-circle-xmark me-1 text-danger"></i>' +
                    'Semua period untuk SO ini sudah penuh'
                );
            } else {
                $('#periodFieldHint').html(
                    '<i class="fa-solid fa-circle-info me-1 text-primary"></i>' +
                    availableCount + ' period tersedia'
                );
            }

            $wrap.slideDown(200);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.trigger('change');
            } else {
                $select.select2({ placeholder: '— Tidak assign ke period —', allowClear: true, width: '100%' });
            }
        });
    }

    function clearPeriodField() {
        $('#periodFieldWrap').slideUp(200);
        var $select = $('#id_period');
        $select.find('option:not(:first)').remove();
        if ($select.hasClass('select2-hidden-accessible')) $select.val('').trigger('change');
        $('#periodFieldHint').text('');
    }

    $(document).ready(function() {

        loadPelangganDetails();
        initBrSelect2();

        if (preselectSoId) {
            $.get("{{ url('sales-orders') }}/" + preselectSoId, function (so) {
                if (!so || !so.id_so) return;
                const opt = new Option(so.no_so + ' — ' + so.judul_order, so.id_so, true, true);
                $('#id_sales_order').append(opt).trigger('change');
                $("input[name='judul_order']").val(so.judul_order).prop('readonly', true);
                loadPeriodsForSo(so.id_so);

                // Kunci SO dan judul field agar tidak bisa diubah
                $('#id_sales_order').prop('disabled', true);
                // Hidden input supaya nilai tetap ter-submit walau select disabled
                $('<input>').attr({ type: 'hidden', name: 'id_sales_order', value: so.id_so }).appendTo('#workOrderForm');
            });
        }

        $('#id_sales_order').on('select2:select', function (e) {
            var data = e.params.data;

            getSO(data).then(function(response) {
                dataSO = response;

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
                    loadPeriodsForSo(dataSO.id_so);
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
        clearPeriodField();
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
            minimumInputLength: 0,
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
            },
            language: {
                noResults: function () {
                    return `<span>Tidak ditemukan. <a href="{{ route('sales-orders.create') }}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fa-solid fa-plus"></i> Add Data</a></span>`;
                },
            },
            escapeMarkup: function (m) { return m; },
        });
    }


    function loadPelangganDetails() {
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
            error: function() {
                Notify.error('Gagal memuat detail pelanggan');
            }
        });

        initSiteSelect2(null);
    }

    function initSiteSelect2(idBr) {
        var $site = $("select[name='id_site_pelanggan']");
        if ($site.hasClass('select2-hidden-accessible')) {
            $site.val(null).trigger('change');
            $site.select2('destroy');
        }
        $site.empty();

        var url = idBr
            ? "{{ url('business-relations') }}/" + idBr + "/sites"
            : "{{ url('business-relations/sites/select2') }}";

        $site.select2({
            placeholder: 'Pilih Site Pelanggan',
            allowClear: true,
            ajax: {
                url: url,
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data }; },
                cache: false,
            }
        });
    }

    $(document).on('change', "select[name='id_pelanggan']", function() {
        var idBr = $(this).val();
        initSiteSelect2(idBr || null);
    });

    submitCreateForm({
        formId: "#workOrderForm",
        url: "{{ url('work-orders') }}",
        redirect: preselectSoId ? null : "{{ url('work-orders') }}",
        onSuccess: preselectSoId ? function () {
            localStorage.setItem('wo_created', JSON.stringify({ id_so: preselectSoId, ts: Date.now() }));
            if (window.opener && !window.opener.closed) window.opener.focus();
            setTimeout(function () { window.close(); }, 800);
        } : null,
    });

</script>
@endsection
