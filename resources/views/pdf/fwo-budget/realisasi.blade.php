@extends('pdf.layouts.document')

@section('title', 'Realisasi Budget')

@section('content')
<style>
    body { font-size: 12px; font-family: Arial, sans-serif; color: #1e293b; }
    .page { padding: 24px 32px; }
    h2 { text-align: center; font-size: 14px; font-weight: bold; text-transform: uppercase; margin-bottom: 4px; }
    .sub-title { text-align: center; font-size: 11px; color: #64748b; margin-bottom: 20px; }
    .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
    .info-table td { padding: 3px 6px; vertical-align: top; font-size: 11px; }
    .info-table td.label { width: 160px; font-weight: 600; color: #475569; }
    .info-table td.colon { width: 12px; }
    .budget-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .budget-table th { background: #1e3a5f; color: #fff; font-size: 11px; padding: 7px 10px; text-align: left; }
    .budget-table td { padding: 7px 10px; font-size: 11px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    .budget-table tr:nth-child(even) td { background: #f8fafc; }
    .text-right { text-align: right; }
    .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .summary-table td { padding: 6px 10px; font-size: 11px; }
    .summary-table tr.border-top td { border-top: 2px solid #1e3a5f; font-weight: bold; }
    .summary-table td.label { width: 60%; text-align: right; color: #475569; padding-right: 16px; }
    .summary-table td.value { width: 40%; text-align: right; font-weight: 600; }
    .surplus-box { border: 2px solid #1e3a5f; border-radius: 4px; padding: 14px 20px; margin-bottom: 24px; background: #f0f9ff; }
    .surplus-box .surplus-label { font-size: 11px; color: #475569; margin-bottom: 4px; }
    .surplus-box .surplus-amount { font-size: 18px; font-weight: bold; color: #1e3a5f; }
    .surplus-note { font-size: 10px; color: #64748b; margin-top: 4px; }
    .ttd-area { margin-top: 32px; }
    .ttd-table { width: 100%; border-collapse: collapse; }
    .ttd-table td { width: 33.33%; padding: 0 10px; vertical-align: top; text-align: center; font-size: 11px; }
    .ttd-box { border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px; min-height: 80px; margin-bottom: 6px; }
    .badge-surplus { display:inline-block; background:#dcfce7; color:#15803d; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:600; }
    .badge-defisit { display:inline-block; background:#fee2e2; color:#dc2626; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:600; }
</style>

<div class="page">
    <h2>Laporan Realisasi Anggaran</h2>
    <p class="sub-title">{{ $budget->no_fwo }} — {{ $budget->label }}</p>

    <table class="info-table">
        <tr>
            <td class="label">No. FWO</td>
            <td class="colon">:</td>
            <td>{{ $budget->no_fwo ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">No. WO</td>
            <td class="colon">:</td>
            <td>{{ $budget->no_wo ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Judul Pekerjaan</td>
            <td class="colon">:</td>
            <td>{{ $budget->fwo_judul ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Budget Plan</td>
            <td class="colon">:</td>
            <td><b>{{ $budget->label }}</b></td>
        </tr>
        @if($budget->tanggal_mulai || $budget->tanggal_selesai)
        <tr>
            <td class="label">Periode</td>
            <td class="colon">:</td>
            <td>
                {{ $budget->tanggal_mulai ? \Carbon\Carbon::parse($budget->tanggal_mulai)->locale('id')->isoFormat('D MMMM Y') : '-' }}
                @if($budget->tanggal_selesai)
                    &mdash; {{ \Carbon\Carbon::parse($budget->tanggal_selesai)->locale('id')->isoFormat('D MMMM Y') }}
                @endif
            </td>
        </tr>
        @endif
        <tr>
            <td class="label">Tanggal Cetak</td>
            <td class="colon">:</td>
            <td>{{ now()->locale('id')->isoFormat('D MMMM Y') }}</td>
        </tr>
    </table>

    <table class="budget-table">
        <thead>
            <tr>
                <th style="width:36px;">No</th>
                <th>Uraian</th>
                <th class="text-right" style="min-width:110px;">Anggaran (Rp)</th>
                <th class="text-right" style="min-width:110px;">Realisasi (Rp)</th>
                <th class="text-right" style="min-width:100px;">Selisih (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
            @php $sel = $item->nominal_budget - $item->total_actual; @endphp
            <tr>
                <td style="text-align:center;">{{ $i + 1 }}</td>
                <td>
                    {{ $item->nama_account }}
                    @if($item->is_cash_advance)
                        <span style="font-size:10px;font-weight:600;color:#1d4ed8;background:#eff6ff;border:1px solid #bfdbfe;border-radius:3px;padding:1px 5px;margin-left:4px;">CA</span>
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->nominal_budget, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->total_actual, 0, ',', '.') }}</td>
                <td class="text-right" style="color:{{ $sel >= 0 ? '#15803d' : '#dc2626' }};font-weight:600;">
                    {{ number_format(abs($sel), 0, ',', '.') }}
                    @if($sel < 0) <span style="font-size:10px;">(Lebih)</span> @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td class="label">Total Anggaran</td>
            <td class="value">Rp {{ number_format($totalBudget, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Total Realisasi</td>
            <td class="value">Rp {{ number_format($totalActual, 0, ',', '.') }}</td>
        </tr>
        <tr class="border-top">
            <td class="label">
                @if($selisih >= 0) Surplus (Sisa Anggaran) @else Defisit (Kelebihan Pengeluaran) @endif
            </td>
            <td class="value" style="color:{{ $selisih >= 0 ? '#15803d' : '#dc2626' }};">
                Rp {{ number_format(abs($selisih), 0, ',', '.') }}
            </td>
        </tr>
    </table>

    @if($selisih > 0)
    <div class="surplus-box">
        <div class="surplus-label">Jumlah yang harus dikembalikan ke perusahaan:</div>
        <div class="surplus-amount">Rp {{ number_format($selisih, 0, ',', '.') }}</div>
        <div class="surplus-note">Merupakan sisa anggaran yang tidak terpakai dan wajib dikembalikan.</div>
    </div>
    @elseif($selisih < 0)
    <div class="surplus-box" style="border-color:#dc2626;background:#fff5f5;">
        <div class="surplus-label" style="color:#dc2626;">Defisit — pengeluaran melebihi anggaran:</div>
        <div class="surplus-amount" style="color:#dc2626;">Rp {{ number_format(abs($selisih), 0, ',', '.') }}</div>
        <div class="surplus-note">Kelebihan pengeluaran memerlukan persetujuan lebih lanjut.</div>
    </div>
    @endif

    <div class="ttd-area">
        <table class="ttd-table">
            <tr>
                <td>
                    <p style="margin-bottom:6px;">Yang Menyerahkan,</p>
                    <div class="ttd-box"></div>
                    <p style="margin-top:4px;">( __________________________ )</p>
                    <p style="font-size:10px;color:#64748b;">Tim Lapangan</p>
                </td>
                <td>
                    <p style="margin-bottom:6px;">Yang Menerima,</p>
                    <div class="ttd-box"></div>
                    <p style="margin-top:4px;">( __________________________ )</p>
                    <p style="font-size:10px;color:#64748b;">Admin / Finance</p>
                </td>
                <td>
                    <p style="margin-bottom:6px;">Mengetahui,</p>
                    <div class="ttd-box"></div>
                    <p style="margin-top:4px;">( __________________________ )</p>
                    <p style="font-size:10px;color:#64748b;">Kepala / Manajer</p>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
