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
    
    <link href="https://unpkg.com/filepond/dist/filepond.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    
    {{-- App --}}
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/x-icon">
    <!-- <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon"> -->


    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">



    <style>
      table.dataTable td,
      table.dataTable th {
          vertical-align: top !important;
      }

      .loader-dots span:nth-child(2) {
          animation-delay: 0.2s;
      }

      .loader-dots span:nth-child(3) {
          animation-delay: 0.4s;
      }

      #global-loader {
          position: fixed;
          inset: 0;
          background: rgba(255, 255, 255, 0.65);
          backdrop-filter: blur(2px);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 9999;
      }

      .loader-dots span {
          width: 14px;
          height: 14px;
          margin: 0 8px;
          border-radius: 50%;
          display: inline-block;
          animation: bounce 0.6s infinite alternate;
      }

      /* RGB Colors */
      .dot-red {
          background: #ea4335;
      }

      .dot-green {
          background: #34a853;
          animation-delay: 0.2s;
      }

      .dot-blue {
          background: #4285f4;
          animation-delay: 0.4s;
      }

      @keyframes bounce {
          from {
              transform: translateY(0);
              opacity: 0.6;
          }
          to {
              transform: translateY(-12px);
              opacity: 1;
          }
      }

        #scientific-toolbar{
            position:absolute;
            background:white;
            border:1px solid #ddd;
            border-radius:8px;
            padding:10px;
            display:none;
            z-index:9999;
            box-shadow:0 4px 12px rgba(0,0,0,0.15);
            max-width:350px;
        }

        .toolbar-header{
            font-size:12px;
            font-weight:bold;
            margin-bottom:5px;
            color:#666;
        }

        .toolbar-group span{
            display:inline-block;
            padding:5px 7px;
            margin:2px;
            cursor:pointer;
            border-radius:4px;
            font-size:14px;
        }

        .toolbar-group span:hover{
            background:#f2f2f2;
        }

        .attachment-card{

         transition: all .2s;

        }

        .attachment-card:hover{

            box-shadow:0 4px 10px rgba(0,0,0,0.1);

        }

        .attachment-icon{

            height:120px;
            display:flex;
            align-items:center;
            justify-content:center;
            flex-direction:column;

        }

        input.flatpickr-input[readonly] {
            background-color: #fff !important;
            cursor: pointer;
        }

    </style>

    @yield('style')
</head>