<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        :root { --color-primary: #203864; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Calibri, sans-serif;
            -webkit-print-color-adjust: exact;
            margin: 0;
            padding: 0;
        }

        .pdf-header {
            display: flex;
            align-items: stretch;
            height: 40px;
            width: calc(100% - 30mm);
            margin: 8mm 15mm 0;
        }

        .header-logo {
            flex: 0 0 20%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #203864;
            border-radius: 6px;
            padding: 8px;
        }

        .header-gap {
            flex: 0 0 20%;
            background-color: white;
        }

        .header-company {
            flex: 1;
            display: flex;
            align-items: center;
            flex-flow: row-reverse;
            padding-right: 15px;
            background: #203864;
            position: relative;
        }

        .header-company-text {
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
        }

        .garis1, .garis2 {
            position: absolute;
            height: 100%;
            width: 30px;
            background-color: red;
            left: 0;
            transform: skewX(-30deg);
        }

        .garis1 {
            background-color: #ff9900;
            left: -30px;
        }

        .garis2 {
            background-color: #d9d9d9;
        }
    </style>
</head>
<body>
    <div class="pdf-header">
        <div class="header-logo">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/pramatek_log.png'))) }}" style="max-height: 20px; width: auto;">
        </div>
        <div class="header-gap"></div>
        <div class="header-company">
            <div class="garis1"></div>
            <div class="garis2"></div>
            <span class="header-company-text">PT Pramatek Andal Analitika</span>
        </div>
    </div>
</body>
</html>
