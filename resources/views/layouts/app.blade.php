<!DOCTYPE html>
<html lang="en">
@include('layouts.header')
{{-- <head> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}"> --}}
{{-- </head> --}}
<body>
    <div id="app">
        @include('layouts.sidebar')

        {{-- Main --}}
        <div id="main">
            @include('layouts.navbar')

            <div class="main-content container-fluid">

                {{-- Page Title --}}
                <div class="page-title">
                    <div class="row">
                        <div class="col-12 col-md-6 order-md-1 order-last">
                            <h3>{{ Str::title(str_replace('-', ' ', request()->segment(1))); }}</h3>
                            <p class="text-subtitle text-muted">@yield('page-descrip')</p>
                        </div>
                        <div class="col-12 col-md-6 order-md-2 order-first">
                            <nav aria-label="breadcrumb" class='breadcrumb-header'>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Bussines Relation</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                {{-- End Page Title --}}
                

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
        let tableId = '{{ Str::lower(request()->segment(1)) }}-table';
    </script>

    <script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>

    
    <script src="{{ asset('assets/js/pm.js') }}"></script>
    <script src="{{ asset('assets/js/scientific-input.js') }}"></script>
    <script src="{{ asset('assets/js/tableForm.js') }}"></script>
    


    @yield('custom-script')
    
</body>
</html>