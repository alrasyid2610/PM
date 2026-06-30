<!DOCTYPE html>
<html lang="en">
@include('layouts.header')
{{-- <head> --}}
{{-- <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}"> --}}
{{-- </head> --}}

<body>
    <style>
        table.dataTable td,
        table.dataTable th {
            white-space: nowrap;
        }

        .table-responsive {
            overflow-x: auto;
        }
    </style>
    <div id="app">
        @include('layouts.sidebar')

        {{-- Main --}}
        <div id="main">
            @include('layouts.navbar')
            {{-- Page Title --}}
                <div class="page-header-banner">
                    <div class="header-bg"></div>
                    <div class="header-circles">
                        <div class="circle circle-1"></div>
                        <div class="circle circle-2"></div>
                        <div class="circle circle-3"></div>
                        <div class="circle circle-4"></div>
                    </div>
                    {{-- <div class="header-accent"></div> --}}
                    {{-- <div class="header-line"></div> --}}

                    <div class="header-content">
                        {{-- <nav aria-label="breadcrumb" class="header-breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="{{ url('/') }}">Dashboard</a>
                                </li>
                                @yield('breadcrumb')
                            </ol>
                        </nav> --}}
                        <h3 class="header-title">
                            @yield('page-title', Str::title(str_replace('-', ' ', request()->segment(1))))
                        </h3>
                        {{-- <p class="header-subtitle">
                            @yield('page-descrip')
                        </p> --}}
                    </div>

                    <div class="header-icon">
                        @yield('page-icon')
                    </div>
                </div>
            {{-- End Page Title --}}

            <div class="main-content container-fluid">
                @yield('content')
            </div>

        </div>
        {{-- End Main --}}

    </div>


    <div id="global-loader">
        <div class="gl-content">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Pramatek" class="gl-logo">
            <div class="gl-bar"><div class="gl-bar-fill"></div></div>
        </div>
    </div>

    <div id="scientific-toolbar">

        <div class="toolbar-header">
            Scientific Symbols
        </div>

        <div class="toolbar-group">

            <span data-symbol="²">²</span>
            <span data-symbol="³">³</span>
            <span data-symbol="µ">µ</span>
            <span data-symbol="°C">°C</span>

            <span data-symbol="CO₂">CO₂</span>
            <span data-symbol="H₂O">H₂O</span>
            <span data-symbol="H₂O₂">H₂O₂</span>
            <span data-symbol="SO₂">SO₂</span>
            <span data-symbol="NO₂">NO₂</span>

            <span data-symbol="m²">m²</span>
            <span data-symbol="m³">m³</span>

            <span data-symbol="mg/L">mg/L</span>
            <span data-symbol="µg/m³">µg/m³</span>
            <span data-symbol="ppm">ppm</span>
            <span data-symbol="ppb">ppb</span>
            <span data-symbol="SO₄²-">SO₄²-</span>



        </div>

    </div>


    <div class="modal fade" id="imagePreviewModal">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <img id="previewImage" class="img-fluid">
                </div>
            </div>
        </div>
    </div>



    <!-- Scripts -->
    <script src="{{ asset('assets/vendor/jquery/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/js/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="{{ asset('assets/vendor/chartjs/Chart.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>

    <script src="{{ asset('assets/vendor/datatables/dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="{{ asset('assets/js/notification.js') }}"></script>

    <script src="{{ asset('assets/js/pages/dashboard.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script>
        document.querySelectorAll('.sidebar-item.has-sub.active').forEach(function(item) {
            var submenu = item.querySelector('.submenu');
            if (submenu) submenu.classList.add('active');
        });
    </script>
    <script>
        let resource = '{{ request()->segment(1) }}';

        let tableId = resource + '-table';

        let tableSelector = '#' + tableId;

        let baseUrl = '/' + resource;
    </script>
    <script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>



    <script src="{{ asset('assets/js/core/wilayahEngine.js') }}"></script>
    <script src="{{ asset('assets/js/core/datatable.js') }}"></script>
    <script src="{{ asset('assets/js/core/tableRowHandler.js') }}"></script>
    <script src="{{ asset('assets/js/core/formEditHandler.js') }}"></script>
    <script src="{{ asset('assets/js/core/detailLoader.js') }}"></script>
    <script src="{{ asset('assets/js/core/masterCrudEngine.js') }}"></script>
    <script src="{{ asset('assets/js/core/formSubmitEngine.js') }}"></script>
    <script src="{{ asset('assets/js/core/createFormHandler.js') }}"></script>
    <script src="{{ asset('assets/js/core/attachmentEngine.js') }}"></script>
    <script src="{{ asset('assets/js/core/formComponents.js') }}"></script>
    <script src="{{ asset('assets/js/core/crudPageController.js') }}"></script>
    <script src="{{ asset('assets/js/core/permissionEngine.js') }}"></script>
    <script src="{{ asset('assets/js/pm.js') }}"></script>
    @auth
    <script>
        window.userPermissions = @json(getUserPermissions(auth()->id()));
        window.currentMenuSlug = '{{ request()->segment(1) }}';
    </script>
    @endauth
    <script src="{{ asset('assets/js/scientific-input.js') }}"></script>
    <script src="{{ asset('assets/js/tableForm.js') }}"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>



    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cleave.js@1.6.0/dist/cleave.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    <script>
        function initNumericMask(container) {
            $(container).find('.input-num-mask').each(function () {
                if (this._cleave) return;
                const isInt = $(this).hasClass('input-num-int');
                this._cleave = new Cleave(this, {
                    numeral: true,
                    numeralThousandsGroupStyle: 'thousand',
                    delimiter: ',',
                    numeralDecimalMark: '.',
                    numeralDecimalScale: isInt ? 0 : 2,
                });
            });
        }
        function rawNumVal(el) {
            if (!el) return null;
            const raw = el._cleave ? el._cleave.getRawValue()
                                   : String($(el).val()).replace(/,/g, '');
            const n = parseFloat(raw);
            return isNaN(n) ? null : n;
        }
        function initFpDate(container) {
            $(container).find('.fp-date').each(function () {
                if (this._fp) return;
                const name = this.name;
                const isSelesai = name && name.toLowerCase().includes('selesai');
                const fp = flatpickr(this, {
                    locale: 'id',
                    dateFormat: 'Y-m-d',
                    allowInput: false,
                    defaultDate: $(this).val() || null,
                    onChange: !isSelesai ? function(selectedDates, dateStr) {
                        const $form = $(container).find('.fp-date[name*="selesai"]');
                        if ($form.length && $form[0]._fp) {
                            $form[0]._fp.set('minDate', dateStr || null);
                            if ($form[0]._fp.selectedDates[0] && $form[0]._fp.selectedDates[0] < selectedDates[0]) {
                                $form[0]._fp.clear();
                            }
                        }
                    } : undefined,
                });
                if (isSelesai) {
                    const mulaiVal = $(container).find('.fp-date[name*="mulai"]').val();
                    if (mulaiVal) fp.set('minDate', mulaiVal);
                }
                this._fp = fp;
            });
            $(container).find('.fp-datetime').each(function () {
                if (this._fp) return;
                this._fp = flatpickr(this, {
                    locale: 'id',
                    dateFormat: 'Y-m-d H:i',
                    enableTime: true,
                    time_24hr: true,
                    allowInput: false,
                    defaultDate: $(this).val() || null,
                });
            });
        }
        $(document).on('input', '.numeric-only', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
    @yield('custom-script')

    {{-- Back to Top --}}
    <button id="btnBackToTop" title="Kembali ke atas"
        style="display:none;position:fixed;bottom:28px;right:28px;z-index:9999;
               width:40px;height:40px;border-radius:50%;border:none;cursor:pointer;
               background:#1d4ed8;color:#fff;box-shadow:0 4px 12px rgba(29,78,216,.35);
               font-size:16px;transition:opacity .2s,transform .2s;">
        <i class="fa-solid fa-chevron-up"></i>
    </button>
    <script>
        (function () {
            const btn = document.getElementById('btnBackToTop');
            window.addEventListener('scroll', function () {
                if (window.scrollY > 300) {
                    btn.style.display = 'flex';
                    btn.style.alignItems = 'center';
                    btn.style.justifyContent = 'center';
                    btn.style.opacity = '1';
                } else {
                    btn.style.opacity = '0';
                    setTimeout(function () {
                        if (window.scrollY <= 300) btn.style.display = 'none';
                    }, 200);
                }
            });
            btn.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        })();
    </script>
</body>

</html>
