<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Calibri, Arial, sans-serif; color: #111; -webkit-print-color-adjust: exact; }
        .pf { width: calc(100% - 30mm); margin: 0 15mm; font-size: 11px; line-height: 1.35; }
        .pf-nama { font-weight: 700; font-size: 12px; }
        .pf-row { display: flex; font-weight: 700; }
        .pf-alamat { flex: 0 0 41%; }
        .pf-kontak { flex: 0 0 34%; display: flex; }
        .pf-web { flex: 1; display: flex; }
        .pf-k { flex: 0 0 62px; }
        .pf-hal { text-align: center; font-size: 10px; font-weight: 400; color: #6b7280; padding-top: 3px; }
    </style>
</head>
<body>
    {{-- Footer penawaran: teks saja (tanpa logo/ gambar kop) --}}
    <div class="pf">
        <div class="pf-nama">PT Pramatek Andal Analitika</div>
        <div class="pf-row">
            <div class="pf-alamat">Jl. Dr. Ratna No. 108 A, Jatikramat, Jatiasih<br>Kota Bekasi 17421, Jawa Barat Indonesia</div>
            <div class="pf-kontak">
                <div class="pf-k">Phone<br>WhatsApp</div>
                <div>+6221-2210-6968<br>+62899-2829-100</div>
            </div>
            <div class="pf-web">
                <div class="pf-k" style="flex-basis:52px">Email<br>Website</div>
                <div>info@pramatek.co.id<br>www.pramatek.co.id</div>
            </div>
        </div>
        <div class="pf-hal">Halaman <span class="pageNumber"></span> dari <span class="totalPages"></span></div>
    </div>
</body>
</html>
