<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Order - SO.25.001</title>
    <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            html {
                margin: 0;
                padding: 0;
            }

            body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
                font-size: 11px;
                line-height: 1.4;
                color: #333;
            }

            @page {
                margin: 0;
                padding: 0;
            }

            /* Header - Fixed Top (No Gap) */
            .page-header {
                position: running(header);
                background: #1e3a5f;
                color: white;
                padding: 12px 30px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin: 0;
            }

            .company-name {
                font-size: 16px;
                font-weight: bold;
            }

            /* Footer - Fixed Bottom */
            .page-footer {
                position: running(footer);
                background: #f8f9fa;
                padding: 10px 30px;
                border-top: 3px solid #ff8c00;
                font-size: 9px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .footer-left {
                flex: 1;
            }

            .footer-center {
                flex: 1;
                text-align: center;
            }

            .footer-right {
                flex: 1;
                text-align: right;
            }

            /* Content wrapper untuk margin dari header/footer */
            .page-content {
                margin-top: 70px;
                margin-bottom: 50px;
            }

            /* Title Bar */
            .title-bar {
                background: white;
                border-bottom: 2px solid #1e3a5f;
                padding: 15px 30px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin: 0;
            }

            .so-title {
                font-size: 20px;
                font-weight: bold;
                color: #1e3a5f;
            }

            .so-info {
                text-align: right;
            }

            .so-number {
                font-size: 16px;
                font-weight: bold;
                color: #1e3a5f;
            }

            /* Content Area */
            .content {
                padding: 20px 30px;
            }

            /* Section Headers */
            .section {
                margin-bottom: 25px;
            }

            .section-header {
                background: #1e3a5f;
                color: white;
                padding: 8px 15px;
                font-weight: bold;
                font-size: 13px;
                margin-bottom: 15px;
            }

            .subsection-header {
                color: #1e3a5f;
                font-weight: bold;
                font-size: 12px;
                margin-bottom: 10px;
                padding-bottom: 5px;
                border-bottom: 2px solid #ff8c00;
            }

            /* Info Blocks */
            .info-grid {
                display: grid;
                grid-template-columns: 150px 1fr;
                gap: 8px;
                margin-bottom: 15px;
            }

            .info-label {
                font-weight: bold;
                color: #555;
            }

            .info-value {
                color: #333;
            }

            /* Tables */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
            }

            table th,
            table td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
            }

            table thead {
                background: #1e3a5f;
                color: white;
                font-weight: bold;
            }

            table tbody tr:nth-child(even) {
                background: #f8f9fa;
            }

            /* Bullet Lists */
            .bullet-list {
                padding-left: 20px;
                margin: 10px 0;
            }

            .bullet-list li {
                margin-bottom: 8px;
                line-height: 1.5;
            }

            /* Page Breaks */
            .page-break {
                page-break-after: always;
            }

            /* Page Number */
            .page-number {
                font-size: 9px;
                color: #666;
            }
    </style>
</head>
<body>

    <!-- PAGE 1: Cover & Customer Info -->
    <div class="page-header">
        <div>
            <div style="font-size: 14px; margin-bottom: 5px;">PT Pramatek Andal Analitika</div>
            <div style="font-size: 9px; font-weight: normal;">Testing Laboratory & Consulting Services</div>
        </div>
        <div style="text-align: right; font-size: 9px;">
            <div>Jl. Dr. Ratna No. 108 A, Jatikramat, Jatiasih</div>
            <div>Kota Bekasi 17421, Jawa Barat, Indonesia</div>
            <div>Telepon: +6221-22106968 | WhatsApp: +62899-2829-100</div>
        </div>
    </div>
    
    <div class="title-bar">
        <div class="so-title">Sales Order</div>
        <div class="so-info">
            <div style="font-size: 11px; color: #666;">No.</div>
            <div class="so-number">SO.25.001</div>
            <div style="font-size: 11px; color: #666; margin-top: 5px;">Tanggal</div>
            <div style="font-weight: bold;">02 Januari 2025</div>
        </div>
    </div>
    
    <div class="content">
        <!-- Section 1: Pelanggan -->
        <div class="section">
            <div class="section-header">1 Pelanggan</div>
            
            <div class="subsection-header">1.1 Purchase Order</div>
            
            <div class="info-grid">
                <div class="info-label">ID Pelanggan</div>
                <div class="info-value">PT Procon Djaya Agung</div>
                
                <div class="info-label">Alamat</div>
                <div class="info-value">Ruko Sentra Niaga 1 No. 31 Grand Galaxy, Jaka Setia, Bekasi Selatan, Kota Bekasi, Jawa Barat, 17147</div>
                
                <div class="info-label">No. PO</div>
                <div class="info-value">180/PO-PDA/PAA/I/2025</div>
                
                <div class="info-label">Judul PO</div>
                <div class="info-value">Pengujian Kualitas Air Limbah Domestik</div>
                
                <div class="info-label">Tanggal PO</div>
                <div class="info-value">2 Januari 2025</div>
                
                <div class="info-label">U.P. PO</div>
                <div class="info-value">Sis Panca Putra</div>
                
                <div class="info-label">No. Telepon</div>
                <div class="info-value">0853-3136-1551</div>
                
                <div class="info-label">Email</div>
                <div class="info-value">sispancaputra@proconwater.co.id</div>
                
                <div class="info-label">Pekerjaan</div>
                <div class="info-value">Pengujian Kualitas Air Limbah Domestik Kegiatan Operasional Richeese Factory Bintaro Bulan Januari Tahun 2025</div>
                
                <div class="info-label">Masa Pekerjaan</div>
                <div class="info-value">12 hari kerja sesudah sampel diterima di Laboratorium</div>
                
                <div class="info-label">Mulai</div>
                <div class="info-value">03/01/2025</div>
                
                <div class="info-label">Selesai</div>
                <div class="info-value">21/01/2025</div>
                
                <div class="info-label">Keterangan</div>
                <div class="info-value">
                    <ul class="bullet-list">
                        <li>Kegiatan sampling dilakukan mulai dari tanggal 03 Januari 2025</li>
                        <li>Penerimaan sampel maksimal 1 hari kerja sesudah Kegiatan Sampling selesai dilakukan</li>
                        <li>Pengujian sampel dilakukan maksimal 8 hari kerja sesudah sampel diterima di Laboratorium</li>
                        <li>Pengiriman draft dilakukan maksimal 10 hari kerja sesudah sampel diterima di Laboratorium</li>
                        <li>Konfirmasi dari client maksimal 1 hari kerja sesudah draft dikirimkan ke client (kecuali konfirmasi dari client lama)</li>
                        <li>Pengiriman finalisasi hasil uji dan invoice dilakukan maksimal 12 hari kerja sesudah sampel diterima di Laboratorium (tergantung dari konfirmasi client)</li>
                    </ul>
                </div>
            </div>
            
            <div class="subsection-header">1.2 Kontrak</div>
            <div class="info-grid">
                <div class="info-label">No. Kontrak</div>
                <div class="info-value">-</div>
                
                <div class="info-label">Tanggal Kontrak</div>
                <div class="info-value">-</div>
                
                <div class="info-label">Masa Berlaku</div>
                <div class="info-value">-</div>
            </div>
        </div>
    </div>
    
    <div class="page-footer">
        <div class="footer-left">
            <strong>SO.25.001</strong> PT Procon Djaya Agung – RF Bintaro
        </div>
        <div class="footer-center">
            Email: info@pramatek.co.id | Website: www.pramatek.co.id
        </div>
        <div class="footer-right">
            <span class="page-number">Halaman 1 dari 6</span>
        </div>
    </div>
    
    <div class="page-break"></div>
    
    <!-- PAGE 2: Penanggung Jawab -->
    <div class="page-header">
        <div class="company-name">PT Pramatek Andal Analitika</div>
    </div>
    
    <div class="title-bar">
        <div class="so-title">Sales Order</div>
        <div class="so-info">
            <div class="so-number">SO.25.001</div>
            <div style="font-size: 11px; margin-top: 5px;">02 Januari 2025</div>
        </div>
    </div>
    
    <div class="content">
        <div class="section">
            <div class="subsection-header">1.3 Penanggung Jawab Pekerjaan</div>
            
            <table>
                <thead>
                    <tr>
                        <th>Personel Penghubung</th>
                        <th>Nama</th>
                        <th>Telepon</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Penanggung Jawab</td>
                        <td>Dhanisworo KF Sandy</td>
                        <td>0813-4367-6477</td>
                        <td>dhanisworo@pramatek.co.id</td>
                    </tr>
                    <tr>
                        <td>Admin 1</td>
                        <td>Nurul Atiqah</td>
                        <td>0852-1224-2742</td>
                        <td>adm.project@pramatek.co.id</td>
                    </tr>
                    <tr>
                        <td>Admin 2</td>
                        <td>Yunia Nurjayanti</td>
                        <td>0877-5067-0817</td>
                        <td>adm.project@pramatek.co.id</td>
                    </tr>
                    <tr>
                        <td>Admin 3</td>
                        <td>Tri Wahyuningsih</td>
                        <td>0813-1131-5223</td>
                        <td>adm.project@pramatek.co.id</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="page-footer">
        <div class="footer-left">
            <strong>SO.25.001</strong> PT Procon Djaya Agung – RF Bintaro
        </div>
        <div class="footer-center">
            Email: info@pramatek.co.id | Website: www.pramatek.co.id
        </div>
        <div class="footer-right">
            <span class="page-number">Halaman 2 dari 6</span>
        </div>
    </div>
    
    <div class="page-break"></div>
    
    <!-- PAGE 3: Pekerjaan -->
    <div class="page-header">
        <div class="company-name">PT Pramatek Andal Analitika</div>
    </div>
    
    <div class="title-bar">
        <div class="so-title">Sales Order</div>
        <div class="so-info">
            <div class="so-number">SO.25.001</div>
            <div style="font-size: 11px; margin-top: 5px;">02 Januari 2025</div>
        </div>
    </div>
    
    <div class="content">
        <div class="section">
            <div class="section-header">2 Pekerjaan</div>
            
            <div class="subsection-header">2.1 Output Pekerjaan</div>
            
            <div class="info-grid" style="margin-bottom: 20px;">
                <div class="info-label">ID Pelanggan</div>
                <div class="info-value">PT Procon Djaya Agung</div>
                
                <div class="info-label">Alamat</div>
                <div class="info-value">Ruko Sentra Niaga 1 No. 31 Grand Galaxy, Jaka Setia, Bekasi Selatan, Kota Bekasi, Jawa Barat, 17147</div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Judul Laporan</th>
                        <th>Alamat</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Laporan Hasil Uji</td>
                        <td>Pengujian Kualitas Air Limbah Domestik Kegiatan Operasional Richeese Factory Bintaro Bulan Januari Tahun 2025</td>
                        <td>Ruko Sentra Niaga 1 No. 31 Grand Galaxy, Jaka Setia, Bekasi Selatan, Kota Bekasi, Jawa Barat, 17147</td>
                        <td>
                            Dokumen yang dilampirkan:<br>
                            1. Asli 1 Eksampler LHU Draft (Softcopy)<br>
                            2. Asli 1 Eksampler LHU Official (Hardcopy)
                        </td>
                    </tr>
                    <tr>
                        <td>Laporan Pelaksanaan</td>
                        <td>Berita acara sampling</td>
                        <td>-</td>
                        <td>Hardcopy</td>
                    </tr>
                    <tr>
                        <td>Laporan Pelengkap</td>
                        <td>Dokumentasi kegiatan<br>Invoice<br>Purchase Order</td>
                        <td>-</td>
                        <td>Softcopy<br>Hardcopy<br>Copy 1 Eksampler</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="subsection-header">2.2 Jangka Waktu Pekerjaan</div>
            
            <div class="info-grid" style="margin-bottom: 20px;">
                <div class="info-label">Mulai Kegiatan</div>
                <div class="info-value">03/01/2025</div>
                
                <div class="info-label">Selesai Kegiatan</div>
                <div class="info-value">21/01/2025</div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Agenda</th>
                        <th>Tenggat</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Mulai Kegiatan Sampling</td>
                        <td>03/01/2025</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Selesai Kegiatan Sampling</td>
                        <td>03/01/2025</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Registrasi Sampel Dilaboratorium</td>
                        <td>06/01/2025</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Analisis Sampel Dilaboratorium</td>
                        <td>10/01/2025</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Pembuatan Draft Report</td>
                        <td>15/01/2025</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Verifikasi dan Validasi Hasil Analisa</td>
                        <td>16/01/2025</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Penerbitan Draft Laporan Hasil Uji</td>
                        <td>17/01/2025</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Pengiriman Draft Laporan Hasil Uji Ke Pelanggan</td>
                        <td>17/01/2025</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Konfirmasi Draft Laporan Hasil Uji Oleh Pelangan</td>
                        <td>20/01/2025</td>
                        <td>Tergantung Konfirmasi Client</td>
                    </tr>
                    <tr>
                        <td>Penerbitan Finalisasi Laporan Hasil Uji</td>
                        <td>21/01/2025</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Pengiriman Finalisasi Laporan Hasil Uji dan Dokumen Lainnya kepada pelanggan</td>
                        <td>21/01/2025</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Konfirmasi Penerimaan Laporan Hasil Uji oleh Pelanggan</td>
                        <td>22/01/2025</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="page-footer">
        <div class="footer-left">
            <strong>SO.25.001</strong> PT Procon Djaya Agung – RF Bintaro
        </div>
        <div class="footer-center">
            Email: info@pramatek.co.id | Website: www.pramatek.co.id
        </div>
        <div class="footer-right">
            <span class="page-number">Halaman 3 dari 6</span>
        </div>
    </div>
    
    <div class="page-break"></div>
    
    <!-- PAGE 4: Agenda & Pekerjaan Sampling -->
    <div class="page-header">
        <div class="company-name">PT Pramatek Andal Analitika</div>
    </div>
    
    <div class="title-bar">
        <div class="so-title">Sales Order</div>
        <div class="so-info">
            <div class="so-number">SO.25.001</div>
            <div style="font-size: 11px; margin-top: 5px;">02 Januari 2025</div>
        </div>
    </div>
    
    <div class="content">
        <div class="section">
            <div class="subsection-header">2.3 Agenda Sampling</div>
            
            <div class="info-grid">
                <div class="info-label">ID Pelanggan</div>
                <div class="info-value">Richeese Factory Bintaro</div>
                
                <div class="info-label">Lokasi</div>
                <div class="info-value">Jl. Bintaro Utama 3A No.61 Blok DC 01, RT.002/RW.005, Pd. Karya, Kec. Pd. Aren, Kota Tangerang Selatan, Banten 15225</div>
                
                <div class="info-label">Mulai</div>
                <div class="info-value">03 Januari 2025</div>
                
                <div class="info-label">Selesai</div>
                <div class="info-value">03 Januari 2025</div>
                
                <div class="info-label">Keterangan</div>
                <div class="info-value">Perhitungan biaya jumlah titik sampling sesuai actual pengambilan sampel di lapangan dan dilampirkan dengan berita acara pengambilan sampel.</div>
                
                <div class="info-label">U.P. Pelanggan</div>
                <div class="info-value">Dwi Aji</div>
                
                <div class="info-label">No. Telpon</div>
                <div class="info-value">+62 857-1039-0986</div>
                
                <div class="info-label">U.P. Sampling</div>
                <div class="info-value">Andika Dwi Bintang</div>
                
                <div class="info-label">No. Telpon</div>
                <div class="info-value">0822-8473-8441</div>
                
                <div class="info-label">Email</div>
                <div class="info-value">info@pramatek.co.id</div>
                
                <div class="info-label">Anggota Tim</div>
                <div class="info-value">Raden Muhammad Ibrahim</div>
            </div>
            
            <div class="subsection-header">2.4 Pekerjaan Sampling</div>
            
            <div class="info-grid" style="margin-bottom: 20px;">
                <div class="info-label">ID Pelanggan</div>
                <div class="info-value">Richeese Factory Bintaro</div>
                
                <div class="info-label">Lokasi</div>
                <div class="info-value">Jl. Bintaro Utama 3A No.61 Blok DC 01, RT.002/RW.005, Pd. Karya, Kec. Pd. Aren, Kota Tangerang Selatan, Banten 15225</div>
                
                <div class="info-label">Judul Pekerjaan</div>
                <div class="info-value">Pengujian Kualitas Air Limbah Domestik Kegiatan Operasional Richeese Factory Bintaro Bulan Januari Tahun 2025</div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Deskripsi Item</th>
                        <th>Regulasi</th>
                        <th>Jumlah</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>Air Limbah Domestik</strong><br>
                            pH, BOD, COD, TSS, Minyak & Lemak, Amoniak, Total Coliform, Debit
                        </td>
                        <td>Permen LHK NOMOR P.68/Menlhk/Setjen/Kum.1/8/2016</td>
                        <td>1</td>
                        <td>Titik</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="page-footer">
        <div class="footer-left">
            <strong>SO.25.001</strong> PT Procon Djaya Agung – RF Bintaro
        </div>
        <div class="footer-center">
            Email: info@pramatek.co.id | Website: www.pramatek.co.id
        </div>
        <div class="footer-right">
            <span class="page-number">Halaman 4 dari 6</span>
        </div>
    </div>
    
    <div class="page-break"></div>
    
    <!-- PAGE 5: Penagihan -->
    <div class="page-header">
        <div class="company-name">PT Pramatek Andal Analitika</div>
    </div>
    
    <div class="title-bar">
        <div class="so-title">Sales Order</div>
        <div class="so-info">
            <div class="so-number">SO.25.001</div>
            <div style="font-size: 11px; margin-top: 5px;">02 Januari 2025</div>
        </div>
    </div>
    
    <div class="content">
        <div class="section">
            <div class="section-header">3 Penagihan</div>
            
            <div class="info-grid">
                <div class="info-label">NPWP</div>
                <div class="info-value">-</div>
                
                <div class="info-label">ID NPWP</div>
                <div class="info-value">-</div>
                
                <div class="info-label">Alamat NPWP</div>
                <div class="info-value">-</div>
                
                <div class="info-label">Termin</div>
                <div class="info-value">Pembayaran dilakukan Maksimal 14 hari setelah invoice diterima dan dinyatakan lengkap</div>
                
                <div class="info-label">Kelengkapan Data</div>
                <div class="info-value">
                    1. Dokumentasi sampling<br>
                    2. Draft laporan hasil uji<br>
                    3. Laporan hasil uji<br>
                    4. Invoice<br>
                    5. Purchase Order
                </div>
                
                <div class="info-label">Rek. Pembayaran</div>
                <div class="info-value">Bank BCA a.n Andriana No. Rekening: 2820155729</div>
                
                <div class="info-label">U.P. Invoice</div>
                <div class="info-value">Muhammad Devananda</div>
                
                <div class="info-label">Alamat Pengiriman</div>
                <div class="info-value">Ruko Sentra Niaga Blok RSN 1, No.31, Kota Bekasi, Jawa Barat 17147</div>
                
                <div class="info-label">No. Telepon</div>
                <div class="info-value">0822-1121-1407</div>
                
                <div class="info-label">Email</div>
                <div class="info-value">purchasing@proconwater.co.id</div>
                
                <div class="info-label">U.P. PPH 23</div>
                <div class="info-value">-</div>
            </div>
        </div>
    </div>
    
    <div class="page-footer">
        <div class="footer-left">
            <strong>SO.25.001</strong> PT Procon Djaya Agung – RF Bintaro
        </div>
        <div class="footer-center">
            Email: info@pramatek.co.id | Website: www.pramatek.co.id
        </div>
        <div class="footer-right">
            <span class="page-number">Halaman 5 dari 6</span>
        </div>
    </div>
    
    <div class="page-break"></div>
    
    <!-- PAGE 6: Distribusi Dokumen -->
    <div class="page-header">
        <div class="company-name">PT Pramatek Andal Analitika</div>
    </div>
    
    <div class="title-bar">
        <div class="so-title">Sales Order</div>
        <div class="so-info">
            <div class="so-number">SO.25.001</div>
            <div style="font-size: 11px; margin-top: 5px;">02 Januari 2025</div>
        </div>
    </div>
    
    <div class="content">
        <div class="section">
            <div class="section-header">4 Distribusi Dokumen Hasil Pekerjaan</div>
            
            <div class="subsection-header">4.1 Daftar Personel Penghubung Pelanggan</div>
            
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Email</th>
                        <th>Telpon</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Muhammad Devananda</td>
                        <td>Procurement</td>
                        <td>purchasing@proconwater.co.id</td>
                        <td>0822-1121-1407</td>
                    </tr>
                    <tr>
                        <td>Sis Panca Putra</td>
                        <td>Kepala Teknis</td>
                        <td>sispancaputra@proconwater.co.id</td>
                        <td>0853-3136-1551</td>
                    </tr>
                    <tr>
                        <td>Dwi Aji</td>
                        <td>Teknisi</td>
                        <td>-</td>
                        <td>0857-1039-0986</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="subsection-header">4.2 Daftar Dokumen dan Penerima Hasil Pekerjaan</div>
            
            <table>
                <thead>
                    <tr>
                        <th>Dokumen</th>
                        <th>PIC</th>
                        <th>Lokasi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Draft Laporan Hasil Uji</td>
                        <td>Muhammad Devananda</td>
                        <td>Email/Drive/Cloud</td>
                    </tr>
                    <tr>
                        <td>Laporan Hasil Uji Official</td>
                        <td>Muhammad Devananda</td>
                        <td>PT Procon Djaya Agung<br>Ruko Sentra Niaga Blok RSN 1, No.31, Kota Bekasi, Jawa Barat 17147</td>
                    </tr>
                    <tr>
                        <td>Berita Acara Pengambilan Sampel</td>
                        <td>Sis Panca Putra, Dwi Aji</td>
                        <td>Richeese Factory Bintaro<br>Jl. Bintaro Utama 3A No.61 Blok DC 01, RT.002/RW.005, Pd. Karya, Kec. Pd. Aren, Kota Tangerang Selatan, Banten 15225</td>
                    </tr>
                    <tr>
                        <td>Dokumentasi Pengambilan Sampel</td>
                        <td>Sis Panca Putra, Dwi Aji</td>
                        <td>Email/Drive/Cloud</td>
                    </tr>
                    <tr>
                        <td>Invoice dan Purchase Order</td>
                        <td>Muhammad Devananda</td>
                        <td>PT Procon Djaya Agung<br>Ruko Sentra Niaga Blok RSN 1, No.31, Kota Bekasi, Jawa Barat 17147</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="page-footer">
        <div class="footer-left">
            <strong>SO.25.001</strong> PT Procon Djaya Agung – RF Bintaro
        </div>
        <div class="footer-center">
            Email: info@pramatek.co.id | Website: www.pramatek.co.id
        </div>
        <div class="footer-right">
            <span class="page-number">Halaman 6 dari 6</span>
        </div>
    </div>

</body>
</html>