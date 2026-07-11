<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            -webkit-print-color-adjust: exact;
            margin: 0;
            padding: 0;
        }
        .pdf-footer {
            width: calc(100% - 30mm);
            margin: 0 15mm;
        }
        .pdf-footer img {
            width: 100%;
            display: block;
        }
        .page-number {
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            padding: 2px 0;
        }
    </style>
</head>
<body>
    <div class="pdf-footer">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/footer_pdf.png'))) }}">
        <div class="page-number">
            Halaman <span class="pageNumber"></span> dari <span class="totalPages"></span>
        </div>
    </div>
</body>
</html>
