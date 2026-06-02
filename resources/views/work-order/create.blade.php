@extends(request('embed') ? 'layouts.embed' : 'layouts.app')

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

{{-- STICKY SO INFO BANNER --}}
<div id="soInfoBanner" style="display:none;position:sticky;top:0;z-index:100;background:#fff;border-bottom:2px solid #e2e8f0;padding:10px 16px;margin:-8px -12px 16px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
    <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:13px;">
        <div style="display:flex;align-items:center;gap:6px;min-width:0;">
            <i class="fa-solid fa-file-contract" style="color:#1a56db;font-size:11px;flex-shrink:0;"></i>
            <span id="soBannerNoSo" style="font-weight:700;color:#1a56db;white-space:nowrap;"></span>
        </div>
        <div style="width:1px;height:16px;background:#e2e8f0;flex-shrink:0;"></div>
        <div style="display:flex;align-items:center;gap:6px;min-width:0;flex:1;">
            <i class="fa-solid fa-file-lines" style="color:#374151;font-size:11px;flex-shrink:0;"></i>
            <span id="soBannerJudul" style="color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
        </div>
    </div>
</div>

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
                        <label class="form-label">Frekuensi</label>
                        <select name="interval_bulan" id="interval_bulan" class="form-select">
                            <option value="">— Tidak ada —</option>
                            <option value="1">Bulanan</option>
                            <option value="2">Bimulanan</option>
                            <option value="3">Triwulan</option>
                            <option value="4">Caturwulan</option>
                            <option value="6">Semester</option>
                            <option value="12">Annual</option>
                        </select>
                    </div>

                    <div class="col-md-1 col-12" id="noUrutWrap" style="display:none;">
                        <label class="form-label">Urutan ke-</label>
                        <input type="number" name="no_urut_period" id="no_urut_period"
                            class="form-control" min="1" placeholder="Auto">
                    </div>

                    <div class="col-md-2 col-12">
                        <label class="form-label">PIC Pekerjaan</label>
                        <select name="pic_pekerjaan"
                            id="pic_pekerjaan"
                            class="form-select">
                            <option value="">Pilih PIC</option>
                        </select>
                    </div>

                    <!-- Preview WO di lokasi yang sama -->
                    <div class="col-md-12" id="woSitePreviewWrap" style="display:none;">
                        <div id="woSitePreviewContent"></div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" rows="4"></textarea>
                    </div>
                </div>
            </x-section-card>
        </div>

        <x-form-actions :back-route="request('embed') ? null : url('work-orders')" submit-label="Simpan Work Order" />

    </form>
</section>
@endsection

@section('custom-script')
<script>

    var dataPelanggan = '';
    var dataSO = '';
    var preselectSoId = new URLSearchParams(window.location.search).get('id_so');

    $(document).ready(function() {

        loadPelangganDetails();
        initBrSelect2();

        if (preselectSoId) {
            $.get("{{ url('sales-orders') }}/" + preselectSoId, function (so) {
                if (!so || !so.id_so) return;
                const opt = new Option(so.no_so + ' — ' + so.judul_order, so.id_so, true, true);
                $('#id_sales_order').append(opt).trigger('change');
                $("input[name='judul_order']").val(so.judul_order).prop('readonly', true);
                $('#id_sales_order').prop('disabled', true);
                $('<input>').attr({ type: 'hidden', name: 'id_sales_order', value: so.id_so }).appendTo('#workOrderForm');

                $('#soBannerNoSo').text(so.no_so ?? '—');
                $('#soBannerJudul').text(so.judul_order ?? '—');
                $('#soInfoBanner').show();
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

        $('#woSitePreviewWrap').hide();
        $('#woSitePreviewContent').html('');
        $('#noUrutWrap').hide();
        $('#no_urut_period').val('');
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

    $(document).on('change', '#interval_bulan', function() {
        var hasInterval = !!$(this).val();
        $('#noUrutWrap').toggle(hasInterval);
        if (!hasInterval) $('#no_urut_period').val('');
    });

    $(document).on('click', '#woSitePreviewToggle', function() {
        var $list = $('#woSitePreviewList');
        var $icon = $(this).find('i');
        $list.toggle();
        $icon.toggleClass('fa-eye fa-eye-slash');
    });

    $(document).on('change', "select[name='id_site_pelanggan']", function() {
        var idSite = $(this).val();
        var idSo = preselectSoId || $('#id_sales_order').val();
        loadWoSitePreview(idSo, idSite);
    });

    const WO_INTERVAL_LABELS = {1:'Bulanan',2:'Bimulanan',3:'Triwulan',4:'Caturwulan',6:'Semester',12:'Annual'};

    function loadWoSitePreview(id_so, id_site) {
        if (!id_so || !id_site) {
            $('#woSitePreviewWrap').hide();
            return;
        }

        $('#woSitePreviewWrap').show();
        $('#woSitePreviewContent').html(
            '<div class="text-muted small py-2"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat WO di lokasi ini...</div>'
        );

        $.get("{{ url('work-orders/by-so') }}/" + id_so, function(wos) {
            var filtered = (wos || []).filter(function(wo) {
                return String(wo.id_site_pelanggan_pekerjaan) === String(id_site);
            });

            if (!filtered.length) {
                $('#woSitePreviewWrap').hide();
                if ($('#no_urut_period').val() === '') $('#no_urut_period').val(1);
                return;
            }

            // Auto-suggest urutan berikutnya
            if ($('#no_urut_period').val() === '') {
                $('#no_urut_period').val(filtered.length + 1);
            }

            var rows = filtered.map(function(wo) {
                var badge = '';
                if (wo.interval_bulan && wo.no_urut_period) {
                    var lbl = WO_INTERVAL_LABELS[wo.interval_bulan] || (wo.interval_bulan + ' bln');
                    badge = '<span style="font-size:10px;font-weight:600;padding:1px 7px;border-radius:20px;'
                          + 'background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;white-space:nowrap;'
                          + 'display:inline-flex;align-items:center;gap:4px;">'
                          + '<i class="fa-solid fa-calendar-days" style="font-size:9px;"></i>'
                          + lbl + ' ke-' + wo.no_urut_period + '</span>';
                }
                return '<tr>'
                    + '<td style="padding:4px 10px;white-space:nowrap;font-size:12px;font-weight:600;color:#1d4ed8;">'
                    +   (wo.no_wo || '—') + '</td>'
                    + '<td style="padding:4px 10px;font-size:12px;color:#374151;">'
                    +   (wo.judul_pekerjaan || '—') + '</td>'
                    + '<td style="padding:4px 10px;">' + badge + '</td>'
                    + '</tr>';
            }).join('');

            var tableHtml = '<div id="woSitePreviewList" style="display:none;margin-top:8px;">'
                + '<div class="table-responsive">'
                + '<table style="width:100%;border-collapse:collapse;"><tbody>'
                + rows
                + '</tbody></table></div></div>';

            $('#woSitePreviewContent').html(
                '<div style="background:#f0f7ff;border:1px solid #bcd0f8;border-radius:8px;padding:8px 14px;">'
                + '<div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;" id="woSitePreviewToggle">'
                + '<span style="font-size:11px;font-weight:700;color:#1a5fbe;text-transform:uppercase;letter-spacing:.5px;">'
                + '<i class="fa-solid fa-briefcase me-1"></i>'
                + 'WO yang sudah ada di lokasi ini (' + filtered.length + ')</span>'
                + '<button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:11px;" title="Tampilkan / Sembunyikan">'
                + '<i class="fa-solid fa-eye"></i></button>'
                + '</div>'
                + tableHtml
                + '</div>'
            );
        }).fail(function() {
            $('#woSitePreviewWrap').hide();
        });
    }

    submitCreateForm({
        formId: "#workOrderForm",
        url: "{{ url('work-orders') }}",
        redirect: preselectSoId ? null : "{{ url('work-orders') }}",
        onSuccess: preselectSoId ? function () {
            localStorage.setItem('wo_created', JSON.stringify({ id_so: preselectSoId, ts: Date.now() }));
            var inIframe = window.self !== window.top;
            if (!inIframe) {
                if (window.opener && !window.opener.closed) window.opener.focus();
                setTimeout(function () { window.close(); }, 800);
            }
        } : null,
    });

</script>
@endsection
