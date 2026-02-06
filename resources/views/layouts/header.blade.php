<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    {{-- Font Awesome --}}
    <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
      {{-- <link rel="stylesheet" href="{{ asset('assets/vendor/fontawesome/all.min.css') }}"> --}}

    
    {{-- Bootstrap --}}
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}">
    
    {{-- ChartJS --}}
    <link rel="stylesheet" href="{{ asset('assets/vendor/chartjs/Chart.min.css') }}">

    {{-- Sweet Alert --}}
    <link rel="stylesheet" href="{{ asset('assets/vendor/sweetalert2/sweetalert2.min.css') }}">

    {{-- Perfect Scrollbar --}}
    <link rel="stylesheet" href="{{ asset('assets/vendor/perfect-scrollbar/perfect-scrollbar.css') }}">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    
    {{-- App --}}
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/x-icon">
    <!-- <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon"> -->


    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">


    <style>
      table.dataTable td,
      table.dataTable th {
          vertical-align: top !important;
      }

    </style>
</head>