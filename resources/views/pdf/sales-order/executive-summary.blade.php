@extends('pdf.layouts.document')

@section('title', 'Executive Summary - ' . $so->no_so)

@section('styles')
    .page { padding: 0; }
    .band { background: #fff; color: #1f2937; padding: 14px 15mm 14px; border-bottom: 2px solid #203864; }
    .band .kicker { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #64748b; }
    .band h1 { font-size: 22px; font-weight: 700; color: #203864; margin: 2px 0 8px; }
    .band table { width: 100%; border-collapse: collapse; }
    .band td { font-size: 11px; color: #334155; padding: 1px 0; vertical-align: top; }
    .band td.k { width: 78px; color: #64748b; }
    .band .tag { float: right; border: 1px solid #b45309; color: #b45309; font-size: 9px; letter-spacing: 1px; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; }
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

    .bar-label { font-size: 10px; color: #64748b; margin: 8px 0 3px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; }
    .split-bar { width: 100%; height: 22px; border-radius: 5px; overflow: hidden; background: #e5e9f0; }
    .split-bar div { height: 22px; float: left; color: #fff; font-size: 10px; font-weight: 700; line-height: 22px; text-align: center; white-space: nowrap; }
    .legend { font-size: 10px; color: #64748b; margin: 6px 0 14px; }
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
    .warn { font-size: 10.5px; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 6px 10px; margin: 10px 0 0; }
@endsection

@section('content')
@php
    $fmt = fn($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $neg = fn($v) => ($v < 0 ? '- ' : '') . 'Rp ' . number_format(abs((float) $v), 0, ',', '.');
    // rtrim '0' hanya boleh untuk desimal — kalau $d = 0, "80" akan terpotong jadi "8"
    $pct = fn($v, $d = 1) => $v === null ? '-' : ($d > 0 ? rtrim(rtrim(number_format($v, $d, ',', '.'), '0'), ',') : number_format($v, 0, ',', '.')) . '%';

    $goodReal = $marginReal >= 0;
    $goodEst = $marginEst >= 0;
    $marginRealPct = $pendapatan > 0 ? $marginReal / $pendapatan * 100 : null;
    $marginEstPct = $pendapatan > 0 ? $marginEst / $pendapatan * 100 : null;
    $biayaRealPct = $pendapatan > 0 ? min(100, $biayaReal / $pendapatan * 100) : ($biayaReal > 0 ? 100 : 0);
    $biayaEstPct = $pendapatan > 0 ? min(100, $biayaEst / $pendapatan * 100) : ($biayaEst > 0 ? 100 : 0);
    $pakaiPct = $biayaEst > 0 ? $biayaReal / $biayaEst * 100 : null;
    $maxAkun = max(1, (int) ($akun->max(fn ($a) => max($a->est, $a->real)) ?? 1));
    $caRealPct = $biayaReal > 0 ? $tot['ca_real'] / $biayaReal * 100 : 0;
    $statusLabel = ['on-progress' => 'On Progress', 'completed' => 'Completed', 'cancel' => 'Cancel'][$so->status] ?? $so->status;
@endphp

<div class="band">
    <span class="tag">Internal — Rahasia</span>
    <div class="kicker">Executive Summary · Pendapatan, Biaya Operasional &amp; Margin</div>
    <h1>{{ $so->judul_order ?? '-' }}</h1>
    <table>
        <tr><td class="k">Pelanggan</td><td>{{ $so->nama_pelanggan_display ?? '-' }}</td><td class="k">No. SO</td><td>{{ $so->no_so }}{{ $so->tanggal_so ? ' · ' . \Carbon\Carbon::parse($so->tanggal_so)->format('d/m/Y') : '' }}</td></tr>
        <tr><td class="k">Work Order</td><td>{{ $rows->count() }} WO</td><td class="k">Status SO</td><td>{{ $statusLabel }}</td></tr>
        @if ($so->no_sq)
        <tr><td class="k">Asal SQ</td><td>{{ $so->no_sq }}{{ $so->sq_revisi > 0 ? ' Rev.' . $so->sq_revisi : '' }}</td><td class="k"></td><td></td></tr>
        @endif
    </table>
</div>

<div class="body">
    @if (!$selesai)
        <div class="warn" style="margin:0 0 12px;">SO berstatus <b>{{ $statusLabel }}</b> — realisasi biaya belum final dan masih bisa bertambah. Margin realisasi adalah posisi per {{ now()->format('d/m/Y H:i') }}.</div>
    @endif

    <table class="kpi">
        <tr>
            <td>
                <div class="lbl">Pendapatan (bersih)</div>
                <div class="val" style="color:#203864;">{{ $fmt($pendapatan) }}</div>
                <div class="sub">BOQ + BOQ Other + Sampling, setelah discount</div>
            </td>
            <td>
                <div class="lbl">Biaya Realisasi</div>
                <div class="val" style="color:#b7791f;">{{ $fmt($biayaReal) }}</div>
                <div class="sub">Estimasi {{ $fmt($biayaEst) }}@if ($pakaiPct !== null) · terpakai {{ $pct($pakaiPct, 0) }}@endif</div>
            </td>
            <td class="hero {{ $goodReal ? 'pos-bg' : 'neg-bg' }}">
                <div class="lbl">Margin (Realisasi)</div>
                <div class="val {{ $goodReal ? 'pos' : 'neg' }}">{{ $neg($marginReal) }}</div>
                <div class="sub"><b class="{{ $goodReal ? 'pos' : 'neg' }}">{{ $pct($marginRealPct) }}</b> dari pendapatan · estimasi {{ $neg($marginEst) }} ({{ $pct($marginEstPct) }})</div>
            </td>
        </tr>
    </table>

    <p class="verdict">
        @if ($pendapatan > 0)
            Dari pendapatan <b>{{ $fmt($pendapatan) }}</b>, biaya operasional direncanakan <b>{{ $fmt($biayaEst) }}</b> ({{ $pct($biayaEstPct) }}) dan
            realisasi tercatat <b>{{ $fmt($biayaReal) }}</b> ({{ $pct($biayaRealPct) }}){{ $pakaiPct !== null ? ', atau ' . $pct($pakaiPct, 0) . ' dari estimasi' : '' }}.
            @if ($biayaReal > $biayaEst && $biayaEst > 0)
                Realisasi <b class="neg">melebihi estimasi sebesar {{ $fmt($biayaReal - $biayaEst) }}</b>.
            @elseif ($biayaEst > 0 && $biayaReal == $biayaEst)
                Realisasi <b>sama dengan estimasi</b>.
            @elseif ($biayaEst > 0)
                Realisasi masih <b class="pos">{{ $fmt($biayaEst - $biayaReal) }} di bawah estimasi</b>.
            @endif
            @if ($goodReal)
                Margin saat ini <b class="pos">{{ $fmt($marginReal) }}</b> ({{ $pct($marginRealPct) }}).
            @else
                Biaya realisasi <b class="neg">melebihi pendapatan sebesar {{ $fmt(abs($marginReal)) }}</b>. Perlu ditinjau.
            @endif
        @else
            Belum ada nilai pendapatan pada SO ini.
        @endif
    </p>

    <div class="bar-label">Estimasi</div>
    <div class="split-bar">
        @if ($biayaEstPct > 0)<div style="width:{{ $biayaEstPct }}%;background:{{ $goodEst ? '#c98a1b' : '#b42318' }};">{{ $biayaEstPct >= 12 ? 'Biaya ' . $pct($biayaEstPct, 0) : '' }}</div>@endif
        @if ($goodEst && $biayaEstPct < 100)<div style="width:{{ 100 - $biayaEstPct }}%;background:#0f766e;">{{ (100 - $biayaEstPct) >= 12 ? 'Margin ' . $pct(100 - $biayaEstPct, 0) : '' }}</div>@endif
    </div>
    <div class="bar-label">Realisasi</div>
    <div class="split-bar">
        @if ($biayaRealPct > 0)<div style="width:{{ $biayaRealPct }}%;background:{{ $goodReal ? '#c98a1b' : '#b42318' }};">{{ $biayaRealPct >= 12 ? 'Biaya ' . $pct($biayaRealPct, 0) : '' }}</div>@endif
        @if ($goodReal && $biayaRealPct < 100)<div style="width:{{ 100 - $biayaRealPct }}%;background:#0f766e;">{{ (100 - $biayaRealPct) >= 12 ? 'Margin ' . $pct(100 - $biayaRealPct, 0) : '' }}</div>@endif
    </div>
    <div class="legend"><i style="background:#c98a1b"></i>Biaya operasional<i style="background:#0f766e"></i>Margin</div>

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
                    <tr class="sub"><td>Discount SO{{ $so->discount_persen !== null ? ' (' . $pct((float) $so->discount_persen, 2) . ')' : '' }}</td><td class="r">- {{ $fmt($discountSo) }}</td></tr>
                    <tr class="tot"><td>Pendapatan bersih</td><td class="r">{{ $fmt($pendapatan) }}</td></tr>
                </table>
            </td>
            <td>
                <h2>Rincian Biaya Operasional</h2>
                @if ($akun->isEmpty())
                    <p style="font-size:11px;color:#94a3b8;">Belum ada Budget Plan.</p>
                @else
                <table class="flat">
                    <thead><tr><th>Akun</th><th class="r">Estimasi</th><th class="r">Realisasi</th></tr></thead>
                    @foreach ($akun->take(6) as $a)
                    <tr>
                        <td>{{ $a->nama }}
                            <div class="mini amber"><div style="width:{{ max($a->est, $a->real) / $maxAkun * 100 }}%"></div></div>
                        </td>
                        <td class="r">{{ $fmt($a->est) }}</td>
                        <td class="r {{ $a->real > $a->est ? 'neg' : '' }}">{{ $fmt($a->real) }}</td>
                    </tr>
                    @endforeach
                    @if ($akun->count() > 6)
                    <tr class="sub"><td>Lainnya ({{ $akun->count() - 6 }} akun)</td><td class="r">{{ $fmt($akun->slice(6)->sum('est')) }}</td><td class="r">{{ $fmt($akun->slice(6)->sum('real')) }}</td></tr>
                    @endif
                    <tr class="tot"><td>Total biaya</td><td class="r">{{ $fmt($biayaEst) }}</td><td class="r">{{ $fmt($biayaReal) }}</td></tr>
                </table>
                <p style="font-size:10px;color:#64748b;margin-top:5px;">Cash Advance: estimasi <b>{{ $fmt($tot['ca_est']) }}</b> · realisasi <b>{{ $fmt($tot['ca_real']) }}</b> ({{ $pct($caRealPct, 0) }} dari biaya realisasi)</p>
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
            <tr><th>Work Order</th><th class="r">Pendapatan</th><th class="r">Biaya Estimasi</th><th class="r">Biaya Realisasi</th><th class="r">Margin</th><th class="r" style="width:62px;">% Margin</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
            <tr>
                <td>{{ $r->no }}<br><span style="font-size:10px;color:#64748b;">{{ $r->judul ?? '-' }}</span></td>
                <td class="r">{{ $fmt($r->pendapatan) }}</td>
                <td class="r">{{ $fmt($r->est) }}</td>
                <td class="r">{{ $fmt($r->real) }}</td>
                <td class="r {{ $r->margin >= 0 ? 'pos' : 'neg' }}">{{ $neg($r->margin) }}</td>
                <td class="r {{ $r->margin >= 0 ? 'pos' : 'neg' }}"><b>{{ $pct($r->margin_pct) }}</b></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p style="font-size:9.5px;color:#7a8495;margin-top:4px;">Margin per WO = pendapatan − biaya realisasi, dihitung sebelum Discount SO level dokumen{{ $discountSo > 0 ? ' (' . $fmt($discountSo) . ')' : '' }}.</p>
    @endif

    <p class="note">
        Dokumen internal, bukan untuk pelanggan. Pendapatan bersumber dari BOQ SO saat ini (bersih setelah discount); biaya estimasi adalah total nominal Budget Plan WO &amp; FWO,
        biaya realisasi adalah seluruh realisasi yang tercatat pada Budget Plan tersebut (belum dipilah status verifikasi). {{ ($tot['tanpa'] ?? 0) > 0 ? 'Bagian item yang ditandai &quot;Tanpa realisasi&quot; senilai ' . $fmt($tot['tanpa']) . ' tidak dihitung ke biaya estimasi maupun realisasi (tetap ada di total Budget Plan).' : '' }}
        Margin = pendapatan bersih − biaya. Dicetak {{ now()->format('d/m/Y H:i') }}.
    </p>
</div>
@endsection
