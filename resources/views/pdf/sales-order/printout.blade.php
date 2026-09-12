@extends('pdf.layouts.document')

@section('title', 'Printout SO - ' . $so->no_so)

@section('styles')
    .page { padding: 18px 15mm; }
    h2.doc-title { text-align: center; font-size: 15px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
    p.doc-sub { text-align: center; font-size: 11px; color: #64748b; margin-bottom: 4px; }
    p.doc-warning { text-align: center; font-size: 10px; color: #b45309; margin-bottom: 16px; }
    .section-title {
        background: #203864; color: #fff; font-size: 12px; font-weight: 600;
        padding: 5px 10px; margin: 18px 0 8px; text-transform: uppercase;
    }
    .section-title.sub { background: #475569; margin-top: 12px; }
    .cols { width: 100%; border-collapse: collapse; }
    .cols td { width: 50%; vertical-align: top; padding: 0 8px 0 0; }
    .wo-block { border: 1px solid #d1d5db; border-radius: 4px; padding: 10px 12px; margin-bottom: 14px; page-break-inside: avoid; }
    .wo-block-header { display: block; font-size: 12px; font-weight: bold; color: #203864; margin-bottom: 6px; }
    .status-badge { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; }
    .status-onprogress { background: #eff6ff; color: #1d4ed8; }
    .status-completed { background: #f0fdf4; color: #15803d; }
    .text-right { text-align: right; }
    .text-muted { color: #94a3b8; }
    .boq-table td, .boq-table th { font-size: 10.5px; }
    .no-boq { font-size: 11px; color: #94a3b8; font-style: italic; padding: 6px 0; }
    tfoot td { font-weight: bold; background: #f1f5f9; }

    /* table-layout:auto (default .boq-table dari layout) membiarkan kolom
       melebar sesuai konten terpanjang — kolom Testing Standard/Matriks
       Sample yang isinya paragraf panjang bikin total lebar tabel lebih besar
       dari lebar halaman, jadi kolom kanan (Subtotal/Keterangan) terdorong
       keluar dari kartu WO. Fix: table-layout:fixed + lebar kolom eksplisit
       (colgroup) + word-wrap supaya teks panjang bungkus di dalam kolomnya,
       bukan melebarkan tabel. */
    .wo-boq-table, .wo-boq-tambahan-table { table-layout: fixed; }
    .wo-boq-table th, .wo-boq-table td,
    .wo-boq-tambahan-table th, .wo-boq-tambahan-table td {
        word-wrap: break-word; overflow-wrap: break-word;
    }
@endsection

@section('content')
@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '-';
    $fmtMoney = fn($v) => 'Rp ' . number_format((float) ($v ?? 0), 0, ',', '.');
    $statusClass = fn($s) => $s === 'completed' ? 'status-completed' : 'status-onprogress';
@endphp

<div class="page">
    <h2 class="doc-title">Printout Sales Order</h2>
    <p class="doc-sub">{{ $so->no_so }} — {{ $so->judul_order ?? '-' }}</p>
    <p class="doc-warning">*Dokumen sementara untuk pengecekan inputan data — bukan dokumen resmi/final</p>

    <div class="section-title">Informasi Sales Order</div>
    <table class="cols">
        <tr>
            <td>
                <table class="info-table">
                    <tr><td class="label">No. SO</td><td class="colon">:</td><td class="value">{{ $so->no_so }}</td></tr>
                    <tr><td class="label">No. Kontrak</td><td class="colon">:</td><td class="value">{{ $so->no_contract ?? '-' }}</td></tr>
                    <tr><td class="label">Judul Order</td><td class="colon">:</td><td class="value">{{ $so->judul_order ?? '-' }}</td></tr>
                    <tr><td class="label">Tanggal SO</td><td class="colon">:</td><td class="value">{{ $fmtDate($so->tanggal_so) }}</td></tr>
                    <tr><td class="label">No. PO</td><td class="colon">:</td><td class="value">{{ $so->tidak_ada_po ? '(Tidak ada PO)' : ($so->no_po ?? '-') }}</td></tr>
                    <tr><td class="label">Tanggal PO</td><td class="colon">:</td><td class="value">{{ $so->tidak_ada_po ? '-' : $fmtDate($so->tanggal_po) }}</td></tr>
                    <tr><td class="label">Periode</td><td class="colon">:</td><td class="value">{{ $fmtDate($so->tanggal_mulai) }} s/d {{ $fmtDate($so->tanggal_selesai) }}</td></tr>
                    <tr><td class="label">Office</td><td class="colon">:</td><td class="value">{{ $so->nama_office ?? '-' }}</td></tr>
                    <tr><td class="label">Status</td><td class="colon">:</td><td class="value">{{ ucfirst($so->status ?? '-') }}</td></tr>
                </table>
            </td>
            <td>
                <table class="info-table">
                    <tr><td class="label">PIC Input</td><td class="colon">:</td><td class="value">{{ $so->nama_pic_input ?? '-' }}</td></tr>
                    <tr><td class="label">PIC Order</td><td class="colon">:</td><td class="value">{{ $so->nama_pic_order ?? '-' }}</td></tr>
                    <tr><td class="label">Marketing Internal</td><td class="colon">:</td><td class="value">{{ $so->nama_marketing_internal ?? '-' }}</td></tr>
                    <tr><td class="label">Marketing Eksternal</td><td class="colon">:</td><td class="value">{{ $so->nama_marketing_eksternal ?? '-' }}</td></tr>
                    <tr><td class="label">Cara Pembayaran</td><td class="colon">:</td><td class="value">{{ $so->cara_pembayaran ?? '-' }}</td></tr>
                    <tr><td class="label">Keterangan</td><td class="colon">:</td><td class="value">{{ $so->keterangan ?? '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title sub">Data Pelanggan</div>
    <table class="cols">
        <tr>
            <td>
                <table class="info-table">
                    <tr><td colspan="3"><strong>Pemesan</strong></td></tr>
                    <tr><td class="label">Perusahaan</td><td class="colon">:</td><td class="value">{{ $so->nama_pelanggan ?? '-' }}</td></tr>
                    <tr><td class="label">Site</td><td class="colon">:</td><td class="value">{{ $so->nama_site_pelanggan ?? '-' }}</td></tr>
                    <tr><td class="label">PIC</td><td class="colon">:</td><td class="value">{{ $so->pic_pelanggan ?? '-' }}</td></tr>
                </table>
            </td>
            <td>
                <table class="info-table">
                    <tr><td colspan="3"><strong>Pengiriman (Delivery)</strong></td></tr>
                    <tr><td class="label">Perusahaan</td><td class="colon">:</td><td class="value">{{ $so->nama_pelanggan_delivery ?? '-' }}</td></tr>
                    <tr><td class="label">Site</td><td class="colon">:</td><td class="value">{{ $so->nama_site_delivery ?? '-' }}</td></tr>
                    <tr><td class="label">PIC</td><td class="colon">:</td><td class="value">{{ $so->pic_delivery ?? '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
    <table class="info-table" style="margin-top:6px;">
        <tr><td colspan="3"><strong>Pembayaran (Payment)</strong></td></tr>
        <tr><td class="label">Perusahaan</td><td class="colon">:</td><td class="value">{{ $so->nama_pelanggan_payment ?? '-' }}</td></tr>
        <tr><td class="label">Site</td><td class="colon">:</td><td class="value">{{ $so->nama_site_payment ?? '-' }}</td></tr>
        <tr><td class="label">PIC</td><td class="colon">:</td><td class="value">{{ $so->pic_payment ?? '-' }}</td></tr>
    </table>

    <div class="section-title">Daftar Work Order ({{ $wos->count() }})</div>
    @if ($wos->isEmpty())
        <p class="no-boq">Belum ada Work Order pada Sales Order ini.</p>
    @else
        <table class="boq-table wo-boq-table" style="margin-bottom:16px;">
            <colgroup>
                <col style="width:5%">
                <col style="width:14%">
                <col style="width:36%">
                <col style="width:20%">
                <col style="width:15%">
                <col style="width:10%">
            </colgroup>
            <thead>
                <tr>
                    <th class="boq-th boq-no">No</th>
                    <th class="boq-th">No. WO</th>
                    <th class="boq-th">Judul Pekerjaan</th>
                    <th class="boq-th">Site</th>
                    <th class="boq-th">Periode</th>
                    <th class="boq-th">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($wos as $i => $wo)
                <tr>
                    <td class="boq-td boq-center">{{ $i + 1 }}</td>
                    <td class="boq-td">{{ $wo->no_wo }}</td>
                    <td class="boq-td">{{ $wo->judul_pekerjaan ?? '-' }}</td>
                    <td class="boq-td">{{ $wo->nama_site ?? '-' }}</td>
                    <td class="boq-td">{{ $fmtDate($wo->tanggal_mulai) }} s/d {{ $fmtDate($wo->tanggal_selesai) }}</td>
                    <td class="boq-td"><span class="status-badge {{ $statusClass($wo->status) }}">{{ $wo->status === 'completed' ? 'Completed' : 'On Progress' }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @foreach ($wos as $wo)
        @php
            $items = $boqRows->get($wo->id_wo, collect());
            $totalNilai = $items->sum(fn($r) => (int) ($r->qty ?? 0) * (int) ($r->harga ?? 0));
            $otherItems = $boqOtherRows->get($wo->id_wo, collect());
            $totalOther = $otherItems->sum(fn($r) => (int) ($r->qty ?? 0) * (int) ($r->harga ?? 0));
            $samplingItems = $boqSamplingRows->get($wo->id_wo, collect());
            $totalSampling = $samplingItems->sum(fn($r) => (int) ($r->qty ?? 0) * (int) ($r->harga ?? 0));
        @endphp
        <div class="wo-block">
            <span class="wo-block-header">{{ $wo->no_wo }} — {{ $wo->judul_pekerjaan ?? '-' }}</span>
            <table class="cols">
                <tr>
                    <td>
                        <table class="info-table">
                            <tr><td class="label">Site Pelanggan</td><td class="colon">:</td><td class="value">{{ $wo->nama_site ?? '-' }}</td></tr>
                            <tr><td class="label">PIC Pekerjaan</td><td class="colon">:</td><td class="value">{{ $wo->nama_pic ?? '-' }}</td></tr>
                            <tr><td class="label">Frekuensi</td><td class="colon">:</td><td class="value">
                                {{ $wo->interval_bulan ? ($intervalLabels[$wo->interval_bulan] ?? $wo->interval_bulan . ' bln') . ' (ke-' . $wo->no_urut_period . ')' : '-' }}
                            </td></tr>
                        </table>
                    </td>
                    <td>
                        <table class="info-table">
                            <tr><td class="label">Periode</td><td class="colon">:</td><td class="value">{{ $fmtDate($wo->tanggal_mulai) }} s/d {{ $fmtDate($wo->tanggal_selesai) }}</td></tr>
                            <tr><td class="label">Status</td><td class="colon">:</td><td class="value"><span class="status-badge {{ $statusClass($wo->status) }}">{{ $wo->status === 'completed' ? 'Completed' : 'On Progress' }}</span></td></tr>
                            <tr><td class="label">Keterangan</td><td class="colon">:</td><td class="value">{{ $wo->keterangan ?? '-' }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="section-title sub">BOQ</div>
            @if ($items->isEmpty())
                <p class="no-boq">Belum ada BOQ pada WO ini.</p>
            @else
                <table class="boq-table wo-boq-table">
                    <colgroup>
                        <col style="width:4%">
                        <col style="width:15%">
                        <col style="width:17%">
                        <col style="width:17%">
                        <col style="width:5%">
                        <col style="width:7%">
                        <col style="width:11%">
                        <col style="width:11%">
                        <col style="width:13%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="boq-th boq-no">No</th>
                            <th class="boq-th">Testing Point</th>
                            <th class="boq-th">Testing Standard</th>
                            <th class="boq-th">Matriks Sample</th>
                            <th class="boq-th boq-center">Qty</th>
                            <th class="boq-th">Satuan</th>
                            <th class="boq-th text-right">Harga</th>
                            <th class="boq-th text-right">Subtotal</th>
                            <th class="boq-th">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $j => $r)
                        <tr>
                            <td class="boq-td boq-center">{{ $j + 1 }}</td>
                            <td class="boq-td">{{ $r->nama_testing_point ?? $r->item_produk_alternate ?? '-' }}</td>
                            <td class="boq-td">
                                @if ($r->standard_nomor || $r->standard_judul)
                                    {{ $r->standard_nomor }}{{ $r->standard_nomor && $r->standard_judul ? ' — ' : '' }}{{ $r->standard_judul }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="boq-td">
                                @if ($r->matriks_kode || $r->matriks_judul)
                                    {{ $r->matriks_kode }}{{ $r->matriks_kode && $r->matriks_judul ? ' — ' : '' }}{{ $r->matriks_judul }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="boq-td boq-center">{{ $r->qty }}</td>
                            <td class="boq-td">{{ $r->satuan ?? '-' }}</td>
                            <td class="boq-td text-right">{{ $fmtMoney($r->harga) }}</td>
                            <td class="boq-td text-right">{{ $fmtMoney(($r->qty ?? 0) * ($r->harga ?? 0)) }}</td>
                            <td class="boq-td">{{ $r->keterangan ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="boq-td text-right" colspan="7">Total Nilai BOQ</td>
                            <td class="boq-td text-right">{{ $fmtMoney($totalNilai) }}</td>
                            <td class="boq-td"></td>
                        </tr>
                    </tfoot>
                </table>
            @endif

            <div class="section-title sub">BOQ Other</div>
            @if ($otherItems->isEmpty())
                <p class="no-boq">Belum ada BOQ Other pada WO ini.</p>
            @else
                @include('pdf.sales-order._boq-tambahan-table', ['items' => $otherItems, 'total' => $totalOther])
            @endif

            <div class="section-title sub">BOQ Sampling</div>
            @if ($samplingItems->isEmpty())
                <p class="no-boq">Belum ada BOQ Sampling pada WO ini.</p>
            @else
                @include('pdf.sales-order._boq-tambahan-table', ['items' => $samplingItems, 'total' => $totalSampling])
            @endif
        </div>
        @endforeach
    @endif
</div>
@endsection
