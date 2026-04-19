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


    <div id="global-loader" style="display:none;">
        <div class="loader-dots">
            <span class="dot-red"></span>
            <span class="dot-green"></span>
            <span class="dot-blue"></span>
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

    @yield('custom-script')
</body>

</html>
