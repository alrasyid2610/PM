@extends('pdf.layouts.document')

@section('title', 'Penawaran - ' . $sq->no_sq)

@section('styles')
    .page { padding: 0 15mm; }
    .title-row { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .title-row td { vertical-align: top; }
    .doc-title { text-align: right; }
    .doc-title .judul { font-size: 20px; font-weight: 700; color: #7f8a99; }
    .doc-title .meta { font-size: 11px; color: #334155; margin-top: 2px; }

    .info { width: 100%; border-collapse: collapse; margin: 10px 0 14px; font-size: 12px; }
    .info td { padding: 2px 0; vertical-align: top; }
    .info td.k { width: 120px; font-weight: 700; }
    .info td.c { width: 12px; }

    .offer { width: 100%; border-collapse: collapse; font-size: 11px; }
    .offer th, .offer td { border: 1px solid #222; padding: 4px 6px; vertical-align: top; }
    .offer th { background: #dbe8f5; font-weight: 700; text-align: center; }
    .offer td.r { text-align: right; white-space: nowrap; }
    .offer td.c { text-align: center; }
    .offer tr.section td { font-weight: 700; background: #f1f5f9; }
    .offer tr.total td { font-weight: 700; background: #203864; color: #fff; border-color: #203864; }
    .offer .sub { font-size: 10px; color: #334155; display: block; margin-top: 2px; }
    .offer .nm { font-weight: 700; }
    .offer .reg { display: block; font-weight: 400; margin-top: 2px; }

    .lokasi { font-size: 11px; font-style: italic; }

    .ket { margin-top: 14px; font-size: 11px; }
    .ket ol { margin: 4px 0 0 22px; }
    .ket li { margin-bottom: 2px; }

    .sign { margin-top: 22px; font-size: 11px; width: 100%; border-collapse: collapse; }
    .sign td { vertical-align: top; }
    .sign-box { width: 90px; height: 90px; border: 1px dashed #94a3b8; color: #94a3b8; font-size: 9px; text-align: center; line-height: 90px; }
    .dummy { color: #b45309; font-style: italic; }
@endsection

@section('content')
@php
    $fmt = fn($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
@endphp

<div class="page">

    <table class="info">
        <tr><td class="k">Ditujukan Kepada</td><td class="c">:</td><td>{{ $sq->pic_pelanggan ?? '-' }}</td></tr>
        <tr><td class="k">Perusahaan</td><td class="c">:</td><td>{{ $sq->nama_pelanggan_display ?? '-' }}</td></tr>
        <tr><td class="k">Alamat</td><td class="c">:</td><td>{{ $sq->alamat_pelanggan ?? '-' }}</td></tr>
        <tr><td class="k">Penawaran</td><td class="c">:</td><td>{{ $sq->judul_order ?? '-' }}</td></tr>
    </table>

    <table class="offer">
        <thead>
            <tr>
                <th style="width:34px;">No</th>
                <th>Deskripsi Pekerjaan</th>
                <th style="width:60px;">Jumlah</th>
                <th style="width:60px;">Unit</th>
                <th style="width:100px;">Harga Unit</th>
                <th style="width:110px;">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="6" class="lokasi">Lokasi Pekerjaan : {{ $lokasi !== '' ? $lokasi : '-' }}</td>
            </tr>

            @if ($bagianI->isNotEmpty())
            <tr class="section">
                <td class="c">I</td>
                <td colspan="5">Biaya Pengujian</td>
            </tr>
            @foreach ($bagianI as $i => $r)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>
                    <span class="nm">{{ $r->nama }}</span>
                    @if ($r->regulasi)<span class="reg">{{ $r->regulasi }}</span>@endif
                    @if ($r->parameter)<span class="sub">Parameter : {{ $r->parameter }}</span>@endif
                </td>
                <td class="c">{{ $r->qty }}</td>
                <td class="c">{{ $r->satuan }}</td>
                <td class="r">{{ $fmt($r->harga) }}</td>
                <td class="r">{{ $fmt($r->total) }}</td>
            </tr>
            @endforeach
            @endif

            @if ($bagianII->isNotEmpty())
            <tr class="section">
                <td class="c">II</td>
                <td colspan="5">Biaya Sampling</td>
            </tr>
            @foreach ($bagianII as $i => $r)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td><span class="nm">{{ $r->nama }}</span></td>
                <td class="c">{{ $r->qty }}</td>
                <td class="c">{{ $r->satuan }}</td>
                <td class="r">{{ $fmt($r->harga) }}</td>
                <td class="r">{{ $fmt($r->total) }}</td>
            </tr>
            @endforeach
            @endif

            <tr class="total">
                <td colspan="5" style="text-align:right;">Grand Total</td>
                <td class="r">{{ $fmt($grandTotal) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="ket">
        <b>Keterangan :</b>
        <ol>
            <li>Penjadwalan sampling dilakukan setelah PO diterbitkan dan diterima oleh laboratorium.</li>
            <li>Penerbitan laporan hasil uji adalah 12 hari kerja setelah sampel diterima laboratorium.</li>
            <li>Penawaran sudah termasuk biaya pengambilan sampel di lokasi yang disepakati pelanggan.</li>
            <li>Pelunasan dilakukan segera setelah draft hasil uji dikirimkan.</li>
            <li>Pembayaran dapat dilakukan dengan tunai atau transfer bank ke Bank BCA, a.n Andriana, No. Rekening : 2820155729.</li>
            <li>Informasi lebih lanjut dapat menghubungi Ilham 0895-3279-75236.</li>
        </ol>
    </div>

    <table class="sign">
        <tr>
            <td style="width:60%;">
                Hormat kami,<br>
                <div class="sign-box" style="margin-top:6px;">QR / TTD</div>
            </td>
            <td>
                <br><br>
                <div class="dummy">[Nama Penandatangan]</div>
                <div class="dummy">[Jabatan]</div>
            </td>
        </tr>
    </table>
</div>
@endsection
