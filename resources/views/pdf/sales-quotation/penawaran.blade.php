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
    .offer tr { page-break-inside: avoid; break-inside: avoid; }
    .offer thead { display: table-header-group; }
    .offer th { background: #dbe8f5; font-weight: 700; text-align: center; }
    .offer td.r { text-align: right; white-space: nowrap; }
    .offer td.c { text-align: center; }
    .offer tr.section td { font-weight: 700; background: #f1f5f9; }
    /* Judul bagian (I/II) tidak boleh tertinggal sendirian di dasar halaman; Grand Total tidak boleh sendirian di halaman baru */
    .offer tr.section { break-after: avoid; page-break-after: avoid; }
    .offer tr.total { break-before: avoid; page-break-before: avoid; }
    /* Sub Total + PPN (hanya bila memakai rekening PPN): satu blok dengan Grand Total */
    .offer tr.subt { break-after: avoid; page-break-after: avoid; }
    .offer tr.subt td { font-weight: 700; background: #dbe8f5; }
    .offer tr.total td { font-weight: 700; background: #203864; color: #fff; border-color: #203864; }
    .offer .sub { font-size: 10px; color: #334155; display: block; margin-top: 2px; }
    .offer .nm { font-weight: 700; }
    .offer .reg { display: block; font-weight: 400; margin-top: 2px; }

    .lokasi { font-size: 11px; font-style: italic; }

    .ket { margin-top: 14px; font-size: 11px; }
    .ket ol { margin: 4px 0 0 22px; }
    .ket li { margin-bottom: 2px; }

    .sign { margin-top: 22px; font-size: 11px; page-break-inside: avoid; break-inside: avoid; }
    .sign-inner { display: inline-block; text-align: center; min-width: 150px; }
    .sign-img { display: block; width: 190px; height: auto; margin: 4px auto 6px; }
    .sign-nama { font-weight: 700; font-size: 12px; }
    .sign-jabatan { font-size: 11px; }
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
                <th style="width:44px;">Freq</th>
                <th style="width:100px;">Harga Unit</th>
                <th style="width:110px;">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="7" class="lokasi">
                    @forelse ($lokasi as $i => $l)
                        <div @if ($i > 0) style="margin-top:4px;" @endif>
                            @if ($i === 0)Lokasi Pekerjaan : @endif{{ $l->nama !== '' ? $l->nama : '-' }}
                            @if ($l->alamat !== '')<br>{!! nl2br(e($l->alamat)) !!}@endif
                        </div>
                    @empty
                        Lokasi Pekerjaan : -
                    @endforelse
                </td>
            </tr>

            @if ($bagianI->isNotEmpty())
            <tr class="section">
                <td class="c">I</td>
                <td colspan="6">Biaya Pengujian</td>
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
                <td class="c">{{ $r->freq }}</td>
                <td class="r">{{ $fmt($r->harga) }}</td>
                <td class="r">{{ $fmt($r->total) }}</td>
            </tr>
            @endforeach
            @endif

            @if ($bagianII->isNotEmpty())
            <tr class="section">
                <td class="c">II</td>
                <td colspan="6">Biaya Sampling</td>
            </tr>
            @foreach ($bagianII as $i => $r)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td><span class="nm">{{ $r->nama }}</span></td>
                <td class="c">{{ $r->qty }}</td>
                <td class="c">{{ $r->satuan }}</td>
                <td class="c">{{ $r->freq }}</td>
                <td class="r">{{ $fmt($r->harga) }}</td>
                <td class="r">{{ $fmt($r->total) }}</td>
            </tr>
            @endforeach
            @endif

            @if ($pakaiPpn)
            <tr class="subt">
                <td colspan="6" style="text-align:right;">Sub Total</td>
                <td class="r">{{ $fmt($subtotal) }}</td>
            </tr>
            <tr class="subt">
                <td colspan="6" style="text-align:right;">PPN</td>
                <td class="r">{{ $fmt($ppn) }}</td>
            </tr>
            @endif
            <tr class="total">
                <td colspan="6" style="text-align:right;">Grand Total</td>
                <td class="r">{{ $fmt($grandTotal) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($keterangan->isNotEmpty())
    <div class="ket">
        <b>Keterangan :</b>
        <ol>
            @foreach ($keterangan as $poin)
            <li>{!! nl2br(e($poin)) !!}</li>
            @endforeach
        </ol>
    </div>
    @endif

    {{-- Tanda tangan di kiri bawah: Hormat kami → kotak QR/TTD → nama (tebal) → jabatan, rata tengah satu blok --}}
    <div class="sign">
        <div class="sign-inner">
            <div>Hormat kami,</div>
            {{-- Gambar tanda tangan elektronik (dari layanan e-sign, tanpa baris kode) — sementara 1 gambar tetap --}}
            <img class="sign-img" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/ttd-penawaran.png'))) }}" alt="Tanda tangan">
            {{-- Nama & jabatan penandatangan: hardcode sementara --}}
            <div class="sign-nama">Fuad P</div>
            <div class="sign-jabatan">Business Manager</div>
        </div>
    </div>
</div>
@endsection
