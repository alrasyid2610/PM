<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Dokumen')</title>
    <link rel="stylesheet" href="{{ config('app.url') }}/assets/css/bootstrap.css">
    <style>
        @font-face {
            font-family: 'Google Sans';
            src: url("{{ config('app.url') }}/assets/fonts/GoogleSans-VariableFont_GRAD,opsz,wght.ttf") format('truetype');
        }

        :root { --color-primary: #203864; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Google Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            -webkit-print-color-adjust: exact;
        }

        .page {
            padding: 20px 15mm;
        }

        .page-break {
            page-break-after: always;
        }

        .info-table {
            margin-top: 0;
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
            font-size: 12px;
        }

        .info-table .label {
            width: 150px;
            white-space: nowrap;
        }

        .info-table .colon {
            width: 20px;
            text-align: center;
        }

        .info-table .value {
            width: auto;
        }

        .boq-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .boq-th {
            background: var(--color-primary);
            color: #ffffff;
            padding: 7px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
        }

        .boq-td {
            padding: 7px 10px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        .boq-th.boq-no, .boq-td.boq-no { width: 36px; }
        .boq-center { text-align: center; }

        @yield('styles')
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
