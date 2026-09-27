@extends('pdf.layouts.document')

@section('title', 'Executive Summary - ' . $sq->no_sq)

@section('styles')
    .page { padding: 0; }
    .band { background: #203864; color: #fff; padding: 20px 15mm 16px; }
    .band .kicker { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #a9bbe0; }
    .band h1 { font-size: 22px; font-weight: 700; margin: 2px 0 8px; }
    .band table { width: 100%; border-collapse: collapse; }
    .band td { font-size: 11px; color: #dbe4f7; padding: 1px 0; vertical-align: top; }
    .band td.k { width: 78px; color: #a9bbe0; }
    .band .tag { float: right; border: 1px solid #f2b45c; color: #f2b45c; font-size: 9px; letter-spacing: 1px; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; }
    .body { padding: 16px 15mm 12px; }

    .kpi { width: calc(100% + 20px); border-collapse: separate; border-spacing: 10px 0; margin: 0 -10px; }
    .kpi td { width: 33.33%; border: 1px solid #d9dee8; border-radius: 8px; padding: 12px 14px; vertical-align: top; }
    .kpi .lbl { font-size: 10px; letter-spacing: .8px; text-transform: uppercase; color: #64748b; font-weight: 600; }
    .kpi .val { font-size: 21px; font-weight: 700; margin-top: 4px; white-space: nowrap; }
    .kpi .sub { font-size: 10.5px; color: #64748b; margin-top: 2px; }
    .kpi td.hero { border-width: 2px; }
    .pos { color: #0f766e; } .neg { color: #b42318; }
    .hero.pos-bg { border-color: #0f766e; background: #eefaf7; }
    .hero.neg-bg { border-color: #b42318; background: #fdf0ee; }

    .verdict { margin: 14px 0 6px; font-size: 12.5px; line-height: 1.5; color: #1f2937; }
    .verdict b { color: #203864; }

    .split-bar { width: 100%; height: 22px; border-radius: 5px; overflow: hidden; background: #e5e9f0; }
    .split-bar div { height: 22px; float: left; color: #fff; font-size: 10px; font-weight: 700; line-height: 22px; text-align: center; white-space: nowrap; }
    .legend { font-size: 10px; color: #64748b; margin: 4px 0 14px; }
    .legend i { display: inline-block; width: 9px; height: 9px; border-radius: 2px; margin: 0 4px 0 10px; vertical-align: -1px; }
    .legend i:first-child { margin-left: 0; }

    h2 { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #203864; border-bottom: 2px solid #203864; padding-bottom: 4px; margin: 14px 0 8px; }
    .two { width: 100%; border-collapse: separate; border-spacing: 0; }
    .two > tbody > tr > td { vertical-align: top; width: 50%; }
    .two > tbody > tr > td:first-child { padding-right: 12px; }
    .two > tbody > tr > td:last-child { padding-left: 12px; }

    table.flat { width: 100%; border-collapse: collapse; font-size: 11px; }
    table.flat td, table.flat th { padding: 5px 6px; border-bottom: 1px solid #e5e9f0; }
    table.flat th { text-align: left; font-size: 9.5px; letter-spacing: .6px; text-transform: uppercase; color: #64748b; border-bottom: 1.5px solid #cbd3e1; font-weight: 600; }
    table.flat .r { text-align: right; white-space: nowrap; }
    table.flat tr.tot td { font-weight: 700; border-top: 1.5px solid #203864; border-bottom: 0; background: #f3f6fb; }
    table.flat tr.sub td { color: #64748b; }
    .mini { height: 7px; background: #e5e9f0; border-radius: 4px; overflow: hidden; margin-top: 3px; }
    .mini div { height: 7px; background: #1a56db; }
    .mini.amber div { background: #c98a1b; }
    .note { font-size: 9.5px; color: #7a8495; margin-top: 12px; border-top: 1px solid #e5e9f0; padding-top: 8px; line-height: 1.45; }
@endsection

@section('content')
@php
    $fmt = fn($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $neg = fn($v) => ($v < 0 ? '- ' : '') . 'Rp ' . number_format(abs((float) $v), 0, ',', '.');
    // rtrim '0' hanya boleh untuk desimal — kalau $d = 0, "80" akan terpotong jadi "8"
    $pct = fn($v, $d = 1) => $v === null ? '-' : ($d > 0 ? rtrim(rtrim(number_format($v, $d, ',', '.'), '0'), ',') : number_format($v, 0, ',', '.')) . '%';
    $good = $margin >= 0;
    $biayaPct = $pendapatan > 0 ? min(100, $biaya / $pendapatan * 100) : ($biaya > 0 ? 100 : 0);
    $marginBarPct = $good ? max(0, 100 - $biayaPct) : 0;
    $maxAkun = max(1, (int) ($akun->max('nominal') ?? 1));
    $caPct = $biaya > 0 ? $tot['cash_advance'] / $biaya * 100 : 0;
@endphp

<div class="band">
    <span class="tag">Internal — Rahasia</span>
    <div class="kicker">Executive Summary · Analisa Pendapatan &amp; Biaya Operasional</div>
    <h1>{{ $sq->judul_order ?? '-' }}</h1>
    <table>
        <tr><td class="k">Pelanggan</td><td>{{ $sq->nama_pelanggan_display ?? '-' }}</td><td class="k">No. SQ</td><td>{{ $sq->no_sq }}{{ $sq->revisi > 0 ? ' Rev.' . $sq->revisi : '' }}</td></tr>
        <tr><td class="k">Work Order</td><td>{{ $rows->count() }} WO</td><td class="k">No. SO</td><td>{{ $sq->no_so ?? '-' }}{{ $sq->tanggal_so ? ' · ' . \Carbon\Carbon::parse($sq->tanggal_so)->format('d/m/Y') : '' }}</td></tr>
    </table>
</div>

<div class="body">
    <table class="kpi">
        <tr>
            <td>
                <div class="lbl">Pendapatan (bersih)</div>
                <div class="val" style="color:#203864;">{{ $fmt($pendapatan) }}</div>
                <div class="sub">BOQ + BOQ Other + Sampling, setelah discount</div>
            </td>
            <td>
                <div class="lbl">Biaya Operasional</div>
                <div class="val" style="color:#b7791f;">{{ $fmt($biaya) }}</div>
                <div class="sub">Total Budget Plan · {{ $pct($biayaPct) }} dari pendapatan</div>
            </td>
            <td class="hero {{ $good ? 'pos-bg' : 'neg-bg' }}">
                <div class="lbl">Estimasi Margin</div>
                <div class="val {{ $good ? 'pos' : 'neg' }}">{{ $neg($margin) }}</div>
                <div class="sub"><b class="{{ $good ? 'pos' : 'neg' }}">{{ $pct($marginPct) }}</b> dari pendapatan</div>
            </td>
        </tr>
    </table>

    <p class="verdict">
        @if ($pendapatan > 0)
            Dari pendapatan <b>{{ $fmt($pendapatan) }}</b>, biaya operasional yang direncanakan <b>{{ $fmt($biaya) }}</b> ({{ $pct($biayaPct) }}).
            @if ($good)
                Sisa <b class="pos">{{ $fmt($margin) }}</b> ({{ $pct($marginPct) }}) menjadi estimasi margin.
            @else
                Biaya <b class="neg">melebihi pendapatan sebesar {{ $fmt(abs($margin)) }}</b>. Perlu ditinjau ulang.
            @endif
        @else
            Belum ada nilai pendapatan pada SQ ini.
        @endif
    </p>

    <div class="split-bar">
        @if ($biayaPct > 0)<div style="width:{{ $biayaPct }}%;background:{{ $good ? '#c98a1b' : '#b42318' }};">{{ $biayaPct >= 12 ? 'Biaya ' . $pct($biayaPct, 0) : '' }}</div>@endif
        @if ($marginBarPct > 0)<div style="width:{{ $marginBarPct }}%;background:#0f766e;">{{ $marginBarPct >= 12 ? 'Margin ' . $pct($marginBarPct, 0) : '' }}</div>@endif
    </div>
    <div class="legend"><i style="background:{{ $good ? '#c98a1b' : '#b42318' }}"></i>Biaya operasional<i style="background:#0f766e"></i>Margin</div>

    <table class="two">
        <tr>
            <td>
                <h2>Rincian Pendapatan</h2>
                <table class="flat">
                    <tr><td>BOQ (sebelum discount)</td><td class="r">{{ $fmt($tot['boq_gross']) }}</td></tr>
                    <tr class="sub"><td>Discount BOQ</td><td class="r">- {{ $fmt($tot['boq_disc']) }}</td></tr>
                    <tr><td>BOQ Other</td><td class="r">{{ $fmt($tot['other']) }}</td></tr>
                    <tr><td>BOQ Sampling</td><td class="r">{{ $fmt($tot['sampling']) }}</td></tr>
                    <tr class="sub"><td>Subtotal</td><td class="r">{{ $fmt($subtotal) }}</td></tr>
                    <tr class="sub"><td>Discount SQ</td><td class="r">- {{ $fmt($discountSq) }}</td></tr>
                    <tr class="tot"><td>Pendapatan bersih</td><td class="r">{{ $fmt($pendapatan) }}</td></tr>
                </table>
            </td>
            <td>
                <h2>Rincian Biaya Operasional</h2>
                @if ($akun->isEmpty())
                    <p style="font-size:11px;color:#94a3b8;">Belum ada Budget Plan.</p>
                @else
                <table class="flat">
                    @foreach ($akun->take(6) as $a)
                    <tr>
                        <td>{{ $a->nama }}
                            <div class="mini amber"><div style="width:{{ $a->nominal / $maxAkun * 100 }}%"></div></div>
                        </td>
                        <td class="r">{{ $fmt($a->nominal) }}<br><span style="font-size:9.5px;color:#94a3b8;">{{ $pct($biaya > 0 ? $a->nominal / $biaya * 100 : 0, 0) }}</span></td>
                    </tr>
                    @endforeach
                    @if ($akun->count() > 6)
                    <tr class="sub"><td>Lainnya ({{ $akun->count() - 6 }} akun)</td><td class="r">{{ $fmt($akun->slice(6)->sum('nominal')) }}</td></tr>
                    @endif
                    <tr class="tot"><td>Total biaya</td><td class="r">{{ $fmt($biaya) }}</td></tr>
                </table>
                <p style="font-size:10px;color:#64748b;margin-top:5px;">Cash Advance: <b>{{ $fmt($tot['cash_advance']) }}</b> ({{ $pct($caPct, 0) }} dari biaya)</p>
                @endif
            </td>
        </tr>
    </table>

    <h2>Kinerja per Work Order</h2>
    @if ($rows->isEmpty())
        <p style="font-size:11px;color:#94a3b8;">Belum ada Work Order.</p>
    @else
    <table class="flat">
        <thead>
            <tr><th>Work Order</th><th class="r">Pendapatan</th><th class="r">Biaya</th><th class="r">Margin</th><th class="r" style="width:70px;">% Margin</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
            <tr>
                <td>{{ $r->no }}<br><span style="font-size:10px;color:#64748b;">{{ $r->judul ?? '-' }}</span></td>
                <td class="r">{{ $fmt($r->pendapatan) }}</td>
                <td class="r">{{ $fmt($r->biaya) }}</td>
                <td class="r {{ $r->margin >= 0 ? 'pos' : 'neg' }}">{{ $neg($r->margin) }}</td>
                <td class="r {{ $r->margin >= 0 ? 'pos' : 'neg' }}"><b>{{ $pct($r->margin_pct) }}</b></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p style="font-size:9.5px;color:#7a8495;margin-top:4px;">Margin per WO dihitung sebelum Discount SQ level dokumen{{ $discountSq > 0 ? ' (' . $fmt($discountSq) . ')' : '' }}.</p>
    @endif

    <p class="note">
        Dokumen internal, bukan untuk pelanggan. Angka bersumber dari data SQ yang terkunci (estimasi saat penawaran), bukan realisasi.
        Pendapatan dihitung bersih setelah discount; biaya operasional adalah total nominal Budget Plan WO.
        Margin = pendapatan bersih − biaya operasional. Dicetak {{ now()->format('d/m/Y H:i') }}.
    </p>
</div>
@endsection
