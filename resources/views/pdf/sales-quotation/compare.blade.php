@extends('pdf.layouts.document')

@section('title', 'Perbandingan SQ - ' . $d['ini']['no_sq'])

@section('styles')
    .page { padding: 0; }
    .band { background: #fff; padding: 12px 15mm 12px; border-bottom: 2px solid #203864; }
    .band .kicker { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #64748b; }
    .band h1 { font-size: 20px; font-weight: 700; color: #203864; margin: 2px 0 8px; }
    .band table { width: 100%; border-collapse: collapse; }
    .band td { font-size: 11px; color: #334155; padding: 1px 0; vertical-align: top; }
    .band td.k { width: 92px; color: #64748b; }
    .band .tag { float: right; border: 1px solid #b45309; color: #b45309; font-size: 9px; letter-spacing: 1px; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; }
    .body { padding: 14px 15mm 8px; }

    .kpi { width: calc(100% + 16px); border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px; }
    .kpi td { width: 25%; border: 1px solid #d9dee8; border-radius: 8px; padding: 10px 12px; vertical-align: top; }
    .kpi .lbl { font-size: 9px; letter-spacing: .8px; text-transform: uppercase; color: #64748b; font-weight: 600; }
    .kpi .val { font-size: 15px; font-weight: 700; margin-top: 3px; white-space: nowrap; color: #0f172a; }
    .kpi .val small { font-size: 10px; color: #64748b; font-weight: 600; }
    .kpi .sub { font-size: 9.5px; color: #64748b; margin-top: 2px; }
    .pos { color: #0f766e; font-weight: 700; } .neg { color: #b42318; font-weight: 700; } .flat { color: #94a3b8; }

    .verdict { margin: 12px 0 4px; padding: 8px 10px; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 6px; font-size: 11.5px; line-height: 1.5; color: #3b0764; }
    .arah { font-size: 10.5px; color: #64748b; margin: 8px 0 0; }

    h2 { font-size: 10.5px; letter-spacing: 1px; text-transform: uppercase; color: #203864; border-bottom: 2px solid #203864; padding-bottom: 3px; margin: 14px 0 6px; break-after: avoid; page-break-after: avoid; }
    h3 { font-size: 10.5px; color: #334155; margin: 10px 0 3px; break-after: avoid; page-break-after: avoid; }
    .wo-head { margin-top: 14px; padding: 6px 8px; background: #f1f5f9; border: 1px solid #d9dee8; border-radius: 5px; font-size: 11px; break-after: avoid; page-break-after: avoid; }
    .wo-head .sub { color: #64748b; font-size: 10px; }

    table.t { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9.5px; }
    table.t th { background: #eef2f7; color: #475569; font-size: 8.5px; letter-spacing: .4px; text-transform: uppercase; text-align: left; padding: 4px 5px; border: 1px solid #d9dee8; }
    table.t td { padding: 4px 5px; border: 1px solid #e2e8f0; vertical-align: top; word-wrap: break-word; }
    table.t tr { break-inside: avoid; page-break-inside: avoid; }
    table.t thead { display: table-header-group; }
    table.t td.r { text-align: right; white-space: nowrap; }
    table.t th.r { text-align: right; }
    .from { color: #94a3b8; text-decoration: line-through; }
    .small { font-size: 9px; color: #64748b; }
    .st { display: inline-block; font-size: 8px; font-weight: 700; padding: 1px 6px; border-radius: 10px; }
    .st.baru { background: #dcfce7; color: #15803d; }
    .st.dihapus { background: #fee2e2; color: #b91c1c; }
    .st.berubah { background: #fef9c3; color: #a16207; }
    .st.sama { background: #f1f5f9; color: #64748b; }
    .kosong { padding: 18px 0; text-align: center; color: #64748b; font-size: 12px; }
@endsection

@section('content')
@php
    $rp = fn ($n) => \App\Support\SqCompare::rp($n);
    $lab = fn ($m) => $m['no_sq'] . ' Rev.' . $m['revisi'] . ' · ' . strtoupper($m['status']);
    $r = $d['ringkasan'];
    $j = $r['jumlah'];
    $pct = fn ($p) => $p === null ? '' : ' (' . ($p > 0 ? '+' : '') . str_replace('.', ',', (string) $p) . '%)';
    // Selisih berwarna: biaya naik = merah; harga/margin naik = hijau
    $delta = function ($n, $cost = false, $p = null) use ($rp, $pct) {
        if ((int) $n === 0) return '<span class="flat">Tidak berubah</span>';
        $baik = $cost ? $n < 0 : $n > 0;
        return '<span class="' . ($baik ? 'pos' : 'neg') . '">' . ($n > 0 ? '▲ +' : '▼ -') . ltrim($rp(abs($n)), '-') . $pct($p) . '</span>';
    };
    $stLabel = ['baru' => 'Baru', 'dihapus' => 'Dihapus', 'berubah' => 'Berubah', 'sama' => 'Sama'];

    // Satu tabel bagian; $amt = tampilkan kolom subtotal/selisih; $cost = selisih naik dianggap buruk
    $tabel = function (array $rows, bool $amt, bool $cost = false) use ($rp, $delta, $stLabel) {
        if (!$rows) return '';
        $h = '<table class="t"><thead><tr><th style="width:' . ($amt ? '27' : '30') . '%;">Item</th><th style="width:8%;">Status</th><th>Perubahan</th>'
            . ($amt ? '<th class="r" style="width:12%;">Subtotal sebelum</th><th class="r" style="width:12%;">Subtotal sesudah</th><th class="r" style="width:15%;">Selisih</th>' : '')
            . '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $ubah = '';
            if ($row['status'] === 'berubah') {
                foreach ($row['changes'] as $c) {
                    $lbl = ($c['label'] === $row['label'] || $c['label'] === 'Isi') ? '' : e($c['label']) . ': ';
                    $ubah .= '<div>' . $lbl . '<span class="from">' . e(\Illuminate\Support\Str::limit((string) $c['dari'], 160)) . '</span> → <b>' . e(\Illuminate\Support\Str::limit((string) $c['ke'], 160)) . '</b></div>';
                }
            } elseif ($row['ringkas']) {
                $ubah = '<span class="small">' . e($row['ringkas']) . '</span>';
            }
            $h .= '<tr><td><b>' . e($row['label']) . '</b>' . ($row['sub'] ? '<div class="small">' . e($row['sub']) . '</div>' : '') . '</td>'
                . '<td><span class="st ' . $row['status'] . '">' . $stLabel[$row['status']] . '</span></td><td>' . $ubah . '</td>';
            if ($amt) {
                $h .= '<td class="r">' . ($row['dari'] === null ? '—' : $rp($row['dari'])) . '</td>'
                    . '<td class="r">' . ($row['ke'] === null ? '—' : $rp($row['ke'])) . '</td>'
                    . '<td class="r">' . ($row['delta'] === 0 ? '<span class="flat">—</span>' : $delta($row['delta'], $cost)) . '</td>';
            }
            $h .= '</tr>';
        }
        return $h . '</tbody></table>';
    };
@endphp

<div class="band">
    <span class="tag">Internal — bukan untuk pelanggan</span>
    <div class="kicker">Sales Quotation</div>
    <h1>Perbandingan SQ</h1>
    <table>
        <tr><td class="k">Sebelum</td><td>: <b>{{ $lab($d['pembanding']) }}</b></td></tr>
        <tr><td class="k">Sesudah</td><td>: <b>{{ $lab($d['ini']) }}</b></td></tr>
        <tr><td class="k">Judul Order</td><td>: {{ $d['ini']['judul'] ?? '—' }}</td></tr>
        <tr><td class="k">Dicetak</td><td>: {{ now()->format('d-m-Y H:i') }} oleh {{ $pencetak }}{{ $semua ? ' · menampilkan semua baris' : ' · hanya baris yang berubah' }}</td></tr>
    </table>
</div>

<div class="body">
    <table class="kpi">
        <tr>
            <td>
                <div class="lbl">Total Harga</div>
                <div class="val">{{ $rp($r['pendapatan']['ke']) }}</div>
                <div class="sub">Sebelum: {{ $rp($r['pendapatan']['dari']) }}</div>
                <div class="sub">{!! $delta($r['pendapatan']['delta'], false, $r['pendapatan']['pct']) !!}</div>
            </td>
            <td>
                <div class="lbl">Biaya (Budget)</div>
                <div class="val">{{ $rp($r['biaya']['ke']) }}</div>
                <div class="sub">Sebelum: {{ $rp($r['biaya']['dari']) }}</div>
                <div class="sub">{!! $delta($r['biaya']['delta'], true, $r['biaya']['pct']) !!}</div>
            </td>
            <td>
                <div class="lbl">Margin</div>
                <div class="val">{{ $rp($r['margin']['ke']) }} @if ($r['margin_pct']['ke'] !== null)<small>{{ str_replace('.', ',', (string) $r['margin_pct']['ke']) }}%</small>@endif</div>
                <div class="sub">Sebelum: {{ $rp($r['margin']['dari']) }}@if ($r['margin_pct']['dari'] !== null) ({{ str_replace('.', ',', (string) $r['margin_pct']['dari']) }}%)@endif</div>
                <div class="sub">{!! $delta($r['margin']['delta'], false, $r['margin']['pct']) !!}</div>
            </td>
            <td>
                <div class="lbl">Perubahan</div>
                <div class="val">{{ $j['berubah'] + $j['baru'] + $j['dihapus'] }}</div>
                <div class="sub">{{ $j['berubah'] }} berubah · {{ $j['baru'] }} baru · {{ $j['dihapus'] }} dihapus</div>
            </td>
        </tr>
    </table>

    <div class="verdict">{{ $d['narasi'] }}</div>
    <div class="arah">Selisih = Sesudah − Sebelum. Biaya naik berwarna merah; harga dan margin naik berwarna hijau.</div>

    @php
        $adaIsi = false;
        $bagian = '';
        if ($d['header']) { $adaIsi = true; $bagian .= '<h2>Data SQ</h2>' . $tabel($d['header'], false); }
        foreach ($d['wos'] as $w) {
            $adaIsi = true;
            $stWo = ['baru' => 'WO baru', 'dihapus' => 'WO dihapus', 'berubah' => 'Berubah', 'sama' => 'Sama'][$w['status']];
            $meta = collect($w['meta'])->map(fn ($c) => e($c['label']) . ' <span class="from">' . e($c['dari']) . '</span> → <b>' . e($c['ke']) . '</b>')->implode(' · ');
            $bagian .= '<div class="wo-head"><b>' . e($w['label']) . '</b> '
                . ($w['sub'] ? '<span class="sub">' . e($w['sub']) . '</span> ' : '')
                . '<span class="st ' . $w['status'] . '">' . $stWo . '</span>'
                . ($w['d_pendapatan'] !== 0 ? ' &nbsp; Harga ' . $delta($w['d_pendapatan']) : '')
                . ($w['d_biaya'] !== 0 ? ' &nbsp; Biaya ' . $delta($w['d_biaya'], true) : '')
                . ($meta ? ' &nbsp; <span class="sub">' . $meta . '</span>' : '') . '</div>';
            foreach ([['boq', 'BOQ', true, false], ['tambahan', 'BOQ Other & Sampling', true, false], ['fwo', 'Fieldwork (FWO)', false, false], ['budget', 'Budget', true, true]] as [$k, $jdl, $amt, $cost]) {
                if ($w['sections'][$k]) $bagian .= '<h3>' . $jdl . '</h3>' . $tabel($w['sections'][$k], $amt, $cost);
            }
        }
        if ($d['keterangan']) { $adaIsi = true; $bagian .= '<h2>Keterangan Penawaran</h2>' . $tabel($d['keterangan'], false); }
    @endphp

    @if ($adaIsi)
        {!! $bagian !!}
    @else
        <div class="kosong">Tidak ada perbedaan isi antara kedua SQ.</div>
    @endif
</div>
@endsection
