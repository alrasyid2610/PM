@extends('pdf.layouts.document')

@section('title', 'Form Registrasi Sample - ' . ($reg->no_registrasi ?? $reg->id_wo_lab_sample_reg))

@section('styles')
    .page { padding: 0; }
    .band { background: #fff; padding: 14px 15mm 12px; border-bottom: 2px solid #203864; }
    .band .kicker { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #64748b; }
    .band h1 { font-size: 20px; font-weight: 700; color: #203864; margin: 2px 0 2px; }
    .band .no { font-size: 13px; font-weight: 700; color: #0369a1; }
    .body { padding: 14px 15mm 10px; }

    table.meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table.meta td { font-size: 11px; padding: 3px 0; vertical-align: top; color: #1f2937; }
    table.meta td.k { width: 105px; color: #64748b; }
    table.meta td.sep { width: 12px; color: #94a3b8; }
    table.meta td.gap { width: 24px; }

    h2 { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #203864; border-bottom: 2px solid #203864; padding-bottom: 4px; margin: 4px 0 8px; }

    table.smp { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    table.smp th { text-align: left; font-size: 8.5px; letter-spacing: .5px; text-transform: uppercase; color: #64748b; border-bottom: 1.5px solid #cbd3e1; padding: 5px 4px; font-weight: 600; }
    table.smp td { padding: 5px 4px; border-bottom: 1px solid #e5e9f0; vertical-align: top; }
    table.smp tr { page-break-inside: avoid; }
    table.smp td.c { text-align: center; }
    .total { font-size: 10.5px; margin-top: 8px; color: #334155; }

    table.ttd { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 26px; page-break-inside: avoid; }
    table.ttd td { width: 50%; text-align: center; font-size: 11px; vertical-align: top; }
    table.ttd .role { color: #64748b; font-size: 10px; letter-spacing: .4px; text-transform: uppercase; }
    table.ttd .space { height: 62px; }
    table.ttd .line { border-top: 1px solid #1f2937; width: 62%; margin: 0 auto; padding-top: 4px; font-weight: 600; }
    .note { font-size: 9px; color: #7a8495; margin-top: 16px; }
@endsection

@section('content')
@php
    $jenis = ['env' => 'ENV', 'we' => 'WE', 'mp' => 'MP', 'product' => 'Product'];
    $kondisi = ['baik' => 'Baik', 'rusak' => 'Rusak', 'tidak_lengkap' => 'Tidak Lengkap'];
    $tgl = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '-';
@endphp

<div class="band">
    <div class="kicker">Lab Sample Reg</div>
    <h1>Form Registrasi Sample</h1>
    <div class="no">{{ $reg->no_registrasi ?? '-' }}</div>
</div>

<div class="body">
    <table class="meta">
        <tr>
            <td class="k">Tanggal Registrasi</td><td class="sep">:</td><td>{{ $tgl($reg->tanggal_registrasi) }}</td>
            <td class="gap"></td>
            <td class="k">No. Work Order</td><td class="sep">:</td><td>{{ $wo->no_wo ?? '-' }}</td>
        </tr>
        <tr>
            <td class="k">Diterima Oleh</td><td class="sep">:</td><td>{{ $reg->nama_penerima ?? '-' }}</td>
            <td class="gap"></td>
            <td class="k">Pekerjaan</td><td class="sep">:</td><td>{{ $wo->judul_pekerjaan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="k">Pelanggan</td><td class="sep">:</td><td>{{ $wo->nama_pelanggan_display ?: '-' }}</td>
            <td class="gap"></td>
            <td class="k">No. Sales Order</td><td class="sep">:</td><td>{{ $wo->no_so ?? '-' }}</td>
        </tr>
        <tr>
            <td class="k">Lokasi / Site</td><td class="sep">:</td><td>{{ $wo->nama_site ?? '-' }}</td>
            <td class="gap"></td>
            <td class="k">Keterangan</td><td class="sep">:</td><td>{{ $reg->keterangan ?: '-' }}</td>
        </tr>
    </table>

    <h2>Daftar Sample</h2>
    <table class="smp">
        <thead>
            <tr>
                <th style="width:22px;">No</th>
                <th>No. Sample</th>
                <th>No. Reg Lab</th>
                <th>Jenis</th>
                <th>BOQ</th>
                <th style="width:30%;">Parameter</th>
                <th>Titik Lokasi</th>
                <th>FWO</th>
                <th>Tgl Ambil</th>
                <th>Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($samples as $i => $s)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td><b>{{ $s->no_sample ?? '-' }}</b></td>
                <td>{{ $s->no_reg_lab ?? '-' }}</td>
                <td>{{ $jenis[$s->jenis_sample] ?? ($s->jenis_sample ?: '-') }}</td>
                <td>{{ $s->nama_boq ?? '-' }}</td>
                <td style="font-size:8.5px;">{{ ($p = $parameter[$s->id_lab_sample] ?? []) ? implode(', ', $p) : '-' }}</td>
                <td>{{ $s->titik_lokasi ?: '-' }}</td>
                <td>{{ $s->id_fwo ? ($fwoNo[$s->id_fwo] ?? '-') : '-' }}</td>
                <td>{{ $tgl($s->tanggal_pengambilan) }}</td>
                <td>{{ $kondisi[$s->kondisi_sample] ?? ($s->kondisi_sample ?: '-') }}</td>
            </tr>
            @empty
            <tr><td colspan="10" style="text-align:center;color:#94a3b8;padding:14px;">Tidak ada sample pada pendaftaran ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="total">Total: <b>{{ $samples->count() }}</b> sample</div>

    <table class="ttd">
        <tr>
            <td>
                <div class="role">Diserahkan oleh</div>
                <div class="space"></div>
                <div class="line">( ............................ )</div>
            </td>
            <td>
                <div class="role">Diterima oleh (Lab)</div>
                <div class="space"></div>
                <div class="line">{{ $reg->nama_penerima ?: '( ............................ )' }}</div>
            </td>
        </tr>
    </table>

    <p class="note">Dokumen ini dicetak dari sistem pada {{ now()->format('d/m/Y H:i') }}. Satu form mencakup seluruh sample yang didaftarkan pada tanggal dan penerima yang sama untuk Work Order ini.</p>
</div>
@endsection
