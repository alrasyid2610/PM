<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Calibri, sans-serif; color: #7a8495; }
        .ph { display: flex; align-items: stretch; width: calc(100% - 30mm); margin: 6mm 15mm 0; }
        .ph-logo { flex: 0 0 55%; }
        .ph-logo img { height: 74px; width: auto; display: block; }
        .ph-doc { flex: 1; text-align: right; border-right: 4px solid #203864; padding-right: 10px; }
        .ph-doc .judul { font-size: 20px; font-weight: 700; color: #7a8495; line-height: 1.1; }
        .ph-doc .meta { font-size: 11px; font-weight: 700; color: #6b7280; margin-top: 2px; }
        .ph-doc .hal { font-size: 10px; color: #a3a9b5; margin-top: 2px; }
    </style>
</head>
<body>
    <div class="ph">
        <div class="ph-logo">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/logo untuk sq.png'))) }}" alt="PRAMATEK">
        </div>
        <div class="ph-doc">
            <div class="judul">Sales Quotation</div>
            <div class="meta">{{ $no }}</div>
            <div class="meta">{{ $tanggal }}</div>
            <div class="hal">Halaman <span class="pageNumber"></span> dari <span class="totalPages"></span></div>
        </div>
    </div>
</body>
</html>
